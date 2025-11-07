<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class PromotionalCampaign extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'promo_code',
        'campaign_type',
        'value',
        'minimum_order_value',
        'maximum_discount_amount',
        'usage_limit',
        'usage_count',
        'customer_eligibility',
        'stacking_allowed',
        'effective_from',
        'effective_to',
        'is_active'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_order_value' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'customer_eligibility' => 'array',
        'stacking_allowed' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('PromotionalCampaign')
            ->logOnly(['name', 'promo_code', 'campaign_type', 'value', 'is_active'])
            ->setDescriptionForEvent(fn (string $eventName) => "Promotional campaign {$eventName}");
    }

    // Relationships
    public function quotationUses()
    {
        return $this->hasMany(Quotation::class, 'promo_code', 'promo_code');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now());
            });
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('promo_code', $code);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('campaign_type', $type);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', now());
        });
    }

    // Business Logic
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();
        
        if ($now->isBefore($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $now->isAfter($this->effective_to)) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function canCustomerUse(Customer $customer): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check customer eligibility
        $eligibility = $this->customer_eligibility;
        if ($eligibility) {
            if (isset($eligibility['customer_types']) && !in_array($customer->customer_type, $eligibility['customer_types'])) {
                return false;
            }

            if (isset($eligibility['minimum_spend']) && $customer->total_spent < $eligibility['minimum_spend']) {
                return false;
            }

            if (isset($eligibility['minimum_shipments']) && $customer->total_shipments < $eligibility['minimum_shipments']) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $orderValue, Customer $customer = null): array
    {
        if (!$customer || !$this->canCustomerUse($customer)) {
            return [
                'valid' => false,
                'error' => 'Customer not eligible for this promotion'
            ];
        }

        $discountAmount = 0;
        $finalAmount = $orderValue;

        switch ($this->campaign_type) {
            case 'percentage':
                $discountAmount = $orderValue * ($this->value / 100);
                if ($this->maximum_discount_amount) {
                    $discountAmount = min($discountAmount, $this->maximum_discount_amount);
                }
                break;

            case 'fixed_amount':
                $discountAmount = min($this->value, $orderValue);
                break;

            case 'free_shipping':
                // This would require shipping cost calculation
                $discountAmount = 10.00; // Default shipping cost
                break;

            case 'tier_upgrade':
                // This is handled differently - customer tier upgrade
                return [
                    'valid' => true,
                    'type' => 'tier_upgrade',
                    'new_tier' => $this->value, // The value field contains the new tier
                    'discount_amount' => 0,
                    'final_amount' => $orderValue
                ];
        }

        $finalAmount = $orderValue - $discountAmount;

        return [
            'valid' => true,
            'type' => $this->campaign_type,
            'value' => $this->value,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'discount_percentage' => $orderValue > 0 ? ($discountAmount / $orderValue) * 100 : 0
        ];
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getUsagePercentage(): float
    {
        if (!$this->usage_limit) {
            return 0;
        }

        return ($this->usage_count / $this->usage_limit) * 100;
    }

    public function getTimeRemaining(): ?int
    {
        if (!$this->effective_to) {
            return null;
        }

        return max(0, now()->diffInDays($this->effective_to, false));
    }

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="badge badge-secondary">Inactive</span>';
        }

        if (!$this->isValid()) {
            if ($this->effective_to && now()->isAfter($this->effective_to)) {
                return '<span class="badge badge-warning">Expired</span>';
            }
            return '<span class="badge badge-light">Not Started</span>';
        }

        $usagePercentage = $this->getUsagePercentage();
        if ($usagePercentage >= 90) {
            return '<span class="badge badge-danger">Almost Full</span>';
        } elseif ($usagePercentage >= 50) {
            return '<span class="badge badge-warning">Active</span>';
        }

        return '<span class="badge badge-success">Active</span>';
    }

    public function getAnalytics(): array
    {
        $quotationUses = $this->quotationUses();
        
        return [
            'total_uses' => $this->usage_count,
            'usage_percentage' => $this->getUsagePercentage(),
            'time_remaining_days' => $this->getTimeRemaining(),
            'conversion_rate' => $this->calculateConversionRate(),
            'average_order_value' => $this->getAverageOrderValue(),
            'status' => $this->getStatusBadge()
        ];
    }

    private function calculateConversionRate(): float
    {
        $totalQuotations = $this->quotationUses()->count();
        $convertedQuotations = $this->quotationUses()->where('status', 'converted')->count();
        
        return $totalQuotations > 0 ? ($convertedQuotations / $totalQuotations) * 100 : 0;
    }

    private function getAverageOrderValue(): float
    {
        return $this->quotationUses()->avg('total_amount') ?? 0;
    }
}