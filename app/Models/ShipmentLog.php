<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentLog extends Model
{
    protected $fillable = [
        'shipment_id',
        'branch_id',
        'user_id',
        'status',
        'description',
        'location',
        'latitude',
        'longitude',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(UnifiedBranch::class, 'branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
