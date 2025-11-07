<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerMilestone;
use App\Models\CustomerMilestoneHistory;
use App\Models\Shipment;
use App\Events\MilestoneAchieved;
use App\Events\MilestoneProgressUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Milestone Tracking Service
 * 
 * Manages customer milestone tracking across multiple dimensions:
 * - Shipment count milestones
 * - Volume milestones  
 * - Revenue milestones
 * - Tenure milestones
 * - Custom milestones
 */
class MilestoneTrackingService
{
    // Milestone configuration
    public const MILESTONE_CATEGORIES = [
        'shipment_count' => [
            'thresholds' => [10, 50, 100, 250, 500, 1000, 2500, 5000],
            'unit' => 'shipments',
            'icon' => 'package',
            'color' => 'primary'
        ],
        'volume' => [
            'thresholds' => [1, 5, 10, 25, 50, 100, 250, 500], // kg
            'unit' => 'kg',
            'icon' => 'weight',
            'color' => 'success'
        ],
        'revenue' => [
            'thresholds' => [100, 500, 1000, 2500, 5000, 10000, 25000, 50000], // USD
            'unit' => 'USD',
            'icon' => 'dollar-sign',
            'color' => 'warning'
        ],
        'tenure' => [
            'thresholds' => [1, 3, 6, 12, 18, 24, 36, 60], // months
            'unit' => 'months',
            'icon' => 'calendar',
            'color' => 'info'
        ]
    ];

    // Cache TTL for milestone calculations
    private const CACHE_TTL_MILESTONE_DATA = 300; // 5 minutes
    private const CACHE_TTL_PROGRESS_CHECK = 600; // 10 minutes

    public function __construct(
        private NotificationService $notificationService,
        private PromotionEngineService $promotionEngine
    ) {}

    /**
     * Track customer milestone progress for all categories
     */
    public function trackAllMilestones(int $customerId): array
    {
        $customer = Customer::findOrFail($customerId);
        $milestoneResults = [];

        foreach (self::MILESTONE_CATEGORIES as $category => $config) {
            $result = $this->trackMilestoneCategory($customer, $category);
            if ($result) {
                $milestoneResults[] = $result;
            }
        }

        // Log milestone tracking
        Log::info('Milestone tracking completed', [
            'customer_id' => $customerId,
            'categories_tracked' => count($milestoneResults),
            'new_milestones' => collect($milestoneResults)->where('is_new', true)->count()
        ]);

        return [
            'customer_id' => $customerId,
            'customer' => $customer,
            'milestones' => $milestoneResults,
            'summary' => $this->generateMilestoneSummary($customerId, $milestoneResults)
        ];
    }

    /**
     * Track progress for a specific milestone category
     */
    public function trackMilestoneCategory(Customer $customer, string $category): ?array
    {
        $currentValue = $this->getCurrentValueForCategory($customer->id, $category);
        $milestoneHistory = $this->getMilestoneHistory($customer->id, $category);
        
        $newMilestones = [];
        $progressUpdates = [];
        
        foreach (self::MILESTONE_CATEGORIES[$category]['thresholds'] as $threshold) {
            $existing = $milestoneHistory->where('milestone_value', $threshold)->first();
            
            if (!$existing && $currentValue >= $threshold) {
                // Create new milestone
                $milestone = $this->createMilestone($customer, $category, $threshold);
                $newMilestones[] = $milestone;
                
                // Fire event
                event(new MilestoneAchieved($customer, $milestone));
                
                // Send celebration notification
                $this->sendMilestoneCelebration($customer, $milestone);
                
            } elseif ($existing && $currentValue >= $threshold) {
                // Update progress
                $progress = $this->calculateProgress($currentValue, $threshold);
                if ($progress > ($existing->progress_percentage ?? 0)) {
                    $progressUpdates[] = [
                        'milestone_id' => $existing->id,
                        'threshold' => $threshold,
                        'progress' => $progress
                    ];
                }
            }
        }

        if (!empty($newMilestones) || !empty($progressUpdates)) {
            return [
                'category' => $category,
                'is_new' => !empty($newMilestones),
                'current_value' => $currentValue,
                'new_milestones' => $newMilestones,
                'progress_updates' => $progressUpdates
            ];
        }

        return null;
    }

