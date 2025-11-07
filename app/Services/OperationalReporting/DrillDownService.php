<?php

namespace App\Services\OperationalReporting;

use App\Models\ETL\FactShipment;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\ETL\FactPerformanceMetrics;
use App\Models\ETL\DimensionClient;
use App\Models\ETL\DimensionBranch;
use App\Models\ETL\DimensionDriver;
use App\Models\ETL\DimensionRoute;
use App\Models\ETL\DimensionCarrier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DrillDownService
{
    private const CACHE_TTL = 180; // 3 minutes for drill-down data

    /**
     * Get drill-down data for any entity
     */
    public function getDrillDownData(string $entityType, string $entityId, string $level = 'detail', array $filters = []): array
    {
        $cacheKey = "drilldown_{$entityType}_{$entityId}_{$level}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($entityType, $entityId, $level, $filters) {
            return match($entityType) {
                'shipment' => $this->getShipmentDrillDown($entityId, $level, $filters),
                'route' => $this->getRouteDrillDown($entityId, $level, $filters),
                'driver' => $this->getDriverDrillDown($entityId, $level, $filters),
                'client' => $this->getClientDrillDown($entityId, $level, $filters),
                'branch' => $this->getBranchDrillDown($entityId, $level, $filters),
                'carrier' => $this->getCarrierDrillDown($entityId, $level, $filters),
                default => $this->getGenericDrillDown($entityType, $entityId, $level, $filters)
            };
        });
    }

    /**
     * Get shipment-level drill-down data
     */
    private function getShipmentDrillDown(string $shipmentKey, string $level, array $filters): array
    {
        $shipment = FactShipment::with([
            'client', 'route', 'driver', 'originBranch', 'destinationBranch', 'carrier',
            'pickupDate', 'deliveryDate'
        ])->find($shipmentKey);

        if (!$shipment) {
            throw new \Exception("Shipment not found: {$shipmentKey}");
        }

        $baseData = [
            'shipment_details' => [
                'shipment_key' => $shipment->shipment_key,
                'tracking_number' => $shipment->tracking_number ?? "TRK{$shipment->shipment_key}",
                'status' => $shipment->shipment_status,
                'created_at' => $shipment->created_at?->toISOString(),
                'pickup_date' => $shipment->pickupDate?->date_value?->toISOString(),
                'delivery_date' => $shipment->deliveryDate?->date_value?->toISOString(),
                'actual_delivery_time' => $shipment->actual_delivery_time?->toISOString(),
                'scheduled_delivery_time' => $shipment->scheduled_delivery_time?->toISOString(),
                'is_on_time' => $shipment->on_time_indicator,
                'delay_minutes' => $shipment->getDeliveryDelay() ? $shipment->getDeliveryDelay() * 60 : 0
            ],
            'financial_details' => $this->getShipmentFinancialDetails($shipment),
            'operational_details' => [
                'distance_miles' => $shipment->distance_miles,
                'weight_lbs' => $shipment->weight_lbs,
                'dimensions_cubic_feet' => $shipment->dimensions_cubic_feet,
                'stops_count' => $shipment->stops_count,
                'transit_time_hours' => $shipment->transit_time_hours,
                'route_efficiency_score' => $shipment->route_efficiency_score,
                'billable_weight' => $shipment->billable_weight
            ],
            'exception_details' => $this->getShipmentExceptionDetails($shipment)
        ];

        $relatedEntities = [];
        if ($level !== 'summary') {
            $relatedEntities = [
                'related_shipments' => $this->getRelatedShipments($shipment, 5),
                'route_performance' => $this->getRoutePerformanceDrillDown($shipment->route_key),
                'driver_performance' => $this->getDriverPerformanceDrillDown($shipment->driver_key),
                'client_history' => $this->getClientHistoryDrillDown($shipment->client_key, 10)
            ];
        }

        return array_merge($baseData, $relatedEntities);
    }

    /**
     * Get route-level drill-down data
     */
    private function getRouteDrillDown(string $routeKey, string $level, array $filters): array
    {
        $route = DimensionRoute::find($routeKey);
        
        if (!$route) {
            throw new \Exception("Route not found: {$routeKey}");
        }

        $query = FactShipment::where('route_key', $routeKey);
        $this->applyFilters($query, $filters);
        
        $shipments = $query->get();

        $baseData = [
            'route_details' => [
                'route_key' => $route->route_key,
                'route_name' => $route->route_name,
                'route_type' => $route->route_type,
                'distance_miles' => $route->distance_miles,
                'estimated_time_hours' => $route->estimated_time_hours,
                'is_active' => $route->is_active
            ],
            'performance_summary' => [
                'total_shipments' => $shipments->count(),
                'average_transit_time' => round($shipments->avg('transit_time_hours') ?? 0, 2),
                'on_time_rate' => $shipments->count() > 0 ? round(($shipments->where('on_time_indicator', true)->count() / $shipments->count()) * 100, 2) : 0,
                'average_efficiency_score' => round($shipments->avg('route_efficiency_score') ?? 0, 2),
                'total_revenue' => round($shipments->sum('revenue'), 2),
                'total_costs' => round($shipments->sum('total_cost'), 2)
            ]
        ];

        if ($level !== 'summary') {
            $baseData['detailed_analysis'] = [
                'shipment_details' => $this->getRouteShipmentDetails($shipments, 20),
                'performance_trends' => $this->getRoutePerformanceTrends($routeKey),
                'bottleneck_analysis' => $this->getRouteBottleneckAnalysis($routeKey),
                'cost_breakdown' => $this->getRouteCostBreakdown($shipments)
            ];
        }

        return $baseData;
    }

    /**
     * Get driver-level drill-down data
     */
    private function getDriverDrillDown(string $driverKey, string $level, array $filters): array
    {
        $driver = DimensionDriver::find($driverKey);
        
        if (!$driver) {
            throw new \Exception("Driver not found: {$driverKey}");
        }

        $query = FactShipment::where('driver_key', $driverKey);
        $this->applyFilters($query, $filters);
        
        $shipments = $query->get();

        $baseData = [
            'driver_details' => [
                'driver_key' => $driver->driver_key,
                'driver_name' => $driver->driver_name,
                'license_class' => $driver->license_class,
                'experience_years' => $driver->experience_years,
                'safety_rating' => $driver->safety_rating,
                'hire_date' => $driver->hire_date?->toISOString(),
                'is_active' => $driver->is_active
            ],
            'performance_summary' => [
                'total_shipments' => $shipments->count(),
                'on_time_rate' => $shipments->count() > 0 ? round(($shipments->where('on_time_indicator', true)->count() / $shipments->count()) * 100, 2) : 0,
                'total_miles_driven' => round($shipments->sum('distance_miles'), 2),
                'average_stops_per_day' => $this->calculateAverageStopsPerDay($shipments),
                'safety_incidents' => $shipments->where('exception_flag', true)->where('exception_type', 'security')->count(),
                'total_revenue_generated' => round($shipments->sum('revenue'), 2)
            ]
        ];

        if ($level !== 'summary') {
            $baseData['detailed_analysis'] = [
                'route_performance' => $this->getDriverRoutePerformance($driverKey, $shipments),
                'weekly_schedule' => $this->getDriverWeeklySchedule($driverKey),
                'efficiency_trends' => $this->getDriverEfficiencyTrends($driverKey),
                'performance_comparison' => $this->getDriverPerformanceComparison($driverKey)
            ];
        }

        return $baseData;
    }

    /**
     * Get client-level drill-down data
     */
    private function getClientDrillDown(string $clientKey, string $level, array $filters): array
    {
        $client = DimensionClient::find($clientKey);
        
        if (!$client) {
            throw new \Exception("Client not found: {$clientKey}");
        }

        $query = FactShipment::where('client_key', $clientKey);
        $this->applyFilters($query, $filters);
        
        $shipments = $query->get();

        $baseData = [
            'client_details' => [
                'client_key' => $client->client_key,
                'client_name' => $client->client_name,
                'client_type' => $client->client_type,
                'industry' => $client->industry,
                'account_manager' => $client->account_manager,
                'contract_start_date' => $client->contract_start_date?->toISOString(),
                'is_active' => $client->is_active
            ],
            'business_summary' => [
                'total_shipments' => $shipments->count(),
                'total_revenue' => round($shipments->sum('revenue'), 2),
                'average_shipment_value' => round($shipments->avg('revenue') ?? 0, 2),
                'on_time_rate' => $shipments->count() > 0 ? round(($shipments->where('on_time_indicator', true)->count() / $shipments->count()) * 100, 2) : 0,
                'service_level_agreement' => $this->getClientSLACompliance($clientKey, $shipments),
                'growth_trend' => $this->calculateClientGrowthTrend($clientKey)
            ]
        ];

        if ($level !== 'summary') {
            $baseData['detailed_analysis'] = [
                'shipment_breakdown' => $this->getClientShipmentBreakdown($clientKey),
                'route_preferences' => $this->getClientRoutePreferences($clientKey),
                'service_quality_metrics' => $this->getClientServiceQuality($clientKey),
                'cost_analysis' => $this->getClientCostAnalysis($shipments)
            ];
        }

        return $baseData;
    }

    /**
     * Get branch-level drill-down data
     */
    private function getBranchDrillDown(string $branchKey, string $level, array $filters): array
    {
        $branch = DimensionBranch::find($branchKey);
        
        if (!$branch) {
            throw new \Exception("Branch not found: {$branchKey}");
        }

        $query = FactShipment::where(function($q) use ($branchKey) {
            $q->where('origin_branch_key', $branchKey)
              ->orWhere('destination_branch_key', $branchKey);
        });
        $this->applyFilters($query, $filters);
        
        $shipments = $query->get();

        $baseData = [
            'branch_details' => [
                'branch_key' => $branch->branch_key,
                'branch_name' => $branch->branch_name,
                'branch_type' => $branch->branch_type,
                'region' => $branch->region,
                'address' => $branch->address,
                'manager' => $branch->manager,
                'is_active' => $branch->is_active
            ],
            'operational_summary' => [
                'total_shipments_handled' => $shipments->count(),
                'outbound_shipments' => $shipments->where('origin_branch_key', $branchKey)->count(),
                'inbound_shipments' => $shipments->where('destination_branch_key', $branchKey)->count(),
                'average_processing_time' => round($shipments->avg('transit_time_hours') ?? 0, 2),
                'throughput_capacity' => $this->calculateBranchThroughput($branchKey),
                'utilization_rate' => $this->calculateBranchUtilization($branchKey)
            ]
        ];

        if ($level !== 'summary') {
            $baseData['detailed_analysis'] = [
                'peak_hours_analysis' => $this->getBranchPeakHours($branchKey),
                'capacity_planning' => $this->getBranchCapacityPlanning($branchKey),
                'quality_metrics' => $this->getBranchQualityMetrics($branchKey),
                'cost_center_analysis' => $this->getBranchCostCenter($branchKey)
            ];
        }

        return $baseData;
    }

    /**
     * Get carrier-level drill-down data
     */
    private function getCarrierDrillDown(string $carrierKey, string $level, array $filters): array
    {
        $carrier = DimensionCarrier::find($carrierKey);
        
        if (!$carrier) {
            throw new \Exception("Carrier not found: {$carrierKey}");
        }

        $query = FactShipment::where('carrier_key', $carrierKey);
        $this->applyFilters($query, $filters);
        
        $shipments = $query->get();

        $baseData = [
            'carrier_details' => [
                'carrier_key' => $carrier->carrier_key,
                'carrier_name' => $carrier->carrier_name,
                'carrier_type' => $carrier->carrier_type,
                'service_areas' => $carrier->service_areas,
                'contract_terms' => $carrier->contract_terms,
                'rating' => $carrier->rating
            ],
            'partnership_summary' => [
                'total_shipments' => $shipments->count(),
                'contract_value' => round($shipments->sum('shipping_cost'), 2),
                'average_cost_per_shipment' => round($shipments->avg('shipping_cost') ?? 0, 2),
                'on_time_performance' => $shipments->count() > 0 ? round(($shipments->where('on_time_indicator', true)->count() / $shipments->count()) * 100, 2) : 0,
                'cost_efficiency' => $this->calculateCarrierCostEfficiency($shipments),
                'service_quality_score' => $this->calculateCarrierServiceScore($carrierKey)
            ]
        ];

        if ($level !== 'summary') {
            $baseData['detailed_analysis'] = [
                'route_performance' => $this->getCarrierRoutePerformance($carrierKey),
                'cost_analysis' => $this->getCarrierCostAnalysis($shipments),
                'quality_trends' => $this->getCarrierQualityTrends($carrierKey),
                'competitive_analysis' => $this->getCarrierCompetitivePosition($carrierKey)
            ];
        }

        return $baseData;
    }

    // Private helper methods
    private function getGenericDrillDown(string $entityType, string $entityId, string $level, array $filters): array
    {
        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'level' => $level,
            'message' => "Drill-down not implemented for entity type: {$entityType}",
            'available_levels' => ['summary', 'detail', 'aggregate'],
            'filters_applied' => $filters
        ];
    }

    private function getShipmentFinancialDetails($shipment): array
    {
        $transaction = FactFinancialTransaction::where('shipment_key', $shipment->shipment_key)->first();
        
        return [
            'revenue' => round($shipment->revenue, 2),
            'total_cost' => round($shipment->total_cost, 2),
            'shipping_cost' => round($shipment->shipping_cost, 2),
            'fuel_cost' => round($shipment->fuel_cost, 2),
            'labor_cost' => round($shipment->labor_cost, 2),
            'late_penalty_cost' => round($shipment->late_penalty_cost, 2),
            'profit_margin' => round($shipment->getProfitMargin(), 2),
            'cost_per_mile' => round($shipment->getCostPerMile(), 2),
            'transaction_details' => $transaction ? [
                'payment_status' => $transaction->payment_status,
                'payment_method' => $transaction->payment_method,
                'invoice_date' => $transaction->invoice_date?->toISOString(),
                'payment_due_date' => $transaction->payment_due_date?->toISOString()
            ] : null
        ];
    }

    private function getShipmentExceptionDetails($shipment): array
    {
        if (!$shipment->exception_flag) {
            return ['has_exceptions' => false];
        }

        return [
            'has_exceptions' => true,
            'exception_type' => $shipment->exception_type,
            'exception_date' => $shipment->exception_date?->toISOString(),
            'resolution_status' => $shipment->resolution_status ?? 'pending',
            'resolution_notes' => $shipment->resolution_notes
        ];
    }

    private function getRelatedShipments($shipment, int $limit): array
    {
        return FactShipment::where('route_key', $shipment->route_key)
            ->where('shipment_key', '!=', $shipment->shipment_key)
            ->orderBy('delivery_date_key', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($relatedShipment) {
                return [
                    'shipment_key' => $relatedShipment->shipment_key,
                    'status' => $relatedShipment->shipment_status,
                    'delivery_date' => $relatedShipment->deliveryDate?->date_value?->toISOString(),
                    'on_time' => $relatedShipment->on_time_indicator,
                    'revenue' => round($relatedShipment->revenue, 2)
                ];
            })->toArray();
    }

    private function getRoutePerformanceDrillDown(string $routeKey): array
    {
        $recentShipments = FactShipment::where('route_key', $routeKey)
            ->whereBetween('delivery_date_key', [
                now()->subDays(30)->format('Ymd'),
                now()->format('Ymd')
            ])
            ->get();

        return [
            'last_30_days' => [
                'total_shipments' => $recentShipments->count(),
                'on_time_rate' => round(($recentShipments->where('on_time_indicator', true)->count() / $recentShipments->count()) * 100, 2) if $recentShipments->count() > 0,
                'avg_transit_time' => round($recentShipments->avg('transit_time_hours') ?? 0, 2),
                'avg_efficiency_score' => round($recentShipments->avg('route_efficiency_score') ?? 0, 2)
            ]
        ];
    }

    private function getDriverPerformanceDrillDown(string $driverKey): array
    {
        $recentShipments = FactShipment::where('driver_key', $driverKey)
            ->whereBetween('delivery_date_key', [
                now()->subDays(30)->format('Ymd'),
                now()->format('Ymd')
            ])
            ->get();

        return [
            'last_30_days' => [
                'total_shipments' => $recentShipments->count(),
                'on_time_rate' => round(($recentShipments->where('on_time_indicator', true)->count() / $recentShipments->count()) * 100, 2) if $recentShipments->count() > 0,
                'total_miles' => round($recentShipments->sum('distance_miles'), 2),
                'avg_stops_per_shipment' => round($recentShipments->avg('stops_count') ?? 0, 2)
            ]
        ];
    }

    private function getClientHistoryDrillDown(string $clientKey, int $limit): array
    {
        return FactShipment::where('client_key', $clientKey)
            ->orderBy('delivery_date_key', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($shipment) {
                return [
                    'shipment_key' => $shipment->shipment_key,
                    'date' => $shipment->deliveryDate?->date_value?->toISOString(),
                    'route' => $shipment->route?->route_name,
                    'status' => $shipment->shipment_status,
                    'revenue' => round($shipment->revenue, 2),
                    'on_time' => $shipment->on_time_indicator
                ];
            })->toArray();
    }

    private function getRouteShipmentDetails($shipments, int $limit): array
    {
        return $shipments->take($limit)->map(function ($shipment) {
            return [
                'shipment_key' => $shipment->shipment_key,
                'status' => $shipment->shipment_status,
                'delivery_date' => $shipment->deliveryDate?->date_value?->toISOString(),
                'on_time' => $shipment->on_time_indicator,
                'transit_time' => $shipment->transit_time_hours,
                'revenue' => round($shipment->revenue, 2),
                'efficiency_score' => round($shipment->route_efficiency_score, 2)
            ];
        })->toArray();
    }

    private function getRoutePerformanceTrends(string $routeKey): array
    {
        $weeklyData = [];
        for ($i = 0; $i < 8; $i++) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            
            $shipments = FactShipment::where('route_key', $routeKey)
                ->whereBetween('delivery_date_key', [
                    $weekStart->format('Ymd'),
                    $weekEnd->format('Ymd')
                ])->get();
            
            $weeklyData[] = [
                'week' => $weekStart->format('Y-m-d'),
                'shipments' => $shipments->count(),
                'on_time_rate' => $shipments->count() > 0 ? round(($shipments->where('on_time_indicator', true)->count() / $shipments->count()) * 100, 2) : 0,
                'avg_transit_time' => round($shipments->avg('transit_time_hours') ?? 0, 2)
            ];
        }
        
        return array_reverse($weeklyData);
    }

    private function getRouteBottleneckAnalysis(string $routeKey): array
    {
        // This would analyze specific bottlenecks on the route
        return [
            'identified_bottlenecks' => [],
            'severity_score' => 0,
            'recommendations' => []
        ];
    }

    private function getRouteCostBreakdown($shipments): array
    {
        return [
            'total_revenue' => round($shipments->sum('revenue'), 2),
            'total_costs' => round($shipments->sum('total_cost'), 2),
            'shipping_costs' => round($shipments->sum('shipping_cost'), 2),
            'fuel_costs' => round($shipments->sum('fuel_cost'), 2),
            'labor_costs' => round($shipments->sum('labor_cost'), 2),
            'profit_margin' => round($shipments->sum('revenue') - $shipments->sum('total_cost'), 2)
        ];
    }

    private function calculateAverageStopsPerDay($shipments): float
    {
        if ($shipments->isEmpty()) return 0;
        
        $totalDays = $shipments->groupBy('delivery_date_key')->count();
        $totalStops = $shipments->sum('stops_count');
        
        return $totalDays > 0 ? round($totalStops / $totalDays, 2) : 0;
    }

    private function getDriverRoutePerformance(string $driverKey, $shipments): array
    {
        $routePerformance = $shipments->groupBy('route_key')->map(function ($routeShipments, $routeKey) {
            $route = DimensionRoute::find($routeKey);
            return [
                'route_name' => $route?->route_name ?? "Route {$routeKey}",
                'shipments' => $routeShipments->count(),
                'on_time_rate' => round(($routeShipments->where('on_time_indicator', true)->count() / $routeShipments->count()) * 100, 2) if $routeShipments->count() > 0,
                'avg_transit_time' => round($routeShipments->avg('transit_time_hours') ?? 0, 2)
            ];
        });
        
        return $routePerformance->values()->toArray();
    }

    private function getDriverWeeklySchedule(string $driverKey): array
    {
        // This would return the driver's schedule for the current week
        return [
            'current_week' => [],
            'next_week' => []
        ];
    }

    private function getDriverEfficiencyTrends(string $driverKey): array
    {
        // This would calculate efficiency trends over time
        return [
            'trend_direction' => 'stable',
            'efficiency_score' => 0,
            'improvement_areas' => []
        ];
    }

    private function getDriverPerformanceComparison(string $driverKey): array
    {
        // This would compare the driver with fleet averages
        return [
            'vs_fleet_avg' => [],
            'ranking' => 0,
            'percentile' => 0
        ];
    }

    private function getClientSLACompliance(string $clientKey, $shipments): array
    {
        $totalShipments = $shipments->count();
        $onTimeShipments = $shipments->where('on_time_indicator', true)->count();
        
        return [
            'compliance_rate' => round(($onTimeShipments / $totalShipments) * 100, 2) if $totalShipments > 0,
            'target_rate' => 95.0, // Example SLA target
            'meets_sla' => $totalShipments > 0 && ($onTimeShipments / $totalShipments) >= 0.95
        ];
    }

    private function calculateClientGrowthTrend(string $clientKey): string
    {
        // This would calculate month-over-month growth
        return 'stable';
    }

    private function getClientShipmentBreakdown(string $clientKey): array
    {
        $shipments = FactShipment::where('client_key', $clientKey)
            ->whereBetween('delivery_date_key', [
                now()->subDays(30)->format('Ymd'),
                now()->format('Ymd')
            ])->get();
        
        return [
            'by_status' => $shipments->groupBy('shipment_status')->map->count(),
            'by_route' => $shipments->groupBy('route_key')->map->count(),
            'daily_volume' => $shipments->groupBy('delivery_date_key')->map->count()
        ];
    }

    private function getClientRoutePreferences(string $clientKey): array
    {
        $shipments = FactShipment::where('client_key', $clientKey)
            ->select('route_key', DB::raw('COUNT(*) as count'))
            ->groupBy('route_key')
            ->orderByDesc('count')
            ->take(5)
            ->get();
        
        return $shipments->map(function ($item) {
            $route = DimensionRoute::find($item->route_key);
            return [
                'route_name' => $route?->route_name ?? "Route {$item->route_key}",
                'usage_count' => $item->count
            ];
        })->toArray();
    }

    private function getClientServiceQuality(string $clientKey): array
    {
        return [
            'satisfaction_score' => 0,
            'complaint_rate' => 0,
            'resolution_time' => 0
        ];
    }

    private function getClientCostAnalysis($shipments): array
    {
        return [
            'total_spend' => round($shipments->sum('revenue'), 2),
            'avg_cost_per_shipment' => round($shipments->avg('revenue') ?? 0, 2),
            'cost_trend' => 'stable',
            'budget_variance' => 0
        ];
    }

    private function calculateBranchThroughput(string $branchKey): string
    {
        // This would calculate the branch's processing capacity
        return 'normal';
    }

    private function calculateBranchUtilization(string $branchKey): float
    {
        // This would calculate how well the branch is being utilized
        return 0.0;
    }

    private function getBranchPeakHours(string $branchKey): array
    {
        // This would analyze peak processing hours
        return [
            'peak_hours' => [],
            'capacity_utilization' => []
        ];
    }

    private function getBranchCapacityPlanning(string $branchKey): array
    {
        return [
            'current_capacity' => 0,
            'projected_demand' => 0,
            'capacity_recommendations' => []
        ];
    }

    private function getBranchQualityMetrics(string $branchKey): array
    {
        return [
            'processing_accuracy' => 0,
            'error_rate' => 0,
            'customer_satisfaction' => 0
        ];
    }

    private function getBranchCostCenter(string $branchKey): array
    {
        return [
            'operational_costs' => 0,
            'cost_per_shipment' => 0,
            'efficiency_ratio' => 0
        ];
    }

    private function calculateCarrierCostEfficiency($shipments): float
    {
        if ($shipments->isEmpty()) return 0;
        
        $totalCost = $shipments->sum('shipping_cost');
        $totalDistance = $shipments->sum('distance_miles');
        
        return $totalDistance > 0 ? round($totalCost / $totalDistance, 2) : 0;
    }

    private function calculateCarrierServiceScore(string $carrierKey): float
    {
        $shipments = FactShipment::where('carrier_key', $carrierKey)
            ->whereBetween('delivery_date_key', [
                now()->subDays(30)->format('Ymd'),
                now()->format('Ymd')
            ])->get();
        
        if ($shipments->isEmpty()) return 0;
        
        $onTimeRate = ($shipments->where('on_time_indicator', true)->count() / $shipments->count()) * 100;
        $efficiencyScore = $shipments->avg('route_efficiency_score') ?? 0;
        
        return round(($onTimeRate * 0.7) + ($efficiencyScore * 0.3), 2);
    }

    private function getCarrierRoutePerformance(string $carrierKey): array
    {
        $shipments = FactShipment::where('carrier_key', $carrierKey)
            ->whereBetween('delivery_date_key', [
                now()->subDays(30)->format('Ymd'),
                now()->format('Ymd')
            ])
            ->select('route_key', DB::raw('COUNT(*) as count'), DB::raw('AVG(route_efficiency_score) as avg_efficiency'))
            ->groupBy('route_key')
            ->get();
        
        return $shipments->map(function ($item) {
            $route = DimensionRoute::find($item->route_key);
            return [
                'route_name' => $route?->route_name ?? "Route {$item->route_key}",
                'shipments' => $item->count,
                'efficiency_score' => round($item->avg_efficiency, 2)
            ];
        })->toArray();
    }

    private function getCarrierCostAnalysis($shipments): array
    {
        return [
            'total_cost' => round($shipments->sum('shipping_cost'), 2),
            'cost_per_shipment' => round($shipments->avg('shipping_cost') ?? 0, 2),
            'cost_trend' => 'stable',
            'competitive_position' => 'average'
        ];
    }

    private function getCarrierQualityTrends(string $carrierKey): array
    {
        return [
            'trend_direction' => 'stable',
            'quality_score' => 0,
            'improvement_areas' => []
        ];
    }

    private function getCarrierCompetitivePosition(string $carrierKey): array
    {
        return [
            'market_position' => 'average',
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => []
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['date_range'])) {
            $query->whereBetween('delivery_date_key', [$filters['date_range']['start'], $filters['date_range']['end']]);
        }
        
        if (isset($filters['client_key'])) {
            $query->where('client_key', $filters['client_key']);
        }
        
        if (isset($filters['route_key'])) {
            $query->where('route_key', $filters['route_key']);
        }
        
        if (isset($filters['driver_key'])) {
            $query->where('driver_key', $filters['driver_key']);
        }
        
        if (isset($filters['carrier_key'])) {
            $query->where('carrier_key', $filters['carrier_key']);
        }
    }
}