<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RateCard extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'origin_country',
        'dest_country',
        'zone_matrix',
        'weight_rules',
        'dim_rules',
        'fuel_surcharge_percent',
        'accessorials',
        'is_active',
    ];

    protected $casts = [
        'zone_matrix' => 'array',
        'weight_rules' => 'array',
        'dim_rules' => 'array',
        'accessorials' => 'array',
        'fuel_surcharge_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('rate_card')
            ->logOnly(['name', 'origin_country', 'dest_country', 'is_active'])
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName} rate card");
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
