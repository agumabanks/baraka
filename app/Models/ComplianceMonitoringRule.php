<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceMonitoringRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_name',
        'compliance_framework',
        'rule_type',
        'rule_definition',
        'severity',
        'is_active',
        'notification_settings',
        'action_settings',
        'last_evaluated_at',
        'evaluation_count',
        'violation_count',
    ];

    protected $casts = [
        'rule_definition' => 'array',
        'is_active' => 'boolean',
        'notification_settings' => 'array',
        'action_settings' => 'array',
        'last_evaluated_at' => 'datetime',
    ];

    /**
     * Get rule type display name
     */
    public function getRuleTypeDisplayAttribute(): string
    {
        return match ($this->rule_type) {
            'threshold' => 'Threshold Monitor',
            'pattern' => 'Pattern Detection',
            'anomaly' => 'Anomaly Detection',
            'real_time' => 'Real-time Monitor',
            default => ucfirst($this->rule_type),
        };
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->evaluation_count === 0) {
            return 0;
        }
        
        $successCount = $this->evaluation_count - $this->violation_count;
        return round(($successCount / $this->evaluation_count) * 100, 2);
    }

    /**
     * Check if rule is recently active
     */
    public function getIsRecentlyActiveAttribute(): bool
    {
        if (!$this->last_evaluated_at) {
            return false;
        }

        return $this->last_evaluated_at->gte(now()->subDay());
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for framework filtering
     */
    public function scopeForFramework($query, $framework)
    {
        return $query->where('compliance_framework', $framework);
    }
}