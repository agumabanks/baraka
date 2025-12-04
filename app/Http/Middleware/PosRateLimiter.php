<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosRateLimiter
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $type);
        $limits = $this->getLimits($type);

        if ($this->limiter->tooManyAttempts($key, $limits['max_attempts'])) {
            return response()->json([
                'success' => false,
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        $this->limiter->hit($key, $limits['decay_seconds']);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $limits['max_attempts'],
            $this->calculateRemainingAttempts($key, $limits['max_attempts'])
        );
    }

    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $user = $request->user();
        $identifier = $user ? $user->id : $request->ip();

        return "pos:{$type}:{$identifier}";
    }

    protected function getLimits(string $type): array
    {
        return match ($type) {
            'search' => ['max_attempts' => 60, 'decay_seconds' => 60], // 60/min for search
            'quote' => ['max_attempts' => 30, 'decay_seconds' => 60],  // 30/min for quotes
            'create' => ['max_attempts' => 10, 'decay_seconds' => 60], // 10/min for creates
            'payment' => ['max_attempts' => 10, 'decay_seconds' => 60], // 10/min for payments
            default => ['max_attempts' => 100, 'decay_seconds' => 60],
        };
    }

    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->remaining($key, $maxAttempts);
    }

    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }
}
