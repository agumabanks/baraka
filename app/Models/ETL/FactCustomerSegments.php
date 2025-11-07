<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactCustomerSegments extends Model
{
    protected $table = 'fact_customer_segments';
    public $timestamps = false;
    protected $primaryKey = 'segment_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'segment_key',
        'client_key',
        'segment_date_key',
        'primary_segment',
        'secondary_segments',
        'volume_tier',
        'profitability_tier',
        'behavioral_segment',
        'lifecycle_stage',
        'rfm_score',
        'segmentation_criteria',
        'value_score',
        'engagement_score',
        'loyalty_score',
        'growth_potential',
        'retention_risk',
        'upsell_opportunities',
        'cross_sell_opportunities',
        'preferred_communication_channel',
        'segment_characteristics',
        'segment_changes',
        'model_version'
    ];

    protected $casts = [
        'segment_date_key' => 'integer',
        'rfm_score' => 'decimal:4',
        'value_score' => 'decimal:4',
        'engagement_score' => 'decimal:4',
        'loyalty_score' => 'decimal:4',
        'growth_potential' => 'decimal:4',
        'retention_risk' => 'decimal:4',
        'secondary_segments' => 'array',
        'segmentation_criteria' => 'array',
        'upsell_opportunities' => 'array',
        'cross_sell_opportunities' => 'array',
        'segment_characteristics' => 'array',
        'segment_changes' => 'array'
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(DimensionClient::class, 'client_key', 'client_key');
    }

    public function segmentDate(): BelongsTo
    {
        return $this->belongsTo(DimensionDate::class, 'segment_date_key', 'date_key');
    }

    // Scopes
    public function scopeByVolumeTier($query, $tier)
    {
        return $query->where('volume_tier', $tier);
    }

    public function scopeByProfitabilityTier($query, $tier)
    {
        return $query->where('profitability_tier', $tier);
    }

    public function scopeByLifecycleStage($query, $stage)
    {
        return $query->where('lifecycle_stage', $stage);
    }

    public function scopeByBehavioralSegment($query, $segment)
    {
        return $query->where('behavioral_segment', $segment);
    }

    public function scopeHighValue($query)
    {
        return $query->where('value_score', '>=', 0.8);
    }

    public function scopeHighEngagement($query)
    {
        return $query->where('engagement_score', '>=', 0.8);
    }

    public function scopeHighRetentionRisk($query)
    {
        return $query->where('retention_risk', '>=', 0.7);
    }

    // Helper methods
    public function getVolumeTierLabel(): string
    {
        $tiers = [
            'low' => 'Low Volume (1-10 shipments/month)',
            'medium' => 'Medium Volume (11-50 shipments/month)',
            'high' => 'High Volume (51-200 shipments/month)',
            'enterprise' => 'Enterprise (200+ shipments/month)'
        ];

        return $tiers[$this->volume_tier] ?? $this->volume_tier;
    }

    public function getProfitabilityTierLabel(): string
    {
        $tiers = [
            'low' => 'Low Profitability',
            'medium' => 'Medium Profitability',
            'high' => 'High Profitability',
            'premium' => 'Premium Profitability'
        ];

        return $tiers[$this->profitability_tier] ?? $this->profitability_tier;
    }

    public function getLifecycleStageLabel(): string
    {
        $stages = [
            'new' => 'New Customer (0-30 days)',
            'trial' => 'Trial Period (30-90 days)',
            'growing' => 'Growing Customer (90-180 days)',
            'established' => 'Established Customer (180+ days)',
            'mature' => 'Mature Customer (2+ years)',
            'at_risk' => 'At-Risk Customer',
            'churning' => 'Churning Customer'
        ];

        return $stages[$this->lifecycle_stage] ?? $this->lifecycle_stage;
    }

    public function getRfmCategory(): string
    {
        $score = $this->rfm_score;
        return match(true) {
            $score >= 4.5 => 'Champion',
            $score >= 4.0 => 'Loyal Customer',
            $score >= 3.5 => 'Potential Loyalist',
            $score >= 3.0 => 'New Customer',
            $score >= 2.5 => 'Promising',
            $score >= 2.0 => 'Need Attention',
            $score >= 1.5 => 'About to Sleep',
            $score >= 1.0 => 'At Risk',
            default => 'Cannot Lose Them'
        };
    }

    public function getTopSecondarySegments(): array
    {
        return array_slice($this->secondary_segments ?? [], 0, 3);
    }

    public function getTopUpsellOpportunities(): array
    {
        return array_slice($this->upsell_opportunities ?? [], 0, 3);
    }

    public function getTopCrossSellOpportunities(): array
    {
        return array_slice($this->cross_sell_opportunities ?? [], 0, 3);
    }

    public function isHighPotential(): bool
    {
        return $this->growth_potential >= 0.8 && $this->retention_risk < 0.3;
    }

    public function needsAttention(): bool
    {
        return $this->retention_risk >= 0.7 || $this->engagement_score < 0.3;
    }
}