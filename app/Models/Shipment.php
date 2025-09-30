<?php

namespace App\Models;

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusChanged;
use App\Models\Backend\Hub;
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
        'service_level',
        'incoterm',
        'price_amount',
        'currency',
        'current_status',
        'created_by',
        'metadata',
        'public_token',
    ];

    protected $casts = [
        'price_amount' => 'decimal:2',
        'current_status' => ShipmentStatus::class,
        'metadata' => 'array',
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
            ->logOnly(['customer_id', 'origin_branch_id', 'dest_branch_id', 'current_status', 'price_amount'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} shipment");
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_branch_id');
    }

    public function destBranch(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'dest_branch_id');
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

    // Scopes
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('origin_branch_id', $branchId)
            ->orWhere('dest_branch_id', $branchId);
    }

    public function scopeByStatus($query, ShipmentStatus $status)
    {
        return $query->where('current_status', $status);
    }

    // Business Logic
    public function updateStatusFromScan(ScanEvent $scanEvent): void
    {
        $newStatus = $this->calculateStatusFromScan($scanEvent);
        if ($newStatus && $newStatus !== $this->current_status) {
            $this->update(['current_status' => $newStatus]);
            // Fire event for notifications
            event(new ShipmentStatusChanged($this, $scanEvent));
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
}
