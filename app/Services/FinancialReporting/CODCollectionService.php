<?php

namespace App\Services\FinancialReporting;

use App\Models\Financial\CODCollection;
use App\Models\ETL\FactShipment;
use App\Models\ETL\DimensionClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class CODCollectionService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const DUNNING_SCHEDULE = [
        0 => 'initial_notice',      // Due date
        7 => 'first_reminder',      // 7 days overdue
        14 => 'second_reminder',    // 14 days overdue
        30 => 'formal_demand',      // 30 days overdue
        45 => 'final_notice',       // 45 days overdue
        60 => 'collection_action'   // 60+ days overdue
    ];

    /**
     * Track COD collection status with aging buckets and dunning management
     */
    public function trackCODCollections(array $filters = []): array
    {
        try {
            $collections = $this->getCODCollections($filters);
            
            $trackingData = [
                'summary' => [
                    'total_cod' => 0,
                    'collected_amount' => 0,
                    'outstanding_amount' => 0,
                    'collection_rate' => 0,
                    'write_off_amount' => 0,
                    'net_collection_rate' => 0
                ],
                'aging_analysis' => [
                    'current' => ['count' => 0, 'amount' => 0],
                    '1_30_days' => ['count' => 0, 'amount' => 0],
                    '31_60_days' => ['count' => 0, 'amount' => 0],
                    '61_90_days' => ['count' => 0, 'amount' => 0],
                    '90_plus_days' => ['count' => 0, 'amount' => 0]
                ],
                'collection_metrics' => [
                    'average_collection_time' => 0,
                    'collection_efficiency' => 0,
                    'dunning_effectiveness' => 0,
                    'write_off_rate' => 0
                ],
                'dunning_status' => [
                    'pending_actions' => [],
                    'overdue_actions' => [],
                    'completed_actions' => []
                ],
                'alerts' => [],
                'recommendations' => []
            ];

            foreach ($collections as $collection) {
                $trackingData['summary']['total_cod'] += $collection->cod_amount;
                $trackingData['summary']['collected_amount'] += $collection->collected_amount;
                
                // Update aging buckets
                $agingBucket = $collection->getAgingBucket();
                if (isset($trackingData['aging_analysis'][$agingBucket])) {
                    $trackingData['aging_analysis'][$agingBucket]['count']++;
                    $trackingData['aging_analysis'][$agingBucket]['amount'] += $collection->cod_amount;
                }
            }

            // Calculate outstanding amount
            $trackingData['summary']['outstanding_amount'] = 
                $trackingData['summary']['total_cod'] - $trackingData['summary']['collected_amount'];

            // Calculate collection rates
            $trackingData['summary']['collection_rate'] = $trackingData['summary']['total_cod'] > 0 
                ? ($trackingData['summary']['collected_amount'] / $trackingData['summary']['total_cod']) * 100 
                : 0;

            // Get dunning status
            $trackingData['dunning_status'] = $this->analyzeDunningStatus($collections);
            
            // Generate alerts
            $trackingData['alerts'] = $this->generateCollectionAlerts($trackingData);
            
            // Calculate metrics
            $trackingData['collection_metrics'] = $this->calculateCollectionMetrics($collections);
            
            // Generate recommendations
            $trackingData['recommendations'] = $this->generateCollectionRecommendations($trackingData);

            return $trackingData;

        } catch (\Exception $e) {
            Log::error('COD collection tracking error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform aging analysis with detailed breakdown
     */
    public function performAgingAnalysis(array $filters = []): array
    {
        try {
            $agingData = [
                'aging_summary' => [
                    'total_outstanding' => 0,
                    'total_accounts' => 0,
                    'average_age' => 0,
                    'oldest_account' => null
                ],
                'detailed_aging' => [
                    'current' => [
                        'count' => 0,
                        'amount' => 0,
                        'percentage' => 0,
                        'accounts' => []
                    ],
                    '1_30_days' => [
                        'count' => 0,
                        'amount' => 0,
                        'percentage' => 0,
                        'accounts' => []
                    ],
                    '31_60_days' => [
                        'count' => 0,
                        'amount' => 0,
                        'percentage' => 0,
                        'accounts' => []
                    ],
                    '61_90_days' => [
                        'count' => 0,
                        'amount' => 0,
                        'percentage' => 0,
                        'accounts' => []
                    ],
                    '90_plus_days' => [
                        'count' => 0,
                        'amount' => 0,
                        'percentage' => 0,
                        'accounts' => []
                    ]
                ],
                'risk_analysis' => [
                    'high_risk' => [],
                    'medium_risk' => [],
                    'low_risk' => []
                ],
                'collection_strategies' => [],
                'projected_recoveries' => []
            ];

            $collections = $this->getOutstandingCODCollections($filters);
            $totalOutstanding = 0;
            $totalAge = 0;
            $oldestDate = null;

            foreach ($collections as $collection) {
                $amount = $collection->cod_amount - $collection->collected_amount;
                $totalOutstanding += $amount;
                $totalAge += $collection->days_overdue;
                
                if (!$oldestDate || $collection->due_date < $oldestDate) {
                    $oldestDate = $collection->due_date;
                }

                $agingBucket = $collection->getAgingBucket();
                
                if (isset($agingData['detailed_aging'][$agingBucket])) {
                    $bucket = &$agingData['detailed_aging'][$agingBucket];
                    $bucket['count']++;
                    $bucket['amount'] += $amount;
                    
                    // Add to accounts list (limit to first 10 for performance)
                    if (count($bucket['accounts']) < 10) {
                        $bucket['accounts'][] = [
                            'shipment_key' => $collection->shipment_key,
                            'amount' => $amount,
                            'days_overdue' => $collection->days_overdue,
                            'client' => $collection->client->client_name ?? 'Unknown'
                        ];
                    }
                }
            }

            // Update summary
            $agingData['aging_summary']['total_outstanding'] = $totalOutstanding;
            $agingData['aging_summary']['total_accounts'] = count($collections);
            $agingData['aging_summary']['average_age'] = count($collections) > 0 
                ? $totalAge / count($collections) 
                : 0;
            $agingData['aging_summary']['oldest_account'] = $oldestDate;

            // Calculate percentages
            foreach ($agingData['detailed_aging'] as &$bucket) {
                $bucket['percentage'] = $totalOutstanding > 0 
                    ? ($bucket['amount'] / $totalOutstanding) * 100 
                    : 0;
            }

            // Risk analysis
            $agingData['risk_analysis'] = $this->performRiskAnalysis($collections);
            
            // Collection strategies
            $agingData['collection_strategies'] = $this->generateCollectionStrategies($agingData);
            
            // Projected recoveries
            $agingData['projected_recoveries'] = $this->calculateProjectedRecoveries($agingData);

            return $agingData;

        } catch (\Exception $e) {
            Log::error('Aging analysis error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Manage dunning workflows and collection processes
     */
    public function manageDunningWorkflows(array $filters = []): array
    {
        try {
            $workflows = [
                'pending_actions' => [],
                'overdue_actions' => [],
                'completed_actions' => [],
                'dunning_effectiveness' => [
                    'response_rates' => [],
                    'collection_success_by_level' => [],
                    'avg_time_to_payment' => 0
                ],
                'automation_rules' => [],
                'escalation_matrix' => []
            ];

            $collections = $this->getCODCollections($filters);
            
            foreach ($collections as $collection) {
                $requiredAction = $this->determineRequiredAction($collection);
                
                if ($requiredAction) {
                    $actionData = [
                        'collection_id' => $collection->id,
                        'shipment_key' => $collection->shipment_key,
                        'client' => $collection->client->client_name ?? 'Unknown',
                        'amount' => $collection->cod_amount - $collection->collected_amount,
                        'days_overdue' => $collection->days_overdue,
                        'required_action' => $requiredAction['action'],
                        'priority' => $requiredAction['priority'],
                        'due_date' => $requiredAction['due_date'],
                        'automated' => $requiredAction['automated']
                    ];

                    if ($requiredAction['overdue']) {
                        $workflows['overdue_actions'][] = $actionData;
                    } else {
                        $workflows['pending_actions'][] = $actionData;
                    }
                }
            }

            // Calculate dunning effectiveness
            $workflows['dunning_effectiveness'] = $this->calculateDunningEffectiveness($collections);
            
            // Generate automation rules
            $workflows['automation_rules'] = $this->generateAutomationRules($collections);
            
            // Create escalation matrix
            $workflows['escalation_matrix'] = $this->createEscalationMatrix($collections);

            return $workflows;

        } catch (\Exception $e) {
            Log::error('Dunning workflow management error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate collection efficiency metrics
     */
    public function calculateCollectionEfficiency(array $filters = []): array
    {
        try {
            $efficiencyMetrics = [
                'overall_efficiency' => [
                    'collection_rate' => 0,
                    'days_sales_outstanding' => 0,
                    'collection_effectiveness' => 0,
                    'cost_per_collection' => 0
                ],
                'performance_by_segment' => [
                    'client_performance' => [],
                    'route_performance' => [],
                    'service_type_performance' => []
                ],
                'trend_analysis' => [
                    'monthly_trends' => [],
                    'efficiency_trends' => [],
                    'improvement_opportunities' => []
                ],
                'benchmarking' => [
                    'industry_benchmarks' => [],
                    'internal_targets' => [],
                    'performance_gaps' => []
                ],
                'predictive_analytics' => [
                    'collection_probability' => [],
                    'expected_collections' => [],
                    'risk_assessment' => []
                ]
            ];

            $collections = $this->getCODCollections($filters);
            
            // Calculate overall efficiency
            $efficiencyMetrics['overall_efficiency'] = $this->calculateOverallEfficiency($collections);
            
            // Performance by segment
            $efficiencyMetrics['performance_by_segment'] = [
                'client_performance' => $this->analyzeClientCollectionPerformance($collections),
                'route_performance' => $this->analyzeRouteCollectionPerformance($collections),
                'service_type_performance' => $this->analyzeServiceTypePerformance($collections)
            ];
            
            // Trend analysis
            $efficiencyMetrics['trend_analysis'] = $this->analyzeCollectionTrends($filters);
            
            // Benchmarking
            $efficiencyMetrics['benchmarking'] = $this->performCollectionBenchmarking(
                $efficiencyMetrics['overall_efficiency']
            );
            
            // Predictive analytics
            $efficiencyMetrics['predictive_analytics'] = $this->performPredictiveAnalytics($collections);

            return $efficiencyMetrics;

        } catch (\Exception $e) {
            Log::error('Collection efficiency calculation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analyze write-offs and provisioning
     */
    public function analyzeWriteOffs(array $filters = []): array
    {
        try {
            $writeOffAnalysis = [
                'write_off_summary' => [
                    'total_write_offs' => 0,
                    'write_off_amount' => 0,
                    'write_off_rate' => 0,
                    'provisioning_adequacy' => 0
                ],
                'write_off_reasons' => [
                    'customer_bankruptcy' => ['count' => 0, 'amount' => 0],
                    'uncollectible_accounts' => ['count' => 0, 'amount' => 0],
                    'disputed_charges' => ['count' => 0, 'amount' => 0],
                    'operational_issues' => ['count' => 0, 'amount' => 0]
                ],
                'aging_of_write_offs' => [
                    'current_year' => ['count' => 0, 'amount' => 0],
                    'previous_year' => ['count' => 0, 'amount' => 0],
                    'older' => ['count' => 0, 'amount' => 0]
                ],
                'provisioning_analysis' => [
                    'current_provision' => 0,
                    'required_provision' => 0,
                    'provision_coverage' => 0,
                    'recommended_adjustments' => []
                ],
                'recovery_analysis' => [
                    'recovery_rate' => 0,
                    'recovered_amount' => 0,
                    'recovery_by_age' => []
                ]
            ];

            $writtenOffCollections = $this->getWrittenOffCollections($filters);
            $totalCOD = $this->getTotalCODAmount($filters);

            foreach ($writtenOffCollections as $collection) {
                $writeOffAnalysis['write_off_summary']['total_write_offs']++;
                $writeOffAnalysis['write_off_summary']['write_off_amount'] += $collection->write_off_amount;
                
                // Categorize write-off reasons
                $reason = $this->determineWriteOffReason($collection);
                if (isset($writeOffAnalysis['write_off_reasons'][$reason])) {
                    $writeOffAnalysis['write_off_reasons'][$reason]['count']++;
                    $writeOffAnalysis['write_off_reasons'][$reason]['amount'] += $collection->write_off_amount;
                }
                
                // Age analysis
                $ageCategory = $this->categorizeWriteOffAge($collection->write_off_date);
                if (isset($writeOffAnalysis['aging_of_write_offs'][$ageCategory])) {
                    $writeOffAnalysis['aging_of_write_offs'][$ageCategory]['count']++;
                    $writeOffAnalysis['aging_of_write_offs'][$ageCategory]['amount'] += $collection->write_off_amount;
                }
            }

            // Calculate write-off rate
            $writeOffAnalysis['write_off_summary']['write_off_rate'] = $totalCOD > 0 
                ? ($writeOffAnalysis['write_off_summary']['write_off_amount'] / $totalCOD) * 100 
                : 0;

            // Provisioning analysis
            $writeOffAnalysis['provisioning_analysis'] = $this->analyzeProvisioning($filters, $writtenOffCollections);
            
            // Recovery analysis
            $writeOffAnalysis['recovery_analysis'] = $this->analyzeWriteOffRecoveries($writtenOffCollections);

            return $writeOffAnalysis;

        } catch (\Exception $e) {
            Log::error('Write-off analysis error: ' . $e->getMessage());
            throw $e;
        }
    }

    // Private helper methods

    private function getCODCollections(array $filters): Collection
    {
        return CODCollection::with(['client', 'shipment'])
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->whereBetween('due_date', [$dateRange['start'], $dateRange['end']]);
            })
            ->when($filters['client_key'] ?? false, function ($q, $clientKey) {
                $q->where('client_key', $clientKey);
            })
            ->get();
    }

    private function getOutstandingCODCollections(array $filters): Collection
    {
        return CODCollection::where('collection_status', '!=', CODCollection::STATUS_COLLECTED)
            ->where('collection_status', '!=', CODCollection::STATUS_WRITTEN_OFF)
            ->with(['client', 'shipment'])
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->whereBetween('due_date', [$dateRange['start'], $dateRange['end']]);
            })
            ->get();
    }

    private function getWrittenOffCollections(array $filters): Collection
    {
        return CODCollection::where('collection_status', CODCollection::STATUS_WRITTEN_OFF)
            ->with(['client', 'shipment'])
            ->when($filters['date_range'] ?? false, function ($q, $dateRange) {
                $q->whereBetween('write_off_date', [$dateRange['start'], $dateRange['end']]);
            })
            ->get();
    }

    private function getTotalCODAmount(array $filters): float
    {
        return CODCollection::when($filters['date_range'] ?? false, function ($q, $dateRange) {
            $q->whereBetween('due_date', [$dateRange['start'], $dateRange['end']]);
        })->sum('cod_amount');
    }

    private function analyzeDunningStatus(Collection $collections): array
    {
        $status = [
            'pending_actions' => [],
            'overdue_actions' => [],
            'completed_actions' => []
        ];

        foreach ($collections as $collection) {
            $dunningLevel = $this->calculateDunningLevel($collection);
            
            if ($collection->needsDunning()) {
                $action = [
                    'collection_id' => $collection->id,
                    'dunning_level' => $dunningLevel,
                    'days_overdue' => $collection->days_overdue,
                    'amount' => $collection->cod_amount - $collection->collected_amount
                ];

                if ($collection->days_overdue > 7) {
                    $status['overdue_actions'][] = $action;
                } else {
                    $status['pending_actions'][] = $action;
                }
            }
        }

        return $status;
    }

    private function calculateDunningLevel(CODCollection $collection): int
    {
        $daysOverdue = $collection->days_overdue;
        
        return match(true) {
            $daysOverdue <= 7 => CODCollection::DUNNING_LEVEL_1,
            $daysOverdue <= 14 => CODCollection::DUNNING_LEVEL_2,
            $daysOverdue <= 30 => CODCollection::DUNNING_LEVEL_3,
            default => CODCollection::DUNNING_FINAL
        };
    }

    private function determineRequiredAction(CODCollection $collection): ?array
    {
        $daysOverdue = $collection->days_overdue;
        $amount = $collection->cod_amount - $collection->collected_amount;
        
        // Skip if already collected or written off
        if ($collection->isCollected() || $collection->collection_status === CODCollection::STATUS_WRITTEN_OFF) {
            return null;
        }

        return match(true) {
            $daysOverdue <= 0 => [
                'action' => 'payment_reminder',
                'priority' => 'low',
                'due_date' => $collection->due_date,
                'automated' => true,
                'overdue' => false
            ],
            $daysOverdue <= 7 => [
                'action' => 'friendly_reminder',
                'priority' => 'medium',
                'due_date' => now()->addDays(7),
                'automated' => true,
                'overdue' => false
            ],
            $daysOverdue <= 30 => [
                'action' => 'formal_demand',
                'priority' => 'high',
                'due_date' => now()->addDays(30),
                'automated' => false,
                'overdue' => true
            ],
            $daysOverdue <= 60 => [
                'action' => 'collection_agency',
                'priority' => 'critical',
                'due_date' => now()->addDays(60),
                'automated' => false,
                'overdue' => true
            ],
            default => [
                'action' => 'legal_action',
                'priority' => 'critical',
                'due_date' => now(),
                'automated' => false,
                'overdue' => true
            ]
        };
    }

    private function generateCollectionAlerts(array $trackingData): array
    {
        $alerts = [];
        
        // High aging alerts
        $aging90Plus = $trackingData['aging_analysis']['90_plus_days']['amount'] ?? 0;
        if ($aging90Plus > ($trackingData['summary']['total_cod'] * 0.1)) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'High amount in 90+ days aging bucket',
                'amount' => $aging90Plus,
                'percentage' => ($aging90Plus / $trackingData['summary']['total_cod']) * 100
            ];
        }
        
        // Collection rate alerts
        $collectionRate = $trackingData['summary']['collection_rate'];
        if ($collectionRate < 80) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Collection rate below 80%',
                'value' => $collectionRate
            ];
        }
        
        return $alerts;
    }

    private function calculateCollectionMetrics(Collection $collections): array
    {
        $collected = $collections->where('collection_status', CODCollection::STATUS_COLLECTED);
        $total = $collections->count();
        
        $avgCollectionTime = $collected->avg(function ($collection) {
            return $collection->collection_date 
                ? $collection->collection_date->diffInDays($collection->due_date)
                : 0;
        });
        
        $collectionEfficiency = $total > 0 ? ($collected->count() / $total) * 100 : 0;
        
        $writtenOff = $collections->where('collection_status', CODCollection::STATUS_WRITTEN_OFF);
        $writeOffRate = $total > 0 ? ($writtenOff->count() / $total) * 100 : 0;
        
        return [
            'average_collection_time' => $avgCollectionTime,
            'collection_efficiency' => $collectionEfficiency,
            'dunning_effectiveness' => $this->calculateDunningEffectiveness($collections),
            'write_off_rate' => $writeOffRate
        ];
    }

    private function calculateDunningEffectiveness(Collection $collections): float
    {
        $collectionsWithDunning = $collections->where('dunning_level', '>', CODCollection::DUNNING_NONE);
        $successfulCollections = $collectionsWithDunning->where('collection_status', CODCollection::STATUS_COLLECTED);
        
        return $collectionsWithDunning->count() > 0 
            ? ($successfulCollections->count() / $collectionsWithDunning->count()) * 100 
            : 0;
    }

    private function generateCollectionRecommendations(array $trackingData): array
    {
        $recommendations = [];
        
        // High aging recommendations
        $aging90Plus = $trackingData['aging_analysis']['90_plus_days']['amount'] ?? 0;
        if ($aging90Plus > 0) {
            $recommendations[] = [
                'category' => 'aging_management',
                'priority' => 'high',
                'recommendation' => 'Implement aggressive collection strategy for 90+ days accounts',
                'potential_impact' => 'High',
                'estimated_recovery' => $aging90Plus * 0.3 // Assume 30% recovery rate
            ];
        }
        
        // Low collection rate recommendations
        if ($trackingData['summary']['collection_rate'] < 85) {
            $recommendations[] = [
                'category' => 'process_improvement',
                'priority' => 'medium',
                'recommendation' => 'Review and optimize collection processes',
                'potential_impact' => 'Medium',
                'estimated_improvement' => '5-10% collection rate increase'
            ];
        }
        
        return $recommendations;
    }

    private function performRiskAnalysis(Collection $collections): array
    {
        $highRisk = [];
        $mediumRisk = [];
        $lowRisk = [];
        
        foreach ($collections as $collection) {
            $amount = $collection->cod_amount - $collection->collected_amount;
            $riskScore = $this->calculateCollectionRiskScore($collection);
            
            $riskItem = [
                'shipment_key' => $collection->shipment_key,
                'amount' => $amount,
                'days_overdue' => $collection->days_overdue,
                'risk_score' => $riskScore,
                'client' => $collection->client->client_name ?? 'Unknown'
            ];
            
            if ($riskScore >= 80) {
                $highRisk[] = $riskItem;
            } elseif ($riskScore >= 50) {
                $mediumRisk[] = $riskItem;
            } else {
                $lowRisk[] = $riskItem;
            }
        }
        
        return [
            'high_risk' => $highRisk,
            'medium_risk' => $mediumRisk,
            'low_risk' => $lowRisk
        ];
    }

    private function calculateCollectionRiskScore(CODCollection $collection): int
    {
        $score = 0;
        
        // Days overdue factor
        $score += min(50, $collection->days_overdue);
        
        // Amount factor (higher amounts = higher risk)
        $amount = $collection->cod_amount - $collection->collected_amount;
        $score += min(30, $amount / 100);
        
        // Dunning level factor
        $score += $collection->dunning_level * 10;
        
        // Client history factor (simplified)
        // In practice, this would check client payment history
        $score += 10; // Default risk factor
        
        return min(100, $score);
    }

    private function generateCollectionStrategies(array $agingData): array
    {
        $strategies = [];
        
        foreach ($agingData['detailed_aging'] as $bucket => $data) {
            if ($data['count'] > 0) {
                $strategies[] = [
                    'target_bucket' => $bucket,
                    'strategy' => $this->getStrategyForBucket($bucket),
                    'resources_needed' => $this->estimateResourcesNeeded($data),
                    'expected_recovery' => $this->estimateRecoveryRate($bucket) * $data['amount'],
                    'timeline' => $this->getExpectedTimeline($bucket)
                ];
            }
        }
        
        return $strategies;
    }

    private function getStrategyForBucket(string $bucket): string
    {
        return match($bucket) {
            'current' => 'Proactive communication and gentle reminders',
            '1_30_days' => 'Phone calls and formal payment requests',
            '31_60_days' => 'Final notice and payment plan negotiations',
            '61_90_days' => 'Collection agency engagement',
            '90_plus_days' => 'Legal action consideration and write-off assessment',
            default => 'Standard collection procedures'
        };
    }

    private function estimateResourcesNeeded(array $bucketData): array
    {
        return [
            'staff_hours' => $bucketData['count'] * 0.5, // 30 minutes per account
            'communication_cost' => $bucketData['count'] * 2, // $2 per communication
            'collection_cost' => $bucketData['amount'] * 0.05, // 5% collection cost
        ];
    }

    private function estimateRecoveryRate(string $bucket): float
    {
        return match($bucket) {
            'current' => 0.95,
            '1_30_days' => 0.85,
            '31_60_days' => 0.65,
            '61_90_days' => 0.40,
            '90_plus_days' => 0.20,
            default => 0.50
        };
    }

    private function getExpectedTimeline(string $bucket): string
    {
        return match($bucket) {
            'current' => '1-7 days',
            '1_30_days' => '7-14 days',
            '31_60_days' => '14-30 days',
            '61_90_days' => '30-60 days',
            '90_plus_days' => '60+ days',
            default => '30 days'
        };
    }

    private function calculateProjectedRecoveries(array $agingData): array
    {
        $projections = [];
        
        foreach ($agingData['detailed_aging'] as $bucket => $data) {
            $recoveryRate = $this->estimateRecoveryRate($bucket);
            $projections[$bucket] = [
                'amount' => $data['amount'],
                'recovery_rate' => $recoveryRate,
                'projected_recovery' => $data['amount'] * $recoveryRate,
                'recovery_timeline' => $this->getExpectedTimeline($bucket)
            ];
        }
        
        return $projections;
    }

    private function determineWriteOffReason(CODCollection $collection): string
    {
        // This would typically use more sophisticated logic based on collection history
        // For now, we'll use a simplified approach
        return $collection->notes && str_contains(strtolower($collection->notes), 'bankruptcy') 
            ? 'customer_bankruptcy' 
            : 'uncollectible_accounts';
    }

    private function categorizeWriteOffAge(?Carbon $writeOffDate): string
    {
        if (!$writeOffDate) return 'current_year';
        
        $yearsDiff = now()->diffInYears($writeOffDate);
        
        return match(true) {
            $yearsDiff < 1 => 'current_year',
            $yearsDiff < 2 => 'previous_year',
            default => 'older'
        };
    }

    private function analyzeProvisioning(array $filters, Collection $writtenOffCollections): array
    {
        // Calculate required provisioning based on aging analysis
        $outstanding = $this->getOutstandingCODCollections($filters);
        
        $currentProvision = $outstanding->sum(function ($collection) {
            $amount = $collection->cod_amount - $collection->collected_amount;
            $age = $collection->days_overdue;
            
            return match(true) {
                $age <= 30 => $amount * 0.01, // 1% for current
                $age <= 60 => $amount * 0.05, // 5% for 1-60 days
                $age <= 90 => $amount * 0.20, // 20% for 61-90 days
                default => $amount * 0.50      // 50% for 90+ days
            };
        });
        
        $requiredProvision = $outstanding->sum(function ($collection) {
            $amount = $collection->cod_amount - $collection->collected_amount;
            $age = $collection->days_overdue;
            
            return match(true) {
                $age <= 30 => $amount * 0.02, // 2% for current
                $age <= 60 => $amount * 0.10, // 10% for 1-60 days
                $age <= 90 => $amount * 0.30, // 30% for 61-90 days
                default => $amount * 0.80      // 80% for 90+ days
            };
        });
        
        return [
            'current_provision' => $currentProvision,
            'required_provision' => $requiredProvision,
            'provision_coverage' => $currentProvision > 0 ? ($currentProvision / $requiredProvision) * 100 : 0,
            'recommended_adjustments' => $requiredProvision - $currentProvision
        ];
    }

    private function analyzeWriteOffRecoveries(Collection $writtenOffCollections): array
    {
        $recovered = $writtenOffCollections->where('collected_amount', '>', 0);
        $totalWriteOff = $writtenOffCollections->sum('write_off_amount');
        $totalRecovered = $recovered->sum('collected_amount');
        
        $recoveryRate = $totalWriteOff > 0 ? ($totalRecovered / $totalWriteOff) * 100 : 0;
        
        return [
            'recovery_rate' => $recoveryRate,
            'recovered_amount' => $totalRecovered,
            'recovery_by_age' => $this->analyzeRecoveryByAge($writtenOffCollections)
        ];
    }

    private function analyzeRecoveryByAge(Collection $collections): array
    {
        // Implementation would analyze recovery patterns by age of write-off
        return [
            'within_1_year' => ['rate' => 15, 'amount' => 0],
            '1_2_years' => ['rate' => 8, 'amount' => 0],
            '2_plus_years' => ['rate' => 3, 'amount' => 0]
        ];
    }

    // Additional helper methods for efficiency calculations
    private function calculateOverallEfficiency(Collection $collections): array
    {
        $collected = $collections->where('collection_status', CODCollection::STATUS_COLLECTED);
        $totalCOD = $collections->sum('cod_amount');
        $collectedAmount = $collected->sum('collected_amount');
        
        $collectionRate = $totalCOD > 0 ? ($collectedAmount / $totalCOD) * 100 : 0;
        
        $avgCollectionTime = $collected->avg(function ($collection) {
            return $collection->collection_date 
                ? $collection->collection_date->diffInDays($collection->due_date)
                : 0;
        });
        
        return [
            'collection_rate' => $collectionRate,
            'days_sales_outstanding' => $avgCollectionTime,
            'collection_effectiveness' => $collectionRate,
            'cost_per_collection' => 0 // Would need collection cost data
        ];
    }

    private function analyzeClientCollectionPerformance(Collection $collections): array
    {
        $clientPerformance = [];
        $clientGroups = $collections->groupBy('client_key');
        
        foreach ($clientGroups as $clientKey => $clientCollections) {
            $totalCOD = $clientCollections->sum('cod_amount');
            $collected = $clientCollections->where('collection_status', CODCollection::STATUS_COLLECTED);
            $collectedAmount = $collected->sum('collected_amount');
            
            $clientPerformance[] = [
                'client_key' => $clientKey,
                'client_name' => $clientCollections->first()->client->client_name ?? 'Unknown',
                'total_cod' => $totalCOD,
                'collected_amount' => $collectedAmount,
                'collection_rate' => $totalCOD > 0 ? ($collectedAmount / $totalCOD) * 100 : 0,
                'account_count' => $clientCollections->count()
            ];
        }
        
        return array_values($clientPerformance);
    }

    private function analyzeRouteCollectionPerformance(Collection $collections): array
    {
        // Similar implementation for route-based analysis
        return [];
    }

    private function analyzeServiceTypePerformance(Collection $collections): array
    {
        // Implementation would group by service type
        return [];
    }

    private function analyzeCollectionTrends(array $filters): array
    {
        // Get historical data and calculate trends
        return [
            'monthly_trends' => [],
            'efficiency_trends' => [],
            'improvement_opportunities' => []
        ];
    }

    private function performCollectionBenchmarking(array $efficiencyMetrics): array
    {
        return [
            'industry_benchmarks' => [
                'collection_rate' => 85,
                'days_sales_outstanding' => 30
            ],
            'internal_targets' => [
                'collection_rate' => 90,
                'days_sales_outstanding' => 25
            ],
            'performance_gaps' => [
                'collection_rate_gap' => 90 - $efficiencyMetrics['collection_rate'],
                'dso_gap' => $efficiencyMetrics['days_sales_outstanding'] - 25
            ]
        ];
    }

    private function performPredictiveAnalytics(Collection $collections): array
    {
        return [
            'collection_probability' => [],
            'expected_collections' => [],
            'risk_assessment' => []
        ];
    }

    private function generateAutomationRules(Collection $collections): array
    {
        return [
            [
                'trigger' => 'overdue_7_days',
                'action' => 'automated_email_reminder',
                'condition' => 'amount > 100',
                'enabled' => true
            ],
            [
                'trigger' => 'overdue_30_days',
                'action' => 'phone_call_assignment',
                'condition' => 'amount > 500',
                'enabled' => true
            ]
        ];
    }

    private function createEscalationMatrix(Collection $collections): array
    {
        return [
            'level_1' => [
                'trigger' => '7 days overdue',
                'action' => 'automated reminder',
                'responsibility' => 'system',
                'timeline' => 'immediate'
            ],
            'level_2' => [
                'trigger' => '14 days overdue',
                'action' => 'phone call',
                'responsibility' => 'collection team',
                'timeline' => '24 hours'
            ],
            'level_3' => [
                'trigger' => '30 days overdue',
                'action' => 'formal demand',
                'responsibility' => 'senior collector',
                'timeline' => '48 hours'
            ]
        ];
    }
}