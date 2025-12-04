<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RateCard extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'origin_country',
        'dest_country',
        'origin_zones',
        'dest_zones',
        'service_level',
        'currency',
        'minimum_charge',
        'zone_matrix',
        'weight_rules',
        'weight_breaks',
        'dim_rules',
        'fuel_surcharge_percent',
        'security_surcharge',
        'remote_area_surcharge',
        'insurance_rate_percent',
        'express_surcharge',
        'priority_surcharge',
        'urgent_surcharge',
        'cod_fee_percent',
        'cod_min_fee',
        'peak_season_surcharge',
        'oversize_surcharge',
        'transit_days',
        'accessorials',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'zone_matrix' => 'array',
        'weight_rules' => 'array',
        'weight_breaks' => 'array',
        'dim_rules' => 'array',
        'origin_zones' => 'array',
        'dest_zones' => 'array',
        'accessorials' => 'array',
        'fuel_surcharge_percent' => 'decimal:2',
        'security_surcharge' => 'decimal:2',
        'remote_area_surcharge' => 'decimal:2',
        'insurance_rate_percent' => 'decimal:2',
        'minimum_charge' => 'decimal:2',
        'express_surcharge' => 'decimal:2',
        'priority_surcharge' => 'decimal:2',
        'urgent_surcharge' => 'decimal:2',
        'cod_fee_percent' => 'decimal:2',
        'cod_min_fee' => 'decimal:2',
        'peak_season_surcharge' => 'decimal:2',
        'oversize_surcharge' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('rate_card')
            ->logOnly(['name', 'origin_country', 'dest_country', 'is_active'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} rate card");
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRoute($query, string $origin, string $dest)
    {
        return $query->where('origin_country', $origin)
            ->where('dest_country', $dest)
            ->active();
    }

    // Business Logic
    public function calculateRate(array $params): array
    {
        // Basic rate calculation logic
        $baseRate = $this->getBaseRate($params);
        $fuelSurcharge = $baseRate * ($this->fuel_surcharge_percent / 100);
        $accessorials = $this->calculateAccessorials($params);

        return [
            'base_rate' => $baseRate,
            'fuel_surcharge' => $fuelSurcharge,
            'accessorials' => $accessorials,
            'total' => $baseRate + $fuelSurcharge + array_sum($accessorials),
        ];
    }

    private function getBaseRate(array $params): float
    {
        // Simplified rate calculation based on weight and zone
        $weight = $params['weight_kg'] ?? 1;
        $zone = $params['zone'] ?? 'A';

        return ($this->zone_matrix[$zone] ?? 10) * $weight;
    }

    private function calculateAccessorials(array $params): array
    {
        $charges = [];
        foreach ($this->accessorials as $accessorial) {
            if (isset($params[$accessorial['condition']])) {
                $charges[$accessorial['name']] = $accessorial['amount'];
            }
        }

        return $charges;
    }
}
