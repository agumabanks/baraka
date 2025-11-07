<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimensionSentimentCategories extends Model
{
    protected $table = 'dimension_sentiment_categories';
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'category_key',
        'category_name',
        'category_type',
        'description',
        'sentiment_score_range',
        'emotion_tags',
        'response_priority',
        'escalation_required',
        'recommended_actions',
        'sla_response_time',
        'nps_impact_score',
        'category_group',
        'is_active',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'sentiment_score_range' => 'array',
        'emotion_tags' => 'array',
        'escalation_required' => 'boolean',
        'recommended_actions' => 'array',
        'sla_response_time' => 'integer',
        'nps_impact_score' => 'decimal:4',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function sentimentAnalysis(): HasMany
    {
        return $this->hasMany(FactCustomerSentiment::class, 'sentiment_key', 'sentiment_key');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('category_type', $type);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('category_group', $group);
    }

    public function scopeEscalationRequired($query)
    {
        return $query->where('escalation_required', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('response_priority', '>=', 8);
    }

    // Helper methods
    public function getCategoryTypeLabel(): string
    {
        $types = [
            'complaint' => 'Customer Complaint',
            'feature_request' => 'Feature Request',
            'bug_report' => 'Bug Report',
            'general_inquiry' => 'General Inquiry',
            'praise' => 'Positive Feedback',
            'suggestion' => 'Improvement Suggestion',
            'billing_issue' => 'Billing Related',
            'technical_support' => 'Technical Support',
            'account_management' => 'Account Management',
            'service_disruption' => 'Service Disruption'
        ];

        return $types[$this->category_type] ?? $this->category_type;
    }

    public function getCategoryGroupLabel(): string
    {
        $groups = [
            'service_quality' => 'Service Quality',
            'product_features' => 'Product Features',
            'account_management' => 'Account Management',
            'technical_issues' => 'Technical Issues',
            'billing_payments' => 'Billing & Payments',
            'user_experience' => 'User Experience',
            'general_feedback' => 'General Feedback'
        ];

        return $groups[$this->category_group] ?? $this->category_group;
    }

    public function getSentimentRangeLabel(): string
    {
        $range = $this->sentiment_score_range;
        if (!$range || count($range) < 2) {
            return 'Unknown range';
        }

        return sprintf('%.2f to %.2f', $range[0], $range[1]);
    }

    public function getTopEmotions(): array
    {
        return array_slice($this->emotion_tags ?? [], 0, 3);
    }

    public function getTopActions(): array
    {
        return array_slice($this->recommended_actions ?? [], 0, 3);
    }

    public function isCritical(): bool
    {
        return $this->nps_impact_score <= -0.5 || $this->escalation_required;
    }

    public function needsUrgentResponse(): bool
    {
        return $this->response_priority >= 9 || $this->escalation_required;
    }

    public function getPriorityLevel(): string
    {
        return match(true) {
            $this->response_priority >= 9 => 'Critical',
            $this->response_priority >= 7 => 'High',
            $this->response_priority >= 5 => 'Medium',
            default => 'Low'
        };
    }
}