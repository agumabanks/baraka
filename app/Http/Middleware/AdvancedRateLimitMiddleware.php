<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Advanced Rate Limiting Middleware
 * 
 * Implements tiered rate limiting based on API endpoint type,
 * user tier, and subscription level. Supports different rate
 * limits for different operations with intelligent throttling.
 */
class AdvancedRateLimitMiddleware
{
    // Rate limit configurations by endpoint type
    private const RATE_LIMITS = [
        'quotes' => [
            'requests_per_minute' => 100,
            'requests_per_hour' => 5000,
            'tier_multipliers' => [
                'platinum' => 2.0,
                'gold' => 1.5,
                'silver' => 1.2,
                'standard' => 1.0,
            ],
        ],
        'bulk_quotes' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 100,
            'batch_size_limit' => 100,
            'priority_multipliers' => [
                'urgent' => 2.0,
                'high' => 1.5,
                'normal' => 1.0,
                'low' => 0.5,
            ],
        ],
        'contracts' => [
            'requests_per_minute' => 50,
            'requests_per_hour' => 2000,
        ],
        'promotions' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 3000,
        ],
        'analytics' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 1000,
        ],
        'integration' => [
            'requests_per_minute' => 20,
            'requests_per_hour' => 500,
        ],
        'webhooks' => [
            'requests_per_minute' => 200,
            'requests_per_hour' => 10000,
        ],
    ];

    // Burst allowance by customer tier
    private const BURST_ALLOWANCE = [
        'platinum' => 20,
        'gold' => 15,
        'silver' => 10,
        'standard' => 5,
    ];

    public function handle(Request $request, Closure $next, string $endpointType = 'quotes'): Response
    {
        try {
            $user = auth('api')->user();
            $customerTier = $user?->customer_type ?? 'standard';
            $apiKey = $this->extractApiKey($request);

            // Skip rate limiting for health checks and status endpoints
            if ($this->shouldSkipRateLimit($request)) {
                return $next($request);
            }

            // Get rate limit configuration for endpoint type
            $config = self::RATE_LIMITS[$endpointType] ?? self::RATE_LIMITS['quotes'];
            
            // Apply tier-based multipliers
            $tierMultiplier = $config['tier_multipliers'][$customerTier] ?? 1.0;
            $burstAllowance = self::BURST_ALLOWANCE[$customerTier] ?? 5;

            // Generate rate limit keys
            $minuteKey = $this->generateRateLimitKey($request, $endpointType, $customerTier, 'minute', $apiKey);
            $hourKey = $this->generateRateLimitKey($request, $endpointType, $customerTier, 'hour', $apiKey);

            // Check minute rate limit
            $minuteLimit = intval($config['requests_per_minute'] * $tierMultiplier + $burstAllowance);
            if (!RateLimiter::attempt($minuteKey, 1, function() {}, $minuteLimit)) {
                $this->logRateLimitViolation($request, $endpointType, 'minute', $minuteLimit);
                return $this->buildRateLimitResponse($minuteKey, $minuteLimit, 60);
            }

            // Check hour rate limit for applicable endpoints
            if ($config['requests_per_hour'] ?? null) {
                $hourLimit = intval($config['requests_per_hour'] * $tierMultiplier + ($burstAllowance * 10));
                if (!RateLimiter::attempt($hourKey, 1, function() {}, $hourLimit)) {
                    $this->logRateLimitViolation($request, $endpointType, 'hour', $hourLimit);
                    return $this->buildRateLimitResponse($hourKey, $hourLimit, 3600);
                }
            }

            // Special validation for bulk operations
            if ($endpointType === 'bulk_quotes') {
                $bulkValidation = $this->validateBulkLimits($request, $config);
                if (!$bulkValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Bulk operation validation failed',
                        'message' => $bulkValidation['message'],
                        'retry_after' => $bulkValidation['retry_after'] ?? null,
                        'timestamp' => now()->toISOString(),
                    ], 429);
                }
            }

            // Add rate limit headers to response
            $response = $next($request);
            $this->addRateLimitHeaders($response, $minuteKey, $hourKey, $minuteLimit, $config);

            return $response;

        } catch (\Exception $e) {
            Log::error('Rate limiting middleware error', [
                'error' => $e->getMessage(),
                'endpoint_type' => $endpointType,
                'request_path' => $request->path(),
                'user_id' => auth('api')->id(),
            ]);

            // Fail open - don't block requests on middleware errors
            return $next($request);
        }
    }

    private function shouldSkipRateLimit(Request $request): bool
    {
        $skipPaths = [
            '/health',
            '/version',
            '/business-rules',
            '/api/v1/health',
            '/api/v1/version',
            '/api/v1/business-rules',
        ];

        return in_array($request->path(), $skipPaths);
    }

    private function extractApiKey(Request $request): string
    {
        return $request->header('Authorization') ? 
            substr($request->header('Authorization'), 7) : 
            ($request->header('X-API-Key') ?? 'anonymous');
    }

    private function generateRateLimitKey(Request $request, string $endpointType, string $tier, string $timeframe, ?string $apiKey): string
    {
        $identifier = $apiKey !== 'anonymous' ? $apiKey : ($request->ip() ?? 'anonymous');
        return "api:{$endpointType}:{$tier}:{$timeframe}:{$identifier}";
    }

    private function validateBulkLimits(Request $request, array $config): array
    {
        $shipmentRequests = $request->input('shipment_requests', []);
        $batchSize = count($shipmentRequests);
        $batchSizeLimit = $config['batch_size_limit'] ?? 100;

        if ($batchSize > $batchSizeLimit) {
            return [
                'valid' => false,
                'message' => "Batch size of {$batchSize} exceeds limit of {$batchSizeLimit}",
                'retry_after' => 300, // 5 minutes
            ];
        }

        // Check for too many bulk requests from same customer
        $customerId = auth('api')->id() ?? $request->input('customer_id');
        $recentBulkRequests = $this->getRecentBulkRequestCount($customerId);
        $hourlyBulkLimit = intval($config['requests_per_hour'] ?? 100);

        if ($recentBulkRequests >= $hourlyBulkLimit) {
            return [
                'valid' => false,
                'message' => "Bulk request limit exceeded. Try again later.",
                'retry_after' => 3600, // 1 hour
            ];
        }

        return ['valid' => true];
    }

    private function getRecentBulkRequestCount(?int $customerId): int
    {
        if (!$customerId) return 0;

        return \DB::table('bulk_quote_requests')
            ->where('customer_id', $customerId)
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }

    private function buildRateLimitResponse(string $key, int $limit, int $timeWindowSeconds): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        return response()->json([
            'success' => false,
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
            'limit' => $limit,
            'time_window_seconds' => $timeWindowSeconds,
            'timestamp' => now()->toISOString(),
        ], 429)->header('Retry-After', $retryAfter);
    }

    private function addRateLimitHeaders(Response $response, string $minuteKey, string $hourKey, int $minuteLimit, array $config): void
    {
        // Minute-based headers
        $response->headers->set('X-RateLimit-Limit-Minute', $minuteLimit);
        $response->headers->set('X-RateLimit-Remaining-Minute', 
            max(0, $minuteLimit - RateLimiter::attempts($minuteKey)));
        $response->headers->set('X-RateLimit-Reset-Minute', now()->addMinute()->timestamp);

        // Hour-based headers
        if (isset($config['requests_per_hour'])) {
            $hourLimit = intval($config['requests_per_hour']);
            $response->headers->set('X-RateLimit-Limit-Hour', $hourLimit);
            $response->headers->set('X-RateLimit-Remaining-Hour', 
                max(0, $hourLimit - RateLimiter::attempts($hourKey)));
            $response->headers->set('X-RateLimit-Reset-Hour', now()->addHour()->timestamp);
        }

        // General rate limit headers
        $response->headers->set('X-RateLimit-Type', 'advanced-tiered');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    private function logRateLimitViolation(Request $request, string $endpointType, string $timeframe, int $limit): void
    {
        Log::warning('Rate limit exceeded', [
            'endpoint_type' => $endpointType,
            'timeframe' => $timeframe,
            'limit' => $limit,
            'user_id' => auth('api')->id(),
            'api_key' => $this->maskApiKey($this->extractApiKey($request)),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'path' => $request->path(),
            'method' => $request->method(),
        ]);
    }

    private function maskApiKey(string $apiKey): string
    {
        if (strlen($apiKey) <= 8) {
            return str_repeat('*', strlen($apiKey));
        }
        return substr($apiKey, 0, 4) . '***' . substr($apiKey, -4);
    }
}