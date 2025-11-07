<?php

namespace App\Services\OperationalReporting;

use App\Models\ETL\FactShipment;
use App\Models\ETL\FactPerformanceMetrics;
use App\Models\ETL\DimensionRoute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RouteEfficiencyService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const EFFICIENCY_WEIGHTS = [
        'on_time_rate' => 0.3,
        'cost_efficiency' => 0.25,
        'route_utilization' => 0.2,
        'distance_optimization' => 0.15,
        'service_quality' => 0.1
    ];

    /**
     * Calculate route efficiency score
     */
    public function calculateEfficiencyScore(string $routeKey, array $dateRange): array
    {
        $cacheKey = "route_efficiency_{$routeKey}_{$dateRange['start']}_{$dateRange['end']}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($routeKey, $dateRange) {
            $shipments = FactShipment::where('route_key', $routeKey)
                ->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']])
                ->get();

            if ($shipments->isEmpty()) {
                return [
                    'route_key' => $routeKey,
                    'efficiency_score' => 0,
                    'components' => [],
                    'benchmark_comparison' => 'insufficient_data'
                ];
            }

            $route = DimensionRoute::find($routeKey);
            
            // Calculate component scores
            $onTimeRate = $this->calculateOnTimeRate($shipments);
            $costEfficiency = $this->calculateCostEfficiency($shipments, $route);
            $routeUtilization = $this->calculateRouteUtilization($shipments, $route);
            $distanceOptimization = $this->calculateDistanceOptimization($shipments, $route);
            $serviceQuality = $this->calculateServiceQuality($shipments);

            // Calculate weighted efficiency score
            $efficiencyScore = (
                $onTimeRate * self::EFFICIENCY_WEIGHTS['on_time_rate'] +
                $costEfficiency * self::EFFICIENCY_WEIGHTS['cost_efficiency'] +
                $routeUtilization * self::EFFICIENCY_WEIGHTS['route_utilization'] +
                $distanceOptimization * self::EFFICIENCY_WEIGHTS['distance_optimization'] +
                $serviceQuality * self::EFFICIENCY_WEIGHTS['service_quality']
            );

            return [
                'route_key' => $routeKey,
                'route_name' => $route?->route_name ?? 'Unknown',
                'efficiency_score' => round($efficiencyScore, 4),
                'grade' => $this->getEfficiencyGrade($efficiencyScore),
                'components' => [
                    'on_time_rate' => round($onTimeRate, 4),
                    'cost_efficiency' => round($costEfficiency, 4),
                    'route_utilization' => round($routeUtilization, 4),
                    'distance_optimization' => round($distanceOptimization, 4),
                    'service_quality' => round($serviceQuality, 4)
                ],
                'metrics' => [
                    'total_shipments' => $shipments->count(),
                    'on_time_shipments' => $shipments->where('on_time_indicator', true)->count(),
                    'total_revenue' => round($shipments->sum('revenue'), 2),
                    'total_cost' => round($shipments->sum('total_cost'), 2),
                    'avg_transit_time' => round($shipments->avg('transit_time_hours'), 2)
                ],
                'benchmark_comparison' => $this->compareWithBenchmark($efficiencyScore),
                'last_calculated' => now()->toISOString()
            ];
        });
    }

    /**
     * Identify bottlenecks on routes
     */
    public function identifyBottlenecks(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('bottlenecks', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = FactShipment::query()
                ->select(
                    'route_key',
                    DB::raw('AVG(transit_time_hours) as avg_transit_time'),
                    DB::raw('STDDEV(transit_time_hours) as transit_time_variance'),
                    DB::raw('COUNT(*) as total_shipments'),
                    DB::raw('SUM(CASE WHEN on_time_indicator = false THEN 1 ELSE 0 END) as delayed_shipments'),
                    DB::raw('AVG(route_efficiency_score) as avg_efficiency_score')
                )
                ->whereNotNull('route_key')
                ->groupBy('route_key')
                ->having('total_shipments', '>', 10); // Minimum sample size

            $this->applyFilters($query, $filters);

            $routes = $query->get();

            $bottlenecks = [];
            foreach ($routes as $route) {
                $issues = [];
                
                // Check for transit time issues
                if ($route->avg_transit_time > 24) { // More than 24 hours
                    $issues[] = 'excessive_transit_time';
                }
                
                // Check for high variance in transit times
                if ($route->transit_time_variance > $route->avg_transit_time * 0.3) {
                    $issues[] = 'inconsistent_transit_times';
                }
                
                // Check for high delay rate
                $delayRate = ($route->delayed_shipments / $route->total_shipments) * 100;
                if ($delayRate > 20) {
                    $issues[] = 'high_delay_rate';
                }
                
                // Check for low efficiency score
                if ($route->avg_efficiency_score < 70) {
                    $issues[] = 'low_efficiency';
                }

                if (!empty($issues)) {
                    $dimension = DimensionRoute::find($route->route_key);
                    
                    $bottlenecks[] = [
                        'route_key' => $route->route_key,
                        'route_name' => $dimension?->route_name ?? 'Unknown',
                        'severity' => $this->calculateBottleneckSeverity($issues, $delayRate),
                        'issues' => $issues,
                        'metrics' => [
                            'avg_transit_time' => round($route->avg_transit_time, 2),
                            'transit_time_variance' => round($route->transit_time_variance, 2),
                            'delay_rate' => round($delayRate, 2),
                            'efficiency_score' => round($route->avg_efficiency_score, 4)
                        ],
                        'recommendations' => $this->generateOptimizationRecommendations($issues)
                    ];
                }
            }

            // Sort by severity
            usort($bottlenecks, function ($a, $b) {
                return $b['severity'] - $a['severity'];
            });

            return [
                'bottlenecks' => $bottlenecks,
                'total_routes_analyzed' => count($routes),
                'bottleneck_routes_count' => count($bottlenecks),
                'analysis_date' => now()->toISOString()
            ];
        });
    }

    /**
     * Get performance comparison across routes
     */
    public function getPerformanceComparison(array $routeKeys, array $dateRange): array
    {
        $cacheKey = "performance_comparison_" . md5(serialize($routeKeys) . serialize($dateRange));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($routeKeys, $dateRange) {
            $comparison = [];
            
            foreach ($routeKeys as $routeKey) {
                $efficiencyData = $this->calculateEfficiencyScore($routeKey, $dateRange);
                $comparison[] = $efficiencyData;
            }

            // Sort by efficiency score
            usort($comparison, function ($a, $b) {
                return $b['efficiency_score'] - $a['efficiency_score'];
            });

            // Calculate rankings
            foreach ($comparison as $index => &$item) {
                $item['rank'] = $index + 1;
                $item['percentile'] = round((($index + 1) / count($comparison)) * 100, 2);
            }

            return [
                'date_range' => $dateRange,
                'routes_comparison' => $comparison,
                'summary' => [
                    'best_performer' => $comparison[0] ?? null,
                    'worst_performer' => end($comparison) ?? null,
                    'average_score' => count($comparison) > 0 ? round(array_sum(array_column($comparison, 'efficiency_score')) / count($comparison), 4) : 0,
                    'score_range' => [
                        'highest' => count($comparison) > 0 ? max(array_column($comparison, 'efficiency_score')) : 0,
                        'lowest' => count($comparison) > 0 ? min(array_column($comparison, 'efficiency_score')) : 0
                    ]
                ]
            ];
        });
    }

    /**
     * Generate optimization recommendations for routes
     */
    public function generateOptimizationRecommendations(string $routeKey): array
    {
        $efficiencyData = $this->calculateEfficiencyScore($routeKey, [
            'start' => now()->subDays(30)->format('Ymd'),
            'end' => now()->format('Ymd')
        ]);

        $recommendations = [];
        $components = $efficiencyData['components'];

        // On-time rate recommendations
        if ($components['on_time_rate'] < 80) {
            $recommendations[] = [
                'area' => 'on_time_performance',
                'priority' => 'high',
                'suggestion' => 'Review scheduling and route planning processes',
                'expected_impact' => 'Improved customer satisfaction and reduced penalties'
            ];
        }

        // Cost efficiency recommendations
        if ($components['cost_efficiency'] < 70) {
            $recommendations[] = [
                'area' => 'cost_optimization',
                'priority' => 'medium',
                'suggestion' => 'Optimize fuel consumption and reduce empty miles',
                'expected_impact' => 'Reduced operational costs and improved margins'
            ];
        }

        // Route utilization recommendations
        if ($components['route_utilization'] < 60) {
            $recommendations[] = [
                'area' => 'capacity_utilization',
                'priority' => 'high',
                'suggestion' => 'Implement dynamic load balancing and consolidation',
                'expected_impact' => 'Increased revenue per route and reduced unit costs'
            ];
        }

        // Distance optimization recommendations
        if ($components['distance_optimization'] < 75) {
            $recommendations[] = [
                'area' => 'route_optimization',
                'priority' => 'medium',
                'suggestion' => 'Use advanced routing algorithms to minimize distance',
                'expected_impact' => 'Reduced fuel costs and improved delivery times'
            ];
        }

        return $recommendations;
    }

    // Private helper methods
    private function calculateOnTimeRate(Collection $shipments): float
    {
        $onTimeShipments = $shipments->where('on_time_indicator', true)->count();
        return $shipments->count() > 0 ? ($onTimeShipments / $shipments->count()) * 100 : 0;
    }

    private function calculateCostEfficiency(Collection $shipments, ?DimensionRoute $route): float
    {
        if ($route && $shipments->isNotEmpty()) {
            $expectedCost = $route->distance_miles * 0.50; // $0.50 per mile baseline
            $actualCost = $shipments->avg('total_cost');
            $efficiency = $expectedCost > 0 ? (1 - ($actualCost - $expectedCost) / $expectedCost) * 100 : 0;
            return max(0, $efficiency);
        }
        return 0;
    }

    private function calculateRouteUtilization(Collection $shipments, ?DimensionRoute $route): float
    {
        if ($route && $shipments->isNotEmpty()) {
            $maxCapacity = 100; // Assuming 100 units max capacity
            $avgUtilization = ($shipments->sum('weight_lbs') / $maxCapacity) / $shipments->count() * 100;
            return min(100, $avgUtilization);
        }
        return 0;
    }

    private function calculateDistanceOptimization(Collection $shipments, ?DimensionRoute $route): float
    {
        if ($route && $shipments->isNotEmpty()) {
            $expectedDistance = $route->distance_miles;
            $actualDistance = $shipments->avg('distance_miles');
            $efficiency = $expectedDistance > 0 ? (1 - abs($actualDistance - $expectedDistance) / $expectedDistance) * 100 : 0;
            return max(0, $efficiency);
        }
        return 0;
    }

    private function calculateServiceQuality(Collection $shipments): float
    {
        if ($shipments->isEmpty()) return 0;
        
        $exceptions = $shipments->where('exception_flag', true)->count();
        $exceptionRate = ($exceptions / $shipments->count()) * 100;
        
        return max(0, 100 - $exceptionRate);
    }

    private function getEfficiencyGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        return 'D';
    }

    private function compareWithBenchmark(float $score): string
    {
        // Assuming 75 is the industry benchmark
        $benchmark = 75;
        
        if ($score >= $benchmark + 15) return 'well_above_benchmark';
        if ($score >= $benchmark + 5) return 'above_benchmark';
        if ($score >= $benchmark - 5) return 'at_benchmark';
        if ($score >= $benchmark - 15) return 'below_benchmark';
        return 'well_below_benchmark';
    }

    private function calculateBottleneckSeverity(array $issues, float $delayRate): int
    {
        $severity = 0;
        
        foreach ($issues as $issue) {
            $severity += match($issue) {
                'excessive_transit_time' => 3,
                'high_delay_rate' => 4,
                'low_efficiency' => 2,
                'inconsistent_transit_times' => 1,
                default => 1
            };
        }
        
        if ($delayRate > 50) $severity += 2;
        elseif ($delayRate > 30) $severity += 1;
        
        return min(10, $severity);
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['date_range'])) {
            $query->whereBetween('delivery_date_key', [$filters['date_range']['start'], $filters['date_range']['end']]);
        }
        
        if (isset($filters['client_key'])) {
            $query->where('client_key', $filters['client_key']);
        }
    }

    private function generateCacheKey(string $type, array $params): string
    {
        ksort($params);
        return "route_efficiency_{$type}_" . md5(serialize($params));
    }
}