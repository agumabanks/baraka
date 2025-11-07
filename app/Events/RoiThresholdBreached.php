<?php

namespace App\Events;

use App\Models\PromotionalCampaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * ROI Threshold Breached Event
 * 
 * Fired when a promotion's ROI crosses specified thresholds (high or low performance)
 */
class RoiThresholdBreached
{
    use InteractsWithSockets, SerializesModels;

    public $promotion;
    public $breachType;
    public $roiData;
    public $threshold;
    public $alertLevel;
    public $recommendedActions;
    public $timestamp;

    public function __construct(
        PromotionalCampaign $promotion,
        string $breachType,
        array $roiData,
        float $threshold,
        string $alertLevel = 'medium'
    ) {
        $this->promotion = $promotion;
        $this->breachType = $breachType;
        $this->roiData = $roiData;
        $this->threshold = $threshold;
        $this->alertLevel = $alertLevel;
        $this->recommendedActions = $this->generateRecommendedActions();
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): Channel
    {
        return new Channel('promotion-analytics');
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'promotion.roi.threshold.breached';
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
            'promo_code' => $this->promotion->promo_code,
            'breach_details' => [
                'breach_type' => $this->breachType,
                'current_roi' => $this->roiData['roi_percentage'] ?? 0,
                'threshold' => $this->threshold,
                'breach_magnitude' => $this->calculateBreachMagnitude(),
                'breach_direction' => $this->getBreachDirection()
            ],
            'roi_analysis' => [
                'revenue_impact' => $this->roiData['revenue_impact'] ?? 0,
                'cost_impact' => $this->roiData['cost_impact'] ?? 0,
                'net_impact' => $this->roiData['net_impact'] ?? 0,
                'conversion_rate' => $this->roiData['conversion_rate'] ?? 0,
                'customer_engagement' => $this->roiData['customer_engagement'] ?? 0
            ],
            'alert_details' => [
                'alert_level' => $this->alertLevel,
                'urgency_score' => $this->calculateUrgencyScore(),
                'action_required' => $this->alertLevel !== 'low',
                'escalation_needed' => $this->shouldEscalate()
            ],
            'recommended_actions' => $this->recommendedActions,
            'timestamp' => $this->timestamp->toISOString()
        ];
    }

    /**
     * Determine if this event should broadcast
     */
    public function broadcastWhen(): bool
    {
        // Always broadcast ROI threshold breaches for monitoring
        return true;
    }

    /**
     * Get comprehensive ROI analysis
     */
    public function getRoiAnalysis(): array
    {
        return [
            'performance_metrics' => [
                'current_roi' => $this->roiData['roi_percentage'] ?? 0,
                'target_roi' => $this->threshold,
                'performance_vs_target' => $this->getPerformanceVsTarget(),
                'trend_direction' => $this->getRoiTrend()
            ],
            'financial_impact' => [
                'total_revenue' => $this->roiData['total_revenue'] ?? 0,
                'total_costs' => $this->roiData['total_costs'] ?? 0,
                'net_profit' => $this->roiData['net_profit'] ?? 0,
                'cost_per_acquisition' => $this->roiData['cost_per_acquisition'] ?? 0
            ],
            'business_impact' => [
                'customer_acquisition' => $this->roiData['new_customers'] ?? 0,
                'customer_retention' => $this->roiData['retained_customers'] ?? 0,
                'customer_satisfaction' => $this->roiData['satisfaction_score'] ?? 0,
                'market_share_impact' => $this->roiData['market_share_change'] ?? 0
            ],
            'competitive_position' => [
                'vs_competitors' => $this->roiData['competitive_roi'] ?? 0,
                'market_position' => $this->getMarketPosition(),
                'differentiation_impact' => $this->roiData['differentiation_impact'] ?? 0
            ]
        ];
    }

    /**
     * Calculate breach magnitude
     */
    private function calculateBreachMagnitude(): string
    {
        $current = $this->roiData['roi_percentage'] ?? 0;
        $difference = abs($current - $this->threshold);
        $percentageDiff = ($difference / $this->threshold) * 100;
        
        return match(true) {
            $percentageDiff > 50 => 'severe',
            $percentageDiff > 25 => 'significant',
            $percentageDiff > 10 => 'moderate',
            default => 'minor'
        };
    }

    /**
     * Get breach direction
     */
    private function getBreachDirection(): string
    {
        $current = $this->roiData['roi_percentage'] ?? 0;
        
        return $current > $this->threshold ? 'above_threshold' : 'below_threshold';
    }

    /**
     * Calculate urgency score
     */
    private function calculateUrgencyScore(): int
    {
        $baseScore = match($this->breachType) {
            'low_performance' => 70,
            'high_performance' => 40,
            'budget_exceeded' => 90,
            default => 50
        };
        
        // Adjust based on magnitude
        $magnitude = $this->calculateBreachMagnitude();
        $magnitudeAdjustment = match($magnitude) {
            'severe' => 20,
            'significant' => 10,
            'moderate' => 5,
            default => 0
        };
        
        return min(100, $baseScore + $magnitudeAdjustment);
    }

    /**
     * Determine if escalation is needed
     */
    private function shouldEscalate(): bool
    {
        return $this->alertLevel === 'high' || 
               $this->calculateBreachMagnitude() === 'severe' ||
               $this->calculateUrgencyScore() > 80;
    }

    /**
     * Get performance vs target
     */
    private function getPerformanceVsTarget(): string
    {
        $current = $this->roiData['roi_percentage'] ?? 0;
        $performance = ($current / $this->threshold) * 100;
        
        return match(true) {
            $performance > 150 => 'excellent',
            $performance > 110 => 'good',
            $performance > 90 => 'acceptable',
            $performance > 70 => 'poor',
            default => 'critical'
        };
    }

    /**
     * Get ROI trend direction
     */
    private function getRoiTrend(): string
    {
        // This would typically analyze historical data
        // For now, return based on breach type
        return match($this->breachType) {
            'low_performance' => 'declining',
            'high_performance' => 'improving',
            'budget_exceeded' => 'exceeding_plan',
            default => 'stable'
        };
    }

    /**
     * Get market position
     */
    private function getMarketPosition(): string
    {
        $competitiveRoi = $this->roiData['competitive_roi'] ?? 0;
        $ourRoi = $this->roiData['roi_percentage'] ?? 0;
        
        if ($competitiveRoi === 0) return 'unknown';
        
        $relative = ($ourRoi / $competitiveRoi) * 100;
        
        return match(true) {
            $relative > 120 => 'market_leader',
            $relative > 100 => 'above_market',
            $relative > 80 => 'at_market',
            $relative > 60 => 'below_market',
            default => 'significantly_below_market'
        };
    }

    /**
     * Generate recommended actions based on breach type
     */
    private function generateRecommendedActions(): array
    {
        return match($this->breachType) {
            'low_performance' => [
                'Review and optimize promotion targeting',
                'Improve promotional messaging and visibility',
                'Consider adjusting discount levels',
                'Analyze customer feedback for insights',
                'Test alternative promotional formats',
                'Monitor competitor activities'
            ],
            'high_performance' => [
                'Scale up the successful promotion strategy',
                'Expand to similar customer segments',
                'Increase marketing investment',
                'Document success factors for replication',
                'Consider permanent implementation',
                'Share best practices across teams'
            ],
            'budget_exceeded' => [
                'Immediately review spending allocation',
                'Implement stricter usage controls',
                'Set per-customer usage limits',
                'Review eligibility criteria',
                'Consider early termination if necessary',
                'Analyze budget planning process'
            ],
            default => [
                'Conduct detailed analysis of current performance',
                'Review promotion parameters and settings',
                'Monitor for continued threshold breaches',
                'Consider market conditions impact'
            ]
        };
    }
}