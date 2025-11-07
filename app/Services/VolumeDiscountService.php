<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractVolumeDiscount;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\CustomerMilestone;
use App\Events\ContractVolumeTierAchieved;
use App\Events\ContractVolumeMilestoneReached;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Volume Discount Service
 * 
 * Handles automated volume discount calculations and tier progression including:
 * - Automated tier progression based on shipment volume
 * - Volume milestone tracking and alerts
 * - Discount calculation and application
 * - Customer milestone achievement detection
 * - Reward distribution system
 */
class VolumeDiscountService
{
    private const CACHE_TTL_VOLUME_DATA = 1800; // 30 minutes
    private const MILESTONE_VOLUME_THRESHOLDS = [10, 50, 100, 500, 1000, 5000];
    private const TIER_CHECK_INTERVAL_HOURS = 1; // Check hourly for tier progression

    public function __construct(
        private WebhookManagementService $webhookService
    ) {}

    /**
     * Calculate applicable discounts for a specific volume
     */
    public function calculateDiscountsForVolume(Contract $contract, int $volume): array
    {
        $applicableTier = ContractVolumeDiscount::getApplicableTier($contract->id, $volume);
        
        if (!$applicableTier) {
            return [
                'applicable' => false,
                'tier_name' => null,
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'final_amount' => 0,
                'savings' => 0,
                'next_tier' => null,
                'volume_to_next_tier' => 0
            ];
        }

        // Calculate discount based on tier
        $discountCalculation = $applicableTier->calculateDiscount(100, $volume); // Use 100 as base amount
        
        // Get next tier information
        $nextTier = $applicableTier->getNextTier();
        $progress = $applicableTier->getVolumeProgress($volume);
        
        return array_merge($discountCalculation, [
            'next_tier' => $nextTier?->tier_name,
            'volume_to_next_tier' => $progress['volume_to_next'] ?? 0,
            'progress_to_next_tier' => $progress['progress_percentage'] ?? 0
        ]);
    }

    /**
     * Get applicable discounts for current shipment
     */
    public function getApplicableDiscounts(Contract $contract, array $shipmentData): array
    {
        $volume = $this->calculateShipmentVolume($shipmentData);
        $period = $this->getCurrentPeriod();
        $periodVolume = $this->getContractVolumeInPeriod($contract->id, $period);
        
        // Calculate discounts for period volume
        $periodDiscounts = $this->calculateDiscountsForVolume($contract, $periodVolume + $volume);
        
        // Check for milestone achievements
        $milestones = $this->checkVolumeMilestones($contract, $periodVolume + $volume);
        
        // Calculate shipment-specific discount
        $shipmentDiscount = $this->calculateShipmentDiscount($contract, $volume, $shipmentData);
        
        return [
            'contract_id' => $contract->id,
            'shipment_volume' => $volume,
            'period_volume' => $periodVolume + $volume,
            'period_discounts' => $periodDiscounts,
            'shipment_discount' => $shipmentDiscount,
            'milestones' => $milestones,
            'total_savings' => $periodDiscounts['savings'] + $shipmentDiscount['savings'],
            'calculated_at' => now()->toISOString()
        ];
    }