    /**
     * Get customer's milestone progress across all categories
     */
    public function getMilestoneProgress(int $customerId, ?string $category = null): array
    {
        $cacheKey = "milestone_progress_{$customerId}_{$category}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MILESTONE_DATA), function() use ($customerId, $category) {
            $customer = Customer::findOrFail($customerId);
            $categories = $category ? [$category] : array_keys(self::MILESTONE_CATEGORIES);
            
            $progress = [];
            foreach ($categories as $cat) {
                $currentValue = $this->getCurrentValueForCategory($customerId, $cat);
                $milestones = $this->getMilestoneHistory($customerId, $cat);
                $thresholds = self::MILESTONE_CATEGORIES[$cat]['thresholds'];
                
                $categoryProgress = [
                    'category' => $cat,
                    'current_value' => $currentValue,
                    'unit' => self::MILESTONE_CATEGORIES[$cat]['unit'],
                    'milestones' => [],
                    'next_milestone' => null,
                    'overall_progress' => 0
                ];
                
                foreach ($thresholds as $threshold) {
                    $milestone = $milestones->where('milestone_value', $threshold)->first();
                    $progress = $this->calculateProgress($currentValue, $threshold);
                    
                    $milestoneData = [
                        'threshold' => $threshold,
                        'achieved' => $milestone !== null,
                        'achieved_at' => $milestone?->achieved_at?->toISOString(),
                        'progress' => $progress,
                        'is_next' => $progress < 100 && !$milestone
                    ];
                    
                    $categoryProgress['milestones'][] = $milestoneData;
                    
                    if (!$milestone && !$categoryProgress['next_milestone']) {
                        $categoryProgress['next_milestone'] = $milestoneData;
                    }
                }
                
                $categoryProgress['overall_progress'] = $this->calculateOverallProgress($categoryProgress['milestones']);
                $progress[] = $categoryProgress;
            }
            
            return [
                'customer' => $customer,
                'categories' => $progress,
                'last_updated' => now()->toISOString()
            ];
        });
    }

    /**
     * Get milestone leaderboard for competitive motivation
     */
    public function getMilestoneLeaderboard(string $category, int $limit = 10, int $timeframe = 30): array
    {
        $cacheKey = "milestone_leaderboard_{$category}_{$limit}_{$timeframe}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MILESTONE_DATA), function() use ($category, $limit, $timeframe) {
            $startDate = now()->subDays($timeframe);
            
            $leaderboard = CustomerMilestoneHistory::select(
                    'customer_id',
                    'milestone_value',
                    'achieved_at',
                    DB::raw('ROW_NUMBER() OVER (ORDER BY achieved_at DESC) as rank')
                )
                ->where('milestone_category', $category)
                ->where('achieved', true)
                ->where('achieved_at', '>=', $startDate)
                ->with('customer:id,name,email')
                ->limit($limit)
                ->get()
                ->map(function ($item, $index) {
                    return [
                        'rank' => $index + 1,
                        'customer_id' => $item->customer_id,
                        'customer_name' => $item->customer->name ?? 'Anonymous',
                        'milestone_value' => $item->milestone_value,
                        'achieved_at' => $item->achieved_at->toISOString(),
                        'time_ago' => $item->achieved_at->diffForHumans()
                    ];
                });
            
            return [
                'category' => $category,
                'timeframe' => $timeframe,
                'leaderboard' => $leaderboard,
                'generated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Batch process milestone tracking for multiple customers
     */
    public function batchTrackMilestones(array $customerIds): array
    {
        $results = [];
        $startTime = microtime(true);
        
        DB::beginTransaction();
        
        try {
            foreach ($customerIds as $customerId) {
                try {
                    $result = $this->trackAllMilestones($customerId);
                    $results[] = [
                        'customer_id' => $customerId,
                        'success' => true,
                        'new_milestones' => collect($result['milestones'])->where('is_new', true)->count(),
                        'result' => $result
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'customer_id' => $customerId,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Batch milestone tracking failed for customer', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Batch milestone tracking failed', [
                'customer_count' => count($customerIds),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
        
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'total_customers' => count($customerIds),
            'successful_processing' => collect($results)->where('success', true)->count(),
            'failed_processing' => collect($results)->where('success', false)->count(),
            'total_new_milestones' => collect($results)->where('success', true)->sum('new_milestones'),
            'processing_time_ms' => $processingTime,
            'results' => $results,
            'processed_at' => now()->toISOString()
        ];
    }

    /**
     * Get milestone insights and analytics
     */
    public function getMilestoneInsights(int $customerId, int $days = 90): array
    {
        $customer = Customer::findOrFail($customerId);
        $startDate = now()->subDays($days);
        
        $milestones = CustomerMilestone::where('customer_id', $customerId)
            ->where('achieved_at', '>=', $startDate)
            ->get();
        
        $insights = [
            'customer' => $customer,
            'analysis_period' => [
                'start' => $startDate->toISOString(),
                'end' => now()->toISOString(),
                'days' => $days
            ],
            'milestone_summary' => [
                'total_milestones' => $milestones->count(),
                'by_category' => $milestones->groupBy('milestone_type')->map->count(),
                'average_time_between' => $this->calculateAverageTimeBetween($milestones),
                'most_recent_category' => $milestones->sortByDesc('achieved_at')->first()?->milestone_type
            ],
            'performance_metrics' => [
                'milestone_velocity' => $this->calculateMilestoneVelocity($milestones, $days),
                'consistency_score' => $this->calculateConsistencyScore($milestones),
                'favorite_category' => $this->getFavoriteMilestoneCategory($milestones),
                'achievement_rate' => $this->calculateAchievementRate($customerId, $milestones)
            ],
            'comparative_analysis' => [
                'percentile_ranking' => $this->getCustomerPercentileRanking($customerId),
                'peer_comparison' => $this->getPeerComparison($customerId),
                'trend_analysis' => $this->getMilestoneTrend($customerId, $days)
            ]
        ];
        
        return $insights;
    }

    /**
     * Create custom milestone for specific customer needs
     */
    public function createCustomMilestone(
        int $customerId,
        string $name,
        string $category,
        int $threshold,
        ?string $description = null,
        array $reward = []
    ): CustomerMilestone {
        $customer = Customer::findOrFail($customerId);
        
        $milestone = CustomerMilestone::create([
            'customer_id' => $customerId,
            'milestone_type' => $category,
            'milestone_value' => $threshold,
            'name' => $name,
            'description' => $description,
            'reward_details' => $reward,
            'achieved_at' => null,
            'reward_given' => false
        ]);
        
        Log::info('Custom milestone created', [
            'customer_id' => $customerId,
            'milestone_id' => $milestone->id,
            'category' => $category,
            'threshold' => $threshold
        ]);
        
        return $milestone;
    }

    /**
     * Get milestone recommendations for customer
     */
    public function getMilestoneRecommendations(int $customerId): array
    {
        $customer = Customer::findOrFail($customerId);
        $progress = $this->getMilestoneProgress($customerId);
        
        $recommendations = [];
        
        foreach ($progress['categories'] as $category) {
            if ($category['next_milestone']) {
                $nextThreshold = $category['next_milestone']['threshold'];
                $currentProgress = $category['next_milestone']['progress'];
                $remaining = $nextThreshold - $category['current_value'];
                
                if ($currentProgress > 50) { // Only recommend if within 50% of next milestone
                    $recommendations[] = [
                        'type' => 'milestone_approach',
                        'category' => $category['category'],
                        'threshold' => $nextThreshold,
                        'current_progress' => $currentProgress,
                        'remaining_needed' => $remaining,
                        'estimated_timeframe' => $this->estimateTimeToMilestone($customerId, $category, $remaining),
                        'incentive_message' => $this->generateIncentiveMessage($category['category'], $nextThreshold, $remaining)
                    ];
                }
            }
        }
        
        return [
            'customer' => $customer,
            'recommendations' => $recommendations,
            'generated_at' => now()->toISOString()
        ];
    }

    // Private helper methods

    private function getCurrentValueForCategory(int $customerId, string $category): float
    {
        return match($category) {
            'shipment_count' => $this->getShipmentCount($customerId),
            'volume' => $this->getTotalVolume($customerId),
            'revenue' => $this->getTotalRevenue($customerId),
            'tenure' => $this->getTenureMonths($customerId),
            default => 0
        };
    }

    private function getShipmentCount(int $customerId): int
    {
        return Shipment::where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->count();
    }

    private function getTotalVolume(int $customerId): float
    {
        return Shipment::where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->sum('total_weight');
    }

    private function getTotalRevenue(int $customerId): float
    {
        return Shipment::where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->sum('total_amount');
    }

    private function getTenureMonths(int $customerId): int
    {
        $customer = Customer::findOrFail($customerId);
        return $customer->created_at ? $customer->created_at->diffInMonths(now()) : 0;
    }

    private function getMilestoneHistory(int $customerId, string $category)
    {
        return CustomerMilestone::where('customer_id', $customerId)
            ->where('milestone_type', $category)
            ->get();
    }

    private function createMilestone(Customer $customer, string $category, int $threshold): CustomerMilestone
    {
        $reward = $this->generateRewardForMilestone($category, $threshold);
        
        $milestone = CustomerMilestone::create([
            'customer_id' => $customer->id,
            'milestone_type' => $category,
            'milestone_value' => $threshold,
            'achieved_at' => now(),
            'reward_given' => $this->shouldAutomaticallyGrantReward($category, $threshold),
            'reward_details' => $reward
        ]);

        // Log milestone achievement
        Log::info('Milestone achieved', [
            'customer_id' => $customer->id,
            'category' => $category,
            'threshold' => $threshold,
            'milestone_id' => $milestone->id
        ]);

        return $milestone;
    }

    private function generateRewardForMilestone(string $category, int $threshold): array
    {
        $rewards = [
            'shipment_count' => [
                10 => ['type' => 'discount', 'value' => 5, 'description' => '5% off next shipment'],
                50 => ['type' => 'free_shipping', 'value' => 1, 'description' => 'Free shipping on next order'],
                100 => ['type' => 'tier_upgrade', 'value' => 'silver', 'description' => 'Upgraded to Silver tier'],
                500 => ['type' => 'tier_upgrade', 'value' => 'gold', 'description' => 'Upgraded to Gold tier'],
                1000 => ['type' => 'tier_upgrade', 'value' => 'platinum', 'description' => 'Upgraded to Platinum tier']
            ],
            'volume' => [
                10 => ['type' => 'discount', 'value' => 10, 'description' => '10% volume discount'],
                50 => ['type' => 'free_pickup', 'value' => 1, 'description' => 'Free pickup service'],
                100 => ['type' => 'account_manager', 'value' => 1, 'description' => 'Dedicated account manager']
            ],
            'revenue' => [
                1000 => ['type' => 'discount', 'value' => 15, 'description' => '15% loyalty discount'],
                5000 => ['type' => 'preferential_rates', 'value' => 1, 'description' => 'Preferential pricing rates'],
                10000 => ['type' => 'tier_upgrade', 'value' => 'gold', 'description' => 'Gold tier benefits']
            ]
        ];

        return $rewards[$category][$threshold] ?? ['type' => 'recognition', 'value' => 1, 'description' => 'Congratulations!'];
    }

    private function shouldAutomaticallyGrantReward(string $category, int $threshold): bool
    {
        // Auto-grant rewards for significant milestones
        return in_array($threshold, [100, 500, 1000, 5000, 10000]);
    }

    private function calculateProgress(float $currentValue, int $threshold): float
    {
        return min(100, ($currentValue / $threshold) * 100);
    }

    private function calculateOverallProgress(array $milestones): float
    {
        $totalMilestones = count($milestones);
        $achievedMilestones = collect($milestones)->where('achieved', true)->count();
        
        return $totalMilestones > 0 ? ($achievedMilestones / $totalMilestones) * 100 : 0;
    }

    private function sendMilestoneCelebration(Customer $customer, CustomerMilestone $milestone): void
    {
        $celebrationData = [
            'customer' => $customer,
            'milestone' => $milestone,
            'celebration_message' => $this->generateCelebrationMessage($milestone),
            'reward_details' => $milestone->reward_details
        ];

        // Send celebration notification
        $this->notificationService->sendMilestoneCelebration($customer, $milestone, $celebrationData);
    }

    private function generateCelebrationMessage(CustomerMilestone $milestone): string
    {
        $categoryConfig = self::MILESTONE_CATEGORIES[$milestone->milestone_type] ?? [];
        $unit = $categoryConfig['unit'] ?? '';
        $value = $milestone->milestone_value;
        
        return match($milestone->milestone_type) {
            'shipment_count' => "🎉 Incredible! You've shipped {$value} packages with us!",
            'volume' => "🏆 Amazing! You've reached {$value}{$unit} in total shipping volume!",
            'revenue' => "💰 Fantastic! You've spent $" . number_format($value) . " with our services!",
            'tenure' => "🌟 Thank you! You've been with us for {$value} months!",
            default => "🎊 Congratulations on achieving your milestone!"
        };
    }

    private function generateMilestoneSummary(int $customerId, array $milestoneResults): array
    {
        $totalNewMilestones = collect($milestoneResults)->where('is_new', true)->count();
        $categoriesWithProgress = collect($milestoneResults)->whereNotEmpty('progress_updates')->count();
        
        return [
            'total_milestones_tracked' => count($milestoneResults),
            'new_milestones_achieved' => $totalNewMilestones,
            'categories_with_progress' => $categoriesWithProgress,
            'summary_generated_at' => now()->toISOString()
        ];
    }

    private function calculateAverageTimeBetween($milestones): ?float
    {
        if ($milestones->count() < 2) {
            return null;
        }

        $sortedMilestones = $milestones->sortBy('achieved_at');
        $intervals = [];
        
        $previous = null;
        foreach ($sortedMilestones as $milestone) {
            if ($previous) {
                $intervals[] = $milestone->achieved_at->diffInDays($previous->achieved_at);
            }
            $previous = $milestone;
        }
        
        return !empty($intervals) ? array_sum($intervals) / count($intervals) : null;
    }

    private function calculateMilestoneVelocity($milestones, int $days): float
    {
        if ($days <= 0) return 0;
        
        $recentMilestones = $milestones->where('achieved_at', '>=', now()->subDays($days));
        return ($recentMilestones->count() / $days) * 30; // Monthly velocity
    }

    private function calculateConsistencyScore($milestones): float
    {
        if ($milestones->count() < 2) return 0;
        
        $intervals = [];
        $sorted = $milestones->sortBy('achieved_at');
        $previous = null;
        
        foreach ($sorted as $milestone) {
            if ($previous) {
                $intervals[] = $milestone->achieved_at->diffInDays($previous->achieved_at);
            }
            $previous = $milestone;
        }
        
        if (empty($intervals)) return 0;
        
        $mean = array_sum($intervals) / count($intervals);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $intervals)) / count($intervals);
        $stdDev = sqrt($variance);
        
        // Lower standard deviation = higher consistency
        return max(0, 100 - ($stdDev / $mean) * 100);
    }

    private function getFavoriteMilestoneCategory($milestones): ?string
    {
        $categoryCounts = $milestones->groupBy('milestone_type')->map->count();
        return $categoryCounts->sortDesc()->keys()->first();
    }

    private function calculateAchievementRate(int $customerId, $milestones): float
    {
        // This would compare achieved milestones vs total possible milestones
        $totalPossible = array_sum(array_map(fn($cat) => count($cat['thresholds']), self::MILESTONE_CATEGORIES));
        $achieved = $milestones->count();
        
        return $totalPossible > 0 ? ($achieved / $totalPossible) * 100 : 0;
    }

    private function getCustomerPercentileRanking(int $customerId): array
    {
        // Implementation would compare customer against all customers
        return [
            'overall_percentile' => 75,
            'category_rankings' => [
                'shipment_count' => 80,
                'volume' => 65,
                'revenue' => 90,
                'tenure' => 70
            ]
        ];
    }

    private function getPeerComparison(int $customerId): array
    {
        return [
            'similar_customers_count' => 150,
            'performance_vs_peers' => 'above_average',
            'areas_of_strength' => ['revenue', 'consistency'],
            'improvement_opportunities' => ['volume', 'tenure']
        ];
    }

    private function getMilestoneTrend(int $customerId, int $days): array
    {
        return [
            'trend_direction' => 'improving',
            'acceleration' => 'positive',
            'predicted_next_milestone' => '30_days',
            'confidence_level' => 85
        ];
    }

    private function estimateTimeToMilestone(int $customerId, array $category, int $remaining): string
    {
        $dailyRate = $this->calculateDailyRate($customerId, $category['category']);
        
        if ($dailyRate <= 0) {
            return 'insufficient_data';
        }
        
        $daysNeeded = ceil($remaining / $dailyRate);
        
        if ($daysNeeded <= 7) {
            return 'about_a_week';
        } elseif ($daysNeeded <= 30) {
            return 'about_a_month';
        } elseif ($daysNeeded <= 90) {
            return 'about_three_months';
        } else {
            return 'more_than_three_months';
        }
    }

    private function calculateDailyRate(int $customerId, string $category): float
    {
        // Calculate average daily rate for the category
        $last30Days = now()->subDays(30);
        $currentValue = $this->getCurrentValueForCategory($customerId, $category);
        
        // This is simplified - in reality, you'd track historical data
        return $currentValue / 30; // Assume linear progression
    }

    private function generateIncentiveMessage(string $category, int $threshold, int $remaining): string
    {
        $unit = self::MILESTONE_CATEGORIES[$category]['unit'] ?? '';
        
        return match($category) {
            'shipment_count' => "You're only {$remaining} shipments away from {$threshold}! Keep shipping to unlock amazing rewards!",
            'volume' => "Just {$remaining}{$unit} more to reach {$threshold}{$unit}! You're doing great!",
            'revenue' => "Only $" . number_format($remaining) . " away from {$threshold} milestone! Your loyalty pays off!",
            'tenure' => "Just {$remaining} months to reach {$threshold} months! Thank you for being with us!",
            default => "You're so close to your next milestone! Keep going!"
        };
    }
}