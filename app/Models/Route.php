<?php

namespace App\Models;

use App\Models\Backend\Hub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Route extends Model
{
    use LogsActivity;

    protected $fillable = [
        'branch_id',
        'driver_id',
        'planned_at',
        'status',
        'stops_sequence',
        'total_distance_km',
        'estimated_duration_hours',
    ];

    protected $casts = [
        'planned_at' => 'datetime',
        'stops_sequence' => 'array',
        'total_distance_km' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('route')
            ->logOnly(['branch_id', 'driver_id', 'status', 'planned_at'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} route");
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'branch_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'driver_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(\App\Models\Stop::class);
    }

    public function shipment()
    {
        $stop = $this->stops()->first();

        return $stop ? $stop->shipment() : null;
    }
}