    /**
     * Process volume update for contract
     */
    public function updateContractVolume(int $contractId, int $volumeIncrease, array $shipmentData = []): array
    {
        return DB::transaction(function() use ($contractId, $volumeIncrease, $shipmentData) {
            $contract = Contract::findOrFail($contractId);
            $oldVolume = $contract->current_volume ?? 0;
            $newVolume = $oldVolume + $volumeIncrease;
            
            // Update contract volume
            $contract->updateVolume($volumeIncrease);
            
            // Check for tier achievements
            $tierResults = $this->checkTierAchievements($contract, $oldVolume, $newVolume);
            
            // Check for milestone achievements
            $milestoneResults = $this->checkMilestoneAchievements($contract, $newVolume);
            
            // Update any active discount applications
            $discountResults = $this->updateActiveDiscounts($contract, $shipmentData);
            
            // Send notifications
            $this->sendVolumeNotifications($contract, $tierResults, $milestoneResults);
            
            // Log volume update
            $this->logVolumeUpdate($contract, $oldVolume, $newVolume, $volumeIncrease);
            
            // Trigger webhooks
            $this->triggerVolumeWebhooks($contract, $tierResults, $milestoneResults);
            
            return [
                'contract_id' => $contractId,
                'old_volume' => $oldVolume,
                'new_volume' => $newVolume,
                'volume_increase' => $volumeIncrease,
                'tier_achievements' => $tierResults,
                'milestone_achievements' => $milestoneResults,
                'discount_updates' => $discountResults,
                'updated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Get customer volume summary
     */
    public function getCustomerVolumeSummary(int $customerId, ?string $period = null): array
    {
        $customer = Customer::findOrFail($customerId);
        $period = $period ?? now()->format('Y-m');
        $startDate = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $contracts = Contract::where('customer_id', $customerId)
                           ->where('status', 'active')
                           ->get();
        
        $summary = [
            'customer_id' => $customerId,
            'customer_name' => $customer->company_name ?? $customer->contact_person,
            'period' => $period,
            'period_start' => $startDate->toISOString(),
            'period_end' => $endDate->toISOString(),
            'total_volume' => 0,
            'total_shipments' => 0,
            'active_contracts' => $contracts->count(),
            'tier_distribution' => [],
            'milestone_progress' => [],
            'projected_savings' => 0,
            'contract_summaries' => []
        ];

        foreach ($contracts as $contract) {
            $contractVolume = $this->getContractVolumeInPeriod($contract->id, $period);
            $contractShipments = $this->getContractShipmentsInPeriod($contract->id, $period);
            $discounts = $this->calculateDiscountsForVolume($contract, $contractVolume);
            
            $summary['total_volume'] += $contractVolume;
            $summary['total_shipments'] += $contractShipments;
            $summary['projected_savings'] += $discounts['savings'] ?? 0;
            
            $summary['contract_summaries'][] = [
                'contract_id' => $contract->id,
                'contract_name' => $contract->name,
                'volume' => $contractVolume,
                'shipments' => $contractShipments,
                'current_tier' => $discounts['tier_name'],
                'discount_percentage' => $discounts['discount_percentage'],
                'savings' => $discounts['savings'] ?? 0
            ];
        }

        // Get tier distribution
        $summary['tier_distribution'] = $this->calculateTierDistribution($contracts);
        
        // Get milestone progress
        $summary['milestone_progress'] = $this->getMilestoneProgress($customer, $summary['total_volume']);

        return $summary;
    }

    /**
     * Get automated tier progression information
     */
    public function getTierProgressionInfo(int $contractId): array
    {
        $contract = Contract::findOrFail($contractId);
        $currentVolume = $contract->current_volume ?? 0;
        $volumeTiers = $contract->volumeDiscounts()->orderBy('volume_requirement')->get();
        
        $progression = [
            'contract_id' => $contractId,
            'current_volume' => $currentVolume,
            'current_tier' => null,
            'next_tier' => null,
            'tier_progression' => [],
            'milestone_achievements' => [],
            'projected_savings' => 0
        ];

        foreach ($volumeTiers as $tier) {
            $tierData = [
                'tier_id' => $tier->id,
                'tier_name' => $tier->tier_name,
                'volume_requirement' => $tier->volume_requirement,
                'discount_percentage' => $tier->discount_percentage,
                'is_achieved' => $currentVolume >= $tier->volume_requirement,
                'progress_percentage' => 0,
                'volume_remaining' => 0
            ];

            if ($currentVolume >= $tier->volume_requirement) {
                $tierData['progress_percentage'] = 100;
                $tierData['volume_remaining'] = 0;
                
                if (!$progression['current_tier'] || $tier->volume_requirement > $progression['current_tier']['volume_requirement']) {
                    $progression['current_tier'] = $tierData;
                }
            } else {
                $tierData['progress_percentage'] = min(100, ($currentVolume / $tier->volume_requirement) * 100);
                $tierData['volume_remaining'] = $tier->volume_requirement - $currentVolume;
                
                if (!$progression['next_tier']) {
                    $progression['next_tier'] = $tierData;
                }
            }

            $progression['tier_progression'][] = $tierData;
        }

        // Calculate projected savings
        if ($progression['current_tier']) {
            $currentTier = $volumeTiers->where('tier_name', $progression['current_tier']['tier_name'])->first();
            $progression['projected_savings'] = $currentTier ? ($currentTier->discount_percentage / 100) * 100 : 0;
        }

        // Get milestone achievements
        $progression['milestone_achievements'] = CustomerMilestone::where('customer_id', $contract->customer_id)
                                                               ->byType('shipment_count')
                                                               ->orderBy('milestone_value')
                                                               ->get()
                                                               ->map(function($milestone) use ($currentVolume) {
                                                                   return [
                                                                       'milestone' => $milestone->getMilestoneTitle(),
                                                                       'value' => $milestone->milestone_value,
                                                                       'achieved_at' => $milestone->achieved_at->toISOString(),
                                                                       'is_current' => $currentVolume >= $milestone->milestone_value
                                                                   ];
                                                               })
                                                               ->toArray();

        return $progression;
    }

    /**
     * Calculate volume-based rewards
     */
    public function calculateVolumeRewards(int $contractId, int $volume, string $period = 'monthly'): array
    {
        $contract = Contract::findOrFail($contractId);
        $customer = $contract->customer;
        
        // Base reward calculation
        $baseReward = $this->calculateBaseReward($volume, $period);
        
        // Tier bonuses
        $tierBonuses = $this->calculateTierBonuses($contract, $volume);
        
        // Milestone bonuses
        $milestoneBonuses = $this->calculateMilestoneBonuses($customer, $volume);
        
        // Loyalty bonuses
        $loyaltyBonus = $this->calculateLoyaltyBonus($customer, $contract);
        
        $totalRewards = $baseReward + $tierBonuses + $milestoneBonuses + $loyaltyBonus;
        
        return [
            'contract_id' => $contractId,
            'volume' => $volume,
            'period' => $period,
            'rewards' => [
                'base_reward' => $baseReward,
                'tier_bonus' => $tierBonuses,
                'milestone_bonus' => $milestoneBonuses,
                'loyalty_bonus' => $loyaltyBonus,
                'total_rewards' => $totalRewards
            ],
            'reward_breakdown' => [
                'discount_percentage' => $totalRewards > 0 ? min(20, $totalRewards) : 0,
                'cashback_amount' => $totalRewards > 100 ? ($totalRewards - 100) * 0.1 : 0,
                'benefits_unlocked' => $this->getUnlockedBenefits($contract, $volume)
            ],
            'calculated_at' => now()->toISOString()
        ];
    }

    // Private helper methods

    private function calculateShipmentVolume(array $shipmentData): int
    {
        $weight = $shipmentData['weight_kg'] ?? 0;
        $pieces = $shipmentData['pieces'] ?? 1;
        
        return (int) ($weight * $pieces);
    }

    private function getCurrentPeriod(): string
    {
        return now()->format('Y-m'); // Monthly periods
    }

    private function getContractVolumeInPeriod(int $contractId, string $period): int
    {
        $startDate = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // This would be implemented based on your shipment tracking system
        // For now, return cached volume or contract current volume
        $contract = Contract::findOrFail($contractId);
        return $contract->current_volume ?? 0;
    }

    private function getContractShipmentsInPeriod(int $contractId, string $period): int
    {
        // Implementation would count actual shipments in the period
        return 0; // Placeholder
    }

    private function checkVolumeMilestones(Contract $contract, int $totalVolume): array
    {
        $milestones = [];
        $customer = $contract->customer;
        
        foreach (self::MILESTONE_VOLUME_THRESHOLDS as $threshold) {
            if ($totalVolume >= $threshold) {
                $milestone = CustomerMilestone::checkAndCreateMilestone($customer, 'shipment_count', $threshold);
                if ($milestone) {
                    $milestones[] = $milestone;
                    event(new ContractVolumeMilestoneReached($contract, $milestone));
                }
            }
        }
        
        return $milestones;
    }

    private function calculateShipmentDiscount(Contract $contract, int $volume, array $shipmentData): array
    {
        // Calculate per-shipment discount
        $baseAmount = 100; // Placeholder base amount
        $discounts = $this->calculateDiscountsForVolume($contract, $volume);
        
        $savings = $discounts['applicable'] ? ($baseAmount * $discounts['discount_percentage'] / 100) : 0;
        
        return [
            'applicable' => $discounts['applicable'],
            'discount_percentage' => $discounts['discount_percentage'],
            'discount_amount' => $savings,
            'savings' => $savings,
            'tier_name' => $discounts['tier_name']
        ];
    }

    private function checkTierAchievements(Contract $contract, int $oldVolume, int $newVolume): array
    {
        $achievements = [];
        $volumeTiers = $contract->volumeDiscounts()->orderBy('volume_requirement')->get();
        
        foreach ($volumeTiers as $tier) {
            $wasAchieved = $oldVolume >= $tier->volume_requirement;
            $isAchieved = $newVolume >= $tier->volume_requirement;
            
            if (!$wasAchieved && $isAchieved) {
                $achievements[] = [
                    'tier_id' => $tier->id,
                    'tier_name' => $tier->tier_name,
                    'volume_requirement' => $tier->volume_requirement,
                    'discount_percentage' => $tier->discount_percentage,
                    'achieved_at' => now()->toISOString()
                ];
                
                // Fire tier achievement event
                event(new ContractVolumeTierAchieved($contract, $tier));
            }
        }
        
        return $achievements;
    }

    private function checkMilestoneAchievements(Contract $contract, int $newVolume): array
    {
        $customer = $contract->customer;
        $achievements = [];
        
        // Check volume milestones
        $volumeMilestones = $this->checkVolumeMilestones($contract, $newVolume);
        $achievements = array_merge($achievements, $volumeMilestones);
        
        // Check revenue milestones
        $revenue = $this->calculateContractRevenue($contract, $newVolume);
        $revenueMilestones = $this->checkRevenueMilestones($customer, $revenue);
        $achievements = array_merge($achievements, $revenueMilestones);
        
        return $achievements;
    }

    private function checkRevenueMilestones(Customer $customer, float $revenue): array
    {
        $milestones = [];
        $revenueThresholds = [1000, 5000, 10000, 50000];
        
        foreach ($revenueThresholds as $threshold) {
            if ($revenue >= $threshold) {
                $milestone = CustomerMilestone::checkAndCreateMilestone($customer, 'revenue_volume', $threshold);
                if ($milestone) {
                    $milestones[] = $milestone;
                }
            }
        }
        
        return $milestones;
    }

    private function calculateContractRevenue(Contract $contract, int $volume): float
    {
        // Simplified revenue calculation
        // In practice, this would use actual shipment data
        $averageRatePerUnit = 5.0; // Placeholder
        return $volume * $averageRatePerUnit;
    }

    private function updateActiveDiscounts(Contract $contract, array $shipmentData): array
    {
        // Update any active discount applications
        // This would integrate with the billing system
        return ['updated' => true, 'changes' => []];
    }

    private function sendVolumeNotifications(Contract $contract, array $tierResults, array $milestoneResults): void
    {
        // Send tier achievement notifications
        foreach ($tierResults as $tier) {
            Log::info('Volume tier achieved', [
                'contract_id' => $contract->id,
                'tier_name' => $tier['tier_name'],
                'volume_requirement' => $tier['volume_requirement']
            ]);
        }
        
        // Send milestone notifications
        foreach ($milestoneResults as $milestone) {
            Log::info('Volume milestone achieved', [
                'customer_id' => $contract->customer_id,
                'milestone_type' => $milestone->milestone_type,
                'milestone_value' => $milestone->milestone_value
            ]);
        }
    }

    private function logVolumeUpdate(Contract $contract, int $oldVolume, int $newVolume, int $volumeIncrease): void
    {
        Log::info('Contract volume updated', [
            'contract_id' => $contract->id,
            'old_volume' => $oldVolume,
            'new_volume' => $newVolume,
            'volume_increase' => $volumeIncrease,
            'updated_by' => auth()->id()
        ]);
    }

    private function triggerVolumeWebhooks(Contract $contract, array $tierResults, array $milestoneResults): void
    {
        // Trigger webhooks for significant events
        if (!empty($tierResults) || !empty($milestoneResults)) {
            $this->webhookService->triggerEvent('volume_milestone_achieved', [
                'contract_id' => $contract->id,
                'customer_id' => $contract->customer_id,
                'tier_achievements' => $tierResults,
                'milestone_achievements' => array_map(function($m) {
                    return [
                        'type' => $m->milestone_type,
                        'value' => $m->milestone_value,
                        'achieved_at' => $m->achieved_at->toISOString()
                    ];
                }, $milestoneResults),
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    private function calculateTierDistribution(Collection $contracts): array
    {
        $distribution = [];
        
        foreach ($contracts as $contract) {
            $currentVolume = $contract->current_volume ?? 0;
            $tier = ContractVolumeDiscount::getApplicableTier($contract->id, $currentVolume);
            
            $tierName = $tier?->tier_name ?? 'No Tier';
            $distribution[$tierName] = ($distribution[$tierName] ?? 0) + 1;
        }
        
        return $distribution;
    }

    private function getMilestoneProgress(Customer $customer, int $totalVolume): array
    {
        $milestones = [];
        
        foreach (self::MILESTONE_VOLUME_THRESHOLDS as $threshold) {
            $milestones[] = [
                'threshold' => $threshold,
                'achieved' => $totalVolume >= $threshold,
                'progress_percentage' => min(100, ($totalVolume / $threshold) * 100),
                'remaining' => max(0, $threshold - $totalVolume)
            ];
        }
        
        return $milestones;
    }

    private function calculateBaseReward(int $volume, string $period): float
    {
        return match($period) {
            'monthly' => $volume * 0.1,
            'quarterly' => $volume * 0.15,
            'annually' => $volume * 0.2,
            default => $volume * 0.1
        };
    }

    private function calculateTierBonuses(Contract $contract, int $volume): float
    {
        $discounts = $this->calculateDiscountsForVolume($contract, $volume);
        
        return $discounts['applicable'] ? ($discounts['discount_percentage'] * 2) : 0; // 2x multiplier for bonus
    }

    private function calculateMilestoneBonuses(Customer $customer, int $volume): float
    {
        $milestoneCount = CustomerMilestone::where('customer_id', $customer->id)
                                         ->byType('shipment_count')
                                         ->count();
        
        return $milestoneCount * 5; // $5 bonus per milestone achieved
    }

    private function calculateLoyaltyBonus(Customer $customer, Contract $contract): float
    {
        $tenureMonths = $customer->created_at ? $customer->created_at->diffInMonths(now()) : 0;
        
        if ($tenureMonths >= 12) {
            return 10; // $10 bonus for 1+ year customers
        } elseif ($tenureMonths >= 6) {
            return 5; // $5 bonus for 6+ month customers
        }
        
        return 0;
    }

    private function getUnlockedBenefits(Contract $contract, int $volume): array
    {
        $benefits = [];
        $discounts = $this->calculateDiscountsForVolume($contract, $volume);
        
        if ($discounts['applicable'] && isset($discounts['benefits'])) {
            $benefits = $discounts['benefits'];
        }
        
        // Add standard benefits based on volume
        if ($volume >= 100) {
            $benefits[] = 'priority_support';
        }
        
        if ($volume >= 500) {
            $benefits[] = 'dedicated_account_manager';
        }
        
        if ($volume >= 1000) {
            $benefits[] = 'custom_pricing';
        }
        
        return $benefits;
    }
}