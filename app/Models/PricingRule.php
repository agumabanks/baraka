<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class PricingRule extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'rule_type',
        'conditions',
        'calculation_formula',
        'priority',
        'active',
        'effective_from',
        'effective_to'
    ];

    protected $casts = [
        'conditions' => 'array',
        'priority' => 'integer',
        'active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('PricingRule')
            ->logOnly(['name', 'rule_type', 'active', 'priority'])
            ->setDescriptionForEvent(fn (string $eventName) => "Pricing rule {$eventName}");
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where('effective_from', '<=', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', Carbon::now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    public function scopeByPriority($query, int $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeEffectiveNow($query)
    {
        return $query->where('effective_from', '<=', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', Carbon::now());
            });
    }

    // Relationships
    public function applications()
    {
        return $this->hasMany(PricingRuleApplication::class);
    }

    // Business Logic
    public function isValid(Carbon $date = null): bool
    {
        $date = $date ?? Carbon::now();
        
        if (!$this->active) {
            return false;
        }

        if ($date->isBefore($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $date->isAfter($this->effective_to)) {
            return false;
        }

        return true;
    }

    public function matchesConditions(array $context): bool
    {
        $conditions = $this->conditions;
        
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $field => $expectedValue) {
            // Handle different condition types
            if (is_array($expectedValue)) {
                if (isset($expectedValue['operator']) && isset($expectedValue['value'])) {
                    $operator = $expectedValue['operator'];
                    $value = $expectedValue['value'];
                    $actualValue = $context[$field] ?? null;
                    
                    if (!$this->evaluateCondition($actualValue, $operator, $value)) {
                        return false;
                    }
                } else {
                    // Array match
                    $actualValue = $context[$field] ?? null;
                    if (is_array($actualValue)) {
                        if (!array_intersect($actualValue, $expectedValue)) {
                            return false;
                        }
                    } else {
                        if (!in_array($actualValue, $expectedValue)) {
                            return false;
                        }
                    }
                }
            } else {
                // Exact match
                $actualValue = $context[$field] ?? null;
                if ($actualValue !== $expectedValue) {
                    return false;
                }
            }
        }

        return true;
    }

    private function evaluateCondition($actual, string $operator, $expected): bool
    {
        return match($operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'greater_than' => $actual > $expected,
            'greater_than_or_equal' => $actual >= $expected,
            'less_than' => $actual < $expected,
            'less_than_or_equal' => $actual <= $expected,
            'contains' => str_contains($actual ?? '', $expected),
            'not_contains' => !str_contains($actual ?? '', $expected),
            'in' => in_array($actual, $expected),
            'not_in' => !in_array($actual, $expected),
            default => false
        };
    }

    public function applyRule(array $context): array
    {
        if (!$this->matchesConditions($context)) {
            return ['applied' => false, 'reason' => 'Conditions not met'];
        }

        // Execute the calculation formula
        $result = $this->executeCalculation($context);
        
        // Log the application
        $this->applications()->create([
            'applied_at' => Carbon::now(),
            'context' => $context,
            'result' => $result,
            'user_id' => auth()->id()
        ]);

        return [
            'applied' => true,
            'result' => $result,
            'rule_name' => $this->name,
            'rule_type' => $this->rule_type
        ];
    }

    private function executeCalculation(array $context): array
    {
        // This would be enhanced with a proper expression parser
        // For now, we handle basic rule types
        
        $result = ['value' => 0, 'type' => $this->rule_type];
        
        switch ($this->rule_type) {
            case 'fuel_surcharge':
                $baseAmount = $context['base_amount'] ?? 0;
                $fuelIndex = $context['fuel_index'] ?? 100;
                $baseIndex = 100;
                
                if ($fuelIndex > $baseIndex) {
                    $surchargeRate = (($fuelIndex - $baseIndex) / $baseIndex) * 0.08;
                    $result['value'] = $baseAmount * $surchargeRate;
                }
                break;

            case 'discount':
                $baseAmount = $context['base_amount'] ?? 0;
                $discountRate = $this->conditions['discount_rate'] ?? 0;
                $result['value'] = $baseAmount * ($discountRate / 100);
                break;

            case 'surcharge':
                $result['value'] = $this->conditions['amount'] ?? 0;
                break;

            case 'tax':
                $baseAmount = $context['base_amount'] ?? 0;
                $taxRate = $this->conditions['tax_rate'] ?? 0;
                $result['value'] = $baseAmount * ($taxRate / 100);
                break;

            default:
                $result['value'] = 0;
        }
        
        return $result;
    }

    public function getRuleDescription(): string
    {
        $description = "{$this->name} - ";
        
        switch ($this->rule_type) {
            case 'fuel_surcharge':
                $description .= "Applies fuel surcharge based on current index";
                break;
            case 'discount':
                $description .= "Applies percentage discount";
                break;
            case 'surcharge':
                $description .= "Adds fixed amount surcharge";
                break;
            case 'tax':
                $description .= "Calculates tax based on jurisdiction";
                break;
            default:
                $description .= "Custom pricing rule";
        }
        
        return $description;
    }

    public static function getApplicableRules(array $context, string $ruleType = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::active()->effectiveNow();
        
        if ($ruleType) {
            $query->byType($ruleType);
        }
        
        return $query->get()
            ->filter(function ($rule) use ($context) {
                return $rule->matchesConditions($context);
            })
            ->sortByDesc('priority');
    }

    public static function createFromTemplate(string $name, string $ruleType, array $conditions, float $priority = 0): self
    {
        return self::create([
            'name' => $name,
            'rule_type' => $ruleType,
            'conditions' => $conditions,
            'calculation_formula' => self::getDefaultFormula($ruleType),
            'priority' => $priority,
            'active' => true,
            'effective_from' => Carbon::now()
        ]);
    }

    private static function getDefaultFormula(string $ruleType): string
    {
        return match($ruleType) {
            'fuel_surcharge' => 'base_amount * ((fuel_index - 100) / 100) * 0.08',
            'discount' => 'base_amount * (discount_rate / 100)',
            'surcharge' => 'fixed_amount',
            'tax' => 'base_amount * (tax_rate / 100)',
            default => 'base_amount'
        };
    }
}