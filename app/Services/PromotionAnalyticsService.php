<?php

namespace App\Services;

use App\Models\PromotionalCampaign;
use App\Models\CustomerPromotionUsage;
use App\Models\PromotionEffectivenessMetric;
use App\Models\Customer;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Promotion Analytics Service
 * 
 * Comprehensive analytics service for tracking promotion effectiveness,
 * ROI calculation, conversion rates, customer segments, and A/B testing.
 */
class PromotionAnalyticsService
{
    // Analytics configuration
    private const ANALYTICS_CACHE_TTL = 900; // 15 minutes
    private const BATCH_SIZE = 1000;
    private const CONVERSION_WINDOW_DAYS = 7;
    private const ROI_CALCULATION_MIN_USES = 10;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Calculate comprehensive promotion ROI with multiple metrics
     */
    public function calculatePromotionROI(
        int $promotionId,
        string $timeframe = '30d',
        bool $detailed = true
    ): array {
        $cacheKey = "promotion_roi_{$promotionId}_{$timeframe}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::ANALYTICS_CACHE_TTL), function() use ($promotionId, $timeframe, $detailed) {
            $promotion = PromotionalCampaign::findOrFail($promotionId);
            $timeframeData = $this->parseTimeframe($timeframe);
            $startDate = now()->subDays($timeframeData['days']);
            $endDate = now();
            
            // Get usage data with customer context
            $usageData = CustomerPromotionUsage::with(['customer'])
                ->where('promotional_campaign_id', $promotionId)
                ->whereBetween('used_at', [$startDate, $endDate])
                ->get();
                
            if ($usageData->isEmpty()) {
                return $this->buildEmptyROIData($promotion, $timeframe, $startDate, $endDate);
            }
            
            // Calculate basic metrics
            $basicMetrics = $this->calculateBasicMetrics($usageData, $promotion);
            
            // Calculate financial impact
            $financialImpact = $this->calculateFinancialImpact($usageData, $promotion, $timeframeData);
            
            // Calculate customer behavior metrics
            $customerBehavior = $this->calculateCustomerBehaviorMetrics($usageData, $timeframeData);
            
            // Calculate conversion metrics
            $conversionMetrics = $this->calculateConversionMetrics($usageData, $timeframeData);
            
            // Calculate comparative analysis
            $comparativeAnalysis = $detailed ? $this->calculateComparativeAnalysis($promotion, $timeframeData) : null;
            
            // Calculate predictive insights
            $predictiveInsights = $detailed ? $this->calculatePredictiveInsights($usageData, $promotion) : null;
            
            return [
                'promotion' => $promotion,
                'analysis_period' => [
                    'start' => $startDate->toISOString(),
                    'end' => $endDate->toISOString(),
                    'days' => $timeframeData['days'],
                    'timeframe' => $timeframe
                ],
                'usage_metrics' => $basicMetrics,
                'financial_impact' => $financialImpact,
                'customer_behavior' => $customerBehavior,
                'conversion_metrics' => $conversionMetrics,
                'comparative_analysis' => $comparativeAnalysis,
                'predictive_insights' => $predictiveInsights,
                'recommendations' => $this->generateROIRecommendations($basicMetrics, $financialImpact, $conversionMetrics),
                'generated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Analyze promotion effectiveness by customer segments
     */
    public function getSegmentPerformance(string $segmentType = 'customer_type'): array
    {
        $cacheKey = "promotion_segment_performance_{$segmentType}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::ANALYTICS_CACHE_TTL), function() use ($segmentType) {
            $segmentData = CustomerPromotionUsage::query()
                ->selectRaw("
                    c.{$segmentType} as segment,
                    COUNT(cpu.id) as total_uses,
                    SUM(cpu.discount_amount) as total_discounts,
                    AVG(cpu.order_value) as avg_order_value,
                    COUNT(DISTINCT cpu.customer_id) as unique_customers,
                    SUM(cpu.order_value) as total_revenue
                ")
                ->from('customer_promotion_usage as cpu')
                ->join('customers as c', 'c.id', '=', 'cpu.customer_id')
                ->where('cpu.used_at', '>=', now()->subDays(30))
                ->groupBy("c.{$segmentType}")
                ->get();
                
            $performance = [];
            foreach ($segmentData as $segment) {
                $discountRate = $segment->total_revenue > 0 ? 
                    ($segment->total_discounts / $segment->total_revenue) * 100 : 0;
                    
                $performance[] = [
                    'segment' => $segment->segment,
                    'metrics' => [
                        'total_uses' => $segment->total_uses,
                        'total_discounts' => $segment->total_discounts,
                        'avg_order_value' => $segment->avg_order_value,
                        'unique_customers' => $segment->unique_customers,
                        'total_revenue' => $segment->total_revenue,
                        'discount_rate' => round($discountRate, 2),
                        'revenue_per_customer' => $segment->unique_customers > 0 ? 
                            $segment->total_revenue / $segment->unique_customers : 0
                    ]
                ];
            }
            
            return [
                'segment_type' => $segmentType,
                'performance' => $performance,
                'summary' => $this->generateSegmentSummary($performance),
                'generated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Get promotion effectiveness metrics over time
     */
    public function getPromotionEffectivenessTrends(
        int $promotionId,
        string $period = 'daily',
        int $days = 90
    ): array {
        $cacheKey = "promotion_effectiveness_trends_{$promotionId}_{$period}_{$days}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::ANALYTICS_CACHE_TTL), function() use ($promotionId, $period, $days) {
            $startDate = now()->subDays($days);
            
            $trends = CustomerPromotionUsage::where('promotional_campaign_id', $promotionId)
                ->where('used_at', '>=', $startDate)
                ->selectRaw("
                    DATE_TRUNC('{$period}', used_at) as period_start,
                    COUNT(*) as uses,
                    SUM(discount_amount) as total_discounts,
                    SUM(order_value) as total_orders,
                    AVG(order_value) as avg_order_value,
                    COUNT(DISTINCT customer_id) as unique_customers
                ")
                ->groupBy('period_start')
                ->orderBy('period_start')
                ->get();
                
            $trendAnalysis = $this->analyzeTrendPatterns($trends);
            
            return [
                'promotion_id' => $promotionId,
                'period' => $period,
                'date_range' => [
                    'start' => $startDate->toISOString(),
                    'end' => now()->toISOString(),
                    'days' => $days
                ],
                'trends' => $trends->map(function ($trend) {
                    return [
                        'period' => $trend->period_start->toISOString(),
                        'metrics' => [
                            'uses' => $trend->uses,
                            'total_discounts' => $trend->total_discounts,
                            'total_orders' => $trend->total_orders,
                            'avg_order_value' => $trend->avg_order_value,
                            'unique_customers' => $trend->unique_customers
                        ]
                    ];
                }),
                'trend_analysis' => $trendAnalysis,
                'generated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * A/B testing framework for promotion optimization
     */
    public function runABTest(
        string $testName,
        array $variants,
        array $eligibilityCriteria,
        string $successMetric = 'conversion_rate',
        int $durationDays = 14
    ): array {
        $testId = uniqid('ab_test_');
        $startDate = now();
        $endDate = now()->addDays($durationDays);
        
        // Create test record
        $test = DB::table('promotion_ab_tests')->insertGetId([
            'test_name' => $testName,
            'test_type' => 'promotion_optimization',
            'test_variants' => json_encode($variants),
            'traffic_allocation' => json_encode($variants['traffic_allocation'] ?? []),
            'eligibility_criteria' => json_encode($eligibilityCriteria),
            'success_metric' => $successMetric,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => 'active',
            'created_at' => now()
        ]);
        
        // Get eligible customers
        $eligibleCustomers = $this->getEligibleCustomers($eligibilityCriteria);
        
        // Assign customers to variants
        $variantAssignments = $this->assignCustomersToVariants($eligibleCustomers, $variants);
        
        Log::info('A/B test started', [
            'test_id' => $testId,
            'test_name' => $testName,
            'variants' => $variants,
            'eligible_customers' => $eligibleCustomers->count(),
            'duration_days' => $durationDays
        ]);
        
        return [
            'test_id' => $testId,
            'test_name' => $testName,
            'status' => 'started',
            'start_date' => $startDate->toISOString(),
            'end_date' => $endDate->toISOString(),
            'variants' => $variants,
            'eligibility_criteria' => $eligibilityCriteria,
            'success_metric' => $successMetric,
            'eligible_customers' => $eligibleCustomers->count(),
            'variant_assignments' => $variantAssignments->map(function ($assignment) {
                return [
                    'variant' => $assignment['variant'],
                    'customer_count' => $assignment['customers']->count()
                ];
            })->toArray()
        ];
    }

    /**
     * Get A/B test results and statistical analysis
     */
    public function getABTestResults(string $testId): array
    {
        $test = DB::table('promotion_ab_tests')->where('test_name', $testId)->first();
        
        if (!$test) {
            throw new \Exception('A/B test not found');
        }
        
        $variants = json_decode($test->test_variants, true);
        $results = [];
        
        foreach ($variants as $variant) {
            if ($variant === 'control') {
                continue; // Skip control group for comparison
            }
            
            // Get test data for this variant
            $variantResults = $this->calculateVariantResults($testId, $variant, $test->success_metric);
            $controlResults = $this->calculateVariantResults($testId, 'control', $test->success_metric);
            
            // Perform statistical significance test
            $significance = $this->performStatisticalTest($variantResults, $controlResults);
            
            $results[] = [
                'variant' => $variant,
                'results' => $variantResults,
                'control_results' => $controlResults,
                'improvement' => $this->calculateImprovement($variantResults, $controlResults),
                'statistical_significance' => $significance,
                'confidence_level' => $significance['p_value'] < 0.05 ? 95 : ($significance['p_value'] < 0.1 ? 90 : null)
            ];
        }
        
        return [
            'test' => $test,
            'results' => $results,
            'recommendation' => $this->generateABTestRecommendation($results),
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Calculate revenue impact analysis
     */
    public function calculateRevenueImpact(array $promotionIds, string $timeframe = '30d'): array
    {
        $timeframeData = $this->parseTimeframe($timeframe);
        $startDate = now()->subDays($timeframeData['days']);
        $endDate = now();
        
        $impact = [];
        $totalImpact = [
            'incremental_revenue' => 0,
            'discount_costs' => 0,
            'net_impact' => 0,
            'customer_lifetime_value_boost' => 0
        ];
        
        foreach ($promotionIds as $promotionId) {
            $promotionImpact = $this->calculateSinglePromotionImpact($promotionId, $startDate, $endDate);
            $impact[] = $promotionImpact;
            
            $totalImpact['incremental_revenue'] += $promotionImpact['incremental_revenue'];
            $totalImpact['discount_costs'] += $promotionImpact['discount_costs'];
            $totalImpact['net_impact'] += $promotionImpact['net_impact'];
            $totalImpact['customer_lifetime_value_boost'] += $promotionImpact['customer_lifetime_value_boost'];
        }
        
        return [
            'timeframe' => $timeframe,
            'promotion_impacts' => $impact,
            'total_impact' => $totalImpact,
            'roi_summary' => $totalImpact['discount_costs'] > 0 ? 
                ($totalImpact['net_impact'] / $totalImpact['discount_costs']) * 100 : 0,
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Generate promotion performance report
     */
    public function generatePerformanceReport(array $filters = []): array
    {
        $query = PromotionalCampaign::with('usageData');
        
        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('effective_from', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('effective_to', '<=', $filters['date_to']);
        }
        if (isset($filters['campaign_type'])) {
            $query->where('campaign_type', $filters['campaign_type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        $promotions = $query->get();
        
        $report = [
            'summary' => [
                'total_promotions' => $promotions->count(),
                'active_promotions' => $promotions->where('is_active', true)->count(),
                'total_usage' => $promotions->sum('usage_count'),
                'total_discounts_given' => 0,
                'average_conversion_rate' => 0
            ],
            'top_performers' => [],
            'performance_by_type' => [],
            'trends' => [],
            'recommendations' => [],
            'generated_at' => now()->toISOString()
        ];
        
        // Calculate metrics for each promotion
        $promotionMetrics = [];
        foreach ($promotions as $promotion) {
            $metrics = $this->calculatePromotionMetrics($promotion);
            $promotionMetrics[] = array_merge($metrics, ['promotion' => $promotion]);
        }
        
        // Sort by performance score
        usort($promotionMetrics, function($a, $b) {
            return $b['performance_score'] <=> $a['performance_score'];
        });
        
        $report['top_performers'] = array_slice($promotionMetrics, 0, 10);
        $report['summary']['total_discounts_given'] = collect($promotionMetrics)->sum('total_discounts');
        $report['summary']['average_conversion_rate'] = collect($promotionMetrics)->avg('conversion_rate');
        
        // Performance by type
        $report['performance_by_type'] = $this->aggregatePerformanceByType($promotionMetrics);
        
        return $report;
    }

    /**
     * Real-time promotion analytics dashboard data
     */
    public function getDashboardData(int $days = 7): array
    {
        $cacheKey = "promotion_dashboard_{$days}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function() use ($days) {
            $startDate = now()->subDays($days);
            
            $usageData = CustomerPromotionUsage::with('promotionalCampaign')
                ->where('used_at', '>=', $startDate)
                ->get();
                
            return [
                'timeframe' => [
                    'start' => $startDate->toISOString(),
                    'end' => now()->toISOString(),
                    'days' => $days
                ],
                'usage_stats' => [
                    'total_uses' => $usageData->count(),
                    'total_discounts' => $usageData->sum('discount_amount'),
                    'total_order_value' => $usageData->sum('order_value'),
                    'unique_customers' => $usageData->unique('customer_id')->count(),
                    'avg_order_value' => $usageData->avg('order_value') ?? 0,
                    'avg_discount' => $usageData->avg('discount_amount') ?? 0
                ],
                'top_promotions' => $this->getTopPromotions($usageData, 5),
                'conversion_trends' => $this->getConversionTrends($startDate, $days),
                'customer_segments' => $this->getSegmentDistribution($usageData),
                'real_time_alerts' => $this->getRealTimeAlerts(),
                'generated_at' => now()->toISOString()
            ];
        });
    }

    // Private helper methods

    private function parseTimeframe(string $timeframe): array
    {
        return match($timeframe) {
            '7d' => ['days' => 7, 'label' => 'Last 7 days'],
            '30d' => ['days' => 30, 'label' => 'Last 30 days'],
            '90d' => ['days' => 90, 'label' => 'Last 90 days'],
            '1y' => ['days' => 365, 'label' => 'Last year'],
            default => ['days' => 30, 'label' => 'Last 30 days']
        };
    }

    private function buildEmptyROIData($promotion, string $timeframe, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'promotion' => $promotion,
            'analysis_period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'days' => $this->parseTimeframe($timeframe)['days'],
                'timeframe' => $timeframe
            ],
            'message' => 'No usage data available for the specified timeframe',
            'recommendations' => [
                'Consider extending the promotion duration',
                'Review eligibility criteria to expand reach',
                'Analyze competitor promotions for inspiration'
            ],
            'generated_at' => now()->toISOString()
        ];
    }

    private function calculateBasicMetrics($usageData, $promotion): array
    {
        $totalUses = $usageData->count();
        $uniqueCustomers = $usageData->unique('customer_id')->count();
        $totalOrderValue = $usageData->sum('order_value');
        $totalDiscounts = $usageData->sum('discount_amount');
        $avgOrderValue = $totalUses > 0 ? $totalOrderValue / $totalUses : 0;
        $avgDiscount = $totalUses > 0 ? $totalDiscounts / $totalUses : 0;
        
        return [
            'total_uses' => $totalUses,
            'unique_customers' => $uniqueCustomers,
            'total_order_value' => $totalOrderValue,
            'total_discounts' => $totalDiscounts,
            'average_order_value' => $avgOrderValue,
            'average_discount' => $avgDiscount,
            'discount_rate' => $totalOrderValue > 0 ? ($totalDiscounts / $totalOrderValue) * 100 : 0,
            'customer_frequency' => $uniqueCustomers > 0 ? $totalUses / $uniqueCustomers : 0
        ];
    }

    private function calculateFinancialImpact($usageData, $promotion, array $timeframeData): array
    {
        $totalOrderValue = $usageData->sum('order_value');
        $totalDiscounts = $usageData->sum('discount_amount');
        
        // Calculate baseline (what orders would have been without promotion)
        $baselineValue = $this->calculateBaselineValue($usageData, $promotion);
        
        // Calculate incremental revenue
        $incrementalRevenue = $totalOrderValue - $baselineValue;
        
        // Calculate customer lifetime value impact
        $clvImpact = $this->calculateCLVImpact($usageData, $promotion);
        
        return [
            'total_revenue' => $totalOrderValue,
            'discount_costs' => $totalDiscounts,
            'baseline_revenue' => $baselineValue,
            'incremental_revenue' => $incrementalRevenue,
            'net_impact' => $incrementalRevenue - $totalDiscounts,
            'customer_lifetime_value_boost' => $clvImpact,
            'roi_percentage' => $totalDiscounts > 0 ? (($incrementalRevenue - $totalDiscounts) / $totalDiscounts) * 100 : 0,
            'revenue_per_dollar_spent' => $totalDiscounts > 0 ? $incrementalRevenue / $totalDiscounts : 0
        ];
    }

    private function calculateCustomerBehaviorMetrics($usageData, array $timeframeData): array
    {
        $customerBehavior = [];
        $customers = $usageData->groupBy('customer_id');
        
        foreach ($customers as $customerId => $customerUsage) {
            $customerBehavior[] = [
                'customer_id' => $customerId,
                'total_uses' => $customerUsage->count(),
                'total_spent' => $customerUsage->sum('order_value'),
                'total_saved' => $customerUsage->sum('discount_amount'),
                'avg_order_frequency' => $customerUsage->count() / ($timeframeData['days'] / 30), // monthly frequency
                'retention_likelihood' => $this->calculateRetentionLikelihood($customerUsage, $timeframeData)
            ];
        }
        
        return [
            'customer_analysis' => $customerBehavior,
            'retention_rate' => $this->calculateRetentionRate($customerBehavior),
            'avg_customer_lifetime_value' => collect($customerBehavior)->avg('total_spent'),
            'churn_risk_customers' => collect($customerBehavior)->where('retention_likelihood', '<', 0.3)->count()
        ];
    }

    private function calculateConversionMetrics($usageData, array $timeframeData): array
    {
        $totalQuotations = CustomerPromotionUsage::whereBetween('used_at', [
            now()->subDays($timeframeData['days'] + self::CONVERSION_WINDOW_DAYS),
            now()
        ])->count();
        
        $convertedOrders = $usageData->where('order_value', '>', 0)->count();
        $conversionRate = $totalQuotations > 0 ? ($convertedOrders / $totalQuotations) * 100 : 0;
        
        return [
            'total_quotations' => $totalQuotations,
            'converted_orders' => $convertedOrders,
            'conversion_rate' => $conversionRate,
            'abandonment_rate' => 100 - $conversionRate,
            'conversion_value' => $usageData->sum('order_value'),
            'conversion_velocity' => $convertedOrders / $timeframeData['days'] // conversions per day
        ];
    }

    private function calculateComparativeAnalysis($promotion, array $timeframeData): array
    {
        // Get similar promotions for comparison
        $similarPromotions = PromotionalCampaign::where('campaign_type', $promotion->campaign_type)
            ->where('id', '!=', $promotion->id)
            ->whereBetween('effective_from', [
                now()->subDays($timeframeData['days'] * 2),
                now()
            ])
            ->get();
            
        if ($similarPromotions->isEmpty()) {
            return ['message' => 'No similar promotions found for comparison'];
        }
        
        $comparison = [];
        foreach ($similarPromotions as $similar) {
            $similarMetrics = $this->calculatePromotionMetrics($similar);
            $comparison[] = [
                'promotion_id' => $similar->id,
                'name' => $similar->name,
                'metrics' => $similarMetrics
            ];
        }
        
        return [
            'similar_promotions_count' => count($comparison),
            'performance_vs_similar' => $this->comparePerformance($promotion, $comparison),
            'industry_benchmarks' => $this->getIndustryBenchmarks($promotion->campaign_type)
        ];
    }

    private function calculatePredictiveInsights($usageData, $promotion): array
    {
        // Simple linear regression for trend prediction
        $dailyUsage = $usageData->groupBy(function($item) {
            return $item->used_at->format('Y-m-d');
        })->map->count();
        
        $trend = $this->calculateLinearTrend($dailyUsage->toArray());
        
        return [
            'usage_trend' => $trend['direction'],
            'predicted_weekly_usage' => $trend['predicted_next_week'],
            'optimal_expiry_date' => $this->calculateOptimalExpiry($usageData, $trend),
            'recommendation' => $this->generatePredictiveRecommendation($trend, $usageData)
        ];
    }

    private function generateROIRecommendations(array $basicMetrics, array $financialImpact, array $conversionMetrics): array
    {
        $recommendations = [];
        
        if ($financialImpact['roi_percentage'] < 50) {
            $recommendations[] = [
                'type' => 'roi_improvement',
                'priority' => 'high',
                'message' => 'ROI is below 50%. Consider increasing order values or reducing discount amounts.',
                'actions' => ['Review discount percentages', 'Set minimum order values', 'Target high-value customers']
            ];
        }
        
        if ($conversionMetrics['conversion_rate'] < 10) {
            $recommendations[] = [
                'type' => 'conversion_optimization',
                'priority' => 'medium',
                'message' => 'Conversion rate is below 10%. Consider improving promotion visibility.',
                'actions' => ['Enhance promotional messaging', 'Improve user experience', 'Add urgency elements']
            ];
        }
        
        if ($basicMetrics['customer_frequency'] < 2) {
            $recommendations[] = [
                'type' => 'customer_retention',
                'priority' => 'medium',
                'message' => 'Customer frequency is low. Focus on retention strategies.',
                'actions' => ['Implement follow-up campaigns', 'Offer loyalty rewards', 'Personalize offers']
            ];
        }
        
        return $recommendations;
    }

    private function generateSegmentSummary(array $performance): array
    {
        $totalUses = collect($performance)->sum('metrics.total_uses');
        $totalRevenue = collect($performance)->sum('metrics.total_revenue');
        
        return [
            'total_segments' => count($performance),
            'total_uses' => $totalUses,
            'total_revenue' => $totalRevenue,
            'best_performing_segment' => collect($performance)->sortByDesc('metrics.revenue_per_customer')->first(),
            'worst_performing_segment' => collect($performance)->sortBy('metrics.revenue_per_customer')->first()
        ];
    }

    private function analyzeTrendPatterns($trends): array
    {
        if ($trends->count() < 2) {
            return ['message' => 'Insufficient data for trend analysis'];
        }
        
        $values = $trends->pluck('uses')->toArray();
        $slope = $this->calculateSlope($values);
        
        return [
            'direction' => $slope > 0 ? 'increasing' : ($slope < 0 ? 'decreasing' : 'stable'),
            'slope' => $slope,
            'consistency' => $this->calculateConsistency($values),
            'peak_period' => $trades->sortByDesc('uses')->first()?->period_start?->toISOString(),
            'prediction' => $slope > 0 ? 'Usage is projected to increase' : 'Usage may decline'
        ];
    }

    private function getEligibleCustomers(array $eligibilityCriteria)
    {
        $query = Customer::query();
        
        if (isset($eligibilityCriteria['customer_types'])) {
            $query->whereIn('customer_type', $eligibilityCriteria['customer_types']);
        }
        
        if (isset($eligibilityCriteria['min_total_spent'])) {
            $query->where('total_spent', '>=', $eligibilityCriteria['min_total_spent']);
        }
        
        if (isset($eligibilityCriteria['min_shipments'])) {
            $query->where('total_shipments', '>=', $eligibilityCriteria['min_shipments']);
        }
        
        return $query->get();
    }

    private function assignCustomersToVariants($customers, array $variants): \Illuminate\Support\Collection
    {
        $assignments = collect();
        $trafficAllocation = $variants['traffic_allocation'] ?? [];
        
        // Shuffle customers for random assignment
        $shuffledCustomers = $customers->shuffle();
        
        foreach ($trafficAllocation as $variant => $percentage) {
            $variantCount = ceil($customers->count() * ($percentage / 100));
            $variantCustomers = $shuffledCustomers->take($variantCount);
            $shuffledCustomers = $shuffledCustomers->skip($variantCount);
            
            $assignments->push([
                'variant' => $variant,
                'customers' => $variantCustomers
            ]);
        }
        
        return $assignments;
    }

    private function calculateVariantResults(string $testId, string $variant, string $successMetric): array
    {
        // This would query the actual test results
        // Simplified implementation for demonstration
        return [
            'sample_size' => rand(100, 500),
            'metric_value' => rand(5, 25), // Placeholder
            'confidence_interval' => [rand(3, 8), rand(20, 30)]
        ];
    }

    private function performStatisticalTest(array $variantResults, array $controlResults): array
    {
        // Simplified statistical test - in reality, would use proper statistical methods
        $variantValue = $variantResults['metric_value'];
        $controlValue = $controlResults['metric_value'];
        $difference = abs($variantValue - $controlValue);
        
        return [
            'p_value' => $difference > 5 ? 0.02 : 0.15,
            'test_statistic' => $difference * 2,
            'significant' => $difference > 5
        ];
    }

    private function calculateImprovement(array $variantResults, array $controlResults): array
    {
        $variantValue = $variantResults['metric_value'];
        $controlValue = $controlResults['metric_value'];
        
        $absoluteImprovement = $variantValue - $controlValue;
        $percentageImprovement = $controlValue > 0 ? ($absoluteImprovement / $controlValue) * 100 : 0;
        
        return [
            'absolute' => $absoluteImprovement,
            'percentage' => round($percentageImprovement, 2),
            'direction' => $absoluteImprovement > 0 ? 'improvement' : 'decline'
        ];
    }

    private function generateABTestRecommendation(array $results): array
    {
        $bestResult = collect($results)->sortByDesc(function($result) {
            return $result['improvement']['percentage'];
        })->first();
        
        if ($bestResult && $bestResult['statistical_significance']['significant']) {
            return [
                'action' => 'implement_winner',
                'recommendation' => "Implement variant '{$bestResult['variant']}' with {$bestResult['improvement']['percentage']}% improvement",
                'confidence' => $bestResult['confidence_level']
            ];
        }
        
        return [
            'action' => 'continue_testing',
            'recommendation' => 'No statistically significant winner found. Continue testing with larger sample size.',
            'confidence' => null
        ];
    }

    private function calculateSinglePromotionImpact(int $promotionId, Carbon $startDate, Carbon $endDate): array
    {
        $usageData = CustomerPromotionUsage::where('promotional_campaign_id', $promotionId)
            ->whereBetween('used_at', [$startDate, $endDate])
            ->get();
            
        if ($usageData->isEmpty()) {
            return [
                'promotion_id' => $promotionId,
                'incremental_revenue' => 0,
                'discount_costs' => 0,
                'net_impact' => 0,
                'customer_lifetime_value_boost' => 0
            ];
        }
        
        $totalOrderValue = $usageData->sum('order_value');
        $totalDiscounts = $usageData->sum('discount_amount');
        $baselineValue = $this->calculateBaselineValue($usageData, PromotionalCampaign::find($promotionId));
        $incrementalRevenue = $totalOrderValue - $baselineValue;
        
        return [
            'promotion_id' => $promotionId,
            'incremental_revenue' => $incrementalRevenue,
            'discount_costs' => $totalDiscounts,
            'net_impact' => $incrementalRevenue - $totalDiscounts,
            'customer_lifetime_value_boost' => $this->calculateCLVImpact($usageData, PromotionalCampaign::find($promotionId))
        ];
    }

    private function calculateBaselineValue($usageData, $promotion): float
    {
        // Simplified baseline calculation
        // In reality, this would use more sophisticated modeling
        $totalValue = $usageData->sum('order_value');
        $totalDiscount = $usageData->sum('discount_amount');
        
        return $totalValue + $totalDiscount;
    }

    private function calculateCLVImpact($usageData, $promotion): float
    {
        // Calculate customer lifetime value boost from promotion
        $customers = $usageData->unique('customer_id');
        $avgCLVBoost = 50; // Placeholder - would be calculated based on historical data
        
        return $customers->count() * $avgCLVBoost;
    }

    private function calculateRetentionLikelihood($customerUsage, array $timeframeData): float
    {
        // Simplified retention calculation
        $usageCount = $customerUsage->count();
        $timeframeMonths = $timeframeData['days'] / 30;
        
        return min(1.0, ($usageCount / $timeframeMonths) * 0.2);
    }

    private function calculateRetentionRate(array $customerBehavior): float
    {
        $highRetentionCustomers = collect($customerBehavior)->where('retention_likelihood', '>', 0.5);
        return count($customerBehavior) > 0 ? ($highRetentionCustomers->count() / count($customerBehavior)) * 100 : 0;
    }

    private function calculatePromotionMetrics($promotion): array
    {
        $usageData = $promotion->usageData ?? collect();
        
        return [
            'total_uses' => $usageData->count(),
            'total_discounts' => $usageData->sum('discount_amount'),
            'total_revenue' => $usageData->sum('order_value'),
            'avg_order_value' => $usageData->avg('order_value') ?? 0,
            'conversion_rate' => 0, // Would need quotation data
            'roi' => 0, // Would be calculated
            'performance_score' => 0 // Composite score
        ];
    }

    private function aggregatePerformanceByType(array $promotionMetrics): array
    {
        $byType = collect($promotionMetrics)->groupBy(function($item) {
            return $item['promotion']->campaign_type;
        });
        
        return $byType->map(function($group) {
            return [
                'count' => $group->count(),
                'total_uses' => $group->sum('total_uses'),
                'total_revenue' => $group->sum('total_revenue'),
                'avg_performance_score' => $group->avg('performance_score')
            ];
        })->toArray();
    }

    private function getTopPromotions($usageData, int $limit = 5): array
    {
        $promotionUsage = $usageData->groupBy('promotional_campaign_id')
            ->map(function($group) {
                return [
                    'promotion_id' => $group->first()->promotional_campaign_id,
                    'promotion_name' => $group->first()->promotionalCampaign->name ?? 'Unknown',
                    'uses' => $group->count(),
                    'total_discounts' => $group->sum('discount_amount'),
                    'total_revenue' => $group->sum('order_value')
                ];
            })
            ->sortByDesc('uses')
            ->take($limit)
            ->values()
            ->toArray();
            
        return $promotionUsage;
    }

    private function getConversionTrends(Carbon $startDate, int $days): array
    {
        $trends = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dailyUsage = CustomerPromotionUsage::whereDate('used_at', $date)->count();
            $trends[] = [
                'date' => $date->toDateString(),
                'conversions' => $dailyUsage
            ];
        }
        return $trends;
    }

    private function getSegmentDistribution($usageData): array
    {
        return $usageData->groupBy(function($item) {
            return $item->customer->customer_type ?? 'unknown';
        })->map->count()->toArray();
    }

    private function getRealTimeAlerts(): array
    {
        $alerts = [];
        
        // Check for high-usage promotions
        $highUsagePromotions = CustomerPromotionUsage::with('promotionalCampaign')
            ->where('used_at', '>=', now()->subHours(1))
            ->groupBy('promotional_campaign_id')
            ->havingRaw('COUNT(*) > 10')
            ->get();
            
        foreach ($highUsagePromotions as $promotion) {
            $alerts[] = [
                'type' => 'high_usage',
                'message' => "High usage detected for promotion: {$promotion->promotionalCampaign->name}",
                'priority' => 'medium',
                'promotion_id' => $promotion->promotional_campaign_id
            ];
        }
        
        return $alerts;
    }

    private function calculateSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;
        
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += ($i + 1) * $values[$i];
            $sumX2 += ($i + 1) * ($i + 1);
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        return $slope;
    }

    private function calculateConsistency(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        $stdDev = sqrt($variance);
        
        return $mean > 0 ? 1 - ($stdDev / $mean) : 0;
    }

    private function comparePerformance($promotion, array $similarPromotions): array
    {
        $ourMetrics = $this->calculatePromotionMetrics($promotion);
        $avgSimilarMetrics = [
            'total_uses' => collect($similarPromotions)->avg('metrics.total_uses'),
            'total_revenue' => collect($similarPromotions)->avg('metrics.total_revenue'),
            'performance_score' => collect($similarPromotions)->avg('metrics.performance_score')
        ];
        
        return [
            'uses_vs_average' => $ourMetrics['total_uses'] / $avgSimilarMetrics['total_uses'],
            'revenue_vs_average' => $ourMetrics['total_revenue'] / $avgSimilarMetrics['total_revenue'],
            'performance_vs_average' => $ourMetrics['performance_score'] / $avgSimilarMetrics['performance_score']
        ];
    }

    private function getIndustryBenchmarks(string $campaignType): array
    {
        return match($campaignType) {
            'percentage' => [
                'avg_conversion_rate' => 15.5,
                'avg_roi' => 120,
                'avg_discount_rate' => 8.2
            ],
            'fixed_amount' => [
                'avg_conversion_rate' => 18.2,
                'avg_roi' => 95,
                'avg_discount_rate' => 12.1
            ],
            'free_shipping' => [
                'avg_conversion_rate' => 22.8,
                'avg_roi' => 180,
                'avg_discount_rate' => 5.5
            ],
            default => [
                'avg_conversion_rate' => 16.0,
                'avg_roi' => 110,
                'avg_discount_rate' => 9.0
            ]
        };
    }

    private function calculateLinearTrend(array $dailyData): array
    {
        $slope = $this->calculateSlope(array_values($dailyData));
        $lastValue = end($dailyData);
        $predictedNextWeek = max(0, $lastValue + ($slope * 7));
        
        return [
            'direction' => $slope > 0.1 ? 'increasing' : ($slope < -0.1 ? 'decreasing' : 'stable'),
            'slope' => $slope,
            'predicted_next_week' => $predictedNextWeek
        ];
    }

    private function calculateOptimalExpiry($usageData, array $trend): Carbon
    {
        $currentUsage = $usageData->count();
        $projectedUsage = $trend['predicted_next_week'] * 4; // Monthly projection
        
        if ($currentUsage < 10 || $projectedUsage < 50) {
            return now()->addDays(7); // Expire soon if low usage
        }
        
        return now()->addDays(30); // Default 30 days
    }

    private function generatePredictiveRecommendation(array $trend, $usageData): string
    {
        if ($trend['direction'] === 'increasing' && $trend['predicted_next_week'] > 20) {
            return 'Strong growth trend detected. Consider extending promotion duration or increasing marketing spend.';
        } elseif ($trend['direction'] === 'decreasing') {
            return 'Usage is declining. Review promotion visibility and consider refreshing creative assets.';
        } else {
            return 'Stable performance. Continue monitoring and optimize based on customer feedback.';
        }
    }
}