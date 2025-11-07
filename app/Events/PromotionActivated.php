<?php

namespace App\Events;

use App\Models\PromotionalCampaign;
use App\Models\Customer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Promotion Activated Event
 * 
 * Fired when a promotional campaign is activated/deployed
 */
class PromotionActivated
{
    use InteractsWithSockets, SerializesModels;

    public $promotion;
    public $activatedBy;
    public $activationData;
    public $timestamp;

    public function __construct(
        PromotionalCampaign $promotion,
        ?int $activatedBy = null,
        array $activationData = []
    ) {
        $this->promotion = $promotion;
        $this->activatedBy = $activatedBy;
        $this->activationData = $activationData;
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
        return 'promotion.activated';
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
            'effective_from' => $this->promotion->effective_from->toISOString(),
            'effective_to' => $this->promotion->effective_to?->toISOString(),
            'usage_limit' => $this->promotion->usage_limit,
            'customer_eligibility' => $this->promotion->customer_eligibility,
            'activation_data' => $this->activationData,
            'activated_by' => $this->activatedBy,
            'timestamp' => $this->timestamp->toISOString()
        ];
    }

    /**
     * Determine if this event should broadcast
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast important promotions
        return $this->promotion->usage_limit > 100 || 
               $this->promotion->value > 50 ||
               ($this->promotion->campaign_type === 'percentage' && $this->promotion->value > 20);
    }

    /**
     * Get the promotion context for listeners
     */
    public function getPromotionContext(): array
    {
        return [
            'promotion' => $this->promotion,
            'activation_details' => [
                'by_user' => $this->activatedBy,
                'data' => $this->activationData,
                'timestamp' => $this->timestamp
            ],
            'business_impact' => [
                'is_high_value' => $this->promotion->value > 100,
                'is_widespread' => $this->promotion->usage_limit > 1000,
                'customer_facing' => $this->promotion->promo_code !== null
            ]
        ];
    }
}