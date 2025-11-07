<?php

namespace App\Services\ApiGateway\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Collects and stores API Gateway metrics
 */
class MetricsCollector
{
    protected string $prefix = 'api_metrics';

    /**
     * Record incoming request metrics
     */
    public function recordIncomingRequest(array $data): void
    {
        $this->incrementCounter('incoming_requests', [
            'method' => $data['method'],
            'path' => $this->normalizePath($data['path']),
        ]);

        $this->gauge('request_size', $data['content_length'], [
            'method' => $data['method'],
            'content_type' => $data['content_type'] ?? 'unknown',
        ]);

        // Store raw request data for analysis
        $this->storeRawMetric('request_log', $data);
    }

    /**
     * Record response metrics
     */
    public function recordResponse(array $data): void
    {
        $this->incrementCounter('responses', [
            'method' => $data['method'],
            'path' => $this->normalizePath($data['path']),
            'status_code' => $this->getStatusCodeGroup($data['status_code']),
        ]);

        $this->gauge('response_size', $data['response_size'], [
            'method' => $data['method'],
            'status_code' => $this->getStatusCodeGroup($data['status_code']),
        ]);

        $this->gauge('processing_time', $data['processing_time'], [
            'method' => $data['method'],
            'path' => $this->normalizePath($data['path']),
        ]);

        $this->gauge('memory_usage', $data['memory_usage'], [
            'method' => $data['method'],
        ]);

        // Store raw response data
        $this->storeRawMetric('response_log', $data);
    }

    /**
     * Record error metrics
     */
    public function recordError(array $data): void
    {
        $this->incrementCounter('errors', [
            'method' => $data['method'],
            'path' => $this->normalizePath($data['path']),
            'error_type' => $this->getErrorTypeGroup($data['error_type']),
        ]);

        // Store error details
        $this->storeRawMetric('error_log', $data);
    }

    /**
     * Record performance metrics
     */
    public function recordPerformance(array $data): void
    {
        $this->gauge('performance_processing_time', $data['processing_time'], [
            'method' => $data['method'],
            'path' => $this->normalizePath($data['path']),
            'is_authenticated' => $data['is_authenticated'] ? 'true' : 'false',
        ]);

        $this->gauge('performance_memory_usage', $data['memory_usage'], [
            'method' => $data['method'],
            'is_authenticated' => $data['is_authenticated'] ? 'true' : 'false',
        ]);

        // Store performance data
        $this->storeRawMetric('performance_log', $data);
    }

    /**
     * Increment counter metric
     */
    protected function incrementCounter(string $metric, array $tags = []): void
    {
        $key = $this->buildCacheKey("counter:{$metric}", $tags);
        
        Cache::increment($key);
        
        // Also store in database for long-term analysis
        $this->storeMetric('counter', $metric, 1, $tags);
    }

    /**
     * Set gauge metric
     */
    protected function gauge(string $metric, float $value, array $tags = []): void
    {
        $key = $this->buildCacheKey("gauge:{$metric}", $tags);
        
        // Store current value
        Cache::put($key, $value, 300); // 5 minutes
        
        // Also store in database for analysis
        $this->storeMetric('gauge', $metric, $value, $tags);
        
        // Store value history for trend analysis
        $this->storeMetricHistory($metric, $value, $tags);
    }

    /**
     * Store metric in database
     */
    protected function storeMetric(string $type, string $metric, float $value, array $tags = []): void
    {
        try {
            DB::table('api_gateway_metrics')->insert([
                'type' => $type,
                'metric' => $metric,
                'value' => $value,
                'tags' => json_encode($tags),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store metric in database', [
                'error' => $e->getMessage(),
                'metric' => $metric,
                'type' => $type,
            ]);
        }
    }

    /**
     * Store metric history for trend analysis
     */
    protected function storeMetricHistory(string $metric, float $value, array $tags = []): void
    {
        $historyKey = $this->buildCacheKey("history:{$metric}", $tags);
        $history = Cache::get($historyKey, []);
        
        $history[] = [
            'value' => $value,
            'timestamp' => now()->timestamp,
        ];
        
        // Keep only last 100 values
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }
        
        Cache::put($historyKey, $history, 3600); // 1 hour
    }

    /**
     * Store raw metric data
     */
    protected function storeRawMetric(string $type, array $data): void
    {
        try {
            DB::table('api_gateway_raw_metrics')->insert([
                'type' => $type,
                'data' => json_encode($data),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store raw metric', [
                'error' => $e->getMessage(),
                'type' => $type,
            ]);
        }
    }

    /**
     * Get metrics summary
     */
    public function getSummary(): array
    {
        $summary = [];
        
        // Get today's metrics
        $todayMetrics = DB::table('api_gateway_metrics')
            ->whereDate('created_at', today())
            ->selectRaw('type, metric, COUNT(*) as count, AVG(value) as avg_value, MAX(value) as max_value, MIN(value) as min_value')
            ->groupBy(['type', 'metric'])
            ->get();

        foreach ($todayMetrics as $metric) {
            $summary['today'][] = [
                'type' => $metric->type,
                'metric' => $metric->metric,
                'count' => $metric->count,
                'avg_value' => round($metric->avg_value, 2),
                'max_value' => round($metric->max_value, 2),
                'min_value' => round($metric->min_value, 2),
            ];
        }

        // Get error rate
        $totalRequests = DB::table('api_gateway_metrics')
            ->where('metric', 'responses')
            ->whereDate('created_at', today())
            ->sum('value');

        $totalErrors = DB::table('api_gateway_metrics')
            ->where('metric', 'errors')
            ->whereDate('created_at', today())
            ->sum('value');

        $summary['error_rate'] = $totalRequests > 0 ? ($totalErrors / $totalRequests) * 100 : 0;

        // Get average response time
        $summary['avg_response_time'] = DB::table('api_gateway_metrics')
            ->where('metric', 'processing_time')
            ->where('type', 'gauge')
            ->whereDate('created_at', today())
            ->avg('value') ?? 0;

        return $summary;
    }

    /**
     * Get metrics for specific time range
     */
    public function getMetricsForRange(string $metric, string $startDate, string $endDate): array
    {
        return DB::table('api_gateway_metrics')
            ->where('metric', $metric)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, AVG(value) as avg_value, MAX(value) as max_value, MIN(value) as min_value, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get top endpoints by traffic
     */
    public function getTopEndpoints(int $limit = 10): array
    {
        return DB::table('api_gateway_raw_metrics')
            ->where('type', 'request_log')
            ->whereDate('created_at', today())
            ->selectRaw('data->>"$.path" as path, COUNT(*) as request_count')
            ->groupBy('path')
            ->orderBy('request_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get slowest endpoints
     */
    public function getSlowestEndpoints(int $limit = 10): array
    {
        return DB::table('api_gateway_metrics')
            ->where('metric', 'processing_time')
            ->where('type', 'gauge')
            ->whereDate('created_at', today())
            ->selectRaw('tags, AVG(value) as avg_processing_time')
            ->groupBy('tags')
            ->orderBy('avg_processing_time', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $tags = json_decode($item->tags, true);
                return [
                    'path' => $tags['path'] ?? 'unknown',
                    'method' => $tags['method'] ?? 'unknown',
                    'avg_processing_time' => round($item->avg_processing_time, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Normalize path for grouping
     */
    protected function normalizePath(string $path): string
    {
        // Remove query parameters
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        
        // Replace IDs with placeholder
        $path = preg_replace('/\/(\d+)\//', '/{id}/', $path);
        $path = preg_replace('/\/([a-f0-9-]{36})\//', '/{uuid}/', $path);
        
        return $path;
    }

    /**
     * Group status codes
     */
    protected function getStatusCodeGroup(int $statusCode): string
    {
        if ($statusCode >= 200 && $statusCode < 300) return '2xx';
        if ($statusCode >= 300 && $statusCode < 400) return '3xx';
        if ($statusCode >= 400 && $statusCode < 500) return '4xx';
        if ($statusCode >= 500) return '5xx';
        return 'unknown';
    }

    /**
     * Group error types
     */
    protected function getErrorTypeGroup(string $errorType): string
    {
        $errorType = strtolower($errorType);
        
        if (str_contains($errorType, 'auth')) return 'auth';
        if (str_contains($errorType, 'validation')) return 'validation';
        if (str_contains($errorType, 'rate')) return 'rate_limit';
        if (str_contains($errorType, 'server') || str_contains($errorType, 'internal')) return 'server';
        
        return 'other';
    }

    /**
     * Build cache key with tags
     */
    protected function buildCacheKey(string $metric, array $tags = []): string
    {
        $key = "{$this->prefix}:{$metric}";
        
        if (!empty($tags)) {
            $tagString = http_build_query($tags);
            $key .= ':' . md5($tagString);
        }
        
        return $key;
    }

    /**
     * Clean up old metrics
     */
    public function cleanup(int $daysToKeep = 30): void
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        DB::table('api_gateway_metrics')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
            
        DB::table('api_gateway_raw_metrics')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
            
        DB::table('api_rate_limit_breaches')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
            
        DB::table('api_performance_alerts')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
}