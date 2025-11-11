<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    private const HEADER_KEY = 'Idempotency-Key';
    private const CACHE_PREFIX = 'idempotency:';
    private const CACHE_TTL = 3600; // 1 hour

    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST, PUT, PATCH requests that modify data
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Skip if no idempotency key provided
        if (!$request->hasHeader(self::HEADER_KEY)) {
            return $next($request);
        }

        $idempotencyKey = $request->header(self::HEADER_KEY);

        // Validate idempotency key format (UUID or similar)
        if (!$this->isValidKey($idempotencyKey)) {
            return response()->json([
                'error' => 'Invalid Idempotency-Key format. Use UUID v4.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $cacheKey = self::CACHE_PREFIX . $idempotencyKey;

        // Check if this request was already processed
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            Log::info('Idempotent request already processed', [
                'idempotency_key' => $idempotencyKey,
                'user_id' => auth()->id(),
            ]);
            return response()->json($cachedResponse, Response::HTTP_OK);
        }

        // Store the route and method for tracking
        $request->attributes->set('idempotency_key', $idempotencyKey);
        $request->attributes->set('idempotency_request_id', Str::uuid());

        $response = $next($request);

        // Only cache successful responses
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            try {
                $responseData = json_decode($response->getContent(), true) ?? [
                    'status' => 'success',
                    'code' => $response->getStatusCode(),
                ];
                Cache::put($cacheKey, $responseData, self::CACHE_TTL);
                Log::info('Idempotent response cached', [
                    'idempotency_key' => $idempotencyKey,
                    'user_id' => auth()->id(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to cache idempotent response', [
                    'idempotency_key' => $idempotencyKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }

    private function isValidKey(string $key): bool
    {
        // Accept UUID v4 format or alphanumeric strings
        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $key) ||
               preg_match('/^[a-zA-Z0-9_-]{20,}$/', $key);
    }
}
