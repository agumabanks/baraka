<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    private const RATE_LIMIT_PREFIX = 'api_rate_limit:';

    public function __construct(private RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $limits = $this->getLimits($request);

        foreach ($limits as $key => $config) {
            if (!$this->checkLimit($request, $key, $config)) {
                Log::warning('Rate limit exceeded', [
                    'limit_key' => $key,
                    'ip' => $request->ip(),
                    'user_id' => auth()->id(),
                    'path' => $request->path(),
                ]);

                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $config['retry_after'] ?? 60,
                ], Response::HTTP_TOO_MANY_REQUESTS)
                    ->header('Retry-After', $config['retry_after'] ?? 60)
                    ->header('X-RateLimit-Limit', $config['limit'])
                    ->header('X-RateLimit-Remaining', 0);
            }
        }

        return $next($request);
    }

    private function getLimits(Request $request): array
    {
        $baseEndpoint = $request->route()?->getName() ?? 'unknown';

        // Different limits for different endpoints
        if ($request->is('api/v1/bookings*')) {
            return ['bookings' => ['limit' => 50, 'window' => 3600, 'retry_after' => 3600]];
        }
        if ($request->is('api/v1/dispatch*')) {
            return ['dispatch' => ['limit' => 100, 'window' => 3600, 'retry_after' => 3600]];
        }
        if ($request->is('api/v1/scan*')) {
            return ['scan' => ['limit' => 500, 'window' => 3600, 'retry_after' => 300]];
        }
        if ($request->is('api/v1/webhooks*')) {
            return ['webhooks' => ['limit' => 10000, 'window' => 3600, 'retry_after' => 60]];
        }

        // Default global limit
        return ['global' => ['limit' => 1000, 'window' => 3600, 'retry_after' => 60]];
    }

    private function checkLimit(Request $request, string $key, array $config): bool
    {
        $identifier = $this->getIdentifier($request);
        $cacheKey = self::RATE_LIMIT_PREFIX . $key . ':' . $identifier;

        $current = Cache::get($cacheKey, 0);

        if ($current >= $config['limit']) {
            return false;
        }

        Cache::put($cacheKey, $current + 1, $config['window']);

        return true;
    }

    private function getIdentifier(Request $request): string
    {
        if (auth()->check()) {
            return 'user_' . auth()->id();
        }

        return 'ip_' . $request->ip();
    }
}
