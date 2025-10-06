<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnifiedBranch extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'is_hub',
        'parent_branch_id',
        'address',
        'phone',
        'email',
        'latitude',
        'longitude',
        'operating_hours',
        'capabilities',
        'metadata',
        'status',
    ];

    protected $casts = [
        'is_hub' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'operating_hours' => 'array',
        'capabilities' => 'array',
        'metadata' => 'array',
        'status' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(UnifiedBranch::class, 'parent_branch_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(UnifiedBranch::class, 'parent_branch_id');
    }

    public function managers(): HasMany
    {
        return $this->hasMany(BranchManager::class, 'branch_id');
    }

    public function workers(): HasMany
    {
        return $this->hasMany(BranchWorker::class, 'branch_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'origin_branch_id');
    }

    public function isHub(): bool
    {
        return $this->is_hub === true || $this->type === 'HUB';
    }

    public function isRegional(): bool
    {
        return $this->type === 'REGIONAL';
    }

    public function isLocal(): bool
    {
        return $this->type === 'LOCAL';
    }

    public function getCapacityAttribute(): ?int
    {
        return $this->metadata['capacity'] ?? null;
    }
}
