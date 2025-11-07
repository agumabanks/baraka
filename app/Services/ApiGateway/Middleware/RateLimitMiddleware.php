<?php

namespace App\Services\ApiGateway\Middleware;

use App\Services\ApiGateway\ApiGatewayContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Rate limiting middleware for API Gateway
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    protected $next;
    protected const DEFAULT_RATE_LIMIT = 100; // requests per minute
    protected const DEFAULT_BURST_LIMIT = 10; // burst capacity

    /**
     * Process the request through the middleware
     */
    public function handle(ApiGatewayContext $context): bool
    {
        if (!$this->shouldExecute($context)) {
            return $this->getNext() ? $this->getNext()->handle($context) : true;
        }

        $request = $context->getRequest();
        $route = $context->getRoute();

        try {
            // Get rate limit configuration for the route
            $rateLimitConfig = $this->getRateLimitConfig($route);
            
            // Calculate rate limit key
            $rateLimitKey = $this->calculateRateLimitKey($request, $rateLimitConfig);
            
            // Check rate limit
            $rateLimitResult = $this->checkRateLimit($rateLimitKey, $rateLimitConfig);
            
            if ($rateLimitResult['exceeded']) {
                return $this->handleRateLimitExceeded($context, $rateLimitResult);
            }

            // Update context with rate limit information
            $context->setRateLimitInfo($rateLimitResult);

            // Continue to next middleware
            return $this->getNext() ? $this->getNext()->handle($context) : true;

        } catch (\Exception $e) {
            // Log rate limiting error
            $context->log('error', 'Rate limit check failed', [
                'error' => $e->getMessage(),
                'route' => $route['path'] ?? 'unknown',
            ]);

            // In case of error, allow request to continue but log it
            return $this->getNext() ? $this->getNext()->handle($context) : true;
        }
    }

    /**
     * Get rate limit configuration for the route
     */
    protected function getRateLimitConfig(array $route): array
    {
        // Default configuration
        $config = [
            'limit' => self::DEFAULT_RATE_LIMIT,
            'window' => 60, // 1 minute
            'burst_limit' => self::DEFAULT_BURST_LIMIT,
            'method' => 'sliding_window',
            'identifier' => 'ip', // ip, user, api_key, or custom
        ];

        // Get route-specific configuration
        if (isset($route['rate_limit_config'])) {
            $routeConfig = $route['rate_limit_config'];
            
            if (is_array($routeConfig)) {
                $config = array_merge($config, $routeConfig);
            }
        }

        return $config;
    }

    /**
     * Calculate rate limit key
     */
    protected function calculateRateLimitKey(Request $request, array $config): string
    {
        $identifier = $config['identifier'];
        $route = $request->path();
        $method = $request->method();

        switch ($identifier) {
            case 'ip':
                $key = $request->ip();
                break;
            case 'user':
                $user = $request->user();
                $key = $user ? $user->id : 'anonymous';
                break;
            case 'api_key':
                $apiKey = $request->header('X-API-Key', 'no_key');
                $key = $apiKey;
                break;
            case 'custom':
                // Allow custom identifier from route config
                $customField = $route['rate_limit_config']['custom_field'] ?? 'ip';
                $key = $request->input($customField, $request->ip());
                break;
            default:
                $key = $request->ip();
        }

        return sprintf('rate_limit:%s:%s:%s', $key, $route, $method);
    }

    /**
     * Check rate limit using sliding window algorithm
     */
    protected function checkRateLimit(string $key, array $config): array
    {
        $limit = $config['limit'];
        $window = $config['window'];
        $burstLimit = $config['burst_limit'];

        $cacheKey = "api_rate_limit:{$key}";
        $currentTime = time();
        $windowStart = $currentTime - $window;

        // Get current request count
        $currentRequests = Cache::get($cacheKey, 0);
        
        // Check if we're still within the sliding window
        $requestsInWindow = $this->getRequestsInSlidingWindow($cacheKey, $windowStart, $currentTime);
        
        $exceeded = $requestsInWindow >= $limit;
        $remaining = max(0, $limit - $requestsInWindow);
        
        // Add to request log
        $this->addRequestToLog($cacheKey, $currentTime);
        
        return [
            'exceeded' => $exceeded,
            'limit' => $limit,
            'remaining' => $remaining,
            'reset_time' => $currentTime + $window,
            'requests_in_window' => $requestsInWindow,
            'burst_allowed' => $requestsInWindow < $burstLimit,
        ];
    }

    /**
     * Get requests in sliding window
     */
    protected function getRequestsInSlidingWindow(string $cacheKey, int $windowStart, int $currentTime): int
    {
        $requestLog = Cache::get($cacheKey . '_log', []);
        
        // Remove old requests outside the window
        $requestLog = array_filter($requestLog, function ($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Cache the cleaned log
        Cache::put($cacheKey . '_log', $requestLog, 300);
        
        return count($requestLog);
    }

    /**
     * Add request to the log
     */
    protected function addRequestToLog(string $cacheKey, int $timestamp): void
    {
        $requestLog = Cache::get($cacheKey . '_log', []);
        $requestLog[] = $timestamp;
        
        // Keep only last 1000 requests to prevent cache bloat
        if (count($requestLog) > 1000) {
            $requestLog = array_slice($requestLog, -1000);
        }
        
        Cache::put($cacheKey . '_log', $requestLog, 300);
        Cache::put($cacheKey, count($requestLog), 300);
    }

    /**
     * Handle rate limit exceeded
     */
    protected function handleRateLimitExceeded(ApiGatewayContext $context, array $rateLimitResult): Response
    {
        $context->log('warning', 'Rate limit exceeded', [
            'limit' => $rateLimitResult['limit'],
            'requests_in_window' => $rateLimitResult['requests_in_window'],
            'client_ip' => $context->getMetadata('client_ip'),
        ]);

        // Record the rate limit breach for analytics
        $this->recordRateLimitBreach($context, $rateLimitResult);

        return $context->createErrorResponse(
            'Rate limit exceeded. Please try again later.',
            'RATE_LIMIT_EXCEEDED',
            429,
            [
                'limit' => $rateLimitResult['limit'],
                'remaining' => $rateLimitResult['remaining'],
                'reset_time' => $rateLimitResult['reset_time'],
                'retry_after' => $rateLimitResult['reset_time'] - time(),
            ]
        );
    }

    /**
     * Record rate limit breach for analytics
     */
    protected function recordRateLimitBreach(ApiGatewayContext $context, array $rateLimitResult): void
    {
        try {
            DB::table('api_rate_limit_breaches')->insert([
                'request_id' => $context->getMetadata('request_id'),
                'client_ip' => $context->getMetadata('client_ip'),
                'route' => $context->getRouteParam('path'),
                'method' => $context->getRequest()->method(),
                'user_agent' => $context->getMetadata('user_agent'),
                'limit_exceeded' => $rateLimitResult['limit'],
                'requests_in_window' => $rateLimitResult['requests_in_window'],
                'rate_limit_config' => json_encode($this->getRateLimitConfig($context->getRoute())),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::error('Failed to record rate limit breach', [
                'error' => $e->getMessage(),
                'context' => $context->getMetadata('request_id'),
            ]);
        }
    }

    /**
     * Set the next middleware in the chain
     */
    public function setNext(MiddlewareInterface $next): self
    {
        $this->next = $next;
        return $this;
    }

    /**
     * Get the next middleware in the chain
     */
    public function getNext(): ?MiddlewareInterface
    {
        return $this->next;
    }

    /**
     * Get middleware priority
     */
    public function getPriority(): int
    {
        return 10; // High priority, early in the chain
    }

    /**
     * Check if middleware should be executed for this request
     */
    public function shouldExecute(ApiGatewayContext $context): bool
    {
        $route = $context->getRoute();
        
        // Skip rate limiting for certain routes
        $skipPaths = config('api_gateway.skip_rate_limit_paths', [
            '/health',
            '/status',
            '/ping',
        ]);
        
        $path = $context->getRouteParam('path', '');
        
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return false;
            }
        }
        
        // Check if route explicitly disables rate limiting
        if (isset($route['skip_rate_limit']) && $route['skip_rate_limit']) {
            return false;
        }
        
        return true;
    }

    /**
     * Get middleware name
     */
    public function getName(): string
    {
        return 'RateLimitMiddleware';
    }

    /**
     * Get rate limit statistics for monitoring
     */
    public function getStatistics(): array
    {
        return [
            'total_breaches_today' => DB::table('api_rate_limit_breaches')
                ->whereDate('created_at', today())
                ->count(),
            'most_common_breach_routes' => DB::table('api_rate_limit_breaches')
                ->selectRaw('route, COUNT(*) as breach_count')
                ->whereDate('created_at', today())
                ->groupBy('route')
                ->orderBy('breach_count', 'desc')
                ->limit(5)
                ->pluck('breach_count', 'route')
                ->toArray(),
            'unique_breach_ips' => DB::table('api_rate_limit_breaches')
                ->whereDate('created_at', today())
                ->distinct('client_ip')
                ->count(),
        ];
    }
}