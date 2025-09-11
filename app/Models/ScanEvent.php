<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Enums\ScanType;
use App\Models\Backend\Hub;

class ScanEvent extends Model
{
    use LogsActivity;

    protected $fillable = [
        'sscc',
        'type',
        'branch_id',
        'leg_id',
        'user_id',
        'occurred_at',
        'geojson',
        'note',
    ];

    protected $casts = [
        'type' => ScanType::class,
        'occurred_at' => 'datetime',
        'geojson' => 'array',
    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('scan_event')
            ->logOnly(['sscc', 'type', 'branch_id', 'occurred_at'])
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName} scan event");
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

    public function shipment()
    {
        // Find shipment through parcel SSCC
        $parcel = \App\Models\Backend\Parcel::where('sscc', $this->sscc)->first();
        return $parcel ? $parcel->shipment : null;
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
