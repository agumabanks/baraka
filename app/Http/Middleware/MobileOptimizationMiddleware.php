<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MobileOptimizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Detect mobile device
        $isMobile = $this->isMobileDevice($request);

        // Apply mobile-specific optimizations
        if ($isMobile) {
            $this->optimizeForMobile($request);
        }

        // Rate limiting for mobile requests
        if ($this->shouldRateLimit($request)) {
            return $this->handleRateLimit($request);
        }

        $response = $next($request);

        // Add mobile-specific headers
        if ($isMobile) {
            $response->headers->set('X-Mobile-Optimized', 'true');
            $response->headers->set('Cache-Control', 'private, max-age=300'); // 5 minutes for mobile
        }

        // Compress response for mobile
        if ($isMobile && $this->shouldCompress($request)) {
            $response->headers->set('Content-Encoding', 'gzip');
        }

        return $response;
    }

    /**
     * Detect if the request is from a mobile device
     */
    private function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->userAgent();

        // Check for mobile user agents
        $mobileAgents = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone',
            'BlackBerry', 'webOS', 'Opera Mini', 'IEMobile'
        ];

        foreach ($mobileAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }

        // Check for mobile-specific headers
        if ($request->hasHeader('X-Mobile-App') ||
            $request->hasHeader('X-Requested-With')) {
            return true;
        }

        return false;
    }

    /**
     * Apply mobile-specific optimizations
     */
    private function optimizeForMobile(Request $request): void
    {
        // Reduce data payload for mobile requests
        $request->merge(['mobile_optimized' => true]);

        // Set mobile-specific pagination limits
        if (!$request->has('per_page')) {
            $request->merge(['per_page' => 20]); // Smaller pages for mobile
        }

        // Enable offline capabilities hint
        $request->merge(['supports_offline' => true]);
    }

    /**
     * Check if request should be rate limited
     */
    private function shouldRateLimit(Request $request): bool
    {
        $userId = auth()->id() ?? $request->ip();
        $route = $request->route() ? $request->route()->getName() : 'unknown';

        // Define rate limits based on route and user type
        $limits = [
            'api.login' => ['attempts' => 5, 'decay' => 60], // 5 attempts per minute
            'api.register' => ['attempts' => 3, 'decay' => 60],
            'api.tracking' => ['attempts' => 30, 'decay' => 60],
            'default' => ['attempts' => 60, 'decay' => 60]
        ];

        $limit = $limits[$route] ?? $limits['default'];
        $key = "rate_limit:{$userId}:{$route}";

        $attempts = Cache::get($key, 0);

        if ($attempts >= $limit['attempts']) {
            return true;
        }

        // Increment attempts
        Cache::put($key, $attempts + 1, $limit['decay']);

        return false;
    }

    /**
     * Handle rate limit exceeded
     */
    private function handleRateLimit(Request $request)
    {
        Log::warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error_code' => 'RATE_LIMIT_EXCEEDED'
        ], 429, [
            'Retry-After' => 60,
            'X-Rate-Limit-Reset' => now()->addMinute()->timestamp
        ]);
    }

    /**
     * Check if response should be compressed
     */
    private function shouldCompress(Request $request): bool
    {
        // Check if client supports gzip
        $acceptEncoding = $request->header('Accept-Encoding', '');
        return stripos($acceptEncoding, 'gzip') !== false;
    }
}
