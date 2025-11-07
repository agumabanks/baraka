<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimensionChurnFactors extends Model
{
    protected $table = 'dimension_churn_factors';
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'factor_key',
        'factor_name',
        'factor_category',
        'factor_description',
        'weight_in_model',
        'is_predictive',
        'is_preventable',
        'typical_impact_range',
        'recommended_intervention',
        'monitoring_threshold',
        'factor_type',
        'data_source',
        'calculation_method',
        'last_updated',
        'is_active'
    ];

    protected $casts = [
        'weight_in_model' => 'decimal:4',
        'is_predictive' => 'boolean',
        'is_preventable' => 'boolean',
        'monitoring_threshold' => 'decimal:4',
        'last_updated' => 'datetime',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function churnMetrics(): HasMany
    {
        return $this->hasMany(FactCustomerChurnMetrics::class, 'churn_key', 'churn_key');
    }

    // Scopes
    public function scopePredictive($query)
    {
        return $query->where('is_predictive', true);
    }

    public function scopePreventable($query)
    {
        return $query->where('is_preventable', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('factor_category', $category);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getFactorCategoryLabel(): string
    {
        $categories = [
            'behavioral' => 'Behavioral Factors',
            'financial' => 'Financial Factors',
            'service' => 'Service-Related Factors',
            'engagement' => 'Engagement Factors',
            'competition' => 'Competitive Factors',
            'demographic' => 'Demographic Factors'
        ];

        return $categories[$this->factor_category] ?? $this->factor_category;
    }

    public function getFactorTypeLabel(): string
    {
        $types = [
            'recency' => 'Time Since Last Activity',
            'frequency' => 'Activity Frequency',
            'monetary' => 'Financial Metrics',
            'satisfaction' => 'Satisfaction Metrics',
            'support' => 'Support Interactions',
            'competition' => 'Competitive Intelligence'
        ];

        return $types[$this->factor_type] ?? $this->factor_type;
    }

    public function getImpactLevel(): string
    {
        return match(true) {
            $this->weight_in_model >= 0.2 => 'High Impact',
            $this->weight_in_model >= 0.1 => 'Medium Impact',
            default => 'Low Impact'
        };
    }

    public function isHighPriority(): bool
    {
        return $this->is_predictive && $this->is_preventable && $this->weight_in_model >= 0.1;
    }

    public function getRecommendedAction(): string
    {
        if (!$this->recommended_intervention) {
            return match($this->factor_category) {
                'behavioral' => 'Implement engagement campaigns',
                'financial' => 'Review payment terms and credit limits',
                'service' => 'Improve service quality and support',
                'engagement' => 'Increase communication frequency',
                default => 'Monitor closely and develop intervention strategy'
            };
        }

        return $this->recommended_intervention;
    }
}