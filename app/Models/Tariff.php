<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Tariff extends Model
{
    protected $fillable = [
        'name',
        'service_level',
        'zone',
        'weight_from',
        'weight_to',
        'base_rate',
        'per_kg_rate',
        'fuel_surcharge_percent',
        'currency',
        'version',
        'effective_from',
        'effective_to',
        'active',
    ];

    protected $casts = [
        'weight_from' => 'decimal:3',
        'weight_to' => 'decimal:3',
        'base_rate' => 'decimal:2',
        'per_kg_rate' => 'decimal:2',
        'fuel_surcharge_percent' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeEffective($query, ?Carbon $date = null)
    {
        $date = $date ?? now();
        return $query
            ->where('effective_from', '<=', $date)
            ->where(fn($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date));
    }

    public function scopeForService($query, string $serviceLevel)
    {
        return $query->where('service_level', $serviceLevel);
    }

    public function scopeForWeight($query, float $weight)
    {
        return $query
            ->where('weight_from', '<=', $weight)
            ->where(fn($q) => $q->whereNull('weight_to')->orWhere('weight_to', '>=', $weight));
    }

    public static function findApplicable(string $serviceLevel, float $weight, ?string $zone = null): ?self
    {
        return static::active()
            ->effective()
            ->forService($serviceLevel)
            ->forWeight($weight)
            ->when($zone, fn($q) => $q->where(fn($q2) => $q2->whereNull('zone')->orWhere('zone', $zone)))
            ->orderBy('zone', 'desc') // Prefer specific zone over null
            ->first();
    }
}
