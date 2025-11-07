<?php

namespace App\Models;

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Models\Backend\Hub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ScanEvent extends Model
{
    use LogsActivity;

    protected $fillable = [
        'sscc',
        'shipment_id',
        'bag_id',
        'route_id',
        'stop_id',
        'type',
        'status_after',
        'branch_id',
        'leg_id',
        'user_id',
        'location_type',
        'location_id',
        'occurred_at',
        'geojson',
        'note',
        'payload',
    ];

    protected $casts = [
        'type' => ScanType::class,
        'status_after' => ShipmentStatus::class,
        'occurred_at' => 'datetime',
        'geojson' => 'array',
        'payload' => 'array',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('scan_event')
            ->logOnly(['sscc', 'type', 'branch_id', 'occurred_at'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} scan event");
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'branch_id');
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TransportLeg::class, 'leg_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function bag(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Bag::class, 'bag_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Route::class, 'route_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Stop::class, 'stop_id');
    }

    public function resolveShipment(): ?Shipment
    {
        if ($this->relationLoaded('shipment') || $this->shipment_id) {
            return $this->shipment;
        }

        $parcel = \App\Models\Backend\Parcel::where('sscc', $this->sscc)->first();

        return $parcel?->shipment;
    }

    // Accessors
    public function getLatitudeAttribute(): ?float
    {
        return $this->geojson['coordinates'][1] ?? null;
    }

    public function getLongitudeAttribute(): ?float
    {
        return $this->geojson['coordinates'][0] ?? null;
    }

    // Scopes
    public function scopeBySscc($query, string $sscc)
    {
        return $query->where('sscc', $sscc);
    }

    public function scopeByType($query, ScanType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }
}
