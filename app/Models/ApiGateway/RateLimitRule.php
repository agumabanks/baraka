<?php

namespace App\Models\ApiGateway;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RateLimitRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_route_id',
        'name',
        'type',
        'limit',
        'window',
        'burst_limit',
        'identifier',
        'conditions',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'limit' => 'integer',
        'window' => 'integer',
        'burst_limit' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'conditions' => 'array',
    ];

    /**
     * Get the API route this rule belongs to
     */
    public function route()
    {
        return $this->belongsTo(ApiRoute::class, 'api_route_id');
    }

    /**
     * Scope to get active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Get rule type options
     */
    public static function getTypeOptions(): array
    {
        return [
            'ip' => 'IP Address',
            'user' => 'User ID',
            'api_key' => 'API Key',
            'endpoint' => 'Endpoint',
            'custom' => 'Custom',
        ];
    }

    /**
     * Check if rule conditions are met
     */
    public function conditionsAreMet(array $context): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $field => $expectedValue) {
            if (!$this->checkCondition($field, $expectedValue, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check individual condition
     */
    protected function checkCondition(string $field, $expectedValue, array $context): bool
    {
        $actualValue = $this->getContextValue($field, $context);
        
        if (is_array($expectedValue)) {
            return in_array($actualValue, $expectedValue);
        }
        
        return $actualValue == $expectedValue;
    }

    /**
     * Get value from context
     */
    protected function getContextValue(string $field, array $context)
    {
        $parts = explode('.', $field);
        $value = $context;
        
        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }
        
        return $value;
    }

    /**
     * Get rule priority for matching
     */
    public function getMatchPriority(): int
    {
        return $this->priority ?? 0;
    }

    /**
     * Check if rule should be applied based on type
     */
    public function shouldApplyToRequest(array $requestContext): bool
    {
        return $this->conditionsAreMet($requestContext);
    }

    /**
     * Get rule statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_requests' => \DB::table('api_rate_limit_breaches')
                ->where('rate_limit_rule_id', $this->id)
                ->count(),
            'breach_count' => \DB::table('api_rate_limit_breaches')
                ->where('rate_limit_rule_id', $this->id)
                ->count(),
            'unique_clients' => \DB::table('api_rate_limit_breaches')
                ->where('rate_limit_rule_id', $this->id)
                ->distinct('client_ip')
                ->count(),
        ];
    }
}