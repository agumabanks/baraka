<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    protected $fillable = [
        'shipment_id',
        'driver_id',
        'type',
        'status',
        'priority',
        'scheduled_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the shipment that this task belongs to.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the driver assigned to this task.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'driver_id');
    }

    /**
     * Get the POD proof for this task.
     */
    public function podProof(): HasOne
    {
        return $this->hasOne(PodProof::class);
    }

    /**
     * Check if task is completed.
     */
    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    /**
     * Mark task as completed.
     */
    public function markCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }
}