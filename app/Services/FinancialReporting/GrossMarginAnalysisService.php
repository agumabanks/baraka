<?php

namespace App\Services\FinancialReporting;

use App\Models\ETL\FactShipment;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\Financial\GrossMarginAnalysis;
use App\Models\ETL\DimensionClient;
use App\Models\ETL\DimensionRoute;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrossMarginAnalysisService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const FORECAST_MONTHS = 12;
    private const BENCHMARK_MARGIN_EXCELLENT = 40;
    private const BENCHMARK_MARGIN_GOOD = 25;
    private const BENCHMARK_MARGIN_AVERAGE = 15;

    /**
     * Perform comprehensive gross margin analysis with historical trending and forecasting
     */
    public function analyzeGrossMargin(array $filters = []): array
    {
        try {
            $analysis = [
                'overall_margin' => [
                    'gross_profit' => 0,
                    'gross_margin_percentage' => 0,
                    'gross_margin_rate' => 0,
                    'total_revenue' => 0,
                    'total_cogs' => 0
                ],
                'margin_by_segment' => [
                    'client' => [],
                    'route' => [],
                    'service_type' => [],
                    'time_period' => []
                ],
                'historical_analysis' => [
                    'margin_trends' => [],
                    'margin_volatility' => 0,
                    'margin_seasonality' => [],
                    'best_performing_periods' => [],
                    'worst_performing_periods' => []
                ],
                'forecasting' => [
                    'short_term_forecast' => [],
                    'long_term_forecast' => [],
                    'forecast_confidence' => 0,
                    'trend_predictions' => []
                ],
                'competitive_analysis' => [
                    'industry_benchmark' => 20,
                    'competitive_position' => 'average',
                    'margin_gap_analysis' => [],
                    'improvement_opportunities' => []
                ],
                'variance_analysis' => [
                    'margin_variance' => 0,
                    'variance_drivers' => [],
                    'significant_variances' => [],
                    'variance_impact' => 0
                ]
            ];

            // Get base data
            $marginData = $this->getMarginData($filters);
            $analysis['overall_margin'] = $this->calculateOverallMargin($marginData);
            
            // Segment analysis
            $analysis['margin_by_segment'] = $this->analyzeMarginBySegment($filters);
            
            // Historical analysis
            $analysis['historical_analysis'] = $this->performHistoricalAnalysis($filters);
            
            // Generate forecasts
            $analysis['forecasting'] = $this->generateMarginForecasts($filters);
            
            // Competitive benchmarking
            $analysis['competitive_analysis'] = $this->performCompetitiveAnalysis($analysis['overall_margin']);
            
            // Variance analysis
            $analysis['variance_analysis'] = $this->performVarianceAnalysis($filters);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Gross margin analysis error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate margin forecasting with predictive analytics
     */
    public function generateMarginForecasts(array $filters = [], int $months = self::FORECAST_MONTHS): array
    {
        try {
            $forecasts = [
                'short_term_forecast' => [], // 1-3 months
                'long_term_forecast' => [], // 4-12 months
                'forecast_confidence' => 0,
                'trend_predictions' => [],
                'scenario_analysis' => []
            ];

            // Get historical data for forecasting
            $historicalData = $this->getHistoricalMarginData($filters);
            
            if (count($historicalData) < 6) {
                $forecasts['error'] = 'Insufficient historical data for forecasting';
                return $forecasts;
            }

            // Calculate trends and patterns
            $trendAnalysis = $this->calculateMarginTrend($historicalData);
            $seasonality = $this->calculateMarginSeasonality($historicalData);
            $volatility = $this->calculateMarginVolatility($historicalData);
            
            // Generate short-term forecasts (1-3 months)
            $forecasts['short_term_forecast'] = $this->generateShortTermForecasts(
                $historicalData, 
                $trendAnalysis, 
                $seasonality, 
                3
            );
            
            // Generate long-term forecasts (4-12 months)
            $forecasts['long_term_forecast'] = $this->generateLongTermForecasts(
                $historicalData, 
                $trendAnalysis, 
                $seasonality, 
                $months - 3
            );
            
            // Calculate forecast confidence
            $forecasts['forecast_confidence'] = $this->calculateForecastConfidence(
                $volatility, 
                count($historicalData)
            );
            
            // Generate trend predictions
            $forecasts['trend_predictions'] = $this->generateTrendPredictions($trendAnalysis);
            
            // Scenario analysis
            $forecasts['scenario_analysis'] = $this->generateScenarioAnalysis(
                $forecasts['short_term_forecast'],
                $forecasts['long_term_forecast']
            );

            return $forecasts;

        } catch (\Exception $e) {
            Log::error('Margin forecasting error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Perform margin variance analysis by segment
     */
    public function performMarginVarianceAnalysis(array $filters = []): array
    {
        try {
            $varianceAnalysis = [
                'overall_variance' => [
                    'current_margin' => 0,
                    'previous_margin' => 0,
                    'variance_amount' => 0,
                    'variance_percentage' => 0
                ],
                'variance_by_segment' => [
                    'client' => [],
                    'route' => [],
                    'service_type' => []
                ],
                'variance_drivers' => [
                    'revenue_drivers' => [],
                    'cost_drivers' => [],
                    'volume_drivers' => []
                ],
                'significant_variances' => [],
                'variance_impact_analysis' => []
            ];

            // Get current and previous period data
            $currentData = $this->getMarginData($filters);
            $previousFilters = $this->adjustFiltersForPreviousPeriod($filters);
            $previousData = $this->getMarginData($previousFilters);

            // Calculate overall variance
            $currentMargin = $this->calculateOverallMargin($currentData);
            $previousMargin = $this->calculateOverallMargin($previousData);
            
            $varianceAnalysis['overall_variance'] = [
                'current_margin' => $currentMargin['gross_margin_percentage'],
                'previous_margin' => $previousMargin['gross_margin_percentage'],
                'variance_amount' => $currentMargin['gross_margin_percentage'] - $previousMargin['gross_margin_percentage'],
                'variance_percentage' => $previousMargin['gross_margin_percentage'] > 0 
                    ? (($currentMargin['gross_margin_percentage'] - $previousMargin['gross_margin_percentage']) 
                       / $previousMargin['gross_margin_percentage']) * 100 
                    : 0
            ];

            // Analyze variance by client
            $varianceAnalysis['variance_by_segment']['client'] = $this->analyzeClientMarginVariance(
                $currentData, 
                $previousData
            );

            // Analyze variance by route
            $varianceAnalysis['variance_by_segment']['route'] = $this->analyzeRouteMarginVariance(
                $currentData, 
                $previousData
            );

            // Identify variance drivers
            $varianceAnalysis['variance_drivers'] = $this->identifyVarianceDrivers(
                $currentData, 
                $previousData
            );

            // Find significant variances
            $varianceAnalysis['significant_variances'] = $this->identifySignificantVariances(
                $varianceAnalysis['variance_by_segment']
            );

            // Impact analysis
            $varianceAnalysis['variance_impact_analysis'] = $this->calculateVarianceImpact(
                $varianceAnalysis['significant_variances']
            );

            return $varianceAnalysis;

        } catch (\Exception $e) {
            Log::error('Margin variance analysis error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate real-time gross margin metrics
     */
    public function calculateRealTimeMargins(array $filters = []): array
    {
        try {
            $realtimeData = $this->getMarginData($filters);
            
            $margins = [
                'current_period' => $this->calculateOverallMargin($realtimeData),
                'intraday_performance' => $this->calculateIntradayPerformance($filters),
                'real_time_trends' => $this->calculateRealTimeTrends($filters),
                'margin_alerts' => [],
                'performance_indicators' => []
            ];

            // Generate alerts for margin thresholds
            $margins['margin_alerts'] = $this->generateMarginAlerts($margins['current_period']);
            
            // Calculate performance indicators
            $margins['performance_indicators'] = $this->calculatePerformanceIndicators($realtimeData);
            
            // Real-time margin trending
            $margins['real_time_trends'] = $this->calculateRealTimeTrends($filters);

            return $margins;

        } catch (\Exception $e) {
            Log::error('Real-time margin calculation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform competitive margin benchmarking
     */
    public function performCompetitiveBenchmarking(array $filters = []): array
    {
        try {
            $benchmarking = [
                'market_position' => 'unknown',
                'competitive_gaps' => [],
                'benchmark_metrics' => [
                    'our_margin' => 0,
                    'industry_average' => 20,
                    'market_leader' => 35,
                    'peer_average' => 25
                ],
                'competitive_analysis' => [],
                'improvement_recommendations' => [],
                'market_opportunities' => []
            ];

            // Get our margin performance
            $ourMargin = $this->calculateOverallMargin($this->getMarginData($filters));
            $benchmarking['benchmark_metrics']['our_margin'] = $ourMargin['gross_margin_percentage'];

            // Determine market position
            $ourMarginValue = $ourMargin['gross_margin_percentage'];
            if ($ourMarginValue >= self::BENCHMARK_MARGIN_EXCELLENT) {
                $benchmarking['market_position'] = 'leader';
            } elseif ($ourMarginValue >= self::BENCHMARK_MARGIN_GOOD) {
                $benchmarking['market_position'] = 'competitive';
            } elseif ($ourMarginValue >= self::BENCHMARK_MARGIN_AVERAGE) {
                $benchmarking['market_position'] = 'average';
            } else {
                $benchmarking['market_position'] = 'below_average';
            }

            // Calculate competitive gaps
            $benchmarking['competitive_gaps'] = [
                'gap_to_industry' => $benchmarking['benchmark_metrics']['industry_average'] - $ourMarginValue,
                'gap_to_leader' => $benchmarking['benchmark_metrics']['market_leader'] - $ourMarginValue,
                'gap_to_peers' => $benchmarking['benchmark_metrics']['peer_average'] - $ourMarginValue
            ];

            // Generate improvement recommendations
            $benchmarking['improvement_recommendations'] = $this->generateImprovementRecommendations(
                $benchmarking['competitive_gaps']
            );

            // Identify market opportunities
            $benchmarking['market_opportunities'] = $this->identifyMarketOpportunities($filters);

            return $benchmarking;

        } catch (\Exception $e) {
            Log::error('Competitive benchmarking error: ' . $e->getMessage());
            throw $e;
        }
    }

    // Private helper methods

    private function getMarginData(array $filters): array
    {
        $query = FactShipment::with(['client', 'route'])
            ->selectRaw('
                SUM(revenue) as total_revenue,
                SUM(total_cost) as total_cost,
                SUM(revenue - total_cost) as gross_profit,
                CASE 
                    WHEN SUM(revenue) > 0 
                    THEN (SUM(revenue - total_cost) / SUM(revenue)) * 100 
                    ELSE 0 
                END as gross_margin_percentage,
                COUNT(*) as shipment_count,
                AVG(revenue) as avg_revenue_per_shipment,
                AVG(total_cost) as avg_cost_per_shipment
            ')
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->byDateRange($dateRange['start'], $dateRange['end']);
            })
            ->when($filters['client_key'] ?? false, function ($q, $clientKey) {
                $q->byClient($clientKey);
            })
            ->when($filters['route_key'] ?? false, function ($q, $routeKey) {
                $q->byRoute($routeKey);
            });

        return $query->first()->toArray();
    }

    private function calculateOverallMargin(array $marginData): array
    {
        return [
            'gross_profit' => $marginData['gross_profit'] ?? 0,
            'total_revenue' => $marginData['total_revenue'] ?? 0,
            'total_cogs' => $marginData['total_cost'] ?? 0,
            'gross_margin_percentage' => $marginData['gross_margin_percentage'] ?? 0,
            'gross_margin_rate' => $marginData['gross_margin_percentage'] ?? 0,
            'shipment_count' => $marginData['shipment_count'] ?? 0,
            'avg_revenue_per_shipment' => $marginData['avg_revenue_per_shipment'] ?? 0,
            'avg_cost_per_shipment' => $marginData['avg_cost_per_shipment'] ?? 0
        ];
    }

    private function analyzeMarginBySegment(array $filters): array
    {
        return [
            'client' => $this->analyzeClientMargins($filters),
            'route' => $this->analyzeRouteMargins($filters),
            'service_type' => $this->analyzeServiceTypeMargins($filters),
            'time_period' => $this->analyzeTimePeriodMargins($filters)
        ];
    }

    private function analyzeClientMargins(array $filters): array
    {
        $query = FactShipment::with('client')
            ->selectRaw('
                client_key,
                client_name,
                SUM(revenue) as total_revenue,
                SUM(total_cost) as total_cost,
                SUM(revenue - total_cost) as gross_profit,
                CASE 
                    WHEN SUM(revenue) > 0 
                    THEN (SUM(revenue - total_cost) / SUM(revenue)) * 100 
                    ELSE 0 
                END as gross_margin_percentage,
                COUNT(*) as shipment_count
            ')
            ->join('dim_clients', 'fact_shipments.client_key', '=', 'dim_clients.client_key')
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->byDateRange($dateRange['start'], $dateRange['end']);
            })
            ->groupBy('client_key', 'client_name')
            ->orderBy('gross_margin_percentage', 'desc')
            ->limit(10);

        return $query->get()->toArray();
    }

    private function analyzeRouteMargins(array $filters): array
    {
        $query = FactShipment::with('route')
            ->selectRaw('
                route_key,
                route_name,
                SUM(revenue) as total_revenue,
                SUM(total_cost) as total_cost,
                SUM(revenue - total_cost) as gross_profit,
                CASE 
                    WHEN SUM(revenue) > 0 
                    THEN (SUM(revenue - total_cost) / SUM(revenue)) * 100 
                    ELSE 0 
                END as gross_margin_percentage,
                COUNT(*) as shipment_count,
                AVG(distance_miles) as avg_distance
            ')
            ->join('dim_routes', 'fact_shipments.route_key', '=', 'dim_routes.route_key')
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->byDateRange($dateRange['start'], $dateRange['end']);
            })
            ->groupBy('route_key', 'route_name')
            ->orderBy('gross_margin_percentage', 'desc')
            ->limit(10);

        return $query->get()->toArray();
    }

    private function analyzeServiceTypeMargins(array $filters): array
    {
        // This would depend on how service types are stored in the system
        return [
            'express' => ['margin' => 35, 'revenue' => 100000],
            'standard' => ['margin' => 20, 'revenue' => 500000],
            'economy' => ['margin' => 15, 'revenue' => 200000]
        ];
    }

    private function analyzeTimePeriodMargins(array $filters): array
    {
        $query = FactShipment::selectRaw('
                DATE_FORMAT(delivery_date, "%Y-%m") as period,
                SUM(revenue) as total_revenue,
                SUM(total_cost) as total_cost,
                SUM(revenue - total_cost) as gross_profit,
                CASE 
                    WHEN SUM(revenue) > 0 
                    THEN (SUM(revenue - total_cost) / SUM(revenue)) * 100 
                    ELSE 0 
                END as gross_margin_percentage,
                COUNT(*) as shipment_count
            ')
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->byDateRange($dateRange['start'], $dateRange['end']);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->limit(12);

        return $query->get()->toArray();
    }

    private function performHistoricalAnalysis(array $filters): array
    {
        $historicalData = $this->getHistoricalMarginData($filters);
        
        return [
            'margin_trends' => $this->calculateHistoricalTrends($historicalData),
            'margin_volatility' => $this->calculateHistoricalVolatility($historicalData),
            'margin_seasonality' => $this->calculateHistoricalSeasonality($historicalData),
            'best_performing_periods' => $this->identifyBestPeriods($historicalData),
            'worst_performing_periods' => $this->identifyWorstPeriods($historicalData)
        ];
    }

    private function getHistoricalMarginData(array $filters): array
    {
        $query = FactShipment::selectRaw('
                DATE_FORMAT(delivery_date, "%Y-%m") as period,
                SUM(revenue) as total_revenue,
                SUM(total_cost) as total_cost,
                SUM(revenue - total_cost) as gross_profit,
                CASE 
                    WHEN SUM(revenue) > 0 
                    THEN (SUM(revenue - total_cost) / SUM(revenue)) * 100 
                    ELSE 0 
                END as gross_margin_percentage
            ')
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->byDateRange($dateRange['start'], $dateRange['end']);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->limit(24);

        return $query->get()->toArray();
    }

    private function calculateHistoricalTrends(array $historicalData): array
    {
        if (count($historicalData) < 2) {
            return ['trend' => 'insufficient_data', 'slope' => 0];
        }

        $n = count($historicalData);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($historicalData as $index => $data) {
            $x = $index + 1;
            $y = $data['gross_margin_percentage'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $avgY = $sumY / $n;
        $growthRate = $avgY > 0 ? ($slope / $avgY) * 100 : 0;

        return [
            'trend' => $slope > 0.1 ? 'improving' : ($slope < -0.1 ? 'declining' : 'stable'),
            'slope' => $slope,
            'growth_rate' => $growthRate,
            'correlation' => $this->calculateCorrelation($historicalData)
        ];
    }

    private function calculateHistoricalVolatility(array $historicalData): float
    {
        if (count($historicalData) < 2) {
            return 0;
        }

        $margins = array_column($historicalData, 'gross_margin_percentage');
        $mean = array_sum($margins) / count($margins);
        
        $variance = 0;
        foreach ($margins as $margin) {
            $variance += pow($margin - $mean, 2);
        }
        
        return sqrt($variance / (count($margins) - 1));
    }

    private function calculateHistoricalSeasonality(array $historicalData): array
    {
        $monthlyAverages = array_fill(1, 12, 0);
        $monthlyCounts = array_fill(1, 12, 0);
        
        foreach ($historicalData as $data) {
            $month = date('n', strtotime($data['period'] . '-01'));
            $monthlyAverages[$month] += $data['gross_margin_percentage'];
            $monthlyCounts[$month]++;
        }
        
        $overallAverage = array_sum(array_column($historicalData, 'gross_margin_percentage')) / count($historicalData);
        $seasonality = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $avg = $monthlyCounts[$month] > 0 
                ? $monthlyAverages[$month] / $monthlyCounts[$month] 
                : $overallAverage;
            $seasonality[$month] = $overallAverage > 0 
                ? ($avg / $overallAverage) * 100 
                : 100;
        }
        
        return $seasonality;
    }

    private function identifyBestPeriods(array $historicalData): array
    {
        $sorted = $historicalData;
        usort($sorted, function($a, $b) {
            return $b['gross_margin_percentage'] <=> $a['gross_margin_percentage'];
        });
        
        return array_slice($sorted, 0, 3);
    }

    private function identifyWorstPeriods(array $historicalData): array
    {
        $sorted = $historicalData;
        usort($sorted, function($a, $b) {
            return $a['gross_margin_percentage'] <=> $b['gross_margin_percentage'];
        });
        
        return array_slice($sorted, 0, 3);
    }

    private function generateShortTermForecasts(array $historicalData, array $trendAnalysis, array $seasonality, int $months): array
    {
        $forecasts = [];
        $lastMargin = end($historicalData)['gross_margin_percentage'] ?? 0;
        
        for ($i = 1; $i <= $months; $i++) {
            $forecastDate = Carbon::now()->addMonths($i);
            $trendValue = $lastMargin + ($trendAnalysis['slope'] * $i);
            $seasonalAdjustment = ($seasonality[$forecastDate->format('n')] ?? 100) / 100;
            
            $forecasts[] = [
                'period' => $forecastDate->format('Y-m'),
                'forecasted_margin' => round($trendValue * $seasonalAdjustment, 2),
                'confidence_level' => max(0.7, 1 - ($i * 0.05))
            ];
        }
        
        return $forecasts;
    }

    private function generateLongTermForecasts(array $historicalData, array $trendAnalysis, array $seasonality, int $months): array
    {
        // Similar to short-term but with lower confidence and more conservative estimates
        $forecasts = $this->generateShortTermForecasts($historicalData, $trendAnalysis, $seasonality, $months);
        
        // Apply conservative adjustment for long-term forecasts
        foreach ($forecasts as &$forecast) {
            $forecast['confidence_level'] *= 0.8; // Reduce confidence for long-term
            $forecast['forecasted_margin'] *= 0.95; // Conservative adjustment
        }
        
        return $forecasts;
    }

    private function calculateForecastConfidence(float $volatility, int $dataPoints): float
    {
        $baseConfidence = 0.8;
        $volatilityPenalty = min(0.3, $volatility / 10); // Penalty for high volatility
        $dataPenalty = max(0, (10 - $dataPoints) * 0.02); // Penalty for insufficient data
        
        return max(0.3, $baseConfidence - $volatilityPenalty - $dataPenalty);
    }

    private function generateTrendPredictions(array $trendAnalysis): array
    {
        $predictions = [];
        
        if ($trendAnalysis['slope'] > 0.1) {
            $predictions[] = 'Margin is expected to improve in the coming periods';
        } elseif ($trendAnalysis['slope'] < -0.1) {
            $predictions[] = 'Margin decline predicted - immediate attention recommended';
        } else {
            $predictions[] = 'Margin expected to remain relatively stable';
        }
        
        if ($trendAnalysis['correlation'] > 0.7) {
            $predictions[] = 'Strong trend pattern detected - forecasts highly reliable';
        }
        
        return $predictions;
    }

    private function generateScenarioAnalysis(array $shortTerm, array $longTerm): array
    {
        $avgShortTerm = count($shortTerm) > 0 
            ? array_sum(array_column($shortTerm, 'forecasted_margin')) / count($shortTerm) 
            : 0;
        $avgLongTerm = count($longTerm) > 0 
            ? array_sum(array_column($longTerm, 'forecasted_margin')) / count($longTerm) 
            : 0;
        
        return [
            'optimistic' => [
                'short_term' => $avgShortTerm * 1.1,
                'long_term' => $avgLongTerm * 1.15,
                'probability' => 20
            ],
            'most_likely' => [
                'short_term' => $avgShortTerm,
                'long_term' => $avgLongTerm,
                'probability' => 60
            ],
            'pessimistic' => [
                'short_term' => $avgShortTerm * 0.9,
                'long_term' => $avgLongTerm * 0.85,
                'probability' => 20
            ]
        ];
    }

    private function adjustFiltersForPreviousPeriod(array $filters): array
    {
        // Logic to adjust date range to previous period
        // This is a simplified implementation
        $adjustedFilters = $filters;
        
        if (isset($filters['date_range'])) {
            // Assuming date range is monthly, shift back by one month
            // In practice, this would need more sophisticated date arithmetic
            $adjustedFilters['date_range']['start'] = date('Ymd', strtotime('-1 month', strtotime($filters['date_range']['start'])));
            $adjustedFilters['date_range']['end'] = date('Ymd', strtotime('-1 month', strtotime($filters['date_range']['end'])));
        }
        
        return $adjustedFilters;
    }

    private function analyzeClientMarginVariance(array $currentData, array $previousData): array
    {
        // This would compare client margins between periods
        // Implementation would depend on how data is structured
        return []; // Placeholder
    }

    private function analyzeRouteMarginVariance(array $currentData, array $previousData): array
    {
        // This would compare route margins between periods
        return []; // Placeholder
    }

    private function identifyVarianceDrivers(array $currentData, array $previousData): array
    {
        return [
            'revenue_drivers' => ['volume_change', 'pricing_change', 'mix_shift'],
            'cost_drivers' => ['fuel_costs', 'labor_costs', 'operational_efficiency'],
            'volume_drivers' => ['shipment_count', 'route_optimization', 'capacity_utilization']
        ];
    }

    private function identifySignificantVariances(array $varianceBySegment): array
    {
        $significant = [];
        
        foreach ($varianceBySegment as $segmentType => $variances) {
            foreach ($variances as $variance) {
                if (abs($variance['variance_percentage'] ?? 0) > 10) { // 10% threshold
                    $significant[] = [
                        'segment_type' => $segmentType,
                        'segment_name' => $variance['name'] ?? 'Unknown',
                        'variance_percentage' => $variance['variance_percentage'] ?? 0,
                        'impact' => $this->calculateVarianceImpact($variance)
                    ];
                }
            }
        }
        
        return $significant;
    }

    private function calculateVarianceImpact(array $variance): float
    {
        // Calculate the financial impact of the variance
        return abs($variance['variance_percentage'] ?? 0) * ($variance['revenue'] ?? 0) / 100;
    }

    private function performVarianceAnalysis(array $filters): array
    {
        // Get current and previous period analysis
        $currentAnalysis = $this->analyzeMarginBySegment($filters);
        $previousFilters = $this->adjustFiltersForPreviousPeriod($filters);
        $previousAnalysis = $this->analyzeMarginBySegment($previousFilters);
        
        return [
            'margin_variance' => 0, // Calculate overall margin variance
            'variance_drivers' => $this->identifyVarianceDrivers($currentAnalysis, $previousAnalysis),
            'significant_variances' => $this->identifySignificantVariances($currentAnalysis),
            'variance_impact' => 0 // Calculate total variance impact
        ];
    }

    private function calculateIntradayPerformance(array $filters): array
    {
        // Calculate performance metrics for current day
        return [
            'current_margin' => 0,
            'margin_trend' => 'stable',
            'alerts' => []
        ];
    }

    private function calculateRealTimeTrends(array $filters): array
    {
        // Calculate real-time trending data
        return [
            'hourly_trend' => [],
            'daily_trend' => [],
            'alerts' => []
        ];
    }

    private function generateMarginAlerts(array $currentMargin): array
    {
        $alerts = [];
        
        if ($currentMargin['gross_margin_percentage'] < 10) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Gross margin below 10% threshold',
                'value' => $currentMargin['gross_margin_percentage']
            ];
        }
        
        if ($currentMargin['gross_margin_percentage'] < 5) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Gross margin critically low',
                'value' => $currentMargin['gross_margin_percentage']
            ];
        }
        
        return $alerts;
    }

    private function calculatePerformanceIndicators(array $realtimeData): array
    {
        return [
            'margin_efficiency' => 0,
            'cost_control' => 0,
            'revenue_quality' => 0,
            'overall_score' => 0
        ];
    }

    private function performCompetitiveAnalysis(array $overallMargin): array
    {
        $ourMargin = $overallMargin['gross_margin_percentage'];
        
        return [
            'industry_benchmark' => 20,
            'competitive_position' => $this->determineCompetitivePosition($ourMargin),
            'margin_gap_analysis' => $this->calculateMarginGaps($ourMargin),
            'improvement_opportunities' => $this->identifyImprovementOpportunities($ourMargin)
        ];
    }

    private function determineCompetitivePosition(float $ourMargin): string
    {
        if ($ourMargin >= 40) return 'market_leader';
        if ($ourMargin >= 25) return 'competitive';
        if ($ourMargin >= 15) return 'average';
        return 'below_average';
    }

    private function calculateMarginGaps(float $ourMargin): array
    {
        return [
            'gap_to_industry' => 20 - $ourMargin,
            'gap_to_leader' => 35 - $ourMargin,
            'gap_to_target' => 30 - $ourMargin
        ];
    }

    private function identifyImprovementOpportunities(float $ourMargin): array
    {
        $opportunities = [];
        
        if ($ourMargin < 20) {
            $opportunities[] = 'Focus on cost reduction initiatives';
            $opportunities[] = 'Review pricing strategy';
        }
        
        if ($ourMargin < 15) {
            $opportunities[] = 'Immediate action required on margin improvement';
            $opportunities[] = 'Consider service mix optimization';
        }
        
        return $opportunities;
    }

    private function calculateCorrelation(array $historicalData): float
    {
        if (count($historicalData) < 2) return 0;
        
        $n = count($historicalData);
        $xValues = range(1, $n);
        $yValues = array_column($historicalData, 'gross_margin_percentage');
        
        $meanX = array_sum($xValues) / $n;
        $meanY = array_sum($yValues) / $n;
        
        $numerator = 0;
        $denominatorX = 0;
        $denominatorY = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($xValues[$i] - $meanX) * ($yValues[$i] - $meanY);
            $denominatorX += pow($xValues[$i] - $meanX, 2);
            $denominatorY += pow($yValues[$i] - $meanY, 2);
        }
        
        $denominator = sqrt($denominatorX * $denominatorY);
        
        return $denominator > 0 ? $numerator / $denominator : 0;
    }

    private function identifyMarketOpportunities(array $filters): array
    {
        return [
            'high_margin_segments' => ['express_delivery', 'same_day_service'],
            'geographic_opportunities' => ['urban_markets', 'specialty_routes'],
            'service_expansion' => ['premium_services', 'value_added_services']
        ];
    }

    private function generateImprovementRecommendations(array $gaps): array
    {
        $recommendations = [];
        
        if ($gaps['gap_to_industry'] > 5) {
            $recommendations[] = 'Implement cost optimization program';
        }
        
        if ($gaps['gap_to_leader'] > 10) {
            $recommendations[] = 'Benchmark against market leaders for best practices';
        }
        
        return $recommendations;
    }
}