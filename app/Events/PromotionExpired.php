<?php

namespace App\Events;

use App\Models\PromotionalCampaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Promotion Expired Event
 * 
 * Fired when a promotional campaign expires or is manually terminated
 */
class PromotionExpired
{
    use InteractsWithSockets, SerializesModels;

    public $promotion;
    public $expiryReason;
    public $impactMetrics;
    public $timestamp;

    public function __construct(
        PromotionalCampaign $promotion,
        string $expiryReason = 'scheduled_expiry',
        array $impactMetrics = []
    ) {
        $this->promotion = $promotion;
        $this->expiryReason = $expiryReason;
        $this->impactMetrics = $impactMetrics;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): Channel
    {
        return new Channel('promotions');
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'promotion.expired';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'promotion_id' => $this->promotion->id,
            'promotion_name' => $this->promotion->name,
            'campaign_type' => $this->promotion->campaign_type,
            'value' => $this->promotion->value,
            'promo_code' => $this->promotion->promo_code,
            'usage_count' => $this->promotion->usage_count,
            'usage_limit' => $this->promotion->usage_limit,
            'expiry_reason' => $this->expiryReason,
            'impact_metrics' => $this->impactMetrics,
            'expired_at' => $this->timestamp->toISOString(),
            'effective_period' => [
                'start' => $this->promotion->effective_from->toISOString(),
                'end' => $this->promotion->effective_to?->toISOString()
            ]
        ];
    }

    /**
     * Determine if this event should broadcast
     */
    public function broadcastWhen(): bool
    {
        // Broadcast for significant promotions
        return $this->promotion->usage_count > 50 || $this->promotion->value > 25;
    }

    /**
     * Get impact analysis for listeners
     */
    public function getImpactAnalysis(): array
    {
        return [
            'usage_stats' => [
                'total_uses' => $this->promotion->usage_count,
                'usage_percentage' => $this->promotion->usage_limit 
                    ? ($this->promotion->usage_count / $this->promotion->usage_limit) * 100 
                    : null,
                'days_active' => $this->promotion->effective_from->diffInDays(now())
            ],
            'performance' => $this->impactMetrics,
            'recommendation' => $this->generateExpiryRecommendation()
        ];
    }

    /**
     * Generate recommendation based on expiry
     */
    private function generateExpiryRecommendation(): string
    {
        $usagePercentage = $this->promotion->usage_limit 
            ? ($this->promotion->usage_count / $this->promotion->usage_limit) * 100 
            : 0;

        return match($this->expiryReason) {
            'manual_termination' => 'Promotion was manually terminated. Review termination reason.',
            'usage_limit_reached' => 'Promotion reached usage limit successfully. Consider similar campaigns.',
            'scheduled_expiry' => $usagePercentage < 50 
                ? 'Low usage during campaign period. Review targeting and promotion strategy.'
                : 'Good performance during campaign period. Consider renewal or similar promotions.',
            default => 'Promotion expired. Review performance for future campaigns.'
        };
    }
}