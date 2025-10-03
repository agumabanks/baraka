<?php

namespace App\Models;

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusChanged;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Shipment extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'origin_branch_id',
        'dest_branch_id',
        'assigned_worker_id',
        'service_level',
        'incoterm',
        'price_amount',
        'currency',
        'current_status',
        'created_by',
        'assigned_at',
        'expected_delivery_date',
        'delivered_at',
        'metadata',
        'public_token',
        // Unified workflow fields
        'transfer_hub_id',
        'hub_processed_at',
        'transferred_at',
        'picked_up_at',
        'delivered_by',
        'has_exception',
        'exception_type',
        'exception_severity',
        'exception_notes',
        'exception_occurred_at',
        'returned_at',
        'return_reason',
        'return_notes',
        'priority',
        'processed_at',
    ];

    protected $casts = [
        'price_amount' => 'decimal:2',
        'current_status' => ShipmentStatus::class,
        'assigned_at' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'delivered_at' => 'datetime',
        'metadata' => 'array',
        // Unified workflow casts
        'hub_processed_at' => 'datetime',
        'transferred_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'has_exception' => 'boolean',
        'exception_severity' => 'string',
        'exception_occurred_at' => 'datetime',
        'returned_at' => 'datetime',
        'priority' => 'integer',
        'processed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (! $shipment->public_token) {
                $shipment->public_token = \Illuminate\Support\Facades\Crypt::encryptString($shipment->id ?? uniqid());
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
            ->logOnly(['customer_id', 'origin_branch_id', 'dest_branch_id', 'assigned_worker_id', 'current_status', 'price_amount'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} shipment");
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destBranch(): BelongsTo
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

    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class);
    }

    public function transportLegs(): HasMany
    {
        return $this->hasMany(TransportLeg::class);
    }

    public function bags(): HasMany
    {
        return $this->hasMany(Bag::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
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
    public function getTrackingNumberAttribute(): string
    {
        return $this->parcels->first()?->sscc ?? 'N/A';
    }

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
            'current_status' => ShipmentStatus::ASSIGNED,
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
            'current_status' => ShipmentStatus::PENDING,
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
            $oldStatus = $this->current_status;
            $this->update(['current_status' => $newStatus]);

            // Set delivered timestamp if status is delivered
            if ($newStatus === ShipmentStatus::DELIVERED && !$this->delivered_at) {
                $this->update(['delivered_at' => $scanEvent->occurred_at]);
            }

            // Fire event for notifications
            event(new ShipmentStatusChanged($this, $scanEvent, $oldStatus));
        }
    }

    private function calculateStatusFromScan(ScanEvent $scanEvent): ?ShipmentStatus
    {
        return match ($scanEvent->type) {
            ScanType::ARRIVE => ShipmentStatus::ARRIVE,
            ScanType::DEPART => ShipmentStatus::DEPART,
            ScanType::ARRIVE_DEST => ShipmentStatus::ARRIVE_DEST,
            ScanType::OUT_FOR_DELIVERY => ShipmentStatus::OUT_FOR_DELIVERY,
            ScanType::DELIVERED => ShipmentStatus::DELIVERED,
            default => null
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->current_status) {
            ShipmentStatus::PENDING => '<span class="badge badge-warning">Pending</span>',
            ShipmentStatus::ASSIGNED => '<span class="badge badge-info">Assigned</span>',
            ShipmentStatus::ARRIVE => '<span class="badge badge-primary">Arrived</span>',
            ShipmentStatus::DEPART => '<span class="badge badge-secondary">Departed</span>',
            ShipmentStatus::ARRIVE_DEST => '<span class="badge badge-info">At Destination</span>',
            ShipmentStatus::OUT_FOR_DELIVERY => '<span class="badge badge-warning">Out for Delivery</span>',
            ShipmentStatus::DELIVERED => '<span class="badge badge-success">Delivered</span>',
            ShipmentStatus::CANCELLED => '<span class="badge badge-danger">Cancelled</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }
}
