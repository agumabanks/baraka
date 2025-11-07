<?php

namespace App\Services\OperationalReporting;

use App\Models\ETL\FactShipment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ExceptionAnalysisService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const EXCEPTION_TYPES = [
        'damaged' => 'Cargo damage or physical harm',
        'delayed' => 'Delivery delays beyond SLA',
        'lost' => 'Shipment loss or misplacement',
        'returned' => 'Returns due to various reasons',
        'security' => 'Security-related issues',
        'documentation' => 'Documentation errors',
        'customs' => 'Customs clearance problems',
        'weather' => 'Weather-related delays',
        'traffic' => 'Traffic congestion delays',
        'vehicle' => 'Vehicle mechanical issues'
    ];

    /**
     * Categorize exceptions and provide frequency analysis
     */
    public function categorizeExceptions(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('exception_categorization', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = FactShipment::where('exception_flag', true)
                ->select(
                    'exception_type',
                    DB::raw('COUNT(*) as total_exceptions'),
                    DB::raw('SUM(late_penalty_cost) as total_penalty_cost'),
                    DB::raw('AVG(late_penalty_cost) as avg_penalty_cost'),
                    DB::raw('COUNT(CASE WHEN on_time_indicator = false THEN 1 END) as delayed_count'),
                    DB::raw('SUM(total_cost) as total_operational_cost')
                )
                ->groupBy('exception_type');

            $this->applyFilters($query, $filters);

            $results = $query->get();

            $totalExceptions = $results->sum('total_exceptions');
            $totalPenaltyCost = $results->sum('total_penalty_cost');

            $categorized = $results->map(function ($item) use ($totalExceptions, $totalPenaltyCost) {
                return [
                    'exception_type' => $item->exception_type,
                    'type_description' => self::EXCEPTION_TYPES[$item->exception_type] ?? 'Unknown exception type',
                    'frequency' => [
                        'count' => (int) $item->total_exceptions,
                        'percentage' => $totalExceptions > 0 ? round(($item->total_exceptions / $totalExceptions) * 100, 2) : 0
                    ],
                    'financial_impact' => [
                        'total_penalty_cost' => round($item->total_penalty_cost, 2),
                        'avg_penalty_per_incident' => round($item->avg_penalty_cost, 2),
                        'percentage_of_total_penalties' => $totalPenaltyCost > 0 ? round(($item->total_penalty_cost / $totalPenaltyCost) * 100, 2) : 0,
                        'operational_cost' => round($item->total_operational_cost, 2)
                    ],
                    'correlation_metrics' => [
                        'delayed_count' => (int) $item->delayed_count,
                        'delay_correlation' => round(($item->delayed_count / $item->total_exceptions) * 100, 2)
                    ]
                ];
            })->values()->toArray();

            // Sort by frequency
            usort($categorized, function ($a, $b) {
                return $b['frequency']['count'] - $a['frequency']['count'];
            });

            return [
                'summary' => [
                    'total_exceptions' => (int) $totalExceptions,
                    'total_penalty_cost' => round($totalPenaltyCost, 2),
                    'unique_exception_types' => count($categorized),
                    'avg_exceptions_per_day' => $this->calculateAverageExceptionsPerDay($totalExceptions, $filters)
                ],
                'categorized_exceptions' => $categorized,
                'trends' => $this->analyzeExceptionTrends($filters),
                'risk_assessment' => $this->performRiskAssessment($categorized)
            ];
        });
    }

    /**
     * Perform root cause analysis for specific exceptions
     */
    public function performRootCauseAnalysis(string $exceptionType, array $filters = []): array
    {
        $cacheKey = "root_cause_{$exceptionType}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($exceptionType, $filters) {
            $query = FactShipment::where('exception_flag', true)
                ->where('exception_type', $exceptionType)
                ->select(
                    DB::raw('COUNT(*) as total_incidents'),
                    DB::raw('AVG(transit_time_hours) as avg_transit_time'),
                    DB::raw('SUM(total_cost) as total_cost'),
                    DB::raw('SUM(late_penalty_cost) as total_penalty_cost'),
                    'route_key',
                    'origin_branch_key',
                    'destination_branch_key',
                    'driver_key',
                    'carrier_key'
                )
                ->groupBy('route_key', 'origin_branch_key', 'destination_branch_key', 'driver_key', 'carrier_key');

            $this->applyFilters($query, $filters);

            $results = $query->get();

            $rootCauses = [];
            foreach ($results->groupBy('route_key') as $routeKey => $routeExceptions) {
                $rootCauses[] = [
                    'cause_category' => 'route_related',
                    'identifier' => "Route {$routeKey}",
                    'incident_count' => $routeExceptions->sum('total_incidents'),
                    'impact_metrics' => [
                        'total_cost_impact' => round($routeExceptions->sum('total_cost'), 2),
                        'avg_transit_time' => round($routeExceptions->avg('avg_transit_time'), 2)
                    ],
                    'recommendations' => $this->getRouteRecommendations($exceptionType, $routeKey)
                ];
            }

            foreach ($results->groupBy('driver_key') as $driverKey => $driverExceptions) {
                $rootCauses[] = [
                    'cause_category' => 'driver_related',
                    'identifier' => "Driver {$driverKey}",
                    'incident_count' => $driverExceptions->sum('total_incidents'),
                    'impact_metrics' => [
                        'total_cost_impact' => round($driverExceptions->sum('total_cost'), 2),
                        'avg_penalty_cost' => round($driverExceptions->avg('total_penalty_cost'), 2)
                    ],
                    'recommendations' => $this->getDriverRecommendations($exceptionType, $driverKey)
                ];
            }

            foreach ($results->groupBy('origin_branch_key') as $branchKey => $branchExceptions) {
                $rootCauses[] = [
                    'cause_category' => 'origin_related',
                    'identifier' => "Origin Branch {$branchKey}",
                    'incident_count' => $branchExceptions->sum('total_incidents'),
                    'impact_metrics' => [
                        'total_cost_impact' => round($branchExceptions->sum('total_cost'), 2)
                    ],
                    'recommendations' => $this->getBranchRecommendations($exceptionType, $branchKey)
                ];
            }

            // Sort by impact
            usort($rootCauses, function ($a, $b) {
                return $b['incident_count'] - $a['incident_count'];
            });

            return [
                'exception_type' => $exceptionType,
                'type_description' => self::EXCEPTION_TYPES[$exceptionType] ?? 'Unknown',
                'analysis_summary' => [
                    'total_incidents' => $results->sum('total_incidents'),
                    'total_cost_impact' => round($results->sum('total_cost'), 2),
                    'total_penalty_impact' => round($results->sum('total_penalty_cost'), 2)
                ],
                'root_cause_breakdown' => $rootCauses,
                'primary_causes' => array_slice($rootCauses, 0, 5),
                'prevention_strategies' => $this->getPreventionStrategies($exceptionType)
            ];
        });
    }

    /**
     * Calculate exception frequency and patterns
     */
    public function calculateExceptionFrequency(string $period = 'daily', int $days = 30): array
    {
        $cacheKey = "exception_frequency_{$period}_{$days}";
        
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

            $frequencyData = FactShipment::query()
                ->select(
                    DB::raw($dateGrouping . ' as period'),
                    DB::raw('COUNT(*) as total_shipments'),
                    DB::raw('SUM(CASE WHEN exception_flag = true THEN 1 ELSE 0 END) as exception_count'),
                    DB::raw('AVG(CASE WHEN exception_flag = true THEN 1 ELSE 0 END) * 100 as exception_rate')
                )
                ->whereBetween('delivery_date_key', [$startDate, $endDate])
                ->groupBy(DB::raw($dateGrouping))
                ->orderBy('period')
                ->get();

            $frequencyPatterns = [
                'period' => $period,
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'frequency_data' => $frequencyData->map(function ($item) {
                    return [
                        'period' => $item->period,
                        'total_shipments' => (int) $item->total_shipments,
                        'exception_count' => (int) $item->exception_count,
                        'exception_rate' => round($item->exception_rate, 2)
                    ];
                })->toArray(),
                'patterns' => [
                    'peak_exception_periods' => $this->identifyPeakExceptionPeriods($frequencyData),
                    'trend_analysis' => $this->analyzeExceptionTrend($frequencyData),
                    'seasonal_patterns' => $this->identifySeasonalPatterns($frequencyData)
                ]
            ];

            return $frequencyPatterns;
        });
    }

    /**
     * Generate preventive action recommendations
     */
    public function generatePreventiveActions(string $exceptionType): array
    {
        $baseRecommendations = match($exceptionType) {
            'damaged' => [
                [
                    'category' => 'packaging',
                    'action' => 'Implement enhanced packaging standards for fragile items',
                    'expected_impact' => 'Reduced damage rate by 15-20%',
                    'implementation_cost' => 'Low',
                    'timeline' => '1-2 weeks'
                ],
                [
                    'category' => 'handling',
                    'action' => 'Train staff on proper handling procedures',
                    'expected_impact' => 'Improved handling quality',
                    'implementation_cost' => 'Medium',
                    'timeline' => '2-4 weeks'
                ]
            ],
            'delayed' => [
                [
                    'category' => 'planning',
                    'action' => 'Optimize route planning and scheduling',
                    'expected_impact' => 'Reduced delays by 25-30%',
                    'implementation_cost' => 'Medium',
                    'timeline' => '2-6 weeks'
                ],
                [
                    'category' => 'resources',
                    'action' => 'Add backup vehicles and drivers',
                    'expected_impact' => 'Improved capacity management',
                    'implementation_cost' => 'High',
                    'timeline' => '4-8 weeks'
                ]
            ],
            'lost' => [
                [
                    'category' => 'tracking',
                    'action' => 'Implement real-time tracking systems',
                    'expected_impact' => 'Reduced loss incidents by 80%',
                    'implementation_cost' => 'High',
                    'timeline' => '6-12 weeks'
                ],
                [
                    'category' => 'verification',
                    'action' => 'Strengthen shipment verification processes',
                    'expected_impact' => 'Improved accountability',
                    'implementation_cost' => 'Low',
                    'timeline' => '1-2 weeks'
                ]
            ],
            default => [
                [
                    'category' => 'general',
                    'action' => 'Review and improve standard operating procedures',
                    'expected_impact' => 'Overall process improvement',
                    'implementation_cost' => 'Medium',
                    'timeline' => '2-4 weeks'
                ]
            ]
        };

        return $baseRecommendations;
    }

    /**
     * Calculate financial impact of exceptions
     */
    public function calculateFinancialImpact(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('financial_impact', $filters);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = FactShipment::where('exception_flag', true);

            $this->applyFilters($query, $filters);

            $exceptions = $query->get();

            $impactAnalysis = [
                'direct_costs' => [
                    'late_penalties' => round($exceptions->sum('late_penalty_cost'), 2),
                    'operational_costs' => round($exceptions->sum('total_cost'), 2),
                    'revenue_impact' => round($exceptions->sum('revenue'), 2)
                ],
                'indirect_costs' => $this->calculateIndirectCosts($exceptions),
                'cost_breakdown' => $this->getCostBreakdownByType($exceptions),
                'roi_analysis' => $this->performROIAnalysis($exceptions)
            ];

            $impactAnalysis['total_estimated_impact'] = round(
                $impactAnalysis['direct_costs']['late_penalties'] + 
                $impactAnalysis['direct_costs']['operational_costs'] + 
                $impactAnalysis['indirect_costs']['total_indirect_costs'], 
                2
            );

            return $impactAnalysis;
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

    private function calculateAverageExceptionsPerDay(int $totalExceptions, array $filters): float
    {
        if (isset($filters['date_range'])) {
            $start = \Carbon\Carbon::createFromFormat('Ymd', $filters['date_range']['start']);
            $end = \Carbon\Carbon::createFromFormat('Ymd', $filters['date_range']['end']);
            $days = $start->diffInDays($end) + 1;
        } else {
            $days = 30; // Default to 30 days
        }
        
        return $days > 0 ? round($totalExceptions / $days, 2) : 0;
    }

    private function analyzeExceptionTrends(array $filters): array
    {
        $currentPeriod = $this->getExceptionCountForPeriod($filters);
        
        // Compare with previous period
        $previousFilters = $filters;
        if (isset($filters['date_range'])) {
            $start = \Carbon\Carbon::createFromFormat('Ymd', $filters['date_range']['start']);
            $end = \Carbon\Carbon::createFromFormat('Ymd', $filters['date_range']['end']);
            
            $previousFilters['date_range'] = [
                'start' => $start->subDays($start->diffInDays($end))->format('Ymd'),
                'end' => $start->format('Ymd')
            ];
        }
        
        $previousPeriod = $this->getExceptionCountForPeriod($previousFilters);
        
        $change = $previousPeriod > 0 ? (($currentPeriod - $previousPeriod) / $previousPeriod) * 100 : 0;
        
        return [
            'current_period_count' => $currentPeriod,
            'previous_period_count' => $previousPeriod,
            'change_percentage' => round($change, 2),
            'trend_direction' => $change > 5 ? 'increasing' : ($change < -5 ? 'decreasing' : 'stable')
        ];
    }

    private function getExceptionCountForPeriod(array $filters): int
    {
        $query = FactShipment::where('exception_flag', true);
        $this->applyFilters($query, $filters);
        return $query->count();
    }

    private function performRiskAssessment(array $categorizedExceptions): array
    {
        $highRisk = [];
        $mediumRisk = [];
        $lowRisk = [];
        
        foreach ($categorizedExceptions as $exception) {
            $frequency = $exception['frequency']['count'];
            $financialImpact = $exception['financial_impact']['total_penalty_cost'];
            
            $riskScore = ($frequency * 0.6) + ($financialImpact * 0.4);
            
            if ($riskScore > 100) {
                $highRisk[] = $exception;
            } elseif ($riskScore > 50) {
                $mediumRisk[] = $exception;
            } else {
                $lowRisk[] = $exception;
            }
        }
        
        return [
            'high_risk_exceptions' => $highRisk,
            'medium_risk_exceptions' => $mediumRisk,
            'low_risk_exceptions' => $lowRisk,
            'assessment_date' => now()->toISOString()
        ];
    }

    private function getRouteRecommendations(string $exceptionType, string $routeKey): array
    {
        return [
            "Review route {$routeKey} optimization",
            "Consider alternative routing options",
            "Analyze traffic patterns and timing"
        ];
    }

    private function getDriverRecommendations(string $exceptionType, string $driverKey): array
    {
        return [
            "Review driver {$driverKey} performance",
            "Consider additional training",
            "Evaluate workload distribution"
        ];
    }

    private function getBranchRecommendations(string $exceptionType, string $branchKey): array
    {
        return [
            "Audit branch {$branchKey} processes",
            "Review local operational procedures",
            "Assess resource availability"
        ];
    }

    private function getPreventionStrategies(string $exceptionType): array
    {
        $strategies = match($exceptionType) {
            'damaged' => [
                'Implement quality control checkpoints',
                'Use improved packaging materials',
                'Regular equipment maintenance'
            ],
            'delayed' => [
                'Real-time tracking and monitoring',
                'Predictive analytics for delays',
                'Contingency planning'
            ],
            'lost' => [
                'Enhanced tracking systems',
                'Double verification processes',
                'Regular inventory audits'
            ],
            default => [
                'Process improvement initiatives',
                'Regular training programs',
                'Performance monitoring'
            ]
        };
        
        return $strategies;
    }

    private function identifyPeakExceptionPeriods(Collection $frequencyData): array
    {
        $peakData = $frequencyData->sortByDesc('exception_count')->take(3)->values();
        return $peakData->map(function ($item) {
            return [
                'period' => $item->period,
                'exception_count' => (int) $item->exception_count,
                'exception_rate' => round($item->exception_rate, 2)
            ];
        })->toArray();
    }

    private function analyzeExceptionTrend(Collection $frequencyData): array
    {
        if (count($frequencyData) < 2) {
            return ['trend' => 'insufficient_data'];
        }
        
        $values = $frequencyData->pluck('exception_rate')->toArray();
        $recentAvg = array_sum(array_slice($values, -3)) / min(3, count($values));
        $earlierAvg = array_sum(array_slice($values, 0, 3)) / min(3, count($values));
        
        $change = (($recentAvg - $earlierAvg) / $earlierAvg) * 100;
        
        return [
            'trend' => $change > 2 ? 'increasing' : ($change < -2 ? 'decreasing' : 'stable'),
            'change_percentage' => round($change, 2)
        ];
    }

    private function identifySeasonalPatterns(Collection $frequencyData): array
    {
        // This would need actual date analysis
        return ['pattern' => 'analysis_needed', 'confidence' => 'low'];
    }

    private function calculateIndirectCosts(Collection $exceptions): array
    {
        $customerComplaints = $exceptions->count() * 25; // Estimated $25 per complaint
        $reputationDamage = $exceptions->sum('revenue') * 0.02; // 2% revenue impact
        $administrativeCosts = $exceptions->count() * 15; // $15 per exception admin cost
        
        return [
            'customer_complaints' => round($customerComplaints, 2),
            'reputation_damage' => round($reputationDamage, 2),
            'administrative_costs' => round($administrativeCosts, 2),
            'total_indirect_costs' => round($customerComplaints + $reputationDamage + $administrativeCosts, 2)
        ];
    }

    private function getCostBreakdownByType(Collection $exceptions): array
    {
        $breakdown = [];
        $grouped = $exceptions->groupBy('exception_type');
        
        foreach ($grouped as $type => $typeExceptions) {
            $breakdown[$type] = [
                'count' => $typeExceptions->count(),
                'total_cost' => round($typeExceptions->sum('total_cost'), 2),
                'total_penalties' => round($typeExceptions->sum('late_penalty_cost'), 2)
            ];
        }
        
        return $breakdown;
    }

    private function performROIAnalysis(Collection $exceptions): array
    {
        $totalExceptionCosts = $exceptions->sum('late_penalty_cost');
        $preventionInvestment = $totalExceptionCosts * 0.1; // Assume 10% investment in prevention
        $potentialSavings = $totalExceptionCosts * 0.3; // Assume 30% reduction possible
        
        return [
            'current_exception_costs' => round($totalExceptionCosts, 2),
            'prevention_investment' => round($preventionInvestment, 2),
            'potential_annual_savings' => round($potentialSavings * 12, 2),
            'roi_percentage' => $preventionInvestment > 0 ? round((($potentialSavings - $preventionInvestment) / $preventionInvestment) * 100, 2) : 0,
            'payback_period_months' => $potentialSavings > 0 ? round($preventionInvestment / ($potentialSavings / 12), 1) : 0
        ];
    }

    private function generateCacheKey(string $type, array $params): string
    {
        ksort($params);
        return "exception_analysis_{$type}_" . md5(serialize($params));
    }
}