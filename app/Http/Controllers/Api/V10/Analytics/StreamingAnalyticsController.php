<?php

namespace App\Http\Controllers\Api\V10\Analytics;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsPerformanceMonitoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class StreamingAnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsPerformanceMonitoringService $performanceService
    ) {
    }

    /**
     * Return recent streaming metrics for the dashboard.
     */
    public function metrics(): JsonResponse
    {
        $realtime = $this->performanceService->getRealTimePerformance();

        $dataPoints = collect($realtime['recent_performance'] ?? [])
            ->map(function (array $entry): array {
                $timestamp = isset($entry['time'])
                    ? Carbon::parse($entry['time'])->toIso8601String()
                    : now()->toIso8601String();

                return [
                    'metric' => $entry['operation'] ?? 'analytics_operation',
                    'value' => (float) ($entry['execution_time'] ?? 0),
                    'timestamp' => $timestamp,
                    'metadata' => [
                        'memory_usage' => $entry['memory_usage'] ?? null,
                        'cache_hit_rate' => $entry['cache_hit_rate'] ?? null,
                    ],
                ];
            })
            ->take(25)
            ->values();

        $connections = $this->estimateConnectionCount();
        $updatesPerSecond = $this->calculateUpdatesPerSecond($dataPoints);

        // Persist lightweight heartbeat so other services can inspect status
        Redis::set('analytics:streaming:last_heartbeat', now()->toIso8601String());

        return response()->json([
            'success' => true,
            'data' => [
                'connections' => $connections,
                'updatesPerSecond' => $updatesPerSecond,
                'dataPoints' => $dataPoints,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Report current streaming status (for health checks).
     */
    public function status(): JsonResponse
    {
        $lastHeartbeat = Redis::get('analytics:streaming:last_heartbeat');
        $uptimeSeconds = (int) (Redis::get('analytics:streaming:uptime_seconds') ?? 0);

        $lastUpdate = $lastHeartbeat ? Carbon::parse($lastHeartbeat) : null;
        $isConnected = $lastUpdate ? $lastUpdate->greaterThan(now()->subSeconds(30)) : false;

        return response()->json([
            'success' => true,
            'data' => [
                'connected' => $isConnected,
                'lastUpdate' => $lastUpdate?->toIso8601String(),
                'uptimeSeconds' => $uptimeSeconds,
            ],
        ]);
    }

    /**
     * Provide WebSocket connection details for the SPA.
     */
    public function websocketConfig(): JsonResponse
    {
        $configuredUrl = Config::get('services.analytics.websocket_url') ?? env('ANALYTICS_WEBSOCKET_URL');

        if (!$configuredUrl) {
            $host = Config::get('broadcasting.connections.pusher.options.host')
                ?? env('PUSHER_HOST', '127.0.0.1');
            $port = Config::get('broadcasting.connections.pusher.options.port')
                ?? (int) env('PUSHER_PORT', 6001);
            $scheme = Config::get('broadcasting.connections.pusher.options.scheme')
                ?? env('PUSHER_SCHEME', 'http');
            $protocol = $scheme === 'https' ? 'wss' : 'ws';
            $path = trim(env('WEBSOCKET_PATH', 'app'), '/');
            $key = env('PUSHER_APP_KEY', 'app-key');

            $configuredUrl = sprintf(
                '%s://%s:%d/%s/%s?client=analytics-dashboard&protocol=1.0',
                $protocol,
                $host,
                $port,
                $path,
                $key
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $configuredUrl,
                'protocols' => ['json'],
            ],
        ]);
    }

    /**
     * Provide baseline SSE configuration for clients that prefer it.
     */
    public function sseConfig(): JsonResponse
    {
        $sseUrl = Config::get('services.analytics.sse_url')
            ?? url('/api/v10/analytics/streaming/events');

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $sseUrl,
                'eventTypes' => [
                    'analytics.metric',
                    'analytics.alert',
                    'analytics.notification',
                ],
            ],
        ]);
    }

    private function estimateConnectionCount(): int
    {
        try {
            $keys = Redis::keys('realtime:branch:*');
            return count($keys);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function calculateUpdatesPerSecond(Collection $dataPoints): float
    {
        if ($dataPoints->isEmpty()) {
            return 0.5;
        }

        $timestamps = $dataPoints
            ->map(fn (array $point) => Carbon::parse($point['timestamp'])->getTimestamp())
            ->sort()
            ->values();

        $duration = $timestamps->last() - $timestamps->first();
        if ($duration <= 0) {
            return round($dataPoints->count() / 5, 2);
        }

        return round($dataPoints->count() / max(1, $duration), 2);
    }
}
