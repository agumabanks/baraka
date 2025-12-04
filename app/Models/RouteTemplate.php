<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteTemplate extends Model
{
    protected $fillable = [
        'name',
        'origin_branch_id',
        'destination_branch_id',
        'default_service_level',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('origin_branch_id', $branchId);
    }
}
