<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class CustomerPromotionUsage extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id',
        'promotional_campaign_id',
        'promo_code',
        'order_id',
        'discount_amount',
        'order_value',
        'discount_percentage',
        'used_at',
        'usage_context',
        'validation_result'
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'order_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'used_at' => 'datetime',
        'usage_context' => 'array',
        'validation_result' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('CustomerPromotionUsage')
            ->logOnly(['promo_code', 'discount_amount', 'order_value', 'used_at'])
            ->setDescriptionForEvent(fn (string $eventName) => "Promotion usage {$eventName}");
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function promotionalCampaign(): BelongsTo
    {
        return $this->belongsTo(PromotionalCampaign::class, 'promotional_campaign_id');
    }

    public function customerPromotionUsage()
    {
        return $this->belongsTo(CustomerPromotionUsage::class);
    }

    // Scopes
    public function scopeByDate($query, string $date)
    {
        return $query->whereDate('used_at', $date);
    }

    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('used_at', [$startDate, $endDate]);
    }

    public function scopeByCampaignType($query, string $campaignType)
    {
        return $query->whereHas('promotionalCampaign', function ($q) use ($campaignType) {
            $q->where('campaign_type', $campaignType);
        });
    }

    public function scopeByCustomerSegment($query, string $segment)
    {
        return $query->whereHas('customer', function ($q) use ($segment) {
            $q->where('customer_type', $segment);
        });
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('used_at', '>=', now()->subDays($days));
    }

    public function scopeHighValue($query, float $minValue = 100)
    {
        return $query->where('order_value', '>=', $minValue);
    }

    public function scopeWithHighDiscount($query, float $minPercentage = 20)
    {
        return $query->where('discount_percentage', '>=', $minPercentage);
    }

    // Business Logic
    public function isRecent(): bool
    {
        return $this->used_at->diffInHours(now()) <= 24;
    }

    public function getDiscountValue(): float
    {
        return $this->discount_amount;
    }

    public function getEffectiveDiscountRate(): float
    {
        return $this->order_value > 0 ? ($this->discount_amount / $this->order_value) * 100 : 0;
    }

    public function getCustomerSegment(): string
    {
        return $this->customer->customer_type ?? 'unknown';
    }

    public function getUsagePattern(): array
    {
        return [
            'is_new_customer' => $this->customer->created_at->diffInDays($this->used_at) <= 30,
            'customer_lifetime_orders' => $this->customer->total_shipments ?? 0,
            'customer_lifetime_value' => $this->customer->total_spent ?? 0,
            'promotion_effectiveness' => $this->evaluateEffectiveness()
        ];
    }

    private function evaluateEffectiveness(): string
    {
        $discountRate = $this->getEffectiveDiscountRate();
        $orderValue = $this->order_value;
        $customerValue = $this->customer->total_spent ?? 0;
        
        // Calculate if promotion encouraged higher order value
        $isAboveAverage = $orderValue > ($customerValue / max(1, $this->customer->total_shipments ?? 1));
        
        return match(true) {
            $discountRate > 20 && $isAboveAverage => 'highly_effective',
            $discountRate > 10 && $isAboveAverage => 'effective',
            $discountRate > 5 => 'moderately_effective',
            default => 'low_effectiveness'
        };
    }

    public function getMarketingValue(): array
    {
        return [
            'acquisition_cost' => $this->discount_amount,
            'customer_lifetime_potential' => $this->customer->total_spent ?? 0,
            'retention_probability' => $this->calculateRetentionProbability(),
            'referral_potential' => $this->customer->total_shipments > 5 ? 'high' : 'medium'
        ];
    }

    private function calculateRetentionProbability(): float
    {
        $customerAge = $this->customer->created_at->diffInDays($this->used_at);
        $totalOrders = $this->customer->total_shipments ?? 0;
        $orderValue = $this->order_value;
        
        $baseProbability = 0.3; // 30% base retention
        $ageBoost = min(0.4, $customerAge / 365 * 0.4); // Up to 40% boost for long-term customers
        $orderBoost = min(0.2, $totalOrders * 0.05); // Up to 20% boost for frequent customers
        $valueBoost = min(0.1, $orderValue / 1000 * 0.1); // Up to 10% boost for high-value orders
        
        return min(0.9, $baseProbability + $ageBoost + $orderBoost + $valueBoost);
    }

    public static function getUsageAnalytics(array $filters = []): array
    {
        $query = self::query();
        
        if (isset($filters['date_from'])) {
            $query->where('used_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('used_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['campaign_type'])) {
            $query->byCampaignType($filters['campaign_type']);
        }
        
        if (isset($filters['customer_segment'])) {
            $query->byCustomerSegment($filters['customer_segment']);
        }
        
        $usageData = $query->with(['customer', 'promotionalCampaign'])->get();
        
        return [
            'total_usage' => $usageData->count(),
            'total_discounts' => $usageData->sum('discount_amount'),
            'total_order_value' => $usageData->sum('order_value'),
            'average_order_value' => $usageData->avg('order_value'),
            'average_discount' => $usageData->avg('discount_amount'),
            'discount_rate' => $usageData->isNotEmpty() 
                ? ($usageData->sum('discount_amount') / $usageData->sum('order_value')) * 100 
                : 0,
            'unique_customers' => $usageData->unique('customer_id')->count(),
            'campaign_performance' => self::getCampaignPerformanceBreakdown($usageData),
            'customer_segments' => self::getSegmentBreakdown($usageData),
            'time_trends' => self::getTimeTrends($usageData)
        ];
    }

    private static function getCampaignPerformanceBreakdown($usageData): array
    {
        return $usageData->groupBy('promotionalCampaign.campaign_type')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_discounts' => $group->sum('discount_amount'),
                    'total_orders' => $group->sum('order_value'),
                    'avg_order_value' => $group->avg('order_value'),
                    'avg_discount_rate' => $group->isNotEmpty() 
                        ? ($group->sum('discount_amount') / $group->sum('order_value')) * 100 
                        : 0
                ];
            })
            ->toArray();
    }

    private static function getSegmentBreakdown($usageData): array
    {
        return $usageData->groupBy('customer.customer_type')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'percentage' => ($group->count() / $usageData->count()) * 100,
                    'avg_order_value' => $group->avg('order_value'),
                    'avg_discount' => $group->avg('discount_amount')
                ];
            })
            ->toArray();
    }

    private static function getTimeTrends($usageData): array
    {
        $dailyUsage = $usageData->groupBy(function ($item) {
            return $item->used_at->format('Y-m-d');
        })->map->count();
        
        return [
            'peak_day' => $dailyUsage->sortDesc()->keys()->first(),
            'peak_usage' => $dailyUsage->max(),
            'average_daily_usage' => $dailyUsage->avg(),
            'trend' => $dailyUsage->count() > 1 
                ? ($dailyUsage->last() > $dailyUsage->first() ? 'increasing' : 'decreasing')
                : 'stable'
        ];
    }
}