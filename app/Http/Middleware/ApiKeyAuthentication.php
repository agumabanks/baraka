<?php

namespace App\Http\Middleware;

use App\Services\Api\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    protected ApiKeyService $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $startTime = microtime(true);

        // Get API key from header or query
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');
        $apiSecret = $request->header('X-API-Secret') ?? $request->query('api_secret');

        if (!$apiKey || !$apiSecret) {
            return $this->unauthorizedResponse('API key and secret required');
        }

        // Validate key
        $key = $this->apiKeyService->validate($apiKey, $apiSecret);

        if (!$key) {
            return $this->unauthorizedResponse('Invalid API credentials');
        }

        // Check IP whitelist
        if (!$key->isIpAllowed($request->ip())) {
            return $this->unauthorizedResponse('IP address not allowed');
        }

        // Check permission
        if ($permission && !$key->hasPermission($permission)) {
            return $this->forbiddenResponse("Permission '{$permission}' required");
        }

        // Check rate limit
        $rateLimit = $this->apiKeyService->checkRateLimit($key, $request->ip());

        if (!$rateLimit['allowed']) {
            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests',
                'retry_after' => $rateLimit['reset_at']?->diffInSeconds(now()),
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $rateLimit['limit'],
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $rateLimit['reset_at']?->diffInSeconds(now()),
            ]);
        }

        // Store key in request for later use
        $request->attributes->set('api_key', $key);

        // Process request
        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $rateLimit['limit']);
        $response->headers->set('X-RateLimit-Remaining', $rateLimit['remaining']);

        // Log request
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);
        $this->apiKeyService->logRequest(
            $key,
            $request->method(),
            $request->path(),
            $request->ip(),
            $response->getStatusCode(),
            $responseTime
        );

        return $response;
    }

    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error' => 'unauthorized',
            'message' => $message,
        ], 401);
    }

    protected function forbiddenResponse(string $message): Response
    {
        return response()->json([
            'error' => 'forbidden',
            'message' => $message,
        ], 403);
    }
}
