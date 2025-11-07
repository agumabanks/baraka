<?php

namespace App\Services\OperationalReporting;

use App\Models\ETL\FactShipment;
use App\Models\ETL\FactPerformanceMetrics;
use App\Models\ETL\DimensionRoute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TransitTimeService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const TRANSIT_TIME_BENCHMARKS = [
        'local_delivery' => 4,      // 4 hours
        'regional' => 24,           // 24 hours
        'interstate' => 48,         // 48 hours
        'long_distance' => 72,      // 72 hours
        'express' => 2              // 2 hours
    ];

    /**
     * Calculate average transit time by route/carrier
     */
    public function calculateAverageTransitTime(string $entityKey, string $type = 'route', array $dateRange = []): array
    {
        $start = $dateRange['start'] ?? 'current';
        $end = $dateRange['end'] ?? 'current';
        $cacheKey = "avg_transit_time_{$type}_{$entityKey}_{$start}_{$end}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($entityKey, $type, $dateRange) {
            $query = FactShipment::whereNotNull('transit_time_hours')
                ->where('transit_time_hours', '>', 0);

            // Apply entity filter based on type
            $field = match($type) {
                'route' => 'route_key',
                'carrier' => 'carrier_key',
                'origin' => 'origin_branch_key',
                'destination' => 'destination_branch_key',
                'driver' => 'driver_key',
                default => 'route_key'
            };

            $query->where($field, $entityKey);

            // Apply date range filter
            if (!empty($dateRange)) {
                $query->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']]);
            }

            $shipments = $query->get();

            if ($shipments->isEmpty()) {
                return [
                    'entity_key' => $entityKey,
                    'type' => $type,
                    'transit_analysis' => null,
                    'message' => 'No shipment data available for the specified criteria'
                ];
            }

            $entityName = $this->getEntityName($entityKey, $type);
            $transitStatistics = $this->calculateTransitStatistics($shipments);
            $performanceMetrics = $this->calculatePerformanceMetrics($shipments);
            $benchmarking = $this->performBenchmarking($shipments, $type);
            
            return [
                'entity_key' => $entityKey,
                'type' => $type,
                'entity_name' => $entityName,
                'transit_analysis' => [
                    'statistics' => $transitStatistics,
                    'performance_metrics' => $performanceMetrics,
                    'benchmarking' => $benchmarking
                ],
                'trends' => $this->analyzeTransitTrends($shipments, $type),
                'optimization_opportunities' => $this->getTransitOptimizationOpportunities($transitStatistics),
                'last_updated' => now()->toISOString()
            ];
        });
    }

    /**
     * Identify transit bottlenecks
     */
    public function identifyTransitBottlenecks(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('transit_bottlenecks', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = FactShipment::whereNotNull('transit_time_hours')
                ->where('transit_time_hours', '>', 0)
                ->select(
                    'route_key',
                    'origin_branch_key',
                    'destination_branch_key',
                    DB::raw('COUNT(*) as total_shipments'),
                    DB::raw('AVG(transit_time_hours) as avg_transit_time'),
                    DB::raw('STDDEV(transit_time_hours) as transit_variance'),
                    DB::raw('MAX(transit_time_hours) as max_transit_time'),
                    DB::raw('MIN(transit_time_hours) as min_transit_time'),
                    DB::raw('SUM(CASE WHEN transit_time_hours > 48 THEN 1 ELSE 0 END) as delayed_shipments'),
                    DB::raw('AVG(route_efficiency_score) as avg_efficiency_score')
                )
                ->groupBy('route_key', 'origin_branch_key', 'destination_branch_key')
                ->having('total_shipments', '>=', 5); // Minimum sample size

            $this->applyFilters($query, $filters);

            $routes = $query->get();

            $bottlenecks = [];
            foreach ($routes as $route) {
                $issues = $this->identifyRouteIssues($route);
                
                if (!empty($issues)) {
                    $routeInfo = $this->getRouteInformation($route->route_key);
                    $impactAssessment = $this->assessBottleneckImpact($route);
                    
                    $bottlenecks[] = [
                        'route_key' => $route->route_key,
                        'route_info' => $routeInfo,
                        'severity_score' => $this->calculateBottleneckSeverity($issues, $route),
                        'identified_issues' => $issues,
                        'performance_metrics' => [
                            'avg_transit_time' => round($route->avg_transit_time, 2),
                            'transit_variance' => round($route->transit_variance, 2),
                            'delay_rate' => round(($route->delayed_shipments / $route->total_shipments) * 100, 2),
                            'efficiency_score' => round($route->avg_efficiency_score, 2)
                        ],
                        'impact_assessment' => $impactAssessment,
                        'root_causes' => $this->identifyRootCauses($route, $issues),
                        'recommendations' => $this->generateBottleneckRecommendations($issues, $routeInfo)
                    ];
                }
            }

            // Sort by severity
            usort($bottlenecks, function ($a, $b) {
                return $b['severity_score'] - $a['severity_score'];
            });

            return [
                'analysis_summary' => [
                    'total_routes_analyzed' => count($routes),
                    'bottleneck_routes' => count($bottlenecks),
                    'critical_bottlenecks' => count(array_filter($bottlenecks, fn($b) => $b['severity_score'] >= 8)),
                    'analysis_date' => now()->toISOString()
                ],
                'bottleneck_analysis' => $bottlenecks,
                'system_wide_metrics' => $this->calculateSystemWideMetrics($routes),
                'prioritized_actions' => $this->prioritizeBottleneckActions($bottlenecks)
            ];
        });
    }

    /**
     * Perform transit time variance analysis
     */
    public function performVarianceAnalysis(string $entityKey, string $type = 'route', array $dateRange = []): array
    {
        $cacheKey = "variance_analysis_{$type}_{$entityKey}_{$dateRange['start'] ?? 'current'}_{$dateRange['end'] ?? 'current'}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($entityKey, $type, $dateRange) {
            $field = match($type) {
                'route' => 'route_key',
                'carrier' => 'carrier_key',
                'origin' => 'origin_branch_key',
                'destination' => 'destination_branch_key',
                default => 'route_key'
            };

            $query = FactShipment::whereNotNull('transit_time_hours')
                ->where($field, $entityKey)
                ->select(
                    'delivery_date_key',
                    'transit_time_hours',
                    'distance_miles',
                    'stops_count',
                    'shipment_status',
                    'on_time_indicator'
                );

            if (!empty($dateRange)) {
                $query->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']]);
            }

            $shipments = $query->orderBy('delivery_date_key')->get();

            if ($shipments->isEmpty()) {
                return [
                    'entity_key' => $entityKey,
                    'type' => $type,
                    'variance_analysis' => null,
                    'message' => 'No data available for variance analysis'
                ];
            }

            $entityName = $this->getEntityName($entityKey, $type);
            $varianceStatistics = $this->calculateVarianceStatistics($shipments);
            $temporalAnalysis = $this->analyzeTemporalVariance($shipments);
            $factorAnalysis = $this->analyzeVarianceFactors($shipments);
            
            return [
                'entity_key' => $entityKey,
                'type' => $type,
                'entity_name' => $entityName,
                'variance_analysis' => [
                    'statistical_summary' => $varianceStatistics,
                    'temporal_patterns' => $temporalAnalysis,
                    'factor_impact' => $factorAnalysis
                ],
                'variance_sources' => $this->identifyVarianceSources($shipments),
                'predictive_insights' => $this->generateVariancePredictions($shipments),
                'optimization_strategies' => $this->getVarianceReductionStrategies($varianceStatistics)
            ];
        });
    }

    /**
     * Benchmark performance across carriers/routes
     */
    public function benchmarkPerformance(array $entityKeys, string $type = 'route', array $dateRange = []): array
    {
        $cacheKey = "benchmarking_" . md5(serialize($entityKeys) . $type . serialize($dateRange));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($entityKeys, $type, $dateRange) {
            $benchmarkData = [];
            
            foreach ($entityKeys as $key) {
                $analysis = $this->calculateAverageTransitTime($key, $type, $dateRange);
                if ($analysis['transit_analysis']) {
                    $benchmarkData[] = [
                        'entity_key' => $key,
                        'entity_name' => $analysis['entity_name'],
                        'avg_transit_time' => $analysis['transit_analysis']['statistics']['average'],
                        'median_transit_time' => $analysis['transit_analysis']['statistics']['median'],
                        'transit_variance' => $analysis['transit_analysis']['statistics']['standard_deviation'],
                        'efficiency_score' => $analysis['transit_analysis']['performance_metrics']['efficiency_score'],
                        'on_time_rate' => $analysis['transit_analysis']['performance_metrics']['on_time_rate']
                    ];
                }
            }

            if (empty($benchmarkData)) {
                return [
                    'benchmark_type' => $type,
                    'entities' => [],
                    'message' => 'No benchmark data available'
                ];
            }

            // Sort by average transit time
            usort($benchmarkData, function ($a, $b) {
                return $a['avg_transit_time'] - $b['avg_transit_time'];
            });

            // Add rankings
            foreach ($benchmarkData as $index => &$entity) {
                $entity['rank'] = $index + 1;
                $entity['percentile'] = round((($index + 1) / count($benchmarkData)) * 100, 2);
            }

            $summary = $this->calculateBenchmarkSummary($benchmarkData);
            
            return [
                'benchmark_type' => $type,
                'date_range' => $dateRange,
                'benchmark_results' => $benchmarkData,
                'summary' => $summary,
                'best_practices' => $this->identifyBestPractices($benchmarkData),
                'improvement_opportunities' => $this->identifyImprovementOpportunities($benchmarkData)
            ];
        });
    }

    /**
     * Identify improvement opportunities
     */
    public function identifyImprovementOpportunities(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('improvement_opportunities', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $bottlenecks = $this->identifyTransitBottlenecks($filters);
            $underperformingRoutes = $this->identifyUnderperformingRoutes($filters);
            $optimizationTargets = $this->identifyOptimizationTargets($filters);
            
            return [
                'opportunity_summary' => [
                    'total_bottlenecks_identified' => count($bottlenecks['bottleneck_analysis']),
                    'underperforming_routes' => count($underperformingRoutes),
                    'high_impact_opportunities' => count(array_filter($optimizationTargets, fn($t) => $t['impact_level'] === 'high'))
                ],
                'bottleneck_improvements' => $this->generateBottleneckImprovements($bottlenecks['bottleneck_analysis']),
                'route_optimizations' => $underperformingRoutes,
                'system_optimizations' => $optimizationTargets,
                'implementation_roadmap' => $this->createImprovementRoadmap($bottlenecks, $underperformingRoutes),
                'expected_benefits' => $this->estimateImprovementBenefits($bottlenecks, $underperformingRoutes)
            ];
        });
    }

    // Private helper methods
    private function getEntityName(string $entityKey, string $type): string
    {
        return match($type) {
            'route' => DimensionRoute::find($entityKey)?->route_name ?? "Route {$entityKey}",
            'carrier' => "Carrier {$entityKey}",
            'origin' => "Origin Branch {$entityKey}",
            'destination' => "Destination Branch {$entityKey}",
            'driver' => "Driver {$entityKey}",
            default => "Entity {$entityKey}"
        };
    }

    private function calculateTransitStatistics(Collection $shipments): array
    {
        $transitTimes = $shipments->pluck('transit_time_hours')->toArray();
        $distances = $shipments->pluck('distance_miles')->toArray();
        
        return [
            'count' => count($transitTimes),
            'average' => round(array_sum($transitTimes) / count($transitTimes), 2),
            'median' => $this->calculateMedian($transitTimes),
            'mode' => $this->calculateMode($transitTimes),
            'minimum' => min($transitTimes),
            'maximum' => max($transitTimes),
            'range' => max($transitTimes) - min($transitTimes),
            'standard_deviation' => round($this->calculateStandardDeviation($transitTimes), 2),
            'variance' => round($this->calculateVariance($transitTimes), 2),
            'coefficient_of_variation' => $this->calculateCoefficientOfVariation($transitTimes),
            'percentiles' => [
                '25th' => $this->calculatePercentile($transitTimes, 25),
                '50th' => $this->calculatePercentile($transitTimes, 50),
                '75th' => $this->calculatePercentile($transitTimes, 75),
                '90th' => $this->calculatePercentile($transitTimes, 90),
                '95th' => $this->calculatePercentile($transitTimes, 95)
            ],
            'speed_analysis' => [
                'average_speed_mph' => round($this->calculateAverageSpeed($transitTimes, $distances), 2),
                'minimum_speed_mph' => $this->calculateMinSpeed($transitTimes, $distances),
                'maximum_speed_mph' => $this->calculateMaxSpeed($transitTimes, $distances)
            ]
        ];
    }

    private function calculatePerformanceMetrics(Collection $shipments): array
    {
        $onTimeShipments = $shipments->where('on_time_indicator', true)->count();
        $totalShipments = $shipments->count();
        $delayedShipments = $shipments->where('transit_time_hours', '>', 24)->count();
        
        return [
            'on_time_rate' => round(($onTimeShipments / $totalShipments) * 100, 2),
            'delay_rate' => round(($delayedShipments / $totalShipments) * 100, 2),
            'efficiency_score' => round($shipments->avg('route_efficiency_score') ?? 0, 2),
            'reliability_score' => $this->calculateReliabilityScore($shipments),
            'consistency_score' => $this->calculateConsistencyScore($shipments)
        ];
    }

    private function performBenchmarking(Collection $shipments, string $type): array
    {
        $avgTransitTime = $shipments->avg('transit_time_hours');
        
        $benchmark = match($type) {
            'route' => $this->getRouteBenchmark($shipments),
            'carrier' => 48, // 48 hours for carrier
            'origin' => 24,  // 24 hours for origin
            'destination' => 24, // 24 hours for destination
            default => 48
        };
        
        return [
            'industry_benchmark' => $benchmark,
            'performance_vs_benchmark' => round((($benchmark - $avgTransitTime) / $benchmark) * 100, 2),
            'benchmark_status' => $avgTransitTime <= $benchmark ? 'meets_or_exceeds' : 'below_benchmark',
            'performance_rating' => $this->getPerformanceRating($avgTransitTime, $benchmark)
        ];
    }

    private function getRouteBenchmark(Collection $shipments): float
    {
        $avgDistance = $shipments->avg('distance_miles');
        
        if ($avgDistance <= 50) return self::TRANSIT_TIME_BENCHMARKS['local_delivery'];
        if ($avgDistance <= 200) return self::TRANSIT_TIME_BENCHMARKS['regional'];
        if ($avgDistance <= 500) return self::TRANSIT_TIME_BENCHMARKS['interstate'];
        return self::TRANSIT_TIME_BENCHMARKS['long_distance'];
    }

    private function getPerformanceRating(float $actualTime, float $benchmark): string
    {
        $performanceRatio = $actualTime / $benchmark;
        
        if ($performanceRatio <= 0.8) return 'excellent';
        if ($performanceRatio <= 0.9) return 'good';
        if ($performanceRatio <= 1.0) return 'acceptable';
        if ($performanceRatio <= 1.2) return 'needs_improvement';
        return 'poor';
    }

    private function analyzeTransitTrends(Collection $shipments, string $type): array
    {
        $dailyTrends = [];
        $grouped = $shipments->groupBy('delivery_date_key');
        
        foreach ($grouped as $dateKey => $dayShipments) {
            $dailyTrends[] = [
                'date' => $dateKey,
                'avg_transit_time' => round($dayShipments->avg('transit_time_hours'), 2),
                'shipment_count' => $dayShipments->count(),
                'efficiency_score' => round($dayShipments->avg('route_efficiency_score') ?? 0, 2)
            ];
        }
        
        return [
            'daily_trends' => $dailyTrends,
            'trend_analysis' => $this->analyzeTrendDirection($dailyTrends),
            'seasonal_patterns' => $this->identifySeasonalPatterns($dailyTrends),
            'volatility_assessment' => $this->assessTransitVolatility($dailyTrends)
        ];
    }

    private function getTransitOptimizationOpportunities(array $statistics): array
    {
        $opportunities = [];
        
        if ($statistics['coefficient_of_variation'] > 0.3) {
            $opportunities[] = [
                'type' => 'consistency_improvement',
                'description' => 'Improve transit time consistency',
                'potential_impact' => 'Reduced variance by 20-30%',
                'priority' => 'high'
            ];
        }
        
        if ($statistics['average'] > 48) {
            $opportunities[] = [
                'type' => 'speed_optimization',
                'description' => 'Optimize routing and processing',
                'potential_impact' => 'Reduce average transit time by 15-20%',
                'priority' => 'medium'
            ];
        }
        
        if ($statistics['standard_deviation'] > $statistics['average'] * 0.4) {
            $opportunities[] = [
                'type' => 'process_standardization',
                'description' => 'Standardize processing procedures',
                'potential_impact' => 'Improved predictability',
                'priority' => 'high'
            ];
        }
        
        return $opportunities;
    }

    private function identifyRouteIssues($route): array
    {
        $issues = [];
        
        // Check for excessive transit time
        if ($route->avg_transit_time > 48) {
            $issues[] = 'excessive_transit_time';
        }
        
        // Check for high variance
        if ($route->transit_variance > $route->avg_transit_time * 0.4) {
            $issues[] = 'high_transit_variance';
        }
        
        // Check for high delay rate
        $delayRate = ($route->delayed_shipments / $route->total_shipments) * 100;
        if ($delayRate > 25) {
            $issues[] = 'high_delay_rate';
        }
        
        // Check for low efficiency
        if ($route->avg_efficiency_score < 70) {
            $issues[] = 'low_efficiency';
        }
        
        return $issues;
    }

    private function getRouteInformation(string $routeKey): array
    {
        $route = DimensionRoute::find($routeKey);
        
        return [
            'route_name' => $route?->route_name ?? "Route {$routeKey}",
            'distance_miles' => $route?->distance_miles ?? 0,
            'estimated_time_hours' => $route?->estimated_time_hours ?? 0,
            'route_type' => $route?->route_type ?? 'standard'
        ];
    }

    private function assessBottleneckImpact($route): array
    {
        $delayRate = ($route->delayed_shipments / $route->total_shipments) * 100;
        
        return [
            'financial_impact' => [
                'estimated_delay_cost' => $route->delayed_shipments * 50, // $50 per delayed shipment
                'penalty_cost_estimate' => $route->delayed_shipments * 25 // $25 penalty per delay
            ],
            'operational_impact' => [
                'customer_satisfaction' => $delayRate > 20 ? 'severely_impacted' : ($delayRate > 10 ? 'impacted' : 'minimal'),
                'resource_utilization' => 'inefficient'
            ],
            'strategic_impact' => [
                'competitive_disadvantage' => $delayRate > 15,
                'growth_limitation' => $route->avg_transit_time > 72
            ]
        ];
    }

    private function identifyRootCauses($route, array $issues): array
    {
        $rootCauses = [];
        
        if (in_array('excessive_transit_time', $issues)) {
            $rootCauses[] = [
                'category' => 'routing',
                'cause' => 'Inefficient route planning',
                'evidence' => 'Average transit time exceeds industry benchmarks'
            ];
        }
        
        if (in_array('high_transit_variance', $issues)) {
            $rootCauses[] = [
                'category' => 'process',
                'cause' => 'Inconsistent processing procedures',
                'evidence' => 'High standard deviation in transit times'
            ];
        }
        
        if (in_array('high_delay_rate', $issues)) {
            $rootCauses[] = [
                'category' => 'capacity',
                'cause' => 'Insufficient capacity or resources',
                'evidence' => 'High percentage of delayed shipments'
            ];
        }
        
        return $rootCauses;
    }

    private function generateBottleneckRecommendations(array $issues, array $routeInfo): array
    {
        $recommendations = [];
        
        if (in_array('excessive_transit_time', $issues)) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Optimize route planning and scheduling',
                'timeline' => '2-4 weeks',
                'expected_improvement' => '15-25% reduction in transit time'
            ];
        }
        
        if (in_array('high_transit_variance', $issues)) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Standardize processing procedures',
                'timeline' => '3-6 weeks',
                'expected_improvement' => '20-30% variance reduction'
            ];
        }
        
        if (in_array('high_delay_rate', $issues)) {
            $recommendations[] = [
                'priority' => 'critical',
                'action' => 'Increase capacity or improve resource allocation',
                'timeline' => '1-2 weeks',
                'expected_improvement' => '50-60% delay reduction'
            ];
        }
        
        return $recommendations;
    }

    private function calculateBottleneckSeverity(array $issues, $route): int
    {
        $severity = 0;
        
        foreach ($issues as $issue) {
            $severity += match($issue) {
                'excessive_transit_time' => 3,
                'high_transit_variance' => 2,
                'high_delay_rate' => 4,
                'low_efficiency' => 1,
                default => 1
            };
        }
        
        $delayRate = ($route->delayed_shipments / $route->total_shipments) * 100;
        if ($delayRate > 50) $severity += 2;
        elseif ($delayRate > 30) $severity += 1;
        
        return min(10, $severity);
    }

    private function calculateSystemWideMetrics($routes): array
    {
        if ($routes->isEmpty()) {
            return [
                'avg_transit_time' => 0,
                'avg_efficiency_score' => 0,
                'routes_above_benchmark' => 0,
                'system_performance_rating' => 'unknown'
            ];
        }
        
        $avgTransitTime = $routes->avg('avg_transit_time');
        $avgEfficiency = $routes->avg('avg_efficiency_score');
        $routesAboveBenchmark = $routes->where('avg_transit_time', '<', 48)->count();
        $totalRoutes = count($routes);
        
        return [
            'avg_transit_time' => round($avgTransitTime, 2),
            'avg_efficiency_score' => round($avgEfficiency, 2),
            'routes_above_benchmark' => $routesAboveBenchmark,
            'benchmark_performance_rate' => round(($routesAboveBenchmark / $totalRoutes) * 100, 2),
            'system_performance_rating' => $this->getSystemPerformanceRating($avgTransitTime, $routesAboveBenchmark / $totalRoutes)
        ];
    }

    private function getSystemPerformanceRating(float $avgTransitTime, float $benchmarkRate): string
    {
        if ($avgTransitTime <= 24 && $benchmarkRate >= 0.8) return 'excellent';
        if ($avgTransitTime <= 36 && $benchmarkRate >= 0.6) return 'good';
        if ($avgTransitTime <= 48 && $benchmarkRate >= 0.4) return 'acceptable';
        if ($avgTransitTime <= 72) return 'needs_improvement';
        return 'critical';
    }

    private function prioritizeBottleneckActions(array $bottlenecks): array
    {
        $actions = [];
        
        foreach ($bottlenecks as $bottleneck) {
            if ($bottleneck['severity_score'] >= 8) {
                $actions[] = [
                    'action' => "Address critical bottleneck on route {$bottleneck['route_info']['route_name']}",
                    'priority' => 'critical',
                    'timeline' => 'immediate',
                    'impact' => 'high'
                ];
            } elseif ($bottleneck['severity_score'] >= 6) {
                $actions[] = [
                    'action' => "Optimize route {$bottleneck['route_info']['route_name']}",
                    'priority' => 'high',
                    'timeline' => '1-2 weeks',
                    'impact' => 'medium'
                ];
            }
        }
        
        return $actions;
    }

    private function calculateVarianceStatistics(Collection $shipments): array
    {
        $transitTimes = $shipments->pluck('transit_time_hours')->toArray();
        
        return [
            'variance' => round($this->calculateVariance($transitTimes), 2),
            'standard_deviation' => round($this->calculateStandardDeviation($transitTimes), 2),
            'coefficient_of_variation' => round($this->calculateCoefficientOfVariation($transitTimes), 3),
            'range' => max($transitTimes) - min($transitTimes),
            'interquartile_range' => $this->calculateInterquartileRange($transitTimes)
        ];
    }

    private function analyzeTemporalVariance(Collection $shipments): array
    {
        $varianceByDay = [];
        $grouped = $shipments->groupBy(function($shipment) {
            return date('N', strtotime($shipment->actual_delivery_time));
        });
        
        foreach ($grouped as $dayOfWeek => $dayShipments) {
            $transitTimes = $dayShipments->pluck('transit_time_hours')->toArray();
            $varianceByDay[$dayOfWeek] = [
                'avg_transit_time' => round(array_sum($transitTimes) / count($transitTimes), 2),
                'variance' => round($this->calculateVariance($transitTimes), 2)
            ];
        }
        
        return [
            'by_day_of_week' => $varianceByDay,
            'most_variable_day' => $this->findMostVariableDay($varianceByDay),
            'least_variable_day' => $this->findLeastVariableDay($varianceByDay)
        ];
    }

    private function analyzeVarianceFactors(Collection $shipments): array
    {
        $distanceFactor = $this->analyzeDistanceImpact($shipments);
        $stopsFactor = $this->analyzeStopsImpact($shipments);
        $statusFactor = $this->analyzeStatusImpact($shipments);
        
        return [
            'distance_impact' => $distanceFactor,
            'stops_impact' => $stopsFactor,
            'status_impact' => $statusFactor,
            'primary_variance_driver' => $this->identifyPrimaryVarianceDriver($distanceFactor, $stopsFactor, $statusFactor)
        ];
    }

    private function identifyVarianceSources(Collection $shipments): array
    {
        return [
            'weather_related' => $this->identifyWeatherRelatedVariance($shipments),
            'traffic_related' => $this->identifyTrafficRelatedVariance($shipments),
            'operational' => $this->identifyOperationalVariance($shipments),
            'capacity_related' => $this->identifyCapacityVariance($shipments)
        ];
    }

    private function generateVariancePredictions(Collection $shipments): array
    {
        // Simple trend-based prediction
        $recentShipments = $shipments->sortByDesc('delivery_date_key')->take(10);
        $historicalShipments = $shipments->sortBy('delivery_date_key')->take(10);
        
        $recentAvg = $recentShipments->avg('transit_time_hours');
        $historicalAvg = $historicalShipments->avg('transit_time_hours');
        $trend = $recentAvg - $historicalAvg;
        
        return [
            'trend_direction' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
            'predicted_next_period' => round($recentAvg + ($trend * 0.5), 2),
            'confidence_level' => $recentShipments->count() >= 5 ? 'medium' : 'low'
        ];
    }

    private function getVarianceReductionStrategies(array $varianceStatistics): array
    {
        $strategies = [];
        
        if ($varianceStatistics['coefficient_of_variation'] > 0.3) {
            $strategies[] = [
                'strategy' => 'Standardize processes to reduce variability',
                'expected_impact' => '15-25% variance reduction',
                'implementation_effort' => 'medium',
                'timeline' => '4-6 weeks'
            ];
        }
        
        if ($varianceStatistics['standard_deviation'] > 12) {
            $strategies[] = [
                'strategy' => 'Implement real-time monitoring and alerts',
                'expected_impact' => '10-15% variance reduction',
                'implementation_effort' => 'low',
                'timeline' => '2-3 weeks'
            ];
        }
        
        return $strategies;
    }

    // Statistical helper methods
    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[$middle];
        }
    }

    private function calculateMode(array $values): float
    {
        $frequency = array_count_values($values);
        arsort($frequency);
        return array_key_first($frequency);
    }

    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / (count($values) - 1);
        
        return $variance;
    }

    private function calculateStandardDeviation(array $values): float
    {
        return sqrt($this->calculateVariance($values));
    }

    private function calculateCoefficientOfVariation(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $stdDev = $this->calculateStandardDeviation($values);
        
        return $mean > 0 ? $stdDev / $mean : 0;
    }

    private function calculatePercentile(array $values, float $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower == $upper) {
            return $values[$lower];
        }
        
        $weight = $index - $lower;
        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }

    private function calculateInterquartileRange(array $values): float
    {
        $q1 = $this->calculatePercentile($values, 25);
        $q3 = $this->calculatePercentile($values, 75);
        return $q3 - $q1;
    }

    private function calculateAverageSpeed(array $transitTimes, array $distances): float
    {
        $totalSpeed = 0;
        $count = 0;
        
        for ($i = 0; $i < count($transitTimes) && $i < count($distances); $i++) {
            if ($transitTimes[$i] > 0 && $distances[$i] > 0) {
                $totalSpeed += $distances[$i] / $transitTimes[$i];
                $count++;
            }
        }
        
        return $count > 0 ? $totalSpeed / $count : 0;
    }

    private function calculateMinSpeed(array $transitTimes, array $distances): float
    {
        $speeds = [];
        
        for ($i = 0; $i < count($transitTimes) && $i < count($distances); $i++) {
            if ($transitTimes[$i] > 0 && $distances[$i] > 0) {
                $speeds[] = $distances[$i] / $transitTimes[$i];
            }
        }
        
        return count($speeds) > 0 ? min($speeds) : 0;
    }

    private function calculateMaxSpeed(array $transitTimes, array $distances): float
    {
        $speeds = [];
        
        for ($i = 0; $i < count($transitTimes) && $i < count($distances); $i++) {
            if ($transitTimes[$i] > 0 && $distances[$i] > 0) {
                $speeds[] = $distances[$i] / $transitTimes[$i];
            }
        }
        
        return count($speeds) > 0 ? max($speeds) : 0;
    }

    private function calculateReliabilityScore(Collection $shipments): float
    {
        $onTimeRate = ($shipments->where('on_time_indicator', true)->count() / $shipments->count()) * 100;
        $varianceScore = max(0, 100 - ($this->calculateCoefficientOfVariation($shipments->pluck('transit_time_hours')->toArray()) * 100));
        
        return ($onTimeRate * 0.7) + ($varianceScore * 0.3);
    }

    private function calculateConsistencyScore(Collection $shipments): float
    {
        $cv = $this->calculateCoefficientOfVariation($shipments->pluck('transit_time_hours')->toArray());
        return max(0, 100 - ($cv * 100));
    }

    private function analyzeTrendDirection(array $dailyTrends): string
    {
        if (count($dailyTrends) < 2) return 'insufficient_data';
        
        $recentValues = array_slice(array_column($dailyTrends, 'avg_transit_time'), -3);
        $earlierValues = array_slice(array_column($dailyTrends, 'avg_transit_time'), 0, 3);
        
        $recentAvg = array_sum($recentValues) / count($recentValues);
        $earlierAvg = array_sum($earlierValues) / count($earlierValues);
        
        $change = (($recentAvg - $earlierAvg) / $earlierAvg) * 100;
        
        return $change > 5 ? 'increasing' : ($change < -5 ? 'decreasing' : 'stable');
    }

    private function identifySeasonalPatterns(array $dailyTrends): array
    {
        // This would require more sophisticated analysis with actual date data
        return ['pattern' => 'analysis_needed', 'confidence' => 'low'];
    }

    private function assessTransitVolatility(array $dailyTrends): string
    {
        $transitTimes = array_column($dailyTrends, 'avg_transit_time');
        $cv = $this->calculateCoefficientOfVariation($transitTimes);
        
        if ($cv < 0.1) return 'very_low';
        if ($cv < 0.2) return 'low';
        if ($cv < 0.3) return 'moderate';
        if ($cv < 0.5) return 'high';
        return 'very_high';
    }

    // Additional analysis methods
    private function identifyUnderperformingRoutes(array $filters): array
    {
        // This would analyze routes that consistently underperform
        return []; // Simplified for now
    }

    private function identifyOptimizationTargets(array $filters): array
    {
        // This would identify high-impact optimization opportunities
        return []; // Simplified for now
    }

    private function generateBottleneckImprovements(array $bottlenecks): array
    {
        return []; // Simplified for now
    }

    private function createImprovementRoadmap(array $bottlenecks, array $underperformingRoutes): array
    {
        return [
            'phase_1' => ['duration' => '1-2 weeks', 'actions' => ['Address critical bottlenecks']],
            'phase_2' => ['duration' => '2-4 weeks', 'actions' => ['Optimize underperforming routes']],
            'phase_3' => ['duration' => '1-2 months', 'actions' => ['Implement system-wide improvements']]
        ];
    }

    private function estimateImprovementBenefits(array $bottlenecks, array $underperformingRoutes): array
    {
        return [
            'estimated_transit_time_reduction' => '15-25%',
            'estimated_cost_savings' => '10-15%',
            'expected_roi' => '300-500%'
        ];
    }

    private function calculateBenchmarkSummary(array $benchmarkData): array
    {
        $avgTransitTimes = array_column($benchmarkData, 'avg_transit_time');
        
        return [
            'best_performer' => $benchmarkData[0] ?? null,
            'worst_performer' => end($benchmarkData) ?? null,
            'average_performance' => round(array_sum($avgTransitTimes) / count($avgTransitTimes), 2),
            'performance_spread' => round(max($avgTransitTimes) - min($avgTransitTimes), 2)
        ];
    }

    private function identifyBestPractices(array $benchmarkData): array
    {
        $topPerformers = array_slice($benchmarkData, 0, 3);
        
        return [
            'common_characteristics' => ['Low variance', 'High efficiency scores', 'Consistent on-time performance'],
            'key_success_factors' => ['Route optimization', 'Process standardization', 'Resource allocation']
        ];
    }

    private function identifyImprovementOpportunities(array $benchmarkData): array
    {
        $bottomPerformers = array_slice($benchmarkData, -3);
        
        return [
            'primary_opportunities' => ['Transit time reduction', 'Variance improvement', 'Efficiency optimization'],
            'focus_areas' => ['Route planning', 'Process improvement', 'Capacity management']
        ];
    }

    private function analyzeDistanceImpact(Collection $shipments): float
    {
        // Correlation between distance and transit time variance
        $correlations = [];
        $distanceGroups = $shipments->groupBy(function($shipment) {
            return floor($shipment->distance_miles / 50) * 50; // Group by 50-mile ranges
        });
        
        foreach ($distanceGroups as $group) {
            if (count($group) > 3) {
                $transitTimes = $group->pluck('transit_time_hours')->toArray();
                $correlations[] = $this->calculateVariance($transitTimes);
            }
        }
        
        return count($correlations) > 0 ? array_sum($correlations) / count($correlations) : 0;
    }

    private function analyzeStopsImpact(Collection $shipments): float
    {
        $stopGroups = $shipments->groupBy('stops_count');
        $varianceByStops = [];
        
        foreach ($stopGroups as $stops => $group) {
            if (count($group) > 3) {
                $transitTimes = $group->pluck('transit_time_hours')->toArray();
                $varianceByStops[] = $this->calculateVariance($transitTimes);
            }
        }
        
        return count($varianceByStops) > 0 ? array_sum($varianceByStops) / count($varianceByStops) : 0;
    }

    private function analyzeStatusImpact(Collection $shipments): float
    {
        $statusVariance = [];
        $statusGroups = $shipments->groupBy('shipment_status');
        
        foreach ($statusGroups as $status => $group) {
            if (count($group) > 3) {
                $transitTimes = $group->pluck('transit_time_hours')->toArray();
                $statusVariance[] = $this->calculateVariance($transitTimes);
            }
        }
        
        return count($statusVariance) > 0 ? array_sum($statusVariance) / count($statusVariance) : 0;
    }

    private function identifyPrimaryVarianceDriver(float $distance, float $stops, float $status): string
    {
        $max = max($distance, $stops, $status);
        
        return match($max) {
            $distance => 'distance',
            $stops => 'stops',
            $status => 'status',
            default => 'unknown'
        };
    }

    private function identifyWeatherRelatedVariance(Collection $shipments): array
    {
        // This would require weather data integration
        return ['impact' => 'estimated', 'confidence' => 'low'];
    }

    private function identifyTrafficRelatedVariance(Collection $shipments): array
    {
        // This would require traffic data integration
        return ['impact' => 'estimated', 'confidence' => 'low'];
    }

    private function identifyOperationalVariance(Collection $shipments): array
    {
        return [
            'handling_delays' => 'medium_impact',
            'processing_time' => 'low_impact',
            'documentation' => 'low_impact'
        ];
    }

    private function identifyCapacityVariance(Collection $shipments): array
    {
        return [
            'resource_availability' => 'high_impact',
            'scheduling_conflicts' => 'medium_impact',
            'equipment_issues' => 'low_impact'
        ];
    }

    private function findMostVariableDay(array $varianceByDay): string
    {
        $maxVariance = 0;
        $mostVariableDay = '';
        
        foreach ($varianceByDay as $day => $data) {
            if ($data['variance'] > $maxVariance) {
                $maxVariance = $data['variance'];
                $mostVariableDay = $day;
            }
        }
        
        return $mostVariableDay;
    }

    private function findLeastVariableDay(array $varianceByDay): string
    {
        $minVariance = PHP_FLOAT_MAX;
        $leastVariableDay = '';
        
        foreach ($varianceByDay as $day => $data) {
            if ($data['variance'] < $minVariance) {
                $minVariance = $data['variance'];
                $leastVariableDay = $day;
            }
        }
        
        return $leastVariableDay;
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['date_range'])) {
            $query->whereBetween('delivery_date_key', [$filters['date_range']['start'], $filters['date_range']['end']]);
        }
        
        if (isset($filters['client_key'])) {
            $query->where('client_key', $filters['client_key']);
        }
        
        if (isset($filters['carrier_key'])) {
            $query->where('carrier_key', $filters['carrier_key']);
        }
    }

    private function generateCacheKey(string $type, array $params): string
    {
        ksort($params);
        return "transit_time_{$type}_" . md5(serialize($params));
    }
}