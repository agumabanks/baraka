<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContractServiceLevel extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'contract_id',
        'service_level_code',
        'delivery_window_min_hours',
        'delivery_window_max_hours',
        'reliability_threshold',
        'sla_claim_ratio',
        'response_time_hours',
        'penalty_conditions',
        'compensation_rules'
    ];

    protected $casts = [
        'delivery_window_min_hours' => 'integer',
        'delivery_window_max_hours' => 'integer',
        'reliability_threshold' => 'decimal:2',
        'sla_claim_ratio' => 'decimal:2',
        'response_time_hours' => 'integer',
        'penalty_conditions' => 'array',
        'compensation_rules' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ContractServiceLevel')
            ->logOnly(['service_level_code', 'delivery_window_min_hours', 'delivery_window_max_hours', 'reliability_threshold'])
            ->setDescriptionForEvent(fn (string $eventName) => "Contract service level {$eventName}");
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function serviceLevelDefinition(): BelongsTo
    {
        return $this->belongsTo(ServiceLevelDefinition::class, 'service_level_code', 'code');
    }

    // Scopes
    public function scopeByServiceLevel($query, string $serviceLevel)
    {
        return $query->where('service_level_code', $serviceLevel);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('contract', function($q) {
            $q->where('status', 'active');
        });
    }

    // Business Logic
    public function isComplying(array $performanceData): bool
    {
        $deliveryTime = $performanceData['actual_delivery_hours'] ?? 0;
        $reliabilityScore = $performanceData['reliability_score'] ?? 0;
        $claimRatio = $performanceData['sla_claim_ratio'] ?? 0;
        
        return $deliveryTime <= $this->delivery_window_max_hours &&
               $reliabilityScore >= $this->reliability_threshold &&
               $claimRatio >= $this->sla_claim_ratio;
    }

    public function getDeliveryWindowText(): string
    {
        if ($this->delivery_window_min_hours && $this->delivery_window_max_hours) {
            if ($this->delivery_window_min_hours < 24) {
                return "{$this->delivery_window_min_hours}-{$this->delivery_window_max_hours} hours";
            } else {
                $minDays = round($this->delivery_window_min_hours / 24, 1);
                $maxDays = round($this->delivery_window_max_hours / 24, 1);
                return "{$minDays}-{$maxDays} days";
            }
        }
        
        return 'Standard delivery';
    }

    public function getPenaltyAmount(array $breachDetails): float
    {
        $penalties = $this->penalty_conditions ?? [];
        $totalPenalty = 0;
        
        foreach ($penalties as $penalty) {
            switch ($penalty['type']) {
                case 'fixed_amount':
                    $totalPenalty += $penalty['amount'] ?? 0;
                    break;
                case 'percentage':
                    $percentage = ($penalty['percentage'] ?? 0) / 100;
                    $baseAmount = $breachDetails['invoice_amount'] ?? 0;
                    $totalPenalty += $baseAmount * $percentage;
                    break;
                case 'refund':
                    $refundAmount = $breachDetails['refund_amount'] ?? 0;
                    $totalPenalty -= $refundAmount;
                    break;
            }
        }
        
        return max(0, $totalPenalty);
    }

    public function getCompensation(array $serviceMetrics): array
    {
        $compensations = [];
        $rules = $this->compensation_rules ?? [];
        
        foreach ($rules as $rule) {
            if ($this->shouldApplyCompensation($rule, $serviceMetrics)) {
                $compensations[] = [
                    'type' => $rule['type'],
                    'description' => $rule['description'],
                    'amount' => $this->calculateCompensationAmount($rule, $serviceMetrics),
                    'conditions_met' => true
                ];
            }
        }
        
        return $compensations;
    }

    private function shouldApplyCompensation(array $rule, array $metrics): bool
    {
        $condition = $rule['condition'] ?? 'always';
        
        return match($condition) {
            'delivery_delay' => ($metrics['delay_hours'] ?? 0) >= ($rule['threshold_hours'] ?? 0),
            'low_reliability' => ($metrics['reliability_score'] ?? 100) < ($rule['threshold_score'] ?? 90),
            'high_claims' => ($metrics['claim_ratio'] ?? 0) > ($rule['threshold_ratio'] ?? 0.05),
            'always' => true,
            default => false
        };
    }

    private function calculateCompensationAmount(array $rule, array $metrics): float
    {
        return match($rule['calculation'] ?? 'fixed') {
            'fixed' => $rule['amount'] ?? 0,
            'percentage' => ($rule['percentage'] ?? 0) * ($metrics['base_amount'] ?? 0) / 100,
            'tiered' => $this->calculateTieredCompensation($rule, $metrics),
            default => 0
        };
    }

    private function calculateTieredCompensation(array $rule, array $metrics): float
    {
        $tiers = $rule['tiers'] ?? [];
        $value = $metrics['delay_hours'] ?? 0;
        
        foreach ($tiers as $tier) {
            if ($value >= ($tier['min'] ?? 0) && $value <= ($tier['max'] ?? PHP_FLOAT_MAX)) {
                return $tier['amount'] ?? 0;
            }
        }
        
        return 0;
    }
}