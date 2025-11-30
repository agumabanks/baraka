<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'branch_id',
        'tracker_id',
        'latitude',
        'longitude',
        'temperature_c',
        'battery_percent',
        'payload',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'temperature_c' => 'float',
        'battery_percent' => 'integer',
        'payload' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Backend\Branch::class, 'branch_id');
    }
}
