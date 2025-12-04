<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceConstraint extends Model
{
    protected $fillable = [
        'service_level',
        'origin_branch_id',
        'destination_branch_id',
        'min_weight',
        'max_weight',
        'min_declared_value',
        'max_declared_value',
        'active',
    ];

    protected $casts = [
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'min_declared_value' => 'decimal:2',
        'max_declared_value' => 'decimal:2',
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

    public function scopeForService($query, string $serviceLevel)
    {
        return $query->where('service_level', $serviceLevel);
    }

    public static function validateWeight(string $service, float $weight, ?int $originId = null, ?int $destId = null): array
    {
        $constraint = static::active()
            ->forService($service)
            ->when($originId, fn($q) => $q->where(fn($q2) => $q2->whereNull('origin_branch_id')->orWhere('origin_branch_id', $originId)))
            ->when($destId, fn($q) => $q->where(fn($q2) => $q2->whereNull('destination_branch_id')->orWhere('destination_branch_id', $destId)))
            ->first();

        if (!$constraint) {
            return ['valid' => true];
        }

        if ($weight < $constraint->min_weight) {
            return [
                'valid' => false,
                'error' => "Weight must be at least {$constraint->min_weight} kg for {$service} service.",
            ];
        }

        if ($constraint->max_weight && $weight > $constraint->max_weight) {
            return [
                'valid' => false,
                'error' => "Weight cannot exceed {$constraint->max_weight} kg for {$service} service.",
            ];
        }

        return ['valid' => true];
    }
}
