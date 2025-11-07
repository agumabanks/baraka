<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactCustomerSentiment extends Model
{
    protected $table = 'fact_customer_sentiment';
    public $timestamps = false;
    protected $primaryKey = 'sentiment_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'sentiment_key',
        'client_key',
        'ticket_key',
        'sentiment_date_key',
        'nps_score',
        'sentiment_score',
        'confidence_level',
        'feedback_category',
        'primary_emotion',
        'emotion_intensity',
        'language_detected',
        'support_channel',
        'ticket_status',
        'resolution_time_hours',
        'customer_satisfaction_rating',
        'sentiment_trend',
        'feedback_keywords',
        'model_version',
        'analysis_metadata'
    ];

    protected $casts = [
        'sentiment_date_key' => 'integer',
        'nps_score' => 'integer',
        'sentiment_score' => 'decimal:4',
        'confidence_level' => 'decimal:4',
        'emotion_intensity' => 'decimal:4',
        'resolution_time_hours' => 'decimal:2',
        'customer_satisfaction_rating' => 'integer',
        'feedback_keywords' => 'array',
        'analysis_metadata' => 'array',
        'sentiment_trend' => 'array'
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(DimensionClient::class, 'client_key', 'client_key');
    }

    public function sentimentDate(): BelongsTo
    {
        return $this->belongsTo(DimensionDate::class, 'sentiment_date_key', 'date_key');
    }

    // Scopes
    public function scopePromoters($query)
    {
        return $query->where('nps_score', '>=', 9);
    }

    public function scopePassives($query)
    {
        return $query->whereBetween('nps_score', [7, 8]);
    }

    public function scopeDetractors($query)
    {
        return $query->where('nps_score', '<=', 6);
    }

    public function scopePositive($query)
    {
        return $query->where('sentiment_score', '>=', 0.1);
    }

    public function scopeNegative($query)
    {
        return $query->where('sentiment_score', '<=', -0.1);
    }

    public function scopeNeutral($query)
    {
        return $query->whereBetween('sentiment_score', [-0.1, 0.1]);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('feedback_category', $category);
    }

    // Helper methods
    public function getNpsCategory(): string
    {
        return match(true) {
            $this->nps_score >= 9 => 'promoter',
            $this->nps_score >= 7 => 'passive',
            default => 'detractor'
        };
    }

    public function getSentimentCategory(): string
    {
        return match(true) {
            $this->sentiment_score >= 0.5 => 'very_positive',
            $this->sentiment_score >= 0.1 => 'positive',
            $this->sentiment_score >= -0.1 => 'neutral',
            $this->sentiment_score >= -0.5 => 'negative',
            default => 'very_negative'
        };
    }

    public function getPrimaryEmotionLabel(): string
    {
        $emotions = [
            'joy' => 'Joy/Happiness',
            'anger' => 'Anger/Frustration',
            'fear' => 'Fear/Anxiety',
            'sadness' => 'Sadness/Disappointment',
            'surprise' => 'Surprise',
            'disgust' => 'Disgust',
            'trust' => 'Trust/Confidence',
            'anticipation' => 'Anticipation/Expectation'
        ];

        return $emotions[$this->primary_emotion] ?? $this->primary_emotion;
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence_level >= 0.8;
    }

    public function getKeyFeedbackKeywords(): array
    {
        return array_slice($this->feedback_keywords ?? [], 0, 5);
    }

    public function calculateNps(): int
    {
        return $this->nps_score;
    }

    public function isUrgent(): bool
    {
        return $this->nps_score <= 6 || $this->sentiment_score <= -0.5;
    }
}