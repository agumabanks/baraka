<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Stop extends Model
{
    use LogsActivity;

    protected $fillable = [
        'route_id',
        'sscc',
        'sequence',
        'status',
        'eta_at',
        'arrived_at',
        'completed_at',
        'notes',
        'geo_location',
    ];

    protected $casts = [
        'eta_at' => 'datetime',
        'arrived_at' => 'datetime',
        'completed_at' => 'datetime',
        'geo_location' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('stop')
            ->logOnly(['route_id', 'sscc', 'status', 'sequence'])
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName} stop");
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Route::class);
    }

    public function parcel()
    {
        return \App\Models\Backend\Parcel::where('sscc', $this->sscc)->first();
    }

    public function shipment()
    {
        $parcel = $this->parcel();
        return $parcel ? $parcel->shipment : null;
    }

    public function epod(): HasOne
    {
        return $this->hasOne(\App\Models\Epod::class);
    }

    public function getLatitudeAttribute(): ?float
    {
        return $this->geo_location['lat'] ?? null;
    }

    public function getLongitudeAttribute(): ?float
    {
        return $this->geo_location['lng'] ?? null;
    }
}
