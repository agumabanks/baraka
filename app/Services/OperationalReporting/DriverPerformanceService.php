<?php

namespace App\Services\OperationalReporting;

use App\Models\ETL\FactShipment;
use App\Models\ETL\FactPerformanceMetrics;
use App\Models\ETL\DimensionDriver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DriverPerformanceService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const HOS_LIMITS = [
        'driving_hours_daily' => 11,
        'driving_hours_weekly' => 60,
        'on_duty_hours_daily' => 14,
        'break_required_hours' => 8
    ];

    /**
     * Calculate stops per hour for a driver
     */
    public function calculateStopsPerHour(string $driverKey, array $dateRange): array
    {
        $cacheKey = "stops_per_hour_{$driverKey}_{$dateRange['start']}_{$dateRange['end']}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($driverKey, $dateRange) {
            $shipments = FactShipment::where('driver_key', $driverKey)
                ->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']])
                ->get();

            if ($shipments->isEmpty()) {
                return [
                    'driver_key' => $driverKey,
                    'performance_data' => null,
                    'message' => 'No data available for the specified period'
                ];
            }

            $driver = DimensionDriver::find($driverKey);
            $totalStops = $shipments->sum('stops_count');
            $totalHours = $this->calculateWorkingHours($shipments);
            
            $stopsPerHour = $totalHours > 0 ? $totalStops / $totalHours : 0;
            
            // Calculate performance metrics
            $dailyAverages = $this->calculateDailyAverages($shipments);
            $efficiencyScore = $this->calculateDriverEfficiencyScore($shipments);
            
            return [
                'driver_key' => $driverKey,
                'driver_info' => [
                    'name' => $driver?->driver_name ?? 'Unknown',
                    'license_class' => $driver?->license_class ?? 'Unknown',
                    'experience_years' => $driver?->experience_years ?? 0,
                    'safety_rating' => $driver?->safety_rating ?? 0
                ],
                'performance_metrics' => [
                    'total_stops' => (int) $totalStops,
                    'total_hours_worked' => round($totalHours, 2),
                    'stops_per_hour' => round($stopsPerHour, 2),
                    'average_stops_per_delivery' => round($shipments->avg('stops_count'), 2),
                    'efficiency_score' => round($efficiencyScore, 2)
                ],
                'daily_breakdown' => $dailyAverages,
                'performance_grade' => $this->getPerformanceGrade($stopsPerHour, $efficiencyScore),
                'comparative_analysis' => $this->compareWithFleetAverage($stopsPerHour, $efficiencyScore)
            ];
        });
    }

    /**
     * Track miles per gallon for drivers
     */
    public function trackMilesPerGallon(string $driverKey, array $dateRange): array
    {
        $cacheKey = "mpg_tracking_{$driverKey}_{$dateRange['start']}_{$dateRange['end']}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($driverKey, $dateRange) {
            $shipments = FactShipment::where('driver_key', $driverKey)
                ->whereBetween('delivery_date_key', [$dateRange['start'], $dateRange['end']])
                ->get();

            if ($shipments->isEmpty()) {
                return [
                    'driver_key' => $driverKey,
                    'fuel_efficiency' => null,
                    'message' => 'No data available for the specified period'
                ];
            }

            $driver = DimensionDriver::find($driverKey);
            $totalMiles = $shipments->sum('distance_miles');
            $totalFuelCost = $shipments->sum('fuel_cost');
            
            // Estimate gallons used (assuming $3.50 per gallon average)
            $averageFuelPrice = 3.50;
            $gallonsUsed = $averageFuelPrice > 0 ? $totalFuelCost / $averageFuelPrice : 0;
            $mpg = $gallonsUsed > 0 ? $totalMiles / $gallonsUsed : 0;
            
            $fuelEfficiencyTrends = $this->analyzeFuelEfficiencyTrends($shipments);
            
            return [
                'driver_key' => $driverKey,
                'driver_info' => [
                    'name' => $driver?->driver_name ?? 'Unknown',
                    'vehicle_type' => $driver?->vehicle_type ?? 'Unknown'
                ],
                'fuel_efficiency_metrics' => [
                    'total_miles_driven' => round($totalMiles, 2),
                    'total_fuel_cost' => round($totalFuelCost, 2),
                    'estimated_gallons_used' => round($gallonsUsed, 2),
                    'miles_per_gallon' => round($mpg, 2),
                    'cost_per_mile' => $totalMiles > 0 ? round($totalFuelCost / $totalMiles, 2) : 0
                ],
                'efficiency_trends' => $fuelEfficiencyTrends,
                'benchmark_comparison' => $this->compareFuelEfficiency($mpg, $driver?->vehicle_type),
                'optimization_opportunities' => $this->getFuelOptimizationSuggestions($mpg, $driver?->vehicle_type)
            ];
        });
    }

    /**
     * Monitor hours of service compliance
     */
    public function monitorHoursOfService(string $driverKey, array $dateRange): array
    {
        $cacheKey = "hos_compliance_{$driverKey}_{$dateRange['start']}_{$dateRange['end']}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($driverKey, $dateRange) {
            $metrics = FactPerformanceMetrics::where('driver_key', $driverKey)
                ->where('kpi_category', 'hours_of_service')
                ->whereBetween('metric_date_key', [$dateRange['start'], $dateRange['end']])
                ->get();

            $driver = DimensionDriver::find($driverKey);

            if ($metrics->isEmpty()) {
                return [
                    'driver_key' => $driverKey,
                    'compliance_status' => 'no_data',
                    'message' => 'No HOS data available for the specified period'
                ];
            }

            $dailyCompliance = $this->analyzeDailyCompliance($metrics);
            $violations = $this->identifyHOSViolations($metrics);
            $complianceScore = $this->calculateComplianceScore($metrics);
            
            return [
                'driver_key' => $driverKey,
                'driver_info' => [
                    'name' => $driver?->driver_name ?? 'Unknown',
                    'hos_compliant' => $driver?->hours_of_service_compliance ?? false
                ],
                'compliance_summary' => [
                    'overall_compliance_score' => round($complianceScore, 2),
                    'total_driving_hours' => round($metrics->where('metric_name', 'driving_hours')->sum('metric_value'), 2),
                    'total_on_duty_hours' => round($metrics->where('metric_name', 'on_duty_hours')->sum('metric_value'), 2),
                    'violation_count' => count($violations),
                    'compliance_percentage' => $this->calculateCompliancePercentage($metrics)
                ],
                'daily_compliance' => $dailyCompliance,
                'violations' => $violations,
                'regulatory_compliance' => [
                    'daily_driving_limit' => self::HOS_LIMITS['driving_hours_daily'],
                    'weekly_driving_limit' => self::HOS_LIMITS['driving_hours_weekly'],
                    'daily_on_duty_limit' => self::HOS_LIMITS['on_duty_hours_daily'],
                    'break_requirement' => self::HOS_LIMITS['break_required_hours']
                ],
                'recommendations' => $this->getComplianceRecommendations($violations, $complianceScore)
            ];
        });
    }

    /**
     * Generate driver ranking and comparison
     */
    public function generateDriverRanking(string $period = 'monthly', int $months = 3): array
    {
        $cacheKey = "driver_ranking_{$period}_{$months}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period, $months) {
            $startDate = now()->subMonths($months)->format('Ymd');
            $endDate = now()->format('Ymd');

            $drivers = FactShipment::select(
                'driver_key',
                DB::raw('COUNT(*) as total_shipments'),
                DB::raw('SUM(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) as on_time_shipments'),
                DB::raw('AVG(CASE WHEN on_time_indicator = true THEN 1 ELSE 0 END) * 100 as on_time_rate'),
                DB::raw('SUM(distance_miles) as total_miles'),
                DB::raw('AVG(route_efficiency_score) as avg_efficiency_score'),
                DB::raw('SUM(stops_count) as total_stops')
            )
                ->whereBetween('delivery_date_key', [$startDate, $endDate])
                ->groupBy('driver_key')
                ->having('total_shipments', '>=', 10) // Minimum sample size
                ->get();

            $driverRankings = [];
            
            foreach ($drivers as $driver) {
                $driverInfo = DimensionDriver::find($driver->driver_key);
                
                // Calculate composite performance score
                $performanceScore = $this->calculateCompositePerformanceScore($driver);
                
                $driverRankings[] = [
                    'driver_key' => $driver->driver_key,
                    'driver_info' => [
                        'name' => $driverInfo?->driver_name ?? 'Unknown',
                        'experience_years' => $driverInfo?->experience_years ?? 0,
                        'safety_rating' => $driverInfo?->safety_rating ?? 0
                    ],
                    'performance_metrics' => [
                        'total_shipments' => (int) $driver->total_shipments,
                        'on_time_rate' => round($driver->on_time_rate, 2),
                        'efficiency_score' => round($driver->avg_efficiency_score, 2),
                        'stops_per_hour' => $this->calculateStopsPerHourForRanking($driver),
                        'miles_per_shipment' => $driver->total_shipments > 0 ? round($driver->total_miles / $driver->total_shipments, 2) : 0
                    ],
                    'composite_score' => round($performanceScore, 2),
                    'performance_category' => $this->categorizePerformance($performanceScore)
                ];
            }

            // Sort by composite score
            usort($driverRankings, function ($a, $b) {
                return $b['composite_score'] - $a['composite_score'];
            });

            // Add rankings
            foreach ($driverRankings as $index => &$driver) {
                $driver['rank'] = $index + 1;
                $driver['percentile'] = round((($index + 1) / count($driverRankings)) * 100, 2);
            }

            return [
                'ranking_period' => $period,
                'analysis_period' => ['start' => $startDate, 'end' => $endDate],
                'total_drivers_ranked' => count($driverRankings),
                'driver_rankings' => $driverRankings,
                'fleet_summary' => [
                    'top_performer' => $driverRankings[0] ?? null,
                    'bottom_performer' => end($driverRankings) ?? null,
                    'average_score' => count($driverRankings) > 0 ? round(array_sum(array_column($driverRankings, 'composite_score')) / count($driverRankings), 2) : 0,
                    'score_distribution' => $this->analyzeScoreDistribution($driverRankings)
                ]
            ];
        });
    }

    /**
     * Get driver safety incidents monitoring
     */
    public function monitorSafetyIncidents(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('safety_incidents', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = FactShipment::whereHas('driver')
                ->where(function ($q) {
                    $q->where('exception_flag', true)
                      ->where('exception_type', 'security');
                });

            $this->applyFilters($query, $filters);

            $safetyIncidents = $query->get();

            $incidentAnalysis = [
                'summary' => [
                    'total_incidents' => $safetyIncidents->count(),
                    'incident_rate' => $this->calculateIncidentRate($safetyIncidents),
                    'most_common_incident_type' => $this->getMostCommonIncidentType($safetyIncidents),
                    'high_risk_drivers' => $this->identifyHighRiskDrivers($safetyIncidents)
                ],
                'incident_breakdown' => $this->breakdownIncidentsByType($safetyIncidents),
                'temporal_analysis' => $this->analyzeIncidentPatterns($safetyIncidents),
                'cost_impact' => [
                    'total_cost' => round($safetyIncidents->sum('total_cost'), 2),
                    'average_cost_per_incident' => round($safetyIncidents->avg('total_cost') ?? 0, 2)
                ],
                'preventive_measures' => $this->suggestPreventiveMeasures($safetyIncidents)
            ];

            return $incidentAnalysis;
        });
    }

    // Private helper methods
    private function calculateWorkingHours(Collection $shipments): float
    {
        // This is a simplified calculation
        // In reality, you'd need actual clock in/out data
        return $shipments->sum('transit_time_hours') * 1.2; // Include pre/post trip time
    }

    private function calculateDailyAverages(Collection $shipments): array
    {
        $dailyData = [];
        $grouped = $shipments->groupBy('delivery_date_key');
        
        foreach ($grouped as $dateKey => $dayShipments) {
            $dailyData[] = [
                'date' => $dateKey,
                'stops' => (int) $dayShipments->sum('stops_count'),
                'hours' => round($this->calculateWorkingHours($dayShipments), 2),
                'stops_per_hour' => round($this->calculateWorkingHours($dayShipments) > 0 ? $dayShipments->sum('stops_count') / $this->calculateWorkingHours($dayShipments) : 0, 2)
            ];
        }
        
        return $dailyData;
    }

    private function calculateDriverEfficiencyScore(Collection $shipments): float
    {
        $onTimeRate = ($shipments->where('on_time_indicator', true)->count() / $shipments->count()) * 100;
        $avgEfficiency = $shipments->avg('route_efficiency_score') ?? 0;
        
        return ($onTimeRate * 0.4) + ($avgEfficiency * 0.6);
    }

    private function getPerformanceGrade(float $stopsPerHour, float $efficiencyScore): string
    {
        $compositeScore = ($stopsPerHour * 10 * 0.6) + ($efficiencyScore * 0.4);
        
        if ($compositeScore >= 90) return 'A+';
        if ($compositeScore >= 85) return 'A';
        if ($compositeScore >= 80) return 'A-';
        if ($compositeScore >= 75) return 'B+';
        if ($compositeScore >= 70) return 'B';
        if ($compositeScore >= 65) return 'B-';
        if ($compositeScore >= 60) return 'C+';
        if ($compositeScore >= 55) return 'C';
        return 'D';
    }

    private function compareWithFleetAverage(float $stopsPerHour, float $efficiencyScore): array
    {
        // This would compare with fleet averages
        $fleetAvgStops = 8.5; // Example fleet average
        $fleetAvgEfficiency = 75.0;
        
        return [
            'stops_per_hour_vs_fleet' => [
                'driver' => $stopsPerHour,
                'fleet_average' => $fleetAvgStops,
                'difference' => round($stopsPerHour - $fleetAvgStops, 2),
                'performance' => $stopsPerHour > $fleetAvgStops ? 'above_average' : 'below_average'
            ],
            'efficiency_vs_fleet' => [
                'driver' => $efficiencyScore,
                'fleet_average' => $fleetAvgEfficiency,
                'difference' => round($efficiencyScore - $fleetAvgEfficiency, 2),
                'performance' => $efficiencyScore > $fleetAvgEfficiency ? 'above_average' : 'below_average'
            ]
        ];
    }

    private function analyzeFuelEfficiencyTrends(Collection $shipments): array
    {
        // Group by date and calculate trends
        $dailyData = [];
        $grouped = $shipments->groupBy('delivery_date_key');
        
        foreach ($grouped as $dateKey => $dayShipments) {
            $totalMiles = $dayShipments->sum('distance_miles');
            $totalFuelCost = $dayShipments->sum('fuel_cost');
            $mpg = $totalFuelCost > 0 ? $totalMiles / ($totalFuelCost / 3.5) : 0;
            
            $dailyData[] = [
                'date' => $dateKey,
                'miles' => round($totalMiles, 2),
                'fuel_cost' => round($totalFuelCost, 2),
                'mpg' => round($mpg, 2)
            ];
        }
        
        return [
            'daily_data' => $dailyData,
            'trend' => $this->calculateEfficiencyTrend($dailyData)
        ];
    }

    private function calculateEfficiencyTrend(array $dailyData): string
    {
        if (count($dailyData) < 2) return 'insufficient_data';
        
        $mpgValues = array_column($dailyData, 'mpg');
        $recentAvg = array_sum(array_slice($mpgValues, -3)) / min(3, count($mpgValues));
        $earlierAvg = array_sum(array_slice($mpgValues, 0, 3)) / min(3, count($mpgValues));
        
        $change = (($recentAvg - $earlierAvg) / $earlierAvg) * 100;
        
        return $change > 2 ? 'improving' : ($change < -2 ? 'declining' : 'stable');
    }

    private function compareFuelEfficiency(float $mpg, ?string $vehicleType): array
    {
        $benchmarks = [
            'van' => 12.0,
            'truck' => 8.0,
            'semi' => 6.5
        ];
        
        $benchmark = $benchmarks[strtolower($vehicleType)] ?? 10.0;
        
        return [
            'driver_mpg' => $mpg,
            'vehicle_type' => $vehicleType,
            'benchmark_mpg' => $benchmark,
            'performance' => $mpg > $benchmark ? 'above_benchmark' : 'below_benchmark',
            'efficiency_rating' => $this->getEfficiencyRating($mpg, $benchmark)
        ];
    }

    private function getFuelOptimizationSuggestions(float $mpg, ?string $vehicleType): array
    {
        $suggestions = [];
        
        if ($mpg < 8) {
            $suggestions[] = [
                'category' => 'driving_behavior',
                'suggestion' => 'Implement eco-driving training programs',
                'potential_savings' => '15-20% fuel efficiency improvement'
            ];
        }
        
        if ($mpg < 10) {
            $suggestions[] = [
                'category' => 'vehicle_maintenance',
                'suggestion' => 'Schedule regular maintenance checks',
                'potential_savings' => '10-15% fuel efficiency improvement'
            ];
        }
        
        $suggestions[] = [
            'category' => 'route_optimization',
            'suggestion' => 'Use route optimization software',
            'potential_savings' => '5-10% fuel savings'
        ];
        
        return $suggestions;
    }

    private function getEfficiencyRating(float $mpg, float $benchmark): string
    {
        $performance = ($mpg / $benchmark) * 100;
        
        if ($performance >= 110) return 'excellent';
        if ($performance >= 95) return 'good';
        if ($performance >= 85) return 'average';
        if ($performance >= 75) return 'below_average';
        return 'poor';
    }

    private function analyzeDailyCompliance(Collection $metrics): array
    {
        $dailyCompliance = [];
        $grouped = $metrics->groupBy('metric_date_key');
        
        foreach ($grouped as $dateKey => $dayMetrics) {
            $compliance = true;
            $violations = [];
            
            foreach ($dayMetrics as $metric) {
                if ($metric->metric_name === 'driving_hours' && $metric->metric_value > self::HOS_LIMITS['driving_hours_daily']) {
                    $compliance = false;
                    $violations[] = 'daily_driving_limit_exceeded';
                }
                
                if ($metric->metric_name === 'on_duty_hours' && $metric->metric_value > self::HOS_LIMITS['on_duty_hours_daily']) {
                    $compliance = false;
                    $violations[] = 'daily_on_duty_limit_exceeded';
                }
            }
            
            $dailyCompliance[] = [
                'date' => $dateKey,
                'compliant' => $compliance,
                'violations' => $violations
            ];
        }
        
        return $dailyCompliance;
    }

    private function identifyHOSViolations(Collection $metrics): array
    {
        $violations = [];
        
        foreach ($metrics as $metric) {
            if ($metric->metric_name === 'driving_hours' && $metric->metric_value > self::HOS_LIMITS['driving_hours_daily']) {
                $violations[] = [
                    'date' => $metric->metric_date_key,
                    'type' => 'daily_driving_limit',
                    'hours' => $metric->metric_value,
                    'limit' => self::HOS_LIMITS['driving_hours_daily'],
                    'excess' => $metric->metric_value - self::HOS_LIMITS['driving_hours_daily']
                ];
            }
        }
        
        return $violations;
    }

    private function calculateComplianceScore(Collection $metrics): float
    {
        $totalDays = $metrics->groupBy('metric_date_key')->count();
        $compliantDays = 0;
        
        foreach ($metrics->groupBy('metric_date_key') as $dayMetrics) {
            $isCompliant = true;
            
            foreach ($dayMetrics as $metric) {
                if (($metric->metric_name === 'driving_hours' && $metric->metric_value > self::HOS_LIMITS['driving_hours_daily']) ||
                    ($metric->metric_name === 'on_duty_hours' && $metric->metric_value > self::HOS_LIMITS['on_duty_hours_daily'])) {
                    $isCompliant = false;
                    break;
                }
            }
            
            if ($isCompliant) $compliantDays++;
        }
        
        return $totalDays > 0 ? ($compliantDays / $totalDays) * 100 : 0;
    }

    private function calculateCompliancePercentage(Collection $metrics): float
    {
        $totalChecks = $metrics->count();
        $compliantChecks = 0;
        
        foreach ($metrics as $metric) {
            $isCompliant = true;
            
            if ($metric->metric_name === 'driving_hours' && $metric->metric_value > self::HOS_LIMITS['driving_hours_daily']) {
                $isCompliant = false;
            }
            
            if ($metric->metric_name === 'on_duty_hours' && $metric->metric_value > self::HOS_LIMITS['on_duty_hours_daily']) {
                $isCompliant = false;
            }
            
            if ($isCompliant) $compliantChecks++;
        }
        
        return $totalChecks > 0 ? ($compliantChecks / $totalChecks) * 100 : 0;
    }

    private function getComplianceRecommendations(array $violations, float $complianceScore): array
    {
        $recommendations = [];
        
        if ($complianceScore < 90) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Review driver scheduling and route planning',
                'expected_impact' => 'Improved HOS compliance'
            ];
        }
        
        if (count($violations) > 5) {
            $recommendations[] = [
                'priority' => 'critical',
                'action' => 'Implement real-time HOS monitoring system',
                'expected_impact' => 'Prevent regulatory violations'
            ];
        }
        
        return $recommendations;
    }

    private function calculateCompositePerformanceScore($driverData): float
    {
        $onTimeRate = $driverData->on_time_rate;
        $efficiencyScore = $driverData->avg_efficiency_score;
        $stopsPerHour = $this->calculateStopsPerHourForRanking($driverData);
        
        // Weighted composite score
        return ($onTimeRate * 0.4) + ($efficiencyScore * 0.4) + (min($stopsPerHour * 10, 100) * 0.2);
    }

    private function calculateStopsPerHourForRanking($driverData): float
    {
        $totalHours = $driverData->total_miles / 45; // Assume 45 mph average
        return $totalHours > 0 ? $driverData->total_stops / $totalHours : 0;
    }

    private function categorizePerformance(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'satisfactory';
        if ($score >= 60) return 'needs_improvement';
        return 'poor';
    }

    private function analyzeScoreDistribution(array $rankings): array
    {
        $scores = array_column($rankings, 'composite_score');
        
        return [
            'highest' => max($scores),
            'lowest' => min($scores),
            'median' => $this->calculateMedian($scores),
            'standard_deviation' => $this->calculateStandardDeviation($scores)
        ];
    }

    private function calculateMedian(array $scores): float
    {
        sort($scores);
        $count = count($scores);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($scores[$middle - 1] + $scores[$middle]) / 2;
        } else {
            return $scores[$middle];
        }
    }

    private function calculateStandardDeviation(array $scores): float
    {
        $mean = array_sum($scores) / count($scores);
        $variance = array_sum(array_map(function($score) use ($mean) {
            return pow($score - $mean, 2);
        }, $scores)) / count($scores);
        
        return sqrt($variance);
    }

    private function calculateIncidentRate(Collection $incidents): float
    {
        // This would need total shipments for the period
        $totalShipments = FactShipment::whereBetween('delivery_date_key', [
            now()->subMonths(1)->format('Ymd'),
            now()->format('Ymd')
        ])->count();
        
        return $totalShipments > 0 ? ($incidents->count() / $totalShipments) * 100 : 0;
    }

    private function getMostCommonIncidentType(Collection $incidents): string
    {
        $types = $incidents->groupBy('exception_type')->map->count();
        return $types->sortDesc()->keys()->first() ?? 'none';
    }

    private function identifyHighRiskDrivers(Collection $incidents): array
    {
        $driverIncidents = $incidents->groupBy('driver_key')->map->count();
        return $driverIncidents->sortDesc()->take(5)->keys()->toArray();
    }

    private function breakdownIncidentsByType(Collection $incidents): array
    {
        $breakdown = [];
        $grouped = $incidents->groupBy('exception_type');
        
        foreach ($grouped as $type => $typeIncidents) {
            $breakdown[$type] = [
                'count' => $typeIncidents->count(),
                'percentage' => round(($typeIncidents->count() / $incidents->count()) * 100, 2)
            ];
        }
        
        return $breakdown;
    }

    private function analyzeIncidentPatterns(Collection $incidents): array
    {
        $byHour = $incidents->groupBy(function($incident) {
            return date('H', strtotime($incident->actual_delivery_time));
        })->map->count();
        
        $byDayOfWeek = $incidents->groupBy(function($incident) {
            return date('N', strtotime($incident->actual_delivery_time));
        })->map->count();
        
        return [
            'peak_hours' => $byHour->sortDesc()->take(3)->keys()->toArray(),
            'peak_days' => $byDayOfWeek->sortDesc()->take(3)->keys()->toArray()
        ];
    }

    private function suggestPreventiveMeasures(Collection $incidents): array
    {
        return [
            'Enhanced driver training programs',
            'Regular safety meetings and briefings',
            'Implement incident reporting system',
            'Regular vehicle safety inspections',
            'Install additional safety equipment'
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['date_range'])) {
            $query->whereBetween('delivery_date_key', [$filters['date_range']['start'], $filters['date_range']['end']]);
        }
        
        if (isset($filters['driver_key'])) {
            $query->where('driver_key', $filters['driver_key']);
        }
    }

    private function generateCacheKey(string $type, array $params): string
    {
        ksort($params);
        return "driver_performance_{$type}_" . md5(serialize($params));
    }
}