<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventStream extends Model
{
    protected $fillable = [
        'event_type',
        'aggregate_id',
        'aggregate_type',
        'actor_id',
        'payload',
        'metadata',
        'timestamp',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'timestamp' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (self $model) {
            if (!$model->timestamp) {
                $model->timestamp = now();
            }
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeForAggregate($query, string $aggregateType, string $aggregateId)
    {
        return $query->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->orderBy('timestamp');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('metadata->branch_id', $branchId);
    }

    public function scopeForType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('timestamp', '>=', now()->subMinutes($minutes));
    }

    public function getStreamKey(): string
    {
        return "{$this->aggregate_type}:{$this->aggregate_id}";
    }

    public function broadcastChannel(): string
    {
        return "events.{$this->aggregate_type}.{$this->aggregate_id}";
    }
}
