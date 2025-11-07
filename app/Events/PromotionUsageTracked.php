<?php

namespace App\Events;

use App\Models\PromotionalCampaign;
use App\Models\Customer;
use App\Models\CustomerPromotionUsage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Promotion Usage Tracked Event
 * 
 * Fired when a customer uses a promotional code
 */
class PromotionUsageTracked
{
    use InteractsWithSockets, SerializesModels;

    public $promotion;
    public $customer;
    public $usage;
    public $orderContext;
    public $impactMetrics;
    public $timestamp;

    public function __construct(
        PromotionalCampaign $promotion,
        Customer $customer,
        CustomerPromotionUsage $usage,
        array $orderContext = [],
        array $impactMetrics = []
    ) {
        $this->promotion = $promotion;
        $this->customer = $customer;
        $this->usage = $usage;
        $this->orderContext = $orderContext;
        $this->impactMetrics = $impactMetrics;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): Channel
    {
        return new Channel('promotion-usage');
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'promotion.usage.tracked';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'promotion_id' => $this->promotion->id,
            'promotion_name' => $this->promotion->name,
            'promo_code' => $this->promotion->promo_code,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_email' => $this->customer->email,
            'usage' => [
                'id' => $this->usage->id,
                'discount_amount' => $this->usage->discount_amount,
                'order_value' => $this->usage->order_value,
                'used_at' => $this->usage->used_at->toISOString(),
                'discount_percentage' => $this->calculateDiscountPercentage()
            ],
            'order_context' => $this->orderContext,
            'promotion_metrics' => [
                'total_usage_count' => $this->promotion->usage_count,
                'remaining_uses' => $this->getRemainingUses(),
                'usage_percentage' => $this->getUsagePercentage()
            ],
            'customer_impact' => $this->impactMetrics,
            'timestamp' => $this->timestamp->toISOString()
        ];
    }

    /**
     * Determine if this event should broadcast
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast for significant usage events
        return $this->usage->discount_amount > 50 || 
               $this->usage->order_value > 500 ||
               $this->getUsagePercentage() > 90;
    }

    /**
     * Get analytics context for this usage
     */
    public function getAnalyticsContext(): array
    {
        return [
            'usage_details' => [
                'discount_value' => $this->usage->discount_amount,
                'order_value' => $this->usage->order_value,
                'discount_rate' => $this->calculateDiscountPercentage(),
                'customer_value' => $this->customer->total_spent ?? 0
            ],
            'promotion_performance' => [
                'total_uses' => $this->promotion->usage_count,
                'usage_efficiency' => $this->calculateUsageEfficiency(),
                'customer_satisfaction_impact' => $this->getCustomerSatisfactionImpact()
            ],
            'business_impact' => [
                'revenue_protected' => $this->usage->order_value,
                'discount_cost' => $this->usage->discount_amount,
                'net_impact' => $this->usage->order_value - $this->usage->discount_amount,
                'customer_lifetime_value_effect' => $this->getCLVEffect()
            ]
        ];
    }

    /**
     * Calculate discount percentage
     */
    private function calculateDiscountPercentage(): float
    {
        return $this->usage->order_value > 0 
            ? ($this->usage->discount_amount / $this->usage->order_value) * 100 
            : 0;
    }

    /**
     * Get remaining uses for the promotion
     */
    private function getRemainingUses(): ?int
    {
        return $this->promotion->usage_limit 
            ? $this->promotion->usage_limit - $this->promotion->usage_count 
            : null;
    }

    /**
     * Get usage percentage
     */
    private function getUsagePercentage(): float
    {
        return $this->promotion->usage_limit > 0
            ? ($this->promotion->usage_count / $this->promotion->usage_limit) * 100
            : 0;
    }

    /**
     * Calculate usage efficiency
     */
    private function calculateUsageEfficiency(): float
    {
        $daysActive = $this->promotion->effective_from->diffInDays(now());
        if ($daysActive === 0) return $this->promotion->usage_count;
        
        return $this->promotion->usage_count / $daysActive;
    }

    /**
     * Get customer satisfaction impact
     */
    private function getCustomerSatisfactionImpact(): string
    {
        $discountPercentage = $this->calculateDiscountPercentage();
        
        return match(true) {
            $discountPercentage > 20 => 'high_positive',
            $discountPercentage > 10 => 'moderate_positive',
            $discountPercentage > 5 => 'slight_positive',
            default => 'neutral'
        };
    }

    /**
     * Get customer lifetime value effect
     */
    private function getCLVEffect(): string
    {
        $orderValue = $this->usage->order_value;
        $customerHistory = $this->customer->total_spent ?? 0;
        
        $growthRate = $customerHistory > 0 ? ($orderValue / $customerHistory) * 100 : 0;
        
        return match(true) {
            $growthRate > 50 => 'significant_boost',
            $growthRate > 25 => 'moderate_boost',
            $growthRate > 10 => 'slight_boost',
            default => 'maintains_trend'
        };
    }
}