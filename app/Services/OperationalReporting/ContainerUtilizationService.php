<?php

namespace App\Services\OperationalReporting;

use App\Models\ETL\FactShipment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ContainerUtilizationService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CONTAINER_TYPES = [
        'standard_20ft' => 20,      // 20 feet length
        'standard_40ft' => 40,      // 40 feet length
        'high_cube_40ft' => 40,     // High cube 40 feet
        'refrigerated_20ft' => 20,  // Reefer 20 feet
        'refrigerated_40ft' => 40,  // Reefer 40 feet
        'open_top_20ft' => 20,      // Open top 20 feet
        'flat_rack_20ft' => 20      // Flat rack 20 feet
    ];

    /**
     * Calculate utilization rates for containers
     */
    public function calculateUtilizationRate(string $containerId, array $dateRange): array
    {
        $cacheKey = "container_utilization_{$containerId}_{$dateRange['start']}_{$dateRange['end']}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($containerId, $dateRange) {
            $shipments = FactShipment::where('container_id', $containerId)
                ->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']])
                ->get();

            if ($shipments->isEmpty()) {
                return [
                    'container_id' => $containerId,
                    'utilization_data' => null,
                    'message' => 'No shipment data found for this container in the specified period'
                ];
            }

            $containerSpecs = $this->getContainerSpecifications($containerId);
            $totalWeight = $shipments->sum('weight_lbs');
            $totalVolume = $shipments->sum('dimensions_cubic_feet');
            $maxCapacityWeight = $containerSpecs['max_weight_lbs'] ?? 50000; // Default 50,000 lbs
            $maxCapacityVolume = $containerSpecs['max_volume_cubic_feet'] ?? 1160; // Default for 20ft container
            
            $weightUtilization = ($totalWeight / $maxCapacityWeight) * 100;
            $volumeUtilization = ($totalVolume / $maxCapacityVolume) * 100;
            $overallUtilization = min($weightUtilization, $volumeUtilization); // Conservative approach
            
            $utilizationTrends = $this->analyzeUtilizationTrends($shipments, $dateRange);
            $efficiencyAnalysis = $this->analyzeEfficiency($shipments, $containerSpecs);
            
            return [
                'container_id' => $containerId,
                'container_specifications' => $containerSpecs,
                'utilization_metrics' => [
                    'weight_utilization' => [
                        'used' => round($totalWeight, 2),
                        'capacity' => $maxCapacityWeight,
                        'percentage' => round($weightUtilization, 2),
                        'status' => $this->getUtilizationStatus($weightUtilization)
                    ],
                    'volume_utilization' => [
                        'used' => round($totalVolume, 2),
                        'capacity' => $maxCapacityVolume,
                        'percentage' => round($volumeUtilization, 2),
                        'status' => $this->getUtilizationStatus($volumeUtilization)
                    ],
                    'overall_utilization' => [
                        'percentage' => round($overallUtilization, 2),
                        'status' => $this->getUtilizationStatus($overallUtilization)
                    ]
                ],
                'operational_efficiency' => $efficiencyAnalysis,
                'trends' => $utilizationTrends,
                'optimization_opportunities' => $this->getOptimizationOpportunities($weightUtilization, $volumeUtilization),
                'cost_analysis' => $this->calculateCostEfficiency($shipments, $containerSpecs)
            ];
        });
    }

    /**
     * Generate optimization suggestions for container usage
     */
    public function generateOptimizationSuggestions(string $routeId): array
    {
        $cacheKey = "container_optimization_{$routeId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($routeId) {
            $shipments = FactShipment::where('route_key', $routeId)
                ->whereNotNull('container_id')
                ->get();

            if ($shipments->isEmpty()) {
                return [
                    'route_id' => $routeId,
                    'optimization_data' => null,
                    'message' => 'No container data found for this route'
                ];
            }

            $containerUtilization = $this->analyzeContainerUtilizationByRoute($shipments);
            $loadBalancingAnalysis = $this->analyzeLoadBalancing($shipments);
            $routingOptimization = $this->analyzeRoutingOptimization($shipments);
            
            return [
                'route_id' => $routeId,
                'current_state' => [
                    'total_containers_used' => $shipments->unique('container_id')->count(),
                    'average_utilization' => round($containerUtilization['average_utilization'], 2),
                    'underutilized_containers' => $containerUtilization['underutilized_count'],
                    'overutilized_containers' => $containerUtilization['overutilized_count']
                ],
                'load_balancing_suggestions' => $loadBalancingAnalysis,
                'routing_optimization' => $routingOptimization,
                'capacity_planning' => $this->getCapacityPlanningSuggestions($shipments),
                'financial_impact' => $this->estimateOptimizationSavings($containerUtilization),
                'implementation_roadmap' => $this->createImplementationRoadmap($containerUtilization)
            ];
        });
    }

    /**
     * Perform capacity planning analysis
     */
    public function performCapacityPlanning(array $dateRange): array
    {
        $cacheKey = "capacity_planning_{$dateRange['start']}_{$dateRange['end']}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateRange) {
            $shipments = FactShipment::whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']])
                ->whereNotNull('container_id')
                ->get();

            if ($shipments->isEmpty()) {
                return [
                    'date_range' => $dateRange,
                    'capacity_analysis' => null,
                    'message' => 'No container data available for capacity planning'
                ];
            }

            $capacityAnalysis = [
                'demand_analysis' => $this->analyzeDemandPatterns($shipments),
                'supply_analysis' => $this->analyzeContainerSupply($shipments),
                'bottleneck_identification' => $this->identifyCapacityBottlenecks($shipments),
                'forecast' => $this->forecastContainerDemand($shipments, $dateRange)
            ];

            return [
                'date_range' => $dateRange,
                'capacity_analysis' => $capacityAnalysis,
                'recommendations' => [
                    'immediate_actions' => $this->getImmediateCapacityActions($capacityAnalysis),
                    'medium_term_suggestions' => $this->getMediumTermCapacityActions($capacityAnalysis),
                    'long_term_strategy' => $this->getLongTermCapacityStrategy($capacityAnalysis)
                ]
            ];
        });
    }

    /**
     * Analyze cost efficiency of container usage
     */
    public function analyzeCostEfficiency(string $containerId, array $dateRange): array
    {
        $cacheKey = "container_cost_efficiency_{$containerId}_{$dateRange['start']}_{$dateRange['end']}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($containerId, $dateRange) {
            $shipments = FactShipment::where('container_id', $containerId)
                ->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']])
                ->get();

            if ($shipments->isEmpty()) {
                return [
                    'container_id' => $containerId,
                    'cost_analysis' => null,
                    'message' => 'No data available for cost efficiency analysis'
                ];
            }

            $containerSpecs = $this->getContainerSpecifications($containerId);
            $totalRevenue = $shipments->sum('revenue');
            $totalCosts = $shipments->sum('total_cost');
            $containerCosts = $this->estimateContainerCosts($shipments, $containerSpecs);
            
            $costPerUnit = $totalCosts / $shipments->count();
            $revenuePerUnit = $totalRevenue / $shipments->count();
            $profitMargin = $totalRevenue > 0 ? (($totalRevenue - $totalCosts) / $totalRevenue) * 100 : 0;
            
            return [
                'container_id' => $containerId,
                'cost_breakdown' => [
                    'total_revenue' => round($totalRevenue, 2),
                    'total_costs' => round($totalCosts, 2),
                    'container_specific_costs' => $containerCosts,
                    'cost_per_shipment' => round($costPerUnit, 2),
                    'revenue_per_shipment' => round($revenuePerUnit, 2),
                    'profit_margin' => round($profitMargin, 2)
                ],
                'efficiency_metrics' => [
                    'revenue_per_container_mile' => $this->calculateRevenuePerMile($totalRevenue, $shipments),
                    'cost_per_container_mile' => $this->calculateCostPerMile($totalCosts, $shipments),
                    'utilization_efficiency' => $this->getUtilizationEfficiencyScore($shipments)
                ],
                'benchmarking' => $this->benchmarkContainerEfficiency($containerId, $shipments),
                'optimization_potential' => $this->estimateCostOptimizationPotential($shipments, $containerCosts)
            ];
        });
    }

    /**
     * Get load optimization suggestions
     */
    public function getLoadOptimizationSuggestions(string $containerId): array
    {
        $cacheKey = "load_optimization_{$containerId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($containerId) {
            $recentShipments = FactShipment::where('container_id', $containerId)
                ->orderBy('delivery_date_key', 'desc')
                ->take(50) // Last 50 shipments
                ->get();

            if ($recentShipments->isEmpty()) {
                return [
                    'container_id' => $containerId,
                    'optimization_suggestions' => null,
                    'message' => 'Insufficient data for load optimization analysis'
                ];
            }

            $loadPatterns = $this->analyzeLoadPatterns($recentShipments);
            $weightDistribution = $this->analyzeWeightDistribution($recentShipments);
            $stackingEfficiency = $this->analyzeStackingEfficiency($recentShipments);
            
            return [
                'container_id' => $containerId,
                'load_analysis' => [
                    'current_patterns' => $loadPatterns,
                    'weight_distribution' => $weightDistribution,
                    'stacking_efficiency' => $stackingEfficiency
                ],
                'optimization_suggestions' => [
                    'weight_optimization' => $this->getWeightOptimizationSuggestions($weightDistribution),
                    'stacking_improvements' => $this->getStackingImprovements($stackingEfficiency),
                    'routing_efficiency' => $this->getRoutingEfficiencySuggestions($recentShipments)
                ],
                'implementation_impact' => [
                    'potential_utilization_improvement' => '15-25%',
                    'estimated_cost_savings' => '10-15%',
                    'implementation_timeline' => '2-4 weeks'
                ]
            ];
        });
    }

    // Private helper methods
    private function getContainerSpecifications(string $containerId): array
    {
        // This would typically come from a container master table
        // For now, return estimated specifications based on container ID pattern
        $containerType = $this->determineContainerType($containerId);
        
        $specifications = [
            'container_type' => $containerType,
            'max_weight_lbs' => self::CONTAINER_TYPES[$containerType] ?? 50000,
            'max_volume_cubic_feet' => $this->getMaxVolume($containerType),
            'standard_dimensions' => $this->getStandardDimensions($containerType)
        ];
        
        return $specifications;
    }

    private function determineContainerType(string $containerId): string
    {
        // This would use actual container master data
        // For now, use a simple pattern match
        if (strpos($containerId, 'REEFER') !== false || strpos($containerId, 'REF') !== false) {
            return 'refrigerated_40ft';
        }
        
        if (strpos($containerId, '40') !== false) {
            return 'standard_40ft';
        }
        
        return 'standard_20ft';
    }

    private function getMaxVolume(string $containerType): float
    {
        return match($containerType) {
            'standard_20ft' => 1160.0,
            'standard_40ft' => 2690.0,
            'high_cube_40ft' => 3040.0,
            'refrigerated_20ft' => 1020.0,
            'refrigerated_40ft' => 2350.0,
            default => 1160.0
        };
    }

    private function getStandardDimensions(string $containerType): array
    {
        return match($containerType) {
            'standard_20ft' => ['length' => 20, 'width' => 8, 'height' => 8.5],
            'standard_40ft' => ['length' => 40, 'width' => 8, 'height' => 8.5],
            'high_cube_40ft' => ['length' => 40, 'width' => 8, 'height' => 9.5],
            'refrigerated_20ft' => ['length' => 20, 'width' => 8, 'height' => 8.5],
            'refrigerated_40ft' => ['length' => 40, 'width' => 8, 'height' => 8.5],
            default => ['length' => 20, 'width' => 8, 'height' => 8.5]
        };
    }

    private function getUtilizationStatus(float $percentage): string
    {
        if ($percentage >= 90) return 'optimal';
        if ($percentage >= 75) return 'efficient';
        if ($percentage >= 60) return 'adequate';
        if ($percentage >= 40) return 'underutilized';
        return 'severely_underutilized';
    }

    private function analyzeUtilizationTrends(Collection $shipments, array $dateRange): array
    {
        $weeklyData = [];
        $grouped = $shipments->groupBy(function($shipment) {
            return date('Y-W', strtotime($shipment->actual_delivery_time));
        });
        
        foreach ($grouped as $week => $weekShipments) {
            $totalWeight = $weekShipments->sum('weight_lbs');
            $totalVolume = $weekShipments->sum('dimensions_cubic_feet');
            
            $weeklyData[] = [
                'week' => $week,
                'shipments' => $weekShipments->count(),
                'weight_utilization' => round(($totalWeight / 50000) * 100, 2),
                'volume_utilization' => round(($totalVolume / 1160) * 100, 2)
            ];
        }
        
        return [
            'weekly_trends' => $weeklyData,
            'trend_direction' => $this->calculateUtilizationTrend($weeklyData)
        ];
    }

    private function calculateUtilizationTrend(array $weeklyData): string
    {
        if (count($weeklyData) < 2) return 'insufficient_data';
        
        $utilizationValues = array_column($weeklyData, 'weight_utilization');
        $recentAvg = array_sum(array_slice($utilizationValues, -2)) / 2;
        $earlierAvg = array_sum(array_slice($utilizationValues, 0, 2)) / 2;
        
        $change = (($recentAvg - $earlierAvg) / $earlierAvg) * 100;
        
        return $change > 5 ? 'improving' : ($change < -5 ? 'declining' : 'stable');
    }

    private function analyzeEfficiency(Collection $shipments, array $containerSpecs): array
    {
        $totalWeight = $shipments->sum('weight_lbs');
        $totalVolume = $shipments->sum('dimensions_cubic_feet');
        $totalStops = $shipments->sum('stops_count');
        $totalDistance = $shipments->sum('distance_miles');
        
        return [
            'weight_efficiency' => round(($totalWeight / $containerSpecs['max_weight_lbs']) * 100, 2),
            'volume_efficiency' => round(($totalVolume / $containerSpecs['max_volume_cubic_feet']) * 100, 2),
            'stops_per_mile' => $totalDistance > 0 ? round($totalStops / $totalDistance, 2) : 0,
            'revenue_per_weight' => $totalWeight > 0 ? round($shipments->sum('revenue') / $totalWeight, 2) : 0
        ];
    }

    private function getOptimizationOpportunities(float $weightUtilization, float $volumeUtilization): array
    {
        $opportunities = [];
        
        if ($weightUtilization < 70) {
            $opportunities[] = [
                'type' => 'weight_optimization',
                'description' => 'Increase weight utilization through better load planning',
                'potential_impact' => '10-15% improvement in weight utilization',
                'implementation_effort' => 'medium'
            ];
        }
        
        if ($volumeUtilization < 70) {
            $opportunities[] = [
                'type' => 'volume_optimization',
                'description' => 'Optimize space utilization through improved packing',
                'potential_impact' => '15-20% improvement in volume utilization',
                'implementation_effort' => 'low'
            ];
        }
        
        if (abs($weightUtilization - $volumeUtilization) > 20) {
            $opportunities[] = [
                'type' => 'balancing_optimization',
                'description' => 'Balance weight and volume utilization',
                'potential_impact' => 'Overall efficiency improvement',
                'implementation_effort' => 'high'
            ];
        }
        
        return $opportunities;
    }

    private function calculateCostEfficiency(Collection $shipments, array $containerSpecs): array
    {
        $totalRevenue = $shipments->sum('revenue');
        $totalCosts = $shipments->sum('total_cost');
        $containerCosts = $this->estimateContainerCosts($shipments, $containerSpecs);
        
        return [
            'revenue_per_container' => round($totalRevenue, 2),
            'cost_per_container' => round($totalCosts, 2),
            'container_specific_costs' => $containerCosts,
            'roi_percentage' => $totalCosts > 0 ? round((($totalRevenue - $totalCosts) / $totalCosts) * 100, 2) : 0
        ];
    }

    private function estimateContainerCosts(Collection $shipments, array $containerSpecs): array
    {
        $daysUsed = $this->estimateDaysUsed($shipments);
        $milesTraveled = $shipments->sum('distance_miles');
        
        return [
            'rental_cost' => $daysUsed * 25, // $25 per day
            'fuel_cost' => $milesTraveled * 0.50, // $0.50 per mile
            'maintenance_cost' => $milesTraveled * 0.05, // $0.05 per mile
            'total_estimated_cost' => ($daysUsed * 25) + ($milesTraveled * 0.55)
        ];
    }

    private function estimateDaysUsed(Collection $shipments): int
    {
        if ($shipments->isEmpty()) return 0;
        
        $firstShipment = $shipments->sortBy('pickup_date_key')->first();
        $lastShipment = $shipments->sortByDesc('delivery_date_key')->first();
        
        return \Carbon\Carbon::createFromFormat('Ymd', $lastShipment->delivery_date_key)
            ->diffInDays(\Carbon\Carbon::createFromFormat('Ymd', $firstShipment->pickup_date_key)) + 1;
    }

    private function analyzeContainerUtilizationByRoute(Collection $shipments): array
    {
        $containerData = $shipments->groupBy('container_id')->map(function($containerShipments) {
            $totalWeight = $containerShipments->sum('weight_lbs');
            $totalVolume = $containerShipments->sum('dimensions_cubic_feet');
            
            return [
                'shipments' => $containerShipments->count(),
                'weight_utilization' => ($totalWeight / 50000) * 100,
                'volume_utilization' => ($totalVolume / 1160) * 100
            ];
        });
        
        $utilizations = $containerData->pluck('weight_utilization')->toArray();
        $averageUtilization = count($utilizations) > 0 ? array_sum($utilizations) / count($utilizations) : 0;
        
        return [
            'containers' => $containerData->toArray(),
            'average_utilization' => $averageUtilization,
            'underutilized_count' => count(array_filter($utilizations, fn($u) => $u < 60)),
            'overutilized_count' => count(array_filter($utilizations, fn($u) => $u > 90))
        ];
    }

    private function analyzeLoadBalancing(Collection $shipments): array
    {
        return [
            'recommendation' => 'Redistribute loads to achieve 75-85% utilization across containers',
            'priority' => 'high',
            'estimated_savings' => '15-20% container costs'
        ];
    }

    private function analyzeRoutingOptimization(Collection $shipments): array
    {
        return [
            'recommendation' => 'Optimize routing to minimize empty miles and maximize container utilization',
            'priority' => 'medium',
            'estimated_savings' => '10-12% operational costs'
        ];
    }

    private function getCapacityPlanningSuggestions(Collection $shipments): array
    {
        $peakDemand = $this->identifyPeakDemand($shipments);
        
        return [
            'current_peak_utilization' => $peakDemand['peak_utilization'],
            'recommend_additional_containers' => $peakDemand['additional_needed'],
            'optimal_fleet_size' => $peakDemand['optimal_fleet']
        ];
    }

    private function identifyPeakDemand(Collection $shipments): array
    {
        $weeklyUtilization = [];
        $grouped = $shipments->groupBy(function($shipment) {
            return date('Y-W', strtotime($shipment->actual_delivery_time));
        });
        
        foreach ($grouped as $week => $weekShipments) {
            $totalWeight = $weekShipments->sum('weight_lbs');
            $utilization = ($totalWeight / 50000) * 100;
            $weeklyUtilization[] = $utilization;
        }
        
        $peakUtilization = !empty($weeklyUtilization) ? max($weeklyUtilization) : 0;
        $additionalNeeded = $peakUtilization > 80 ? ceil(($peakUtilization - 80) / 20) : 0;
        
        return [
            'peak_utilization' => round($peakUtilization, 2),
            'additional_needed' => $additionalNeeded,
            'optimal_fleet' => $shipments->unique('container_id')->count() + $additionalNeeded
        ];
    }

    private function estimateOptimizationSavings(array $containerUtilization): array
    {
        $currentUnderutilized = $containerUtilization['underutilized_count'];
        $totalContainers = count($containerUtilization['containers']);
        
        return [
            'container_cost_reduction' => round(($currentUnderutilized / max($totalContainers, 1)) * 100, 2),
            'estimated_monthly_savings' => $currentUnderutilized * 500, // $500 per underutilized container
            'implementation_cost' => $currentUnderutilized * 100, // $100 per optimization
            'net_monthly_benefit' => ($currentUnderutilized * 500) - ($currentUnderutilized * 100)
        ];
    }

    private function createImplementationRoadmap(array $containerUtilization): array
    {
        return [
            'phase_1' => [
                'duration' => '1-2 weeks',
                'actions' => ['Identify underutilized containers', 'Redistribute loads'],
                'expected_improvement' => '10-15%'
            ],
            'phase_2' => [
                'duration' => '2-4 weeks',
                'actions' => ['Implement load optimization', 'Route optimization'],
                'expected_improvement' => '5-10%'
            ],
            'phase_3' => [
                'duration' => '1-2 months',
                'actions' => ['Capacity planning', 'Fleet optimization'],
                'expected_improvement' => '5-8%'
            ]
        ];
    }

    private function analyzeDemandPatterns(Collection $shipments): array
    {
        $byDayOfWeek = $shipments->groupBy(function($shipment) {
            return date('N', strtotime($shipment->actual_delivery_time));
        })->map->count();
        
        $byHour = $shipments->groupBy(function($shipment) {
            return date('H', strtotime($shipment->actual_delivery_time));
        })->map->count();
        
        return [
            'peak_days' => $byDayOfWeek->sortDesc()->take(3)->keys()->toArray(),
            'peak_hours' => $byHour->sortDesc()->take(3)->keys()->toArray(),
            'demand_variance' => $this->calculateDemandVariance($shipments)
        ];
    }

    private function analyzeContainerSupply(Collection $shipments): array
    {
        $uniqueContainers = $shipments->unique('container_id')->count();
        $totalShipments = $shipments->count();
        
        return [
            'available_containers' => $uniqueContainers,
            'containers_per_shipment' => round($totalShipments / max($uniqueContainers, 1), 2),
            'supply_utilization' => round(($totalShipments / ($uniqueContainers * 10)) * 100, 2) // Assuming 10 shipments per container capacity
        ];
    }

    private function identifyCapacityBottlenecks(Collection $shipments): array
    {
        $containerUtilization = $this->analyzeContainerUtilizationByRoute($shipments);
        $bottlenecks = [];
        
        foreach ($containerUtilization['containers'] as $containerId => $data) {
            if ($data['weight_utilization'] > 90) {
                $bottlenecks[] = [
                    'container_id' => $containerId,
                    'type' => 'capacity_constraint',
                    'utilization' => $data['weight_utilization'],
                    'impact' => 'high'
                ];
            }
        }
        
        return $bottlenecks;
    }

    private function forecastContainerDemand(Collection $shipments, array $dateRange): array
    {
        // Simple linear trend forecast
        $historicalDemand = $shipments->groupBy(function($shipment) {
            return date('Y-W', strtotime($shipment->actual_delivery_time));
        })->map->count();
        
        if ($historicalDemand->count() < 2) {
            return ['method' => 'insufficient_data'];
        }
        
        $demandValues = $historicalDemand->values()->toArray();
        $trend = $this->calculateLinearTrend(range(1, count($demandValues)), $demandValues);
        $lastValue = end($demandValues);
        $nextWeek = count($demandValues) + 1;
        $forecast = $lastValue + ($trend * ($nextWeek - count($demandValues)));
        
        return [
            'method' => 'linear_trend',
            'forecasted_demand' => round(max(0, $forecast), 0),
            'confidence' => $historicalDemand->count() >= 4 ? 'medium' : 'low'
        ];
    }

    private function calculateLinearTrend(array $x, array $y): float
    {
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($a, $b) => $a * $b, $x, $y));
        $sumXX = array_sum(array_map(fn($a) => $a * $a, $x));
        
        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
    }

    private function getImmediateCapacityActions(array $capacityAnalysis): array
    {
        return [
            'Redistribute underutilized containers to high-demand routes',
            'Implement dynamic container allocation system',
            'Monitor real-time utilization rates'
        ];
    }

    private function getMediumTermCapacityActions(array $capacityAnalysis): array
    {
        return [
            'Optimize fleet composition based on demand patterns',
            'Implement predictive analytics for capacity planning',
            'Develop container sharing agreements with partners'
        ];
    }

    private function getLongTermCapacityStrategy(array $capacityAnalysis): array
    {
        return [
            'Invest in modular container systems',
            'Develop automated container management systems',
            'Create strategic partnerships for capacity flexibility'
        ];
    }

    private function calculateRevenuePerMile(float $totalRevenue, Collection $shipments): float
    {
        $totalMiles = $shipments->sum('distance_miles');
        return $totalMiles > 0 ? round($totalRevenue / $totalMiles, 2) : 0;
    }

    private function calculateCostPerMile(float $totalCosts, Collection $shipments): float
    {
        $totalMiles = $shipments->sum('distance_miles');
        return $totalMiles > 0 ? round($totalCosts / $totalMiles, 2) : 0;
    }

    private function getUtilizationEfficiencyScore(Collection $shipments): float
    {
        $weightUtilizations = [];
        $grouped = $shipments->groupBy('container_id');
        
        foreach ($grouped as $containerShipments) {
            $totalWeight = $containerShipments->sum('weight_lbs');
            $weightUtilizations[] = ($totalWeight / 50000) * 100;
        }
        
        $averageUtilization = count($weightUtilizations) > 0 ? array_sum($weightUtilizations) / count($weightUtilizations) : 0;
        
        // Score based on how close to optimal range (75-85%)
        if ($averageUtilization >= 75 && $averageUtilization <= 85) {
            return 100;
        } elseif ($averageUtilization < 75) {
            return $averageUtilization; // Linear scale
        } else {
            return max(50, 100 - (($averageUtilization - 85) * 5)); // Penalty for overutilization
        }
    }

    private function benchmarkContainerEfficiency(string $containerId, Collection $shipments): array
    {
        // This would compare with industry benchmarks
        return [
            'industry_benchmark' => 75.0,
            'container_performance' => $this->getUtilizationEfficiencyScore($shipments),
            'performance_rating' => 'above_average' // or below_average, etc.
        ];
    }

    private function estimateCostOptimizationPotential(Collection $shipments, array $containerCosts): array
    {
        $currentCost = $containerCosts['total_estimated_cost'];
        $optimizationPotential = $currentCost * 0.15; // 15% potential savings
        
        return [
            'current_monthly_cost' => round($currentCost, 2),
            'optimization_potential' => round($optimizationPotential, 2),
            'potential_savings_percentage' => 15,
            'implementation_cost' => round($currentCost * 0.05, 2),
            'net_benefit' => round($optimizationPotential - ($currentCost * 0.05), 2)
        ];
    }

    private function analyzeLoadPatterns(Collection $shipments): array
    {
        $weightDistribution = $this->analyzeWeightDistribution($shipments);
        $volumeDistribution = $this->analyzeVolumeDistribution($shipments);
        
        return [
            'weight_distribution' => $weightDistribution,
            'volume_distribution' => $volumeDistribution,
            'load_consistency' => $this->calculateLoadConsistency($shipments)
        ];
    }

    private function analyzeWeightDistribution(Collection $shipments): array
    {
        $weights = $shipments->pluck('weight_lbs')->toArray();
        $avgWeight = count($weights) > 0 ? array_sum($weights) / count($weights) : 0;
        $maxWeight = max($weights);
        $minWeight = min($weights);
        
        return [
            'average' => round($avgWeight, 2),
            'maximum' => round($maxWeight, 2),
            'minimum' => round($minWeight, 2),
            'variance' => round($this->calculateVariance($weights), 2)
        ];
    }

    private function analyzeVolumeDistribution(Collection $shipments): array
    {
        $volumes = $shipments->pluck('dimensions_cubic_feet')->toArray();
        $avgVolume = count($volumes) > 0 ? array_sum($volumes) / count($volumes) : 0;
        
        return [
            'average' => round($avgVolume, 2),
            'maximum' => round(max($volumes), 2),
            'minimum' => round(min($volumes), 2)
        ];
    }

    private function analyzeStackingEfficiency(Collection $shipments): array
    {
        $totalStops = $shipments->sum('stops_count');
        $totalShipments = $shipments->count();
        $avgStopsPerShipment = $totalShipments > 0 ? $totalStops / $totalShipments : 0;
        
        return [
            'avg_stops_per_shipment' => round($avgStopsPerShipment, 2),
            'stacking_efficiency_rating' => $avgStopsPerShipment < 2 ? 'excellent' : ($avgStopsPerShipment < 3 ? 'good' : 'needs_improvement')
        ];
    }

    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / (count($values) - 1);
        
        return $variance;
    }

    private function calculateLoadConsistency(Collection $shipments): string
    {
        $weights = $shipments->pluck('weight_lbs')->toArray();
        $variance = $this->calculateVariance($weights);
        $cv = sqrt($variance) / (array_sum($weights) / count($weights)); // Coefficient of variation
        
        return $cv < 0.3 ? 'high' : ($cv < 0.5 ? 'medium' : 'low');
    }

    private function getWeightOptimizationSuggestions(array $weightDistribution): array
    {
        $suggestions = [];
        
        if ($weightDistribution['variance'] > 1000) {
            $suggestions[] = 'Standardize load sizes to improve consistency';
        }
        
        if ($weightDistribution['maximum'] / $weightDistribution['average'] > 2) {
            $suggestions[] = 'Consider splitting very heavy loads across multiple containers';
        }
        
        return $suggestions;
    }

    private function getStackingImprovements(array $stackingEfficiency): array
    {
        $suggestions = [];
        
        if ($stackingEfficiency['stacking_efficiency_rating'] === 'needs_improvement') {
            $suggestions[] = 'Optimize loading sequence to reduce stops per shipment';
            $suggestions[] = 'Implement zone-based loading for faster access';
        }
        
        return $suggestions;
    }

    private function getRoutingEfficiencySuggestions(Collection $shipments): array
    {
        return [
            'Implement dynamic routing based on real-time traffic',
            'Group deliveries by geographic proximity',
            'Optimize stop sequence for minimum travel time'
        ];
    }

    private function calculateDemandVariance(Collection $shipments): string
    {
        $dailyCounts = [];
        $grouped = $shipments->groupBy(function($shipment) {
            return date('Y-m-d', strtotime($shipment->actual_delivery_time));
        });
        
        foreach ($grouped as $date => $dayShipments) {
            $dailyCounts[] = $dayShipments->count();
        }
        
        $cv = sqrt($this->calculateVariance($dailyCounts)) / (array_sum($dailyCounts) / count($dailyCounts));
        
        return $cv < 0.3 ? 'low' : ($cv < 0.5 ? 'medium' : 'high');
    }
}