<?php

namespace App\Models;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteCapability extends Model
{
    protected $fillable = [
        'origin_branch_id',
        'destination_branch_id',
        'service_level',
        'max_weight',
        'hazmat_allowed',
        'cod_allowed',
        'status',
    ];

    protected $casts = [
        'max_weight' => 'decimal:2',
        'hazmat_allowed' => 'boolean',
        'cod_allowed' => 'boolean',
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
        return $query->where('status', 'active');
    }

    public function scopeForRoute($query, int $originId, int $destId)
    {
        return $query->where('origin_branch_id', $originId)
            ->where('destination_branch_id', $destId);
    }

    public static function getAvailableServices(int $originId, int $destId): array
    {
        return static::forRoute($originId, $destId)
            ->active()
            ->pluck('service_level')
            ->toArray();
    }

    public static function isServiceAllowed(int $originId, int $destId, string $service): bool
    {
        return static::forRoute($originId, $destId)
            ->active()
            ->where('service_level', $service)
            ->exists();
    }
}
