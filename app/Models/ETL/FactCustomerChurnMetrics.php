<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactCustomerChurnMetrics extends Model
{
    protected $table = 'fact_customer_churn_metrics';
    public $timestamps = false;
    protected $primaryKey = 'churn_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'churn_key',
        'client_key',
        'churn_date_key',
        'churn_probability',
        'risk_score',
        'retention_score',
        'days_since_last_shipment',
        'total_shipments_90_days',
        'complaints_count_90_days',
        'payment_delays_90_days',
        'credit_utilization',
        'churn_indicators',
        'primary_churn_factors',
        'secondary_churn_factors',
        'predicted_churn_date',
        'recommended_actions',
        'model_version',
        'confidence_level'
    ];

    protected $casts = [
        'churn_date_key' => 'integer',
        'churn_probability' => 'decimal:4',
        'risk_score' => 'decimal:4',
        'retention_score' => 'decimal:4',
        'days_since_last_shipment' => 'integer',
        'total_shipments_90_days' => 'integer',
        'complaints_count_90_days' => 'integer',
        'payment_delays_90_days' => 'integer',
        'credit_utilization' => 'decimal:4',
        'predicted_churn_date' => 'date',
        'confidence_level' => 'decimal:4',
        'churn_indicators' => 'array',
        'primary_churn_factors' => 'array',
        'secondary_churn_factors' => 'array',
        'recommended_actions' => 'array'
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(DimensionClient::class, 'client_key', 'client_key');
    }

    public function churnDate(): BelongsTo
    {
        return $this->belongsTo(DimensionDate::class, 'churn_date_key', 'date_key');
    }

    // Scopes
    public function scopeHighRisk($query)
    {
        return $query->where('churn_probability', '>=', 0.7);
    }

    public function scopeMediumRisk($query)
    {
        return $query->whereBetween('churn_probability', [0.3, 0.7]);
    }

    public function scopeLowRisk($query)
    {
        return $query->where('churn_probability', '<', 0.3);
    }

    public function scopeByRiskLevel($query, $level)
    {
        return match($level) {
            'high' => $query->highRisk(),
            'medium' => $query->mediumRisk(),
            'low' => $query->lowRisk(),
            default => $query
        };
    }

    // Helper methods
    public function getRiskLevel(): string
    {
        return match(true) {
            $this->churn_probability >= 0.7 => 'high',
            $this->churn_probability >= 0.3 => 'medium',
            default => 'low'
        };
    }

    public function getTopChurnFactors(): array
    {
        return array_slice($this->primary_churn_factors ?? [], 0, 3);
    }

    public function isChurned(): bool
    {
        return $this->churn_probability >= 0.9;
    }
}