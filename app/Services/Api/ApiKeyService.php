<?php

namespace App\Services\Api;

use App\Models\ApiKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * ApiKeyService
 * 
 * API key management:
 * - Key generation
 * - Validation
 * - Rate limiting
 * - Request logging
 */
class ApiKeyService
{
    /**
     * Create new API key
     */
    public function createKey(array $attributes): array
    {
        return ApiKey::generate($attributes);
    }

    /**
     * Validate API key and secret
     */
    public function validate(string $key, string $secret): ?ApiKey
    {
        $apiKey = ApiKey::where('key', $key)->first();

        if (!$apiKey) {
            return null;
        }

        if (!$apiKey->isValid()) {
            return null;
        }

        if (!$apiKey->verifySecret($secret)) {
            return null;
        }

        $apiKey->touchLastUsed();

        return $apiKey;
    }

    /**
     * Check rate limit
     */
    public function checkRateLimit(ApiKey $apiKey, string $ip): array
    {
        $key = "api_rate_limit:{$apiKey->id}:{$ip}";
        $limit = $apiKey->rate_limit_per_minute;

        $current = Cache::get($key, 0);

        if ($current >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'remaining' => 0,
                'reset_at' => Cache::get("{$key}:reset", now()->addMinute()),
            ];
        }

        // Increment counter
        if ($current === 0) {
            Cache::put($key, 1, 60);
            Cache::put("{$key}:reset", now()->addMinute(), 60);
        } else {
            Cache::increment($key);
        }

        return [
            'allowed' => true,
            'limit' => $limit,
            'remaining' => $limit - $current - 1,
            'reset_at' => Cache::get("{$key}:reset"),
        ];
    }

    /**
     * Log API request
     */
    public function logRequest(
        ?ApiKey $apiKey,
        string $method,
        string $endpoint,
        ?string $ip,
        int $responseCode,
        int $responseTimeMs,
        ?array $params = null
    ): void {
        DB::table('api_request_logs')->insert([
            'api_key_id' => $apiKey?->id,
            'method' => $method,
            'endpoint' => $endpoint,
            'ip_address' => $ip,
            'response_code' => $responseCode,
            'response_time_ms' => $responseTimeMs,
            'request_params' => $params ? json_encode($params) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Get API usage statistics
     */
    public function getUsageStats(ApiKey $apiKey, int $days = 30): array
    {
        $stats = DB::table('api_request_logs')
            ->where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(CASE WHEN response_code >= 200 AND response_code < 300 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as errors,
                AVG(response_time_ms) as avg_response_time,
                MAX(created_at) as last_request
            ')
            ->first();

        $byEndpoint = DB::table('api_request_logs')
            ->where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->select('endpoint', DB::raw('COUNT(*) as count'))
            ->groupBy('endpoint')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'total_requests' => $stats->total_requests ?? 0,
            'successful' => $stats->successful ?? 0,
            'errors' => $stats->errors ?? 0,
            'avg_response_time_ms' => round($stats->avg_response_time ?? 0),
            'last_request' => $stats->last_request,
            'by_endpoint' => $byEndpoint->toArray(),
        ];
    }

    /**
     * Revoke API key
     */
    public function revokeKey(ApiKey $apiKey): void
    {
        $apiKey->update(['is_active' => false]);
    }

    /**
     * Regenerate secret
     */
    public function regenerateSecret(ApiKey $apiKey): string
    {
        $secret = 'bs_' . \Illuminate\Support\Str::random(48);
        
        $apiKey->update([
            'secret_hash' => hash('sha256', $secret),
        ]);

        return $secret;
    }
}
