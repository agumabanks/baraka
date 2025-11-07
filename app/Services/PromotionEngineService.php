<?php

namespace App\Services;

use App\Models\PromotionalCampaign;
use App\Models\Customer;
use App\Models\CustomerMilestone;
use App\Models\CustomerPromotionUsage;
use App\Models\PromotionStackingRule;
use App\Models\CustomerMilestoneHistory;
use App\Models\PromotionEffectivenessMetric;
use App\Events\PromotionActivated;
use App\Events\PromotionExpired;
use App\Events\MilestoneAchieved;
use App\Events\PromotionStackingViolated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Discount & Promotion Engine Service
 * 
 * Comprehensive service for managing promotional campaigns, milestone tracking,
 * dynamic rate overrides, anti-stacking logic, and promotion analytics.
 */
class PromotionEngineService
{
    // Promotion stacking rules
    public const STACKING_RULES = [
        'percentage_cap' => 50.0, // Maximum 50% discount when stacking
        'mutual_exclusion' => true,
        'tier_priority' => true
    ];

    // Milestone thresholds
    public const MILESTONE_THRESHOLDS = [
        'shipment_count' => [10, 50, 100, 500, 1000],
        'volume' => [1, 10, 100, 1000], // kg
        'revenue' => [1000, 5000, 10000, 50000], // USD
        'tenure' => [6, 12, 24, 36] // months
    ];

    // Cache TTL constants
    private const CACHE_TTL_PROMOTION_VALIDATION = 60; // 1 minute
    private const CACHE_TTL_MILESTONE_CHECK = 300; // 5 minutes
    private const CACHE_TTL_ANALYTICS = 900; // 15 minutes

    public function __construct(
        private MilestoneTrackingService $milestoneService,
        private PromotionAnalyticsService $analyticsService,
        private NotificationService $notificationService,
        private DynamicPricingService $dynamicPricingService,
        private ContractManagementService $contractService
    ) {}

    /**
     * Validate promotional code with comprehensive checks
     */
    public function validatePromotionalCode(
        string $code,
        ?int $customerId = null,
        array $orderData = []
    ): array {
        $startTime = microtime(true);
        
        try {
            $cacheKey = "promo_validation_{$code}_{$customerId}_" . md5(serialize($orderData));
            
            // Check cache first
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult) {
                return $cachedResult;
            }

            // Find the campaign
            $campaign = PromotionalCampaign::with('stackingRules')
                ->byCode($code)
                ->active()
                ->first();

            if (!$campaign) {
                $result = $this->buildValidationResult(false, 'Invalid or expired promotional code');
                Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_PROMOTION_VALIDATION));
                return $result;
            }

            // Get customer if provided
            $customer = $customerId ? Customer::find($customerId) : null;

            // Check basic validity
            if (!$campaign->isValid()) {
                $result = $this->buildValidationResult(false, 'Promotional campaign is not currently valid');
                Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_PROMOTION_VALIDATION));
                return $result;
            }

            // Check customer eligibility
            if ($customer && !$campaign->canCustomerUse($customer)) {
                $result = $this->buildValidationResult(false, 'Customer not eligible for this promotion');
                Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_PROMOTION_VALIDATION));
                return $result;
            }

            // Check minimum order value
            if (isset($orderData['total_amount']) && $campaign->minimum_order_value) {
                if ($orderData['total_amount'] < $campaign->minimum_order_value) {
                    $result = $this->buildValidationResult(false, 
                        "Minimum order value of {$campaign->minimum_order_value} required");
                    Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_PROMOTION_VALIDATION));
                    return $result;
                }
            }

            // Check anti-stacking rules
            $stackingCheck = $this->checkAntiStackingRules($customerId, $campaign, $orderData);
            if (!$stackingCheck['valid']) {
                $result = $this->buildValidationResult(false, $stackingCheck['message'], $stackingCheck);
                Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_PROMOTION_VALIDATION));
                return $result;
            }

            // Check customer usage limits
            if ($customerId) {
                $usageCheck = $this->checkCustomerUsageLimits($customerId, $campaign);
                if (!$usageCheck['valid']) {
                    $result = $this->buildValidationResult(false, $usageCheck['message']);
                    Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_PROMOTION_VALIDATION));
                    return $result;
                }
            }

            $result = $this->buildValidationResult(true, 'Promotional code is valid', [
                'campaign' => $campaign,
                'customer' => $customer,
                'order_data' => $orderData,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_PROMOTION_VALIDATION));
            
            Log::info('Promotional code validated', [
                'code' => $code,
                'customer_id' => $customerId,
                'valid' => true,
                'processing_time_ms' => $result['processing_time_ms']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Promotional code validation failed', [
                'code' => $code,
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return $this->buildValidationResult(false, 'Validation service temporarily unavailable');
        }
    }

    /**
     * Apply promotional discount with comprehensive calculations
     */
    public function applyPromotionalDiscount(
        string $campaignType,
        float $amount,
        ?int $customerId = null,
        array $contextData = []
    ): array {
        DB::beginTransaction();
        
        try {
            $customer = $customerId ? Customer::find($customerId) : null;
            $baseAmount = $amount;
            $discountBreakdown = [];
            $totalDiscount = 0;

            // Apply campaign-specific discount logic
            switch ($campaignType) {
                case 'percentage':
                    $discountAmount = $baseAmount * ($contextData['percentage'] / 100);
                    if (isset($contextData['max_discount'])) {
                        $discountAmount = min($discountAmount, $contextData['max_discount']);
                    }
                    $totalDiscount = $discountAmount;
                    $discountBreakdown['percentage'] = [
                        'rate' => $contextData['percentage'],
                        'amount' => $discountAmount
                    ];
                    break;

                case 'fixed_amount':
                    $totalDiscount = min($contextData['fixed_amount'], $baseAmount);
                    $discountBreakdown['fixed'] = [
                        'amount' => $contextData['fixed_amount'],
                        'applied' => $totalDiscount
                    ];
                    break;

                case 'free_shipping':
                    $shippingCost = $contextData['shipping_cost'] ?? 10.00;
                    $totalDiscount = $shippingCost;
                    $discountBreakdown['free_shipping'] = [
                        'shipping_cost' => $shippingCost
                    ];
                    break;

                case 'tier_upgrade':
                    // Handle tier upgrade separately
                    $discountBreakdown['tier_upgrade'] = [
                        'new_tier' => $contextData['new_tier'],
                        'benefits' => $contextData['benefits'] ?? []
                    ];
                    $totalDiscount = 0; // No immediate monetary discount
                    break;
            }

            // Check if discount exceeds maximum allowed
            $maxDiscountPercentage = self::STACKING_RULES['percentage_cap'];
            $maxDiscountAmount = $baseAmount * ($maxDiscountPercentage / 100);
            
            if ($totalDiscount > $maxDiscountAmount) {
                $totalDiscount = $maxDiscountAmount;
            }

            $finalAmount = $baseAmount - $totalDiscount;
            $discountPercentage = $baseAmount > 0 ? ($totalDiscount / $baseAmount) * 100 : 0;

            $result = [
                'valid' => true,
                'original_amount' => $baseAmount,
                'discount_amount' => $totalDiscount,
                'discount_percentage' => $discountPercentage,
                'final_amount' => $finalAmount,
                'breakdown' => $discountBreakdown,
                'applied_at' => now()->toISOString(),
                'customer_id' => $customerId
            ];

            // Track usage if customer provided
            if ($customerId && $totalDiscount > 0) {
                $this->trackPromotionUsage($customerId, $campaignType, $totalDiscount, $baseAmount, $contextData);
            }

            DB::commit();
            
            Log::info('Promotional discount applied', [
                'customer_id' => $customerId,
                'campaign_type' => $campaignType,
                'original_amount' => $baseAmount,
                'discount_amount' => $totalDiscount,
                'final_amount' => $finalAmount
            ]);

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to apply promotional discount', [
                'customer_id' => $customerId,
                'campaign_type' => $campaignType,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Failed to apply promotional discount: ' . $e->getMessage());
        }
    }

    /**
     * Track milestone progress and trigger achievements
     */
    public function trackMilestoneProgress(int $customerId, array $shipmentData): array
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $milestoneUpdates = [];
            
            // Update shipment count milestone
            $shipmentCount = $this->calculateCurrentShipmentCount($customerId);
            $shipmentMilestone = $this->checkShipmentMilestone($customer, $shipmentCount);
            if ($shipmentMilestone) {
                $milestoneUpdates[] = $shipmentMilestone;
            }

            // Update volume milestone
            $totalVolume = $this->calculateCurrentVolume($customerId);
            $volumeMilestone = $this->checkVolumeMilestone($customer, $totalVolume);
            if ($volumeMilestone) {
                $milestoneUpdates[] = $volumeMilestone;
            }

            // Update revenue milestone
            $totalRevenue = $this->calculateCurrentRevenue($customerId);
            $revenueMilestone = $this->checkRevenueMilestone($customer, $totalRevenue);
            if ($revenueMilestone) {
                $milestoneUpdates[] = $revenueMilestone;
            }

            // Update tenure milestone
            $tenureMonths = $this->calculateCustomerTenure($customer);
            $tenureMilestone = $this->checkTenureMilestone($customer, $tenureMonths);
            if ($tenureMilestone) {
                $milestoneUpdates[] = $tenureMilestone;
            }

            // Send notifications for new milestones
            foreach ($milestoneUpdates as $milestone) {
                $this->notifyMilestoneAchievement($customerId, $milestone);
            }

            // Update analytics
            $this->analyticsService->updateMilestoneAnalytics($customerId, $milestoneUpdates);

            return [
                'customer_id' => $customerId,
                'milestones_updated' => count($milestoneUpdates),
                'milestones' => $milestoneUpdates,
                'current_stats' => [
                    'shipment_count' => $shipmentCount,
                    'total_volume' => $totalVolume,
                    'total_revenue' => $totalRevenue,
                    'tenure_months' => $tenureMonths
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Milestone tracking failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Check anti-stacking rules to prevent multiple discounts
     */
    public function checkAntiStackingRules(?int $customerId, PromotionalCampaign $campaign, array $orderData): array
    {
        if (!$customerId) {
            return ['valid' => true];
        }

        // Get customer's recent promotion usage
        $recentUsage = CustomerPromotionUsage::where('customer_id', $customerId)
            ->where('used_at', '>=', now()->subDays(30))
            ->with('promotionalCampaign')
            ->get();

        $existingDiscounts = $recentUsage->map(function ($usage) {
            return $usage->promotionalCampaign;
        })->filter();

        // Check for incompatible promotion types
        $incompatibleTypes = $this->getIncompatiblePromotionTypes($campaign);
        foreach ($existingDiscounts as $existingCampaign) {
            if (in_array($existingCampaign->campaign_type, $incompatibleTypes)) {
                return [
                    'valid' => false,
                    'message' => "This promotion cannot be combined with {$existingCampaign->campaign_type} discounts",
                    'conflicting_promotion' => $existingCampaign->name
                ];
            }
        }

        // Check percentage stacking limits
        $totalPercentage = $this->calculateTotalDiscountPercentage($existingDiscounts, $orderData);
        $campaignPercentage = $this->getCampaignPercentage($campaign);
        $combinedPercentage = $totalPercentage + $campaignPercentage;

        if ($combinedPercentage > self::STACKING_RULES['percentage_cap']) {
            return [
                'valid' => false,
                'message' => "Maximum discount of " . self::STACKING_RULES['percentage_cap'] . "% would be exceeded",
                'current_percentage' => $totalPercentage,
                'campaign_percentage' => $campaignPercentage,
                'combined_percentage' => $combinedPercentage
            ];
        }

        return ['valid' => true];
    }

    /**
     * Calculate promotion ROI for effectiveness analysis
     */
    public function calculatePromotionROI(int $promotionId, string $timeframe = '30d'): array
    {
        $promotion = PromotionalCampaign::findOrFail($promotionId);
        $timeframeDays = $this->parseTimeframe($timeframe);
        
        $startDate = now()->subDays($timeframeDays);
        $endDate = now();

        // Get usage data
        $usageData = CustomerPromotionUsage::where('promotional_campaign_id', $promotionId)
            ->whereBetween('used_at', [$startDate, $endDate])
            ->get();

        if ($usageData->isEmpty()) {
            return [
                'promotion_id' => $promotionId,
                'timeframe' => $timeframe,
                'roi' => 0,
                'revenue_impact' => 0,
                'cost_impact' => 0,
                'net_impact' => 0,
                'usage_count' => 0
            ];
        }

        // Calculate metrics
        $totalDiscounts = $usageData->sum('discount_amount');
        $totalOrderValue = $usageData->sum('order_value');
        $usageCount = $usageData->count();
        $averageOrderValue = $usageCount > 0 ? $totalOrderValue / $usageCount : 0;

        // Calculate baseline (what would have happened without promotion)
        $baselineOrderValue = $this->calculateBaselineOrderValue($usageData, $promotion);
        
        // Calculate ROI components
        $revenueImpact = $totalOrderValue - $baselineOrderValue;
        $costImpact = $totalDiscounts; // Cost of providing discounts
        $netImpact = $revenueImpact - $costImpact;
        $roi = $costImpact > 0 ? ($netImpact / $costImpact) * 100 : 0;

        return [
            'promotion_id' => $promotionId,
            'timeframe' => $timeframe,
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'days' => $timeframeDays
            ],
            'usage_metrics' => [
                'total_uses' => $usageCount,
                'total_discounts' => $totalDiscounts,
                'total_order_value' => $totalOrderValue,
                'average_order_value' => $averageOrderValue
            ],
            'financial_impact' => [
                'baseline_order_value' => $baselineOrderValue,
                'revenue_impact' => $revenueImpact,
                'cost_impact' => $costImpact,
                'net_impact' => $netImpact
            ],
            'roi_metrics' => [
                'roi_percentage' => round($roi, 2),
                'revenue_per_use' => $usageCount > 0 ? $revenueImpact / $usageCount : 0,
                'cost_per_use' => $usageCount > 0 ? $costImpact / $usageCount : 0
            ]
        ];
    }

    /**
     * Generate promotion code with customizable patterns
     */
    public function generatePromotionCode(array $template, array $constraints = []): string
    {
        $code = '';
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $code = $this->buildCodeFromTemplate($template);
            $attempts++;
        } while ($this->isCodeExists($code) && $attempts < $maxAttempts);

        if ($attempts >= $maxAttempts) {
            throw new \Exception('Failed to generate unique promotion code after ' . $maxAttempts . ' attempts');
        }

        // Track code generation
        $this->trackCodeGeneration($code, $template, $constraints);

        return $code;
    }

    /**
     * Notify milestone achievement with comprehensive communication
     */
    public function notifyMilestoneAchievement(int $customerId, array $milestone): void
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $notificationData = [
                'customer' => $customer,
                'milestone' => $milestone,
                'reward_details' => $milestone['reward_details'] ?? [],
                'celebration_message' => $this->getMilestoneCelebrationMessage($milestone)
            ];

            // Send email notification
            if ($customer->email && $this->shouldSendEmailNotification($customerId)) {
                $this->notificationService->sendMilestoneEmail($customer->email, $notificationData);
            }

            // Send SMS notification
            if ($customer->phone && $this->shouldSendSmsNotification($customerId)) {
                $this->notificationService->sendMilestoneSms($customer->phone, $notificationData);
            }

            // Send push notification
            if ($this->shouldSendPushNotification($customerId)) {
                $this->notificationService->sendMilestonePush($customerId, $notificationData);
            }

            // Update milestone status
            $this->updateMilestoneNotificationStatus($customerId, $milestone['id'], 'sent');

            Log::info('Milestone achievement notification sent', [
                'customer_id' => $customerId,
                'milestone_type' => $milestone['type'],
                'milestone_value' => $milestone['value']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send milestone notification', [
                'customer_id' => $customerId,
                'milestone' => $milestone,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enforce promotion expiry and cleanup
     */
    public function enforcePromotionExpiry(): array
    {
        $expiredPromotions = PromotionalCampaign::where('is_active', true)
            ->where('effective_to', '<', now())
            ->get();

        $results = [];

        foreach ($expiredPromotions as $promotion) {
            try {
                // Deactivate the promotion
                $promotion->update(['is_active' => false]);

                // Log the expiry event
                $this->logPromotionEvent('expired', $promotion->id, null, [
                    'expired_at' => now()->toISOString(),
                    'reason' => 'Automatic expiry enforcement'
                ]);

                // Send expiry notifications to active users
                $this->notifyPromotionExpiry($promotion);

                // Update analytics
                $this->analyticsService->recordPromotionExpiry($promotion);

                $results[] = [
                    'promotion_id' => $promotion->id,
                    'name' => $promotion->name,
                    'status' => 'expired',
                    'expired_at' => now()->toISOString()
                ];

                // Fire event
                event(new PromotionExpired($promotion));

            } catch (\Exception $e) {
                Log::error('Failed to expire promotion', [
                    'promotion_id' => $promotion->id,
                    'error' => $e->getMessage()
                ]);

                $results[] = [
                    'promotion_id' => $promotion->id,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'total_processed' => count($expiredPromotions),
            'results' => $results,
            'processed_at' => now()->toISOString()
        ];
    }

    /**
     * Optimize promotion strategy based on customer segments
     */
    public function optimizePromotionStrategy(string $customerSegment): array
    {
        // Get segment analytics
        $segmentData = $this->analyticsService->getSegmentPerformance($customerSegment);
        
        // Analyze promotion effectiveness
        $effectiveness = $this->analyticsService->getPromotionEffectivenessBySegment($customerSegment);
        
        // Generate optimization recommendations
        $recommendations = $this->generateOptimizationRecommendations($segmentData, $effectiveness);
        
        return [
            'segment' => $customerSegment,
            'current_performance' => $segmentData,
            'promotion_effectiveness' => $effectiveness,
            'optimization_recommendations' => $recommendations,
            'estimated_impact' => $this->calculateOptimizationImpact($recommendations)
        ];
    }

    // Private helper methods

    private function buildValidationResult(bool $valid, string $message, array $data = []): array
    {
        $result = [
            'valid' => $valid,
            'message' => $message,
            'validated_at' => now()->toISOString()
        ];

        return array_merge($result, $data);
    }

    private function checkCustomerUsageLimits(int $customerId, PromotionalCampaign $campaign): array
    {
        if (!$campaign->usage_limit) {
            return ['valid' => true];
        }

        $customerUsage = CustomerPromotionUsage::where('customer_id', $customerId)
            ->where('promotional_campaign_id', $campaign->id)
            ->count();

        if ($customerUsage >= $campaign->usage_limit) {
            return [
                'valid' => false,
                'message' => 'Customer has reached the usage limit for this promotion'
            ];
        }

        return ['valid' => true];
    }

    private function trackPromotionUsage(
        int $customerId,
        string $campaignType,
        float $discountAmount,
        float $orderValue,
        array $contextData
    ): void {
        $campaign = PromotionalCampaign::where('campaign_type', $campaignType)
            ->active()
            ->first();

        if (!$campaign) {
            return;
        }

        CustomerPromotionUsage::create([
            'customer_id' => $customerId,
            'promotional_campaign_id' => $campaign->id,
            'usage_type' => 'single_use',
            'discount_amount' => $discountAmount,
            'order_value' => $orderValue,
            'order_details' => $contextData,
            'used_at' => now(),
            'source_channel' => $contextData['source_channel'] ?? 'api'
        ]);

        // Increment campaign usage count
        $campaign->increment('usage_count');
    }

    private function calculateCurrentShipmentCount(int $customerId): int
    {
        return \DB::table('shipments')
            ->where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->count();
    }

    private function calculateCurrentVolume(int $customerId): float
    {
        return \DB::table('shipments')
            ->where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->sum('total_weight');
    }

    private function calculateCurrentRevenue(int $customerId): float
    {
        return \DB::table('shipments')
            ->where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->sum('total_amount');
    }

    private function calculateCustomerTenure(Customer $customer): int
    {
        return $customer->created_at ? $customer->created_at->diffInMonths(now()) : 0;
    }

    private function checkShipmentMilestone(Customer $customer, int $currentCount): ?array
    {
        foreach (self::MILESTONE_THRESHOLDS['shipment_count'] as $threshold) {
            if ($currentCount >= $threshold) {
                $existing = CustomerMilestone::where('customer_id', $customer->id)
                    ->where('milestone_type', 'shipment_count')
                    ->where('milestone_value', $threshold)
                    ->first();

                if (!$existing) {
                    $milestone = CustomerMilestone::create([
                        'customer_id' => $customer->id,
                        'milestone_type' => 'shipment_count',
                        'milestone_value' => $threshold,
                        'achieved_at' => now()
                    ]);

                    return [
                        'id' => $milestone->id,
                        'type' => 'shipment_count',
                        'value' => $threshold,
                        'achieved_at' => now()->toISOString()
                    ];
                }
            }
        }

        return null;
    }

    private function checkVolumeMilestone(Customer $customer, float $currentVolume): ?array
    {
        foreach (self::MILESTONE_THRESHOLDS['volume'] as $threshold) {
            if ($currentVolume >= $threshold) {
                $existing = CustomerMilestone::where('customer_id', $customer->id)
                    ->where('milestone_type', 'volume')
                    ->where('milestone_value', $threshold)
                    ->first();

                if (!$existing) {
                    $milestone = CustomerMilestone::create([
                        'customer_id' => $customer->id,
                        'milestone_type' => 'volume',
                        'milestone_value' => $threshold,
                        'achieved_at' => now()
                    ]);

                    return [
                        'id' => $milestone->id,
                        'type' => 'volume',
                        'value' => $threshold,
                        'achieved_at' => now()->toISOString()
                    ];
                }
            }
        }

        return null;
    }

    private function checkRevenueMilestone(Customer $customer, float $currentRevenue): ?array
    {
        foreach (self::MILESTONE_THRESHOLDS['revenue'] as $threshold) {
            if ($currentRevenue >= $threshold) {
                $existing = CustomerMilestone::where('customer_id', $customer->id)
                    ->where('milestone_type', 'revenue')
                    ->where('milestone_value', $threshold)
                    ->first();

                if (!$existing) {
                    $milestone = CustomerMilestone::create([
                        'customer_id' => $customer->id,
                        'milestone_type' => 'revenue',
                        'milestone_value' => $threshold,
                        'achieved_at' => now()
                    ]);

                    return [
                        'id' => $milestone->id,
                        'type' => 'revenue',
                        'value' => $threshold,
                        'achieved_at' => now()->toISOString()
                    ];
                }
            }
        }

        return null;
    }

    private function checkTenureMilestone(Customer $customer, int $currentTenure): ?array
    {
        foreach (self::MILESTONE_THRESHOLDS['tenure'] as $threshold) {
            if ($currentTenure >= $threshold) {
                $existing = CustomerMilestone::where('customer_id', $customer->id)
                    ->where('milestone_type', 'tenure')
                    ->where('milestone_value', $threshold)
                    ->first();

                if (!$existing) {
                    $milestone = CustomerMilestone::create([
                        'customer_id' => $customer->id,
                        'milestone_type' => 'tenure',
                        'milestone_value' => $threshold,
                        'achieved_at' => now()
                    ]);

                    return [
                        'id' => $milestone->id,
                        'type' => 'tenure',
                        'value' => $threshold,
                        'achieved_at' => now()->toISOString()
                    ];
                }
            }
        }

        return null;
    }

    private function getIncompatiblePromotionTypes(PromotionalCampaign $campaign): array
    {
        return match($campaign->campaign_type) {
            'free_shipping' => ['free_shipping'],
            'tier_upgrade' => ['tier_upgrade'],
            'percentage' => ['percentage', 'fixed_amount'],
            'fixed_amount' => ['fixed_amount', 'percentage'],
            default => []
        };
    }

    private function calculateTotalDiscountPercentage($existingDiscounts, array $orderData): float
    {
        $totalPercentage = 0;
        
        foreach ($existingDiscounts as $campaign) {
            if ($campaign->campaign_type === 'percentage') {
                $totalPercentage += $campaign->value;
            }
        }
        
        return $totalPercentage;
    }

    private function getCampaignPercentage(PromotionalCampaign $campaign): float
    {
        return $campaign->campaign_type === 'percentage' ? $campaign->value : 0;
    }

    private function parseTimeframe(string $timeframe): int
    {
        return match($timeframe) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };
    }

    private function calculateBaselineOrderValue($usageData, PromotionalCampaign $promotion): float
    {
        // Calculate what orders would have been without promotion
        // This is a simplified calculation - in reality, you'd use more sophisticated modeling
        $totalValue = $usageData->sum('order_value');
        $totalDiscount = $usageData->sum('discount_amount');
        
        return $totalValue + $totalDiscount;
    }

    private function buildCodeFromTemplate(array $template): string
    {
        $code = '';
        
        foreach ($template as $segment) {
            switch ($segment['type']) {
                case 'static':
                    $code .= $segment['value'];
                    break;
                case 'random':
                    $code .= $this->generateRandomString($segment['length'] ?? 6);
                    break;
                case 'date':
                    $code .= now()->format($segment['format'] ?? 'Ymd');
                    break;
                case 'sequential':
                    $code .= $this->getNextSequence($segment['prefix'] ?? '');
                    break;
            }
        }
        
        return strtoupper($code);
    }

    private function generateRandomString(int $length): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $string;
    }

    private function getNextSequence(string $prefix): string
    {
        $cacheKey = "promo_sequence_{$prefix}";
        
        $sequence = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $sequence, now()->addDay());
        
        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    private function isCodeExists(string $code): bool
    {
        return PromotionalCampaign::where('promo_code', $code)->exists();
    }

    private function trackCodeGeneration(string $code, array $template, array $constraints): void
    {
        // Track in promotion_code_generations table
        // This would be implemented based on the migration structure
        Log::info('Promotion code generated', [
            'code' => $code,
            'template' => $template,
            'constraints' => $constraints
        ]);
    }

    private function shouldSendEmailNotification(int $customerId): bool
    {
        // Check customer preferences
        return true; // Simplified for now
    }

    private function shouldSendSmsNotification(int $customerId): bool
    {
        // Check customer preferences
        return true; // Simplified for now
    }

    private function shouldSendPushNotification(int $customerId): bool
    {
        // Check customer preferences
        return true; // Simplified for now
    }

    private function updateMilestoneNotificationStatus(int $customerId, int $milestoneId, string $status): void
    {
        // Update milestone history with notification status
        CustomerMilestoneHistory::where('customer_id', $customerId)
            ->where('id', $milestoneId)
            ->update(['notification_sent' => ['status' => $status, 'sent_at' => now()]]);
    }

    private function getMilestoneCelebrationMessage(array $milestone): string
    {
        return match($milestone['type']) {
            'shipment_count' => "Congratulations! You've shipped {$milestone['value']} packages with us!",
            'volume' => "Amazing! You've reached {$milestone['value']}kg in total shipping volume!",
            'revenue' => "Fantastic! You've spent $" . number_format($milestone['value']) . " with our services!",
            'tenure' => "Thank you for being with us for {$milestone['value']} months!",
            default => "Congratulations on achieving this milestone!"
        };
    }

    private function logPromotionEvent(string $eventType, int $promotionId, ?int $customerId, array $eventData): void
    {
        // Implementation for promotion event logging
        Log::info('Promotion event', [
            'event_type' => $eventType,
            'promotion_id' => $promotionId,
            'customer_id' => $customerId,
            'event_data' => $eventData
        ]);
    }

    private function notifyPromotionExpiry(PromotionalCampaign $promotion): void
    {
        // Send notifications about promotion expiry
        Log::info('Promotion expired notification', [
            'promotion_id' => $promotion->id,
            'name' => $promotion->name
        ]);
    }

    private function generateOptimizationRecommendations(array $segmentData, array $effectiveness): array
    {
        return [
            'increase_conversion_rates' => [
                'recommendation' => 'Test higher discount percentages for this segment',
                'expected_impact' => '15-25% increase in conversion rates',
                'implementation_effort' => 'Low'
            ],
            'improve_retention' => [
                'recommendation' => 'Implement milestone-based rewards',
                'expected_impact' => '20-30% improvement in customer retention',
                'implementation_effort' => 'Medium'
            ]
        ];
    }

    private function calculateOptimizationImpact(array $recommendations): array
    {
        return [
            'estimated_revenue_increase' => '10-20%',
            'estimated_conversion_improvement' => '15-25%',
            'estimated_cost_impact' => '5-10% increase in discount costs'
        ];
    }
}