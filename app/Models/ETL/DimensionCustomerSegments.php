<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimensionCustomerSegments extends Model
{
    protected $table = 'dimension_customer_segments';
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'segment_key',
        'segment_name',
        'segment_type',
        'segment_description',
        'volume_criteria',
        'profitability_criteria',
        'behavioral_criteria',
        'value_score_range',
        'engagement_criteria',
        'lifecycle_stage_criteria',
        'retention_risk_range',
        'growth_potential_score',
        'targeting_criteria',
        'marketing_messaging',
        'retention_strategies',
        'upsell_opportunities',
        'cross_sell_opportunities',
        'priority_level',
        'is_active',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'volume_criteria' => 'array',
        'profitability_criteria' => 'array',
        'behavioral_criteria' => 'array',
        'value_score_range' => 'array',
        'engagement_criteria' => 'array',
        'lifecycle_stage_criteria' => 'array',
        'retention_risk_range' => 'array',
        'growth_potential_score' => 'decimal:4',
        'targeting_criteria' => 'array',
        'marketing_messaging' => 'array',
        'retention_strategies' => 'array',
        'upsell_opportunities' => 'array',
        'cross_sell_opportunities' => 'array',
        'priority_level' => 'integer',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function customerSegments(): HasMany
    {
        return $this->hasMany(FactCustomerSegments::class, 'segment_key', 'segment_key');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('segment_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority_level', $priority);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority_level', '>=', 8);
    }

    public function scopeBySegmentType($query, $type)
    {
        return $query->where('segment_type', $type);
    }

    // Helper methods
    public function getSegmentTypeLabel(): string
    {
        $types = [
            'volume_based' => 'Volume-Based Segment',
            'profitability_based' => 'Profitability-Based Segment',
            'behavioral' => 'Behavioral Segment',
            'lifecycle' => 'Lifecycle Stage Segment',
            'value_based' => 'Value-Based Segment',
            'engagement_based' => 'Engagement-Based Segment',
            'demographic' => 'Demographic Segment',
            'geographic' => 'Geographic Segment',
            'psychographic' => 'Psychographic Segment',
            'transactional' => 'Transactional Segment'
        ];

        return $types[$this->segment_type] ?? $this->segment_type;
    }

    public function getValueRangeLabel(): string
    {
        $range = $this->value_score_range;
        if (!$range || count($range) < 2) {
            return 'No value range defined';
        }

        return sprintf('%.2f to %.2f', $range[0], $range[1]);
    }

    public function getRetentionRiskRangeLabel(): string
    {
        $range = $this->retention_risk_range;
        if (!$range || count($range) < 2) {
            return 'No risk range defined';
        }

        return sprintf('%.2f to %.2f', $range[0], $range[1]);
    }

    public function getTopRetentionStrategies(): array
    {
        return array_slice($this->retention_strategies ?? [], 0, 3);
    }

    public function getTopUpsellOpportunities(): array
    {
        return array_slice($this->upsell_opportunities ?? [], 0, 3);
    }

    public function getTopCrossSellOpportunities(): array
    {
        return array_slice($this->cross_sell_opportunities ?? [], 0, 3);
    }

    public function getPriorityLevelLabel(): string
    {
        return match(true) {
            $this->priority_level >= 9 => 'Critical',
            $this->priority_level >= 7 => 'High',
            $this->priority_level >= 5 => 'Medium',
            $this->priority_level >= 3 => 'Low',
            default => 'Very Low'
        };
    }

    public function isHighValue(): bool
    {
        $range = $this->value_score_range;
        return $range && count($range) >= 2 && $range[1] >= 0.8;
    }

    public function isHighRisk(): bool
    {
        $range = $this->retention_risk_range;
        return $range && count($range) >= 2 && $range[0] >= 0.7;
    }

    public function hasHighGrowthPotential(): bool
    {
        return $this->growth_potential_score >= 0.8;
    }

    public function getTargetingCriteriaSummary(): string
    {
        $criteria = $this->targeting_criteria;
        if (!$criteria || empty($criteria)) {
            return 'No specific targeting criteria defined';
        }

        return implode(', ', array_slice($criteria, 0, 3));
    }
}