<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class ContractVolumeDiscount extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'contract_id',
        'tier_name',
        'volume_requirement',
        'volume_unit',
        'discount_percentage',
        'discount_amount',
        'tier_benefits',
        'tier_conditions',
        'is_automatic',
        'effective_from',
        'effective_until',
        'usage_count',
        'total_savings',
        'is_active'
    ];

    protected $casts = [
        'volume_requirement' => 'integer',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tier_benefits' => 'array',
        'tier_conditions' => 'array',
        'is_automatic' => 'boolean',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'usage_count' => 'integer',
        'total_savings' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ContractVolumeDiscount')
            ->logOnly(['tier_name', 'volume_requirement', 'discount_percentage', 'discount_amount'])
            ->setDescriptionForEvent(fn (string $eventName) => "Contract volume discount tier {$eventName}");
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('effective_from')
                          ->orWhere('effective_from', '<=', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('effective_until')
                          ->orWhere('effective_until', '>=', now());
                    });
    }

    public function scopeByVolume($query, int $volume)
    {
        return $query->where('volume_requirement', '<=', $volume)
                    ->orderBy('volume_requirement', 'desc');
    }

    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    public function scopeByTier($query, string $tierName)
    {
        return $query->where('tier_name', $tierName);
    }

    // Business Logic
    public function isApplicable(int $currentVolume, array $additionalConditions = []): bool
    {
        // Check volume requirement
        if ($currentVolume < $this->volume_requirement) {
            return false;
        }

        // Check effective dates
        if ($this->effective_from && now()->isBefore($this->effective_from)) {
            return false;
        }

        if ($this->effective_until && now()->isAfter($this->effective_until)) {
            return false;
        }

        if (!$this->is_active) {
            return false;
        }

        // Check additional conditions
        return $this->checkConditions($additionalConditions);
    }

    public function calculateDiscount(float $baseAmount, int $volume): array
    {
        if (!$this->isApplicable($volume)) {
            return [
                'applicable' => false,
                'discount_amount' => 0,
                'final_amount' => $baseAmount,
                'tier_name' => null
            ];
        }

        $discountAmount = 0;

        // Calculate percentage discount
        if ($this->discount_percentage > 0) {
            $discountAmount += $baseAmount * ($this->discount_percentage / 100);
        }

        // Add fixed amount discount
        if ($this->discount_amount > 0) {
            $discountAmount += $this->discount_amount;
        }

        $finalAmount = max(0, $baseAmount - $discountAmount);

        // Update usage statistics
        $this->incrementUsage();

        return [
            'applicable' => true,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'tier_name' => $this->tier_name,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount_fixed' => $this->discount_amount,
            'benefits' => $this->tier_benefits ?? []
        ];
    }

    public function getNextTier(): ?self
    {
        return self::where('contract_id', $this->contract_id)
                  ->where('volume_requirement', '>', $this->volume_requirement)
                  ->where('is_active', true)
                  ->orderBy('volume_requirement', 'asc')
                  ->first();
    }

    public function getVolumeProgress(int $currentVolume): array
    {
        $nextTier = $this->getNextTier();
        
        if (!$nextTier) {
            return [
                'has_next_tier' => false,
                'progress_percentage' => 100,
                'volume_to_next' => 0,
                'next_tier_name' => null
            ];
        }

        $requiredVolume = $nextTier->volume_requirement;
        $currentProgress = $this->volume_requirement;
        $remainingVolume = max(0, $requiredVolume - $currentVolume);
        
        $progressPercentage = min(100, (($currentVolume - $currentProgress) / ($requiredVolume - $currentProgress)) * 100);

        return [
            'has_next_tier' => true,
            'progress_percentage' => $progressPercentage,
            'volume_to_next' => $remainingVolume,
            'next_tier_name' => $nextTier->tier_name,
            'next_tier_requirement' => $nextTier->volume_requirement
        ];
    }

    public function getTierSummary(): array
    {
        return [
            'id' => $this->id,
            'tier_name' => $this->tier_name,
            'volume_requirement' => $this->volume_requirement,
            'volume_unit' => $this->volume_unit,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'benefits' => $this->tier_benefits ?? [],
            'is_automatic' => $this->is_automatic,
            'usage_count' => $this->usage_count,
            'total_savings' => $this->total_savings,
            'is_active' => $this->is_active,
            'effective_period' => [
                'from' => $this->effective_from?->toISOString(),
                'until' => $this->effective_until?->toISOString()
            ]
        ];
    }

    public function checkConditions(array $conditions): bool
    {
        $tierConditions = $this->tier_conditions ?? [];
        
        foreach ($tierConditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;
            
            if (!isset($conditions[$field])) {
                return false;
            }
            
            $conditionValue = $conditions[$field];
            
            if (!$this->evaluateCondition($conditionValue, $operator, $value)) {
                return false;
            }
        }
        
        return true;
    }

    private function evaluateCondition($actualValue, string $operator, $expectedValue): bool
    {
        return match($operator) {
            'equals' => $actualValue == $expectedValue,
            'not_equals' => $actualValue != $expectedValue,
            'greater_than' => $actualValue > $expectedValue,
            'greater_equal' => $actualValue >= $expectedValue,
            'less_than' => $actualValue < $expectedValue,
            'less_equal' => $actualValue <= $expectedValue,
            'in' => in_array($actualValue, (array) $expectedValue),
            'not_in' => !in_array($actualValue, (array) $expectedValue),
            'contains' => str_contains((string) $actualValue, (string) $expectedValue),
            'starts_with' => str_starts_with((string) $actualValue, (string) $expectedValue),
            'ends_with' => str_ends_with((string) $actualValue, (string) $expectedValue),
            default => false
        };
    }

    public function incrementUsage(float $savingsAmount = 0): void
    {
        $this->increment('usage_count');
        
        if ($savingsAmount > 0) {
            $this->increment('total_savings', $savingsAmount);
        }
    }

    public function resetUsage(): void
    {
        $this->update([
            'usage_count' => 0,
            'total_savings' => 0
        ]);
    }

    public static function getApplicableTier(int $contractId, int $volume): ?self
    {
        return self::where('contract_id', $contractId)
                  ->active()
                  ->where('volume_requirement', '<=', $volume)
                  ->orderBy('volume_requirement', 'desc')
                  ->first();
    }

    public static function createTierFromTemplate(array $tierData, int $contractId): self
    {
        return self::create(array_merge($tierData, [
            'contract_id' => $contractId,
            'usage_count' => 0,
            'total_savings' => 0,
            'is_active' => true
        ]));
    }
}