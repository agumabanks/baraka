<?php

namespace App\Services\OperationalReporting;

use App\Models\ETL\FactShipment;
use App\Models\ETL\FactPerformanceMetrics;
use App\Models\ETL\DimensionClient;
use App\Models\ETL\DimensionDriver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class OnTimeDeliveryService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const SLA_THRESHOLDS = [
        'express' => 2,    // 2 hours
        'standard' => 24,  // 24 hours
        'economy' => 72    // 72 hours
    ];

    /**
     * Calculate on-time delivery rate
     */
    public function calculateOnTimeRate(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('on_time_rate', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = FactShipment::query()
                ->select(
                    'shipment_status',
                    DB::raw('COUNT(*) as total_shipments'),
                    DB::raw('SUM(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) as on_time_shipments'),
                    DB::raw('AVG(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) * 100 as on_time_percentage'),
                    DB::raw('AVG(datediff(HOUR, scheduled_delivery_time, actual_delivery_time)) as avg_delay_hours'),
                    DB::raw('SUM(CASE WHEN on_time_indicator = false THEN 1 ELSE 0 END) as delayed_shipments')
                )
                ->groupBy('shipment_status');

            $this->applyFilters($query, $filters);

            $results = $query->get();

            $totalShipments = $results->sum('total_shipments');
            $totalOnTime = $results->sum('on_time_shipments');
            $overallRate = $totalShipments > 0 ? ($totalOnTime / $totalShipments) * 100 : 0;

            return [
                'overall_metrics' => [
                    'total_shipments' => (int) $totalShipments,
                    'on_time_shipments' => (int) $totalOnTime,
                    'delayed_shipments' => (int) ($totalShipments - $totalOnTime),
                    'on_time_delivery_rate' => round($overallRate, 2),
                    'average_delay_hours' => round($results->avg('avg_delay_hours') ?? 0, 2)
                ],
                'breakdown_by_status' => $results->map(function ($item) {
                    return [
                        'status' => $item->shipment_status,
                        'total_shipments' => (int) $item->total_shipments,
                        'on_time_shipments' => (int) $item->on_time_shipments,
                        'delayed_shipments' => (int) $item->delayed_shipments,
                        'on_time_percentage' => round($item->on_time_percentage, 2)
                    ];
                })->toArray(),
                'performance_grade' => $this->calculatePerformanceGrade($overallRate),
                'analysis_date' => now()->toISOString()
            ];
        });
    }

    /**
     * Perform variance analysis by different dimensions
     */
    public function performVarianceAnalysis(array $dateRange, string $dimension = 'route'): array
    {
        $cacheKey = "variance_analysis_{$dimension}_{$dateRange['start']}_{$dateRange['end']}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateRange, $dimension) {
            $validDimensions = ['route', 'driver', 'client', 'branch', 'carrier'];
            
            if (!in_array($dimension, $validDimensions)) {
                throw new \InvalidArgumentException("Invalid dimension: {$dimension}. Valid options: " . implode(', ', $validDimensions));
            }

            $dimensionField = match($dimension) {
                'route' => 'route_key',
                'driver' => 'driver_key',
                'client' => 'client_key',
                'branch' => 'origin_branch_key',
                'carrier' => 'carrier_key'
            };

            $query = FactShipment::query()
                ->select(
                    $dimensionField,
                    DB::raw('COUNT(*) as total_shipments'),
                    DB::raw('SUM(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) as on_time_shipments'),
                    DB::raw('AVG(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) * 100 as on_time_rate'),
                    DB::raw('STDDEV(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) * 100 as rate_variance'),
                    DB::raw('AVG(datediff(HOUR, scheduled_delivery_time, actual_delivery_time)) as avg_delay'),
                    DB::raw('MAX(datediff(HOUR, scheduled_delivery_time, actual_delivery_time)) as max_delay'),
                    DB::raw('SUM(revenue) as total_revenue')
                )
                ->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']])
                ->groupBy($dimensionField)
                ->having('total_shipments', '>=', 5); // Minimum sample size

            $results = $query->get();

            $analysis = [
                'dimension' => $dimension,
                'date_range' => $dateRange,
                'summary' => [
                    'total_entities' => count($results),
                    'average_on_time_rate' => round($results->avg('on_time_rate') ?? 0, 2),
                    'rate_variance' => round($results->avg('rate_variance') ?? 0, 2),
                    'best_performer' => $this->getBestPerformer($results, 'on_time_rate'),
                    'worst_performer' => $this->getWorstPerformer($results, 'on_time_rate')
                ],
                'detailed_analysis' => $this->getVarianceAnalysisDetails($results, $dimension)
            ];

            return $analysis;
        });
    }

    /**
     * Get historical trends and forecasting
     */
    public function getHistoricalTrends(string $period = 'daily', int $days = 30): array
    {
        $cacheKey = "historical_trends_{$period}_{$days}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period, $days) {
            $startDate = now()->subDays($days)->format('Ymd');
            $endDate = now()->format('Ymd');

            $dateGrouping = match($period) {
                'hourly' => 'HOUR(actual_delivery_time)',
                'daily' => 'DATE(actual_delivery_time)',
                'weekly' => 'YEARWEEK(actual_delivery_time)',
                'monthly' => 'YEAR(actual_delivery_time), MONTH(actual_delivery_time)',
                default => 'DATE(actual_delivery_time)'
            };

            $trends = FactShipment::query()
                ->select(
                    DB::raw($dateGrouping . ' as period'),
                    DB::raw('COUNT(*) as total_shipments'),
                    DB::raw('SUM(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) as on_time_shipments'),
                    DB::raw('AVG(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) * 100 as on_time_rate')
                )
                ->whereBetween('delivery_date_key', [$startDate, $endDate])
                ->groupBy(DB::raw($dateGrouping))
                ->orderBy('period')
                ->get();

            $forecast = $this->generateForecast($trends);
            $trendAnalysis = $this->analyzeTrendDirection($trends);

            return [
                'period' => $period,
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'trends_data' => $trends->map(function ($item) {
                    return [
                        'period' => $item->period,
                        'shipments' => (int) $item->total_shipments,
                        'on_time_shipments' => (int) $item->on_time_shipments,
                        'on_time_rate' => round($item->on_time_rate, 2)
                    ];
                })->toArray(),
                'forecast' => $forecast,
                'trend_analysis' => $trendAnalysis,
                'key_insights' => $this->generateKeyInsights($trends, $forecast)
            ];
        });
    }

    /**
     * Monitor SLA compliance
     */
    public function monitorSLACompliance(string $clientKey, array $dateRange = []): array
    {
        $client = DimensionClient::find($clientKey);
        if (!$client) {
            throw new \InvalidArgumentException("Client not found: {$clientKey}");
        }

        $start = $dateRange['start'] ?? 'current';
        $end = $dateRange['end'] ?? 'current';
        $cacheKey = "sla_compliance_{$clientKey}_{$start}_{$end}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($clientKey, $dateRange, $client) {
            $query = FactShipment::where('client_key', $clientKey);

            if (!empty($dateRange)) {
                $query->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']]);
            }

            $shipments = $query->get();

            if ($shipments->isEmpty()) {
                return [
                    'client' => [
                        'key' => $clientKey,
                        'name' => $client->client_name,
                        'sla_level' => $client->service_level_agreement
                    ],
                    'compliance_status' => 'no_data',
                    'message' => 'No shipment data available for the specified period'
                ];
            }

            $slaThreshold = self::SLA_THRESHOLDS[$client->service_level_agreement] ?? 24;
            $slaCompliant = $shipments->filter(function ($shipment) use ($slaThreshold) {
                return $shipment->getDeliveryDelay() <= $slaThreshold;
            });

            $complianceRate = ($slaCompliant->count() / $shipments->count()) * 100;
            $penaltyIncurringShipments = $shipments->where('late_penalty_cost', '>', 0);

            return [
                'client' => [
                    'key' => $clientKey,
                    'name' => $client->client_name,
                    'sla_level' => $client->service_level_agreement,
                    'sla_threshold_hours' => $slaThreshold
                ],
                'compliance_metrics' => [
                    'total_shipments' => $shipments->count(),
                    'sla_compliant_shipments' => $slaCompliant->count(),
                    'sla_compliance_rate' => round($complianceRate, 2),
                    'penalty_incurred_shipments' => $penaltyIncurringShipments->count(),
                    'total_penalty_cost' => round($penaltyIncurringShipments->sum('late_penalty_cost'), 2)
                ],
                'compliance_status' => $this->determineComplianceStatus($complianceRate),
                'performance_breakdown' => [
                    'on_time' => $shipments->where('on_time_indicator', true)->count(),
                    'minor_delay' => $shipments->filter(function ($s) use ($slaThreshold) {
                        return $s->on_time_indicator == false && $s->getDeliveryDelay() <= $slaThreshold * 1.5;
                    })->count(),
                    'major_delay' => $shipments->filter(function ($s) use ($slaThreshold) {
                        return $s->on_time_indicator == false && $s->getDeliveryDelay() > $slaThreshold * 1.5;
                    })->count()
                ],
                'recommendations' => $this->generateSLARecommendations($complianceRate, $client)
            ];
        });
    }

    /**
     * Get exception handling for delayed shipments
     */
    public function getDelayedShipmentAnalysis(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('delayed_analysis', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = FactShipment::where('on_time_indicator', false);

            $this->applyFilters($query, $filters);

            $delayedShipments = $query->get();

            $delayBuckets = [
                '0-2 hours' => 0,
                '2-6 hours' => 0,
                '6-12 hours' => 0,
                '12-24 hours' => 0,
                '24-48 hours' => 0,
                '48+ hours' => 0
            ];

            foreach ($delayedShipments as $shipment) {
                $delayHours = $shipment->getDeliveryDelay() ?? 0;
                
                if ($delayHours <= 2) $delayBuckets['0-2 hours']++;
                elseif ($delayHours <= 6) $delayBuckets['2-6 hours']++;
                elseif ($delayHours <= 12) $delayBuckets['6-12 hours']++;
                elseif ($delayHours <= 24) $delayBuckets['12-24 hours']++;
                elseif ($delayHours <= 48) $delayBuckets['24-48 hours']++;
                else $delayBuckets['48+ hours']++;
            }

            return [
                'summary' => [
                    'total_delayed_shipments' => $delayedShipments->count(),
                    'average_delay_hours' => round($delayedShipments->avg('transit_time_hours') - $delayedShipments->avg('scheduled_delivery_time') ?? 0, 2),
                    'max_delay_hours' => round($delayedShipments->max('transit_time_hours') ?? 0, 2)
                ],
                'delay_distribution' => $delayBuckets,
                'top_delayed_routes' => $this->getTopDelayedRoutes($delayedShipments),
                'financial_impact' => [
                    'total_penalty_costs' => round($delayedShipments->sum('late_penalty_cost'), 2),
                    'avg_penalty_per_delayed_shipment' => round($delayedShipments->where('late_penalty_cost', '>', 0)->avg('late_penalty_cost') ?? 0, 2)
                ],
                'urgent_actions_needed' => $delayedShipments->where('transit_time_hours', '>', 48)->count()
            ];
        });
    }

    // Private helper methods
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
    }

    private function calculatePerformanceGrade(float $rate): string
    {
        if ($rate >= 95) return 'A+';
        if ($rate >= 90) return 'A';
        if ($rate >= 85) return 'A-';
        if ($rate >= 80) return 'B+';
        if ($rate >= 75) return 'B';
        if ($rate >= 70) return 'B-';
        if ($rate >= 65) return 'C+';
        if ($rate >= 60) return 'C';
        return 'D';
    }

    private function getBestPerformer(Collection $results, string $metric): ?array
    {
        $best = $results->sortByDesc($metric)->first();
        return $best ? [
            'value' => round($best->$metric, 2),
            'identifier' => $this->getIdentifier($best)
        ] : null;
    }

    private function getWorstPerformer(Collection $results, string $metric): ?array
    {
        $worst = $results->sortBy($metric)->first();
        return $worst ? [
            'value' => round($worst->$metric, 2),
            'identifier' => $this->getIdentifier($worst)
        ] : null;
    }

    private function getIdentifier($item): string
    {
        return "Entity {$item->getKey()}";
    }

    private function getVarianceAnalysisDetails(Collection $results, string $dimension): array
    {
        return $results->map(function ($item) use ($dimension) {
            return [
                'identifier' => $this->getIdentifier($item),
                'on_time_rate' => round($item->on_time_rate, 2),
                'total_shipments' => (int) $item->total_shipments,
                'performance_category' => $this->categorizePerformance($item->on_time_rate)
            ];
        })->toArray();
    }

    private function categorizePerformance(float $rate): string
    {
        if ($rate >= 90) return 'excellent';
        if ($rate >= 80) return 'good';
        if ($rate >= 70) return 'acceptable';
        if ($rate >= 60) return 'needs_improvement';
        return 'critical';
    }

    private function generateForecast(Collection $trends): array
    {
        // Simple linear trend forecast
        if (count($trends) < 2) {
            return ['method' => 'insufficient_data', 'prediction' => null];
        }

        // Calculate trend slope
        $values = $trends->pluck('on_time_rate')->toArray();
        $periods = range(1, count($values));
        $trend = $this->calculateLinearTrend($periods, $values);
        
        $lastValue = end($values);
        $nextPeriod = count($values) + 1;
        $prediction = $lastValue + ($trend * ($nextPeriod - count($values)));

        return [
            'method' => 'linear_trend',
            'current_trend' => $trend > 0 ? 'improving' : ($trend < 0 ? 'declining' : 'stable'),
            'next_period_prediction' => round(max(0, min(100, $prediction)), 2),
            'confidence_level' => $this->calculateForecastConfidence($values)
        ];
    }

    private function calculateLinearTrend(array $x, array $y): float
    {
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(function($a, $b) { return $a * $b; }, $x, $y));
        $sumXX = array_sum(array_map(function($a) { return $a * $a; }, $x));
        
        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
    }

    private function calculateForecastConfidence(array $values): string
    {
        if (count($values) < 5) return 'low';
        if (count($values) < 10) return 'medium';
        return 'high';
    }

    private function analyzeTrendDirection(Collection $trends): array
    {
        if (count($trends) < 2) {
            return ['direction' => 'insufficient_data'];
        }

        $values = $trends->pluck('on_time_rate')->toArray();
        $recentValues = array_slice($values, -3);
        $olderValues = array_slice($values, 0, 3);
        
        $recentAvg = array_sum($recentValues) / count($recentValues);
        $olderAvg = array_sum($olderValues) / count($olderValues);
        
        $change = (($recentAvg - $olderAvg) / $olderAvg) * 100;
        
        return [
            'direction' => $change > 2 ? 'improving' : ($change < -2 ? 'declining' : 'stable'),
            'change_percentage' => round($change, 2),
            'volatility' => $this->calculateVolatility($values)
        ];
    }

    private function calculateVolatility(array $values): string
    {
        if (count($values) < 2) return 'low';
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($v) use ($mean) { 
            return pow($v - $mean, 2); 
        }, $values)) / (count($values) - 1);
        
        $stdDev = sqrt($variance);
        $cv = ($mean > 0) ? ($stdDev / $mean) * 100 : 0;
        
        return $cv < 5 ? 'low' : ($cv < 15 ? 'medium' : 'high');
    }

    private function generateKeyInsights(Collection $trends, array $forecast): array
    {
        $insights = [];
        
        $currentRate = $trends->last()?->on_time_rate ?? 0;
        $trendDirection = $forecast['current_trend'] ?? 'stable';
        
        if ($currentRate >= 90) {
            $insights[] = "Excellent performance with {$currentRate}% on-time rate";
        } elseif ($currentRate >= 80) {
            $insights[] = "Good performance but room for improvement ({$currentRate}% on-time rate)";
        } else {
            $insights[] = "Performance needs immediate attention ({$currentRate}% on-time rate)";
        }
        
        if ($trendDirection === 'improving') {
            $insights[] = "Performance is trending positively";
        } elseif ($trendDirection === 'declining') {
            $insights[] = "Performance is declining and requires intervention";
        }
        
        return $insights;
    }

    private function determineComplianceStatus(float $rate): string
    {
        if ($rate >= 95) return 'excellent';
        if ($rate >= 90) return 'good';
        if ($rate >= 80) return 'acceptable';
        if ($rate >= 70) return 'concerning';
        return 'critical';
    }

    private function generateSLARecommendations(float $rate, DimensionClient $client): array
    {
        $recommendations = [];
        
        if ($rate < 90) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Review and optimize current routing processes',
                'expected_impact' => 'Improved on-time delivery performance'
            ];
        }
        
        if ($client->service_level_agreement === 'express' && $rate < 95) {
            $recommendations[] = [
                'priority' => 'critical',
                'action' => 'Implement dedicated resources for express shipments',
                'expected_impact' => 'Meeting express service expectations'
            ];
        }
        
        return $recommendations;
    }

    private function getTopDelayedRoutes(Collection $delayedShipments): array
    {
        return $delayedShipments->groupBy('route_key')
            ->map(function ($routeShipments) {
                return [
                    'route_key' => $routeShipments->first()->route_key,
                    'delayed_count' => $routeShipments->count(),
                    'avg_delay' => round($routeShipments->avg('transit_time_hours') - $routeShipments->avg('scheduled_delivery_time') ?? 0, 2)
                ];
            })
            ->sortByDesc('delayed_count')
            ->take(5)
            ->values()
            ->toArray();
    }

    private function generateCacheKey(string $type, array $params): string
    {
        ksort($params);
        return "on_time_delivery_{$type}_" . md5(serialize($params));
    }
}