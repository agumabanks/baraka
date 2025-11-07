<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class PricingRuleApplication extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'pricing_rule_id',
        'applied_at',
        'context',
        'result',
        'user_id'
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'context' => 'array',
        'result' => 'array',
        'user_id' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('PricingRuleApplication')
            ->logOnly(['applied_at', 'context', 'result'])
            ->setDescriptionForEvent(fn (string $eventName) => "Pricing rule application {$eventName}");
    }

    // Relationships
    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('applied_at', [$startDate, $endDate]);
    }

    public function scopeByRuleType($query, string $ruleType)
    {
        return $query->whereHas('pricingRule', function ($q) use ($ruleType) {
            $q->where('rule_type', $ruleType);
        });
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('applied_at', '>=', now()->subDays($days));
    }

    // Business Logic
    public function getFormattedContext(): array
    {
        $context = $this->context;
        
        // Format key fields for display
        if (isset($context['base_amount'])) {
            $context['base_amount_formatted'] = '$' . number_format($context['base_amount'], 2);
        }
        
        if (isset($context['fuel_index'])) {
            $context['fuel_index_formatted'] = number_format($context['fuel_index'], 2);
        }
        
        return $context;
    }

    public function getFormattedResult(): array
    {
        $result = $this->result;
        
        if (isset($result['value'])) {
            $result['value_formatted'] = '$' . number_format($result['value'], 2);
        }
        
        return $result;
    }

    public function getRuleTypeDisplay(): string
    {
        $ruleType = $this->pricingRule->rule_type ?? 'unknown';
        
        return match($ruleType) {
            'fuel_surcharge' => 'Fuel Surcharge',
            'discount' => 'Discount',
            'surcharge' => 'Surcharge',
            'tax' => 'Tax',
            'base_rate' => 'Base Rate',
            default => ucfirst($ruleType)
        };
    }

    public function getSuccessStatus(): string
    {
        $result = $this->result;
        
        if (isset($result['applied']) && $result['applied']) {
            return '<span class="badge badge-success">Applied</span>';
        }
        
        return '<span class="badge badge-danger">Failed</span>';
    }

    public static function getUsageAnalytics(string $ruleType = null, int $days = 30): array
    {
        $query = self::recent();
        
        if ($ruleType) {
            $query->byRuleType($ruleType);
        }
        
        $applications = $query->get();
        
        if ($applications->isEmpty()) {
            return [
                'total_applications' => 0,
                'successful_applications' => 0,
                'failed_applications' => 0,
                'success_rate' => 0,
                'average_result_value' => 0,
                'rule_type_breakdown' => []
            ];
        }
        
        $successful = $applications->filter(function ($app) {
            return $app->result['applied'] ?? false;
        });
        
        $totalValue = $applications->sum(function ($app) {
            return $app->result['value'] ?? 0;
        });
        
        $ruleTypeBreakdown = $applications->groupBy(function ($app) {
            return $app->pricingRule->rule_type;
        })->map(function ($apps) {
            return [
                'count' => $apps->count(),
                'total_value' => $apps->sum(function ($app) {
                    return $app->result['value'] ?? 0;
                })
            ];
        });
        
        return [
            'total_applications' => $applications->count(),
            'successful_applications' => $successful->count(),
            'failed_applications' => $applications->count() - $successful->count(),
            'success_rate' => $applications->count() > 0 
                ? ($successful->count() / $applications->count()) * 100 
                : 0,
            'average_result_value' => $applications->count() > 0 
                ? $totalValue / $applications->count() 
                : 0,
            'rule_type_breakdown' => $ruleTypeBreakdown,
            'period_days' => $days
        ];
    }
}