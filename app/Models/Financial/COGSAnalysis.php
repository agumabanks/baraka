<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class COGSAnalysis extends Model
{
    protected $fillable = [
        'shipment_key',
        'client_key',
        'route_key',
        'driver_key',
        'period_key',
        'fuel_cost',
        'labor_cost',
        'insurance_cost',
        'maintenance_cost',
        'depreciation_cost',
        'vehicle_cost',
        'driver_wages',
        'other_costs',
        'total_cogs',
        'budgeted_cogs',
        'variance_amount',
        'variance_percentage',
        'cost_per_shipment',
        'cost_per_mile',
        'cost_per_weight',
        'cost_category',
        'cost_subcategory',
        'allocation_method',
        'is_budgeted',
        'is_actual',
        'calculation_date',
        'notes'
    ];

    protected $casts = [
        'fuel_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'maintenance_cost' => 'decimal:2',
        'depreciation_cost' => 'decimal:2',
        'vehicle_cost' => 'decimal:2',
        'driver_wages' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'total_cogs' => 'decimal:2',
        'budgeted_cogs' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_percentage' => 'decimal:4',
        'cost_per_shipment' => 'decimal:2',
        'cost_per_mile' => 'decimal:2',
        'cost_per_weight' => 'decimal:2',
        'is_budgeted' => 'boolean',
        'is_actual' => 'boolean',
        'calculation_date' => 'date'
    ];

    // Cost category constants
    const CATEGORY_FUEL = 'fuel';
    const CATEGORY_LABOR = 'labor';
    const CATEGORY_INSURANCE = 'insurance';
    const CATEGORY_MAINTENANCE = 'maintenance';
    const CATEGORY_DEPRECIATION = 'depreciation';
    const CATEGORY_VEHICLE = 'vehicle';
    const CATEGORY_DRIVER_WAGES = 'driver_wages';
    const CATEGORY_OTHER = 'other';

    // Allocation method constants
    const METHOD_DIRECT = 'direct';
    const METHOD_PROPORTIONAL = 'proportional';
    const METHOD_ACTIVITY_BASED = 'activity_based';
    const METHOD_TIME_BASED = 'time_based';
    const METHOD_DISTANCE_BASED = 'distance_based';

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\FactShipment::class, 'shipment_key', 'shipment_key');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionClient::class, 'client_key', 'client_key');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionRoute::class, 'route_key', 'route_key');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionDriver::class, 'driver_key', 'driver_key');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ETL\DimensionDate::class, 'period_key', 'date_key');
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('cost_category', $category);
    }

    public function scopeActual($query)
    {
        return $query->where('is_actual', true);
    }

    public function scopeBudgeted($query)
    {
        return $query->where('is_budgeted', true);
    }

    public function scopeVariance($query)
    {
        return $query->where('variance_amount', '!=', 0);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('calculation_date', [$startDate, $endDate]);
    }

    public function scopeByClient($query, $clientKey)
    {
        return $query->where('client_key', $clientKey);
    }

    public function scopeByRoute($query, $routeKey)
    {
        return $query->where('route_key', $routeKey);
    }

    // Helper methods
    public function calculateTotalCOGS(): float
    {
        return $this->fuel_cost + $this->labor_cost + $this->insurance_cost + 
               $this->maintenance_cost + $this->depreciation_cost + $this->vehicle_cost + 
               $this->driver_wages + $this->other_costs;
    }

    public function calculateVariance(): void
    {
        $this->total_cogs = $this->calculateTotalCOGS();
        
        if ($this->budgeted_cogs > 0) {
            $this->variance_amount = $this->total_cogs - $this->budgeted_cogs;
            $this->variance_percentage = ($this->variance_amount / $this->budgeted_cogs) * 100;
        } else {
            $this->variance_amount = 0;
            $this->variance_percentage = 0;
        }
    }

    public function isOverBudget(): bool
    {
        return $this->variance_amount > 0;
    }

    public function isUnderBudget(): bool
    {
        return $this->variance_amount < 0;
    }

    public function getCostBreakdown(): array
    {
        return [
            'fuel' => $this->fuel_cost,
            'labor' => $this->labor_cost,
            'insurance' => $this->insurance_cost,
            'maintenance' => $this->maintenance_cost,
            'depreciation' => $this->depreciation_cost,
            'vehicle' => $this->vehicle_cost,
            'driver_wages' => $this->driver_wages,
            'other' => $this->other_costs,
            'total' => $this->total_cogs
        ];
    }

    public function getCostPercentages(): array
    {
        $breakdown = $this->getCostBreakdown();
        $total = $breakdown['total'];
        
        if ($total <= 0) {
            return array_fill_keys(array_keys($breakdown), 0);
        }

        foreach ($breakdown as $key => $value) {
            $breakdown[$key] = ($value / $total) * 100;
        }

        return $breakdown;
    }

    public function getCostPerShipment(float $shipmentCount): float
    {
        return $shipmentCount > 0 ? $this->total_cogs / $shipmentCount : 0;
    }

    public function getCostPerMile(float $totalMiles): float
    {
        return $totalMiles > 0 ? $this->total_cogs / $totalMiles : 0;
    }

    public function getCostPerWeight(float $totalWeight): float
    {
        return $totalWeight > 0 ? $this->total_cogs / $totalWeight : 0;
    }

    public function allocateCosts(string $method, array $allocationData): void
    {
        match($method) {
            self::METHOD_PROPORTIONAL => $this->allocateProportionally($allocationData),
            self::METHOD_ACTIVITY_BASED => $this->allocateByActivity($allocationData),
            self::METHOD_TIME_BASED => $this->allocateByTime($allocationData),
            self::METHOD_DISTANCE_BASED => $this->allocateByDistance($allocationData),
            default => $this->allocateDirectly($allocationData)
        };
    }

    private function allocateProportionally(array $data): void
    {
        // Implementation for proportional allocation
        // Based on shipment count, revenue, or other proportional metrics
    }

    private function allocateByActivity(array $data): void
    {
        // Implementation for activity-based allocation
        // Based on specific activities like stops, pickups, deliveries
    }

    private function allocateByTime(array $data): void
    {
        // Implementation for time-based allocation
        // Based on time spent, service duration, etc.
    }

    private function allocateByDistance(array $data): void
    {
        // Implementation for distance-based allocation
        // Based on miles driven, route distance, etc.
    }

    private function allocateDirectly(array $data): void
    {
        // Implementation for direct allocation
        // Direct assignment to specific cost centers
    }

    public function getCategoryTotal(string $category): float
    {
        return match($category) {
            self::CATEGORY_FUEL => $this->fuel_cost,
            self::CATEGORY_LABOR => $this->labor_cost,
            self::CATEGORY_INSURANCE => $this->insurance_cost,
            self::CATEGORY_MAINTENANCE => $this->maintenance_cost,
            self::CATEGORY_DEPRECIATION => $this->depreciation_cost,
            self::CATEGORY_VEHICLE => $this->vehicle_cost,
            self::CATEGORY_DRIVER_WAGES => $this->driver_wages,
            self::CATEGORY_OTHER => $this->other_costs,
            default => 0
        };
    }
}