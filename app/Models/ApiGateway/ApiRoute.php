<?php

namespace App\Models\ApiGateway;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'methods',
        'target_service',
        'version',
        'description',
        'is_active',
        'timeout',
        'connect_timeout',
        'auth_type',
        'auth_config',
        'rate_limit_config',
        'transform_config',
        'validation_config',
        'load_balanced',
        'target_services',
        'load_balancing_strategy',
        'health_check_path',
        'retry_config',
        'cors_config',
        'metadata',
    ];

    protected $casts = [
        'methods' => 'array',
        'is_active' => 'boolean',
        'timeout' => 'integer',
        'connect_timeout' => 'integer',
        'auth_config' => 'array',
        'rate_limit_config' => 'array',
        'transform_config' => 'array',
        'validation_config' => 'array',
        'load_balanced' => 'boolean',
        'target_services' => 'array',
        'retry_config' => 'array',
        'cors_config' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the API version this route belongs to
     */
    public function version()
    {
        return $this->belongsTo(ApiVersion::class);
    }

    /**
     * Get rate limit rules for this route
     */
    public function rateLimitRules()
    {
        return $this->hasMany(RateLimitRule::class);
    }

    /**
     * Check if route allows specific HTTP method
     */
    public function allowsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods);
    }

    /**
     * Get route URL pattern
     */
    public function getPattern(): string
    {
        return $this->path;
    }

    /**
     * Get full path with version prefix
     */
    public function getFullPath(): string
    {
        $versionPrefix = $this->version ? "/v{$this->version->version}" : '';
        return $versionPrefix . $this->path;
    }

    /**
     * Get target services (handles both single and load-balanced)
     */
    public function getTargetServices(): array
    {
        if ($this->load_balanced) {
            return $this->target_services ?? [];
        }
        
        return [$this->target_service];
    }

    /**
     * Get retry configuration
     */
    public function getRetryConfig(): array
    {
        return $this->retry_config ?? [
            'max_attempts' => 3,
            'backoff_strategy' => 'exponential',
            'base_delay' => 1000, // milliseconds
        ];
    }

    /**
     * Get CORS configuration
     */
    public function getCorsConfig(): array
    {
        return $this->cors_config ?? [
            'allow_origins' => ['*'],
            'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            'allow_headers' => ['*'],
            'max_age' => 86400,
        ];
    }

    /**
     * Check if route requires authentication
     */
    public function requiresAuth(): bool
    {
        return !empty($this->auth_type) && $this->auth_type !== 'none';
    }

    /**
     * Get rate limit configuration
     */
    public function getRateLimitConfig(): array
    {
        return $this->rate_limit_config ?? [
            'limit' => 100,
            'window' => 60,
            'identifier' => 'ip',
        ];
    }

    /**
     * Get transformation configuration
     */
    public function getTransformConfig(): array
    {
        return $this->transform_config ?? [
            'transform_request' => true,
            'transform_response' => true,
            'data_format' => 'json',
        ];
    }

    /**
     * Get validation configuration
     */
    public function getValidationConfig(): array
    {
        return $this->validation_config ?? [
            'enabled' => true,
            'validate_input' => true,
            'sanitize_input' => true,
        ];
    }

    /**
     * Scope to get active routes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by version
     */
    public function scopeForVersion($query, string $version)
    {
        return $query->whereHas('version', function ($q) use ($version) {
            $q->where('version', $version);
        });
    }

    /**
     * Scope to filter by service
     */
    public function scopeForService($query, string $service)
    {
        return $query->where(function ($q) use ($service) {
            $q->where('target_service', $service)
              ->orWhereJsonContains('target_services', $service);
        });
    }

    /**
     * Get route statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_requests' => \DB::table('api_gateway_logs')
                ->where('type', 'request')
                ->where('data->path', $this->path)
                ->count(),
            'avg_response_time' => \DB::table('api_gateway_metrics')
                ->where('metric', 'processing_time')
                ->where('tags->path', $this->path)
                ->avg('value') ?? 0,
            'error_rate' => $this->calculateErrorRate(),
        ];
    }

    /**
     * Calculate error rate for this route
     */
    protected function calculateErrorRate(): float
    {
        $totalRequests = \DB::table('api_gateway_logs')
            ->where('type', 'request')
            ->where('data->path', $this->path)
            ->count();

        $errorRequests = \DB::table('api_gateway_logs')
            ->where('type', 'response')
            ->where('data->path', $this->path)
            ->where('data->status_code', '>=', 400)
            ->count();

        return $totalRequests > 0 ? ($errorRequests / $totalRequests) * 100 : 0;
    }
}