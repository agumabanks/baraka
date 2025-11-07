<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\CustomerMilestone;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Milestone Achieved Event
 * 
 * Fired when a customer achieves a significant milestone
 */
class MilestoneAchieved
{
    use InteractsWithSockets, SerializesModels;

    public $customer;
    public $milestone;
    public $milestoneContext;
    public $isMajor;
    public $timestamp;

    public function __construct(
        Customer $customer,
        CustomerMilestone $milestone,
        array $milestoneContext = []
    ) {
        $this->customer = $customer;
        $this->milestone = $milestone;
        $this->milestoneContext = $milestoneContext;
        $this->isMajor = $this->determineIfMajor($milestone);
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): Channel
    {
        return new Channel("customer.{$this->customer->id}.milestones");
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'milestone.achieved';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_email' => $this->customer->email,
            'milestone' => [
                'id' => $this->milestone->id,
                'type' => $this->milestone->milestone_type,
                'value' => $this->milestone->milestone_value,
                'achieved_at' => $this->milestone->achieved_at?->toISOString(),
                'reward_given' => $this->milestone->reward_given,
                'reward_details' => $this->milestone->reward_details
            ],
            'milestone_context' => $this->milestoneContext,
            'is_major' => $this->isMajor,
            'achievement_details' => $this->getAchievementDetails(),
            'next_steps' => $this->getRecommendedNextSteps(),
            'timestamp' => $this->timestamp->toISOString()
        ];
    }

    /**
     * Determine if this event should broadcast
     */
    public function broadcastWhen(): bool
    {
        // Always broadcast milestone achievements for engagement
        return true;
    }

    /**
     * Get comprehensive achievement details
     */
    public function getAchievementDetails(): array
    {
        return [
            'achievement_title' => $this->milestone->getMilestoneTitle(),
            'achievement_message' => $this->generateAchievementMessage(),
            'milestone_description' => $this->getMilestoneDescription(),
            'celebration_data' => [
                'warrants_celebration' => $this->isMajor,
                'social_share_enabled' => $this->isMajor,
                'notification_level' => $this->isMajor ? 'high' : 'normal'
            ],
            'rewards' => $this->milestone->reward_details,
            'customer_impact' => $this->getCustomerImpact()
        ];
    }

    /**
     * Get recommended next steps for the customer
     */
    public function getRecommendedNextSteps(): array
    {
        $nextSteps = [];

        switch ($this->milestone->milestone_type) {
            case 'shipment_count':
                $nextSteps = [
                    'Continue shipping to unlock higher tiers',
                    'Check milestone progress in your dashboard',
                    'Explore tier-specific benefits'
                ];
                break;

            case 'volume':
                $nextSteps = [
                    'Track your volume progress',
                    'Take advantage of volume-based discounts',
                    'Consider bulk shipping options'
                ];
                break;

            case 'revenue':
                $nextSteps = [
                    'Enjoy increased spending power',
                    'Access premium customer benefits',
                    'Explore exclusive offers'
                ];
                break;

            case 'tenure':
                $nextSteps = [
                    'Thank you for your loyalty',
                    'Explore loyalty program benefits',
                    'Refer friends to earn rewards'
                ];
                break;

            default:
                $nextSteps = ['Continue your journey with us'];
        }

        // Add next milestone info if available
        $nextMilestone = $this->getNextMilestoneInfo();
        if ($nextMilestone) {
            $nextSteps[] = "Your next milestone: {$nextMilestone['description']} at {$nextMilestone['threshold']}";
        }

        return $nextSteps;
    }

    /**
     * Determine if this is a major milestone
     */
    private function determineIfMajor(CustomerMilestone $milestone): bool
    {
        $majorThresholds = [
            'shipment_count' => [100, 500, 1000, 5000],
            'volume' => [50, 100, 500, 1000],
            'revenue' => [5000, 10000, 25000, 50000],
            'tenure' => [12, 24, 36, 60]
        ];

        $type = $milestone->milestone_type;
        $value = $milestone->milestone_value;

        return in_array($value, $majorThresholds[$type] ?? []) || 
               $this->isFirstMilestone($milestone);
    }

    /**
     * Check if this is the customer's first milestone
     */
    private function isFirstMilestone(CustomerMilestone $milestone): bool
    {
        return CustomerMilestone::where('customer_id', $milestone->customer_id)
            ->where('id', '!=', $milestone->id)
            ->doesntExist();
    }

    /**
     * Generate achievement message
     */
    private function generateAchievementMessage(): string
    {
        $type = $this->milestone->milestone_type;
        $value = $this->milestone->milestone_value;

        return match($type) {
            'shipment_count' => "🎉 Incredible! You've shipped {$value} packages with us!",
            'volume' => "🏆 Amazing! You've reached {$value}kg in total shipping volume!",
            'revenue' => "💰 Fantastic! You've spent $" . number_format($value) . " with our services!",
            'tenure' => "🌟 Thank you! You've been with us for {$value} months!",
            default => "🎊 Congratulations on achieving this milestone!"
        };
    }

    /**
     * Get milestone description
     */
    private function getMilestoneDescription(): string
    {
        $type = $this->milestone->milestone_type;
        $value = $this->milestone->milestone_value;

        return match($type) {
            'shipment_count' => "You've successfully completed {$value} shipments",
            'volume' => "You've shipped a total volume of {$value}kg",
            'revenue' => "Your total spending has reached $" . number_format($value),
            'tenure' => "You've been a valued customer for {$value} months",
            default => "You've achieved a significant milestone"
        };
    }

    /**
     * Get customer impact information
     */
    private function getCustomerImpact(): array
    {
        $type = $this->milestone->milestone_type;
        $customerMetrics = $this->customer;

        return [
            'customer_tier' => $customerMetrics->customer_type ?? 'standard',
            'previous_milestones' => CustomerMilestone::where('customer_id', $this->customer->id)
                ->where('id', '!=', $this->milestone->id)
                ->count(),
            'total_spent' => $customerMetrics->total_spent ?? 0,
            'total_shipments' => $customerMetrics->total_shipments ?? 0,
            'customer_since' => $customerMetrics->created_at?->format('Y-m-d')
        ];
    }

    /**
     * Get next milestone information
     */
    private function getNextMilestoneInfo(): ?array
    {
        // This would typically come from the milestone service
        // For now, return a placeholder
        return null;
    }
}