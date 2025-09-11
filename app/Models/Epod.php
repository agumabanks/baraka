<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Epod extends Model
{
    use LogsActivity;

    protected $fillable = [
        'stop_id',
        'signer_name',
        'signature_image_path',
        'photo_paths',
        'gps_point',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'photo_paths' => 'array',
        'gps_point' => 'array',
        'completed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('epod')
            ->logOnly(['stop_id', 'signer_name', 'completed_at'])
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName} ePOD");
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Stop::class);
    }

    public function getLatitudeAttribute(): ?float
    {
        return $this->gps_point['lat'] ?? null;
    }

    public function getLongitudeAttribute(): ?float
    {
        return $this->gps_point['lng'] ?? null;
    }
}
