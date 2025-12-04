<?php

namespace App\Models;

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusChanged;
use App\Models\Backend\Branch;
use App\Models\Backend\Client as BackendClient;
use App\Models\Customer;
use App\Models\TrackerEvent;
use App\Models\ShipmentTransition;
use App\Observers\ShipmentInvoiceObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Backend\BranchWorker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Shipment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'client_id',
        'customer_id',
        'customer_profile_id',
        'origin_branch_id',
        'dest_branch_id',
        'assigned_worker_id',
        'tracking_number',
        'waybill_number', // New
        'status',
        'service_level', // New
        'incoterms', // New
        'payer_type', // New
        'special_instructions', // New
        'declared_value', // New
        'insurance_amount', // New
        'customs_value', // New
        'currency',
        'price_amount',
        'chargeable_weight_kg', // New
        'volume_cbm', // New
        'current_status',
        'created_by',
        'assigned_at',
        'expected_delivery_date',
        'delivered_at',
        'metadata',
        'public_token',
        // Unified workflow fields
        'booked_at',
        'pickup_scheduled_at',
        'transfer_hub_id',
        'hub_processed_at',
        'transferred_at',
        'picked_up_at',
        'origin_hub_arrived_at',
        'bagged_at',
        'linehaul_departed_at',
        'linehaul_arrived_at',
        'destination_hub_arrived_at',
        'customs_hold_at',
        'customs_cleared_at',
        'out_for_delivery_at',
        'delivered_by',
        'has_exception',
        'exception_type',
        'exception_severity',
        'exception_notes',
        'exception_occurred_at',
        'returned_at',
        'return_initiated_at',
        'return_in_transit_at',
        'return_reason',
        'return_notes',
        'priority',
        'processed_at',
        'cancelled_at',
        'current_location_type',
        'current_location_id',
        'last_scan_event_id',
        'is_consolidation',
        'consolidation_id',
        'consolidation_type',
        'held_at',
        'held_by',
        'hold_reason',
        'rerouted_from_branch_id',
        'rerouted_at',
        'rerouted_by',
        'barcode',
        'qr_code',
        // POS Hardening fields
        'content_type',
        'un_number',
        'hazmat_class',
        'packaging_group',
        'rate_table_version',
        'last_label_printed_at',
        'label_print_count',
        'payment_status',
        'draft_id',
        'base_rate',
        'weight_charge',
        'surcharges_total',
        'insurance_fee',
        'cod_fee',
        'tax_amount',
        'discount_amount',
        'discount_reason',
        'discount_approved_by',
    ];

    protected $casts = [
        'price_amount' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'customs_value' => 'decimal:2',
        'chargeable_weight_kg' => 'decimal:2',
        'volume_cbm' => 'decimal:4',
        'status' => 'string',
        'assigned_at' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'delivered_at' => 'datetime',
        'metadata' => 'array',
        // Unified workflow casts
        'booked_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'hub_processed_at' => 'datetime',
        'transferred_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'origin_hub_arrived_at' => 'datetime',
        'bagged_at' => 'datetime',
        'linehaul_departed_at' => 'datetime',
        'linehaul_arrived_at' => 'datetime',
        'destination_hub_arrived_at' => 'datetime',
        'customs_hold_at' => 'datetime',
        'customs_cleared_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'has_exception' => 'boolean',
        'exception_severity' => 'string',
        'exception_occurred_at' => 'datetime',
        'returned_at' => 'datetime',
        'return_initiated_at' => 'datetime',
        'return_in_transit_at' => 'datetime',
        'priority' => 'integer',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'held_at' => 'datetime',
        'rerouted_at' => 'datetime',
        // POS Hardening casts
        'last_label_printed_at' => 'datetime',
        'label_print_count' => 'integer',
        'base_rate' => 'decimal:2',
        'weight_charge' => 'decimal:2',
        'surcharges_total' => 'decimal:2',
        'insurance_fee' => 'decimal:2',
        'cod_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    // ... (existing methods)

    // Relationships
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    public function shipmentEvents(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class);
    }

    public function consolidation(): BelongsTo
    {
        return $this->belongsTo(Consolidation::class);
    }

    // Business Logic
    public function calculateTotals()
    {
        $this->load('parcels');
        
        $totalWeight = $this->parcels->sum('weight_kg');
        $totalVolume = $this->parcels->sum('volume_cbm');
        
        // Volumetric weight calculation (Standard: 1 CBM = 167 kg)
        $volumetricWeight = $totalVolume * 167;
        
        $this->chargeable_weight_kg = max($totalWeight, $volumetricWeight);
        $this->volume_cbm = $totalVolume;
        $this->weight = $totalWeight; // Update legacy weight column if needed
        
        $this->save();
        
        return $this;
    }

    public function getCurrentStatusAttribute(?string $value): ?ShipmentStatus
    {
        if (blank($value)) {
            return null;
        }

        return ShipmentStatus::fromString($value);
    }

    public function setCurrentStatusAttribute(ShipmentStatus|string|null $value): void
    {
        if ($value instanceof ShipmentStatus) {
            $this->attributes['current_status'] = $value->value;

            return;
        }

        if (blank($value)) {
            $this->attributes['current_status'] = null;

            return;
        }

        $normalized = ShipmentStatus::fromString((string) $value);

        if (! $normalized instanceof ShipmentStatus) {
            Log::warning('Attempted to set unknown shipment current_status', [
                'shipment_id' => $this->id,
                'provided_status' => $value,
            ]);

            return;
        }

        $this->attributes['current_status'] = $normalized->value;
    }

    protected static function boot()
    {
        parent::boot();
        static::observe(ShipmentInvoiceObserver::class);

        static::creating(function ($shipment) {
            if (! $shipment->public_token) {
                $shipment->public_token = \Illuminate\Support\Facades\Crypt::encryptString($shipment->id ?? uniqid());
            }

            if (Schema::hasColumn('shipments', 'barcode') && empty($shipment->barcode)) {
                $shipment->barcode = 'BC'.strtoupper(Str::random(12));
            }

            if (Schema::hasColumn('shipments', 'qr_code') && empty($shipment->qr_code)) {
                $shipment->qr_code = 'QR'.strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('shipment')
            ->logOnly(['client_id', 'customer_id', 'origin_branch_id', 'dest_branch_id', 'assigned_worker_id', 'status', 'price_amount'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} shipment");
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * POS/CRM customer profile (customers table).
     */
    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_profile_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(BackendClient::class, 'client_id');
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'dest_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'dest_branch_id');
    }

    public function assignedWorker(): BelongsTo
    {
        return $this->belongsTo(BranchWorker::class, 'assigned_worker_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class);
    }

    public function transportLegs(): HasMany
    {
        return $this->hasMany(TransportLeg::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(ShipmentTransition::class);
    }

    public function handoffs(): HasMany
    {
        return $this->hasMany(BranchHandoff::class);
    }

    public function bags(): HasMany
    {
        return $this->hasMany(Bag::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    public function trackerEvents(): HasMany
    {
        return $this->hasMany(TrackerEvent::class);
    }

    public function chargeLines(): HasMany
    {
        return $this->hasMany(ChargeLine::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function codReceipt(): HasOne
    {
        return $this->hasOne(CodReceipt::class);
    }

    public function podProof(): HasOne
    {
        return $this->hasOne(PodProof::class);
    }

    public function commodities(): HasMany
    {
        return $this->hasMany(Commodity::class);
    }

    public function customsDocs(): HasMany
    {
        return $this->hasMany(CustomsDoc::class);
    }

    // Accessors
    // Note: tracking_number is now a database column, not an accessor

    public function getTotalWeightAttribute(): float
    {
        return $this->parcels->sum('weight_kg');
    }

    public function getTotalParcelsAttribute(): int
    {
        return $this->parcels->count();
    }

    public function getLastScanAttribute()
    {
        return $this->scanEvents()->latest('occurred_at')->first();
    }

    public function getAssignedWorkerNameAttribute(): string
    {
        return $this->assignedWorker ? $this->assignedWorker->full_name : 'Unassigned';
    }

    public function getOriginBranchNameAttribute(): string
    {
        return $this->originBranch ? $this->originBranch->name : 'Unknown';
    }

    public function getDestBranchNameAttribute(): string
    {
        return $this->destBranch ? $this->destBranch->name : 'Unknown';
    }

    public function getIsLateAttribute(): bool
    {
        if (!$this->expected_delivery_date || in_array($this->current_status, [ShipmentStatus::DELIVERED, ShipmentStatus::CANCELLED])) {
            return false;
        }

        return now()->isAfter($this->expected_delivery_date);
    }

    public function getDeliveryTimeAttribute(): ?int
    {
        if (!$this->delivered_at || !$this->assigned_at) {
            return null;
        }

        return $this->assigned_at->diffInHours($this->delivered_at);
    }

    // Scopes
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('origin_branch_id', $branchId)
            ->orWhere('dest_branch_id', $branchId);
    }

    public function scopeByWorker($query, $workerId)
    {
        return $query->where('assigned_worker_id', $workerId);
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_worker_id');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_worker_id');
    }

    public function scopeLate($query)
    {
        return $query->where('expected_delivery_date', '<', now())
            ->whereNotIn('current_status', [ShipmentStatus::DELIVERED, ShipmentStatus::CANCELLED]);
    }

    public function scopeByStatus($query, ShipmentStatus $status)
    {
        return $query->where('current_status', $status);
    }

    // Business Logic
    public function assignToWorker(BranchWorker $worker): bool
    {
        if (!$worker->canPerform('assign_shipments')) {
            return false;
        }

        if (!$worker->isAvailable()) {
            return false;
        }

        $this->update([
            'assigned_worker_id' => $worker->id,
            'assigned_at' => now(),
            'pickup_scheduled_at' => $this->pickup_scheduled_at ?? now(),
            'current_status' => ShipmentStatus::PICKUP_SCHEDULED,
        ]);

        // Log the assignment
        activity()
            ->performedOn($this)
            ->causedBy($worker->user)
            ->withProperties([
                'worker_id' => $worker->id,
                'worker_name' => $worker->full_name,
            ])
            ->log("Shipment assigned to worker: {$worker->full_name}");

        return true;
    }

    public function unassignFromWorker(): bool
    {
        $oldWorker = $this->assignedWorker;

        $this->update([
            'assigned_worker_id' => null,
            'assigned_at' => null,
            'pickup_scheduled_at' => null,
            'current_status' => ShipmentStatus::BOOKED,
        ]);

        if ($oldWorker) {
            activity()
                ->performedOn($this)
                ->log("Shipment unassigned from worker: {$oldWorker->full_name}");
        }

        return true;
    }

    public function canBeAssignedToBranch(Branch $branch): bool
    {
        // Check if shipment can be handled by this branch
        return $this->origin_branch_id === $branch->id ||
               $this->dest_branch_id === $branch->id ||
               $branch->is_hub; // HUB can handle all shipments
    }

    public function updateStatusFromScan(ScanEvent $scanEvent): void
    {
        $newStatus = $this->calculateStatusFromScan($scanEvent);
        if ($newStatus && $newStatus !== $this->current_status) {
            $this->update(['current_status' => $newStatus]);

            // Set delivered timestamp if status is delivered
            if ($newStatus === ShipmentStatus::DELIVERED && !$this->delivered_at) {
                $this->update(['delivered_at' => $scanEvent->occurred_at]);
            }

            // Fire event for notifications
            event(new ShipmentStatusChanged($this, $scanEvent));
        }
    }

    private function calculateStatusFromScan(ScanEvent $scanEvent): ?ShipmentStatus
    {
        $scanType = $scanEvent->type;

        if (! $scanType instanceof ScanType) {
            return null;
        }

        return $scanType->resultingStatus();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->current_status) {
            ShipmentStatus::BOOKED => '<span class="badge badge-warning">Booked</span>',
            ShipmentStatus::PICKUP_SCHEDULED => '<span class="badge badge-info">Pickup Scheduled</span>',
            ShipmentStatus::PICKED_UP => '<span class="badge badge-primary">Picked Up</span>',
            ShipmentStatus::AT_ORIGIN_HUB => '<span class="badge badge-primary">At Origin Hub</span>',
            ShipmentStatus::BAGGED => '<span class="badge badge-secondary">Bagged</span>',
            ShipmentStatus::LINEHAUL_DEPARTED => '<span class="badge badge-secondary">Linehaul Departed</span>',
            ShipmentStatus::LINEHAUL_ARRIVED => '<span class="badge badge-secondary">Linehaul Arrived</span>',
            ShipmentStatus::AT_DESTINATION_HUB => '<span class="badge badge-primary">At Destination Hub</span>',
            ShipmentStatus::CUSTOMS_HOLD => '<span class="badge badge-danger">Customs Hold</span>',
            ShipmentStatus::CUSTOMS_CLEARED => '<span class="badge badge-success">Customs Cleared</span>',
            ShipmentStatus::OUT_FOR_DELIVERY => '<span class="badge badge-warning">Out for Delivery</span>',
            ShipmentStatus::DELIVERED => '<span class="badge badge-success">Delivered</span>',
            ShipmentStatus::RETURN_INITIATED => '<span class="badge badge-danger">Return Initiated</span>',
            ShipmentStatus::RETURN_IN_TRANSIT => '<span class="badge badge-danger">Return In Transit</span>',
            ShipmentStatus::RETURNED => '<span class="badge badge-danger">Returned</span>',
            ShipmentStatus::CANCELLED => '<span class="badge badge-dark">Cancelled</span>',
            ShipmentStatus::EXCEPTION => '<span class="badge badge-danger">Exception</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    public function setStatusAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['status'] = null;
            return;
        }

        if (is_string($value)) {
            $derived = ShipmentStatus::fromString($value);
            if ($derived) {
                $this->attributes['status'] = strtolower($derived->value);
            } else {
                Log::warning('Attempted to set unknown shipment status', [
                    'shipment_id' => $this->id,
                    'provided_status' => $value,
                ]);
            }

            return;
        }

        throw new \InvalidArgumentException('Invalid status value provided for Shipment');
    }
}
