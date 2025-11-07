<?php

namespace App\Services\ApiGateway\Monitoring;

use App\Services\ApiGateway\ApiGatewayContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Collects and stores API Gateway logs
 */
class LogCollector
{
    protected string $prefix = 'api_logs';

    /**
     * Log incoming request
     */
    public function logRequest(ApiGatewayContext $context, string $direction = 'incoming'): void
    {
        $request = $context->getRequest();
        $route = $context->getRoute();

        $logData = [
            'timestamp' => now()->toISOString(),
            'request_id' => $context->getMetadata('request_id'),
            'direction' => $direction,
            'method' => $request->method(),
            'path' => $route['path'] ?? $request->path(),
            'query_params' => $request->query(),
            'client_ip' => $context->getMetadata('client_ip'),
            'user_agent' => $context->getMetadata('user_agent'),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->getContentLength(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'authenticated' => $context->isAuthenticated(),
            'user_id' => $context->getUser()?->id,
        ];

        // Add request body for non-GET requests
        if (!in_array($request->method(), ['GET', 'HEAD'])) {
            $logData['body'] = $this->sanitizeRequestBody($request->getContent());
        }

        // Store in database
        $this->storeLog('request', $logData);

        // Store in cache for real-time access
        $this->storeRecentLog($logData);
    }

    /**
     * Log outgoing response
     */
    public function logResponse(ApiGatewayContext $context, string $direction = 'outgoing'): void
    {
        $request = $context->getRequest();
        $response = $context->getResponse();
        $route = $context->getRoute();

        $logData = [
            'timestamp' => now()->toISOString(),
            'request_id' => $context->getMetadata('request_id'),
            'direction' => $direction,
            'method' => $request->method(),
            'path' => $route['path'] ?? $request->path(),
            'status_code' => $response ? $response->getStatusCode() : null,
            'content_length' => $response ? strlen($response->getContent()) : 0,
            'processing_time' => $context->getMetadata('processing_time'),
            'memory_usage' => $context->getMetadata('memory_delta'),
            'headers' => $response ? $this->sanitizeResponseHeaders($response->headers->all()) : [],
            'rate_limit_info' => $context->getRateLimitInfo(),
            'errors' => $context->getErrors(),
        ];

        // Store in database
        $this->storeLog('response', $logData);

        // Store in cache for real-time access
        $this->storeRecentLog($logData);

        // Log to Laravel's log system for debugging
        $this->logToLaravel($context, $logData);
    }

    /**
     * Log error
     */
    public function logError(ApiGatewayContext $context, \Exception $e, string $level = 'error'): void
    {
        $request = $context->getRequest();
        $route = $context->getRoute();

        $logData = [
            'timestamp' => now()->toISOString(),
            'request_id' => $context->getMetadata('request_id'),
            'level' => $level,
            'method' => $request->method(),
            'path' => $route['path'] ?? $request->path(),
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'client_ip' => $context->getMetadata('client_ip'),
            'user_agent' => $context->getMetadata('user_agent'),
            'authenticated' => $context->isAuthenticated(),
            'user_id' => $context->getUser()?->id,
            'request_data' => $this->getRequestSnapshot($request),
        ];

        // Store in database
        $this->storeLog('error', $logData);

        // Log to Laravel's log system
        Log::log($level, 'API Gateway Error', [
            'request_id' => $context->getMetadata('request_id'),
            'path' => $logData['path'],
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    /**
     * Store log in database
     */
    protected function storeLog(string $type, array $data): void
    {
        try {
            DB::table('api_gateway_logs')->insert([
                'type' => $type,
                'data' => json_encode($data),
                'request_id' => $data['request_id'],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log to Laravel if database storage fails
            Log::error('Failed to store API gateway log', [
                'error' => $e->getMessage(),
                'type' => $type,
            ]);
        }
    }

    /**
     * Store recent log in cache for real-time access
     */
    protected function storeRecentLog(array $data): void
    {
        $cacheKey = "{$this->prefix}:recent";
        $recentLogs = Cache::get($cacheKey, []);
        
        // Add new log to the beginning
        array_unshift($recentLogs, $data);
        
        // Keep only last 100 logs
        if (count($recentLogs) > 100) {
            $recentLogs = array_slice($recentLogs, 0, 100);
        }
        
        Cache::put($cacheKey, $recentLogs, 3600); // 1 hour
    }

    /**
     * Log to Laravel's log system
     */
    protected function logToLaravel(ApiGatewayContext $context, array $logData): void
    {
        $level = $this->determineLogLevel($logData['status_code'] ?? 200);
        
        $context->log($level, 'API Gateway Request', [
            'status_code' => $logData['status_code'],
            'processing_time' => $logData['processing_time'],
            'content_length' => $logData['content_length'],
        ]);
    }

    /**
     * Determine log level based on status code
     */
    protected function determineLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) return 'error';
        if ($statusCode >= 400) return 'warning';
        return 'info';
    }

    /**
     * Get recent logs
     */
    public function getRecentLogs(int $limit = 50): array
    {
        return Cache::get("{$this->prefix}:recent", []);
    }

    /**
     * Get logs for specific time range
     */
    public function getLogsForRange(string $type, string $startDate, string $endDate, int $limit = 1000): array
    {
        return DB::table('api_gateway_logs')
            ->where('type', $type)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                $log->data = json_decode($log->data, true);
                return $log;
            })
            ->toArray();
    }

    /**
     * Get error logs
     */
    public function getErrorLogs(string $startDate = null, string $endDate = null, int $limit = 100): array
    {
        $query = DB::table('api_gateway_logs')
            ->where('type', 'error')
            ->orderBy('created_at', 'desc');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->limit($limit)
            ->get()
            ->map(function ($log) {
                $log->data = json_decode($log->data, true);
                return $log;
            })
            ->toArray();
    }

    /**
     * Get logs statistics
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Today's request counts
        $stats['today_requests'] = DB::table('api_gateway_logs')
            ->where('type', 'request')
            ->whereDate('created_at', today())
            ->count();

        $stats['today_responses'] = DB::table('api_gateway_logs')
            ->where('type', 'response')
            ->whereDate('created_at', today())
            ->count();

        $stats['today_errors'] = DB::table('api_gateway_logs')
            ->where('type', 'error')
            ->whereDate('created_at', today())
            ->count();

        // Status code distribution
        $statusStats = DB::table('api_gateway_logs')
            ->where('type', 'response')
            ->whereDate('created_at', today())
            ->selectRaw('data->>"$.status_code" as status_code, COUNT(*) as count')
            ->groupBy('status_code')
            ->pluck('count', 'status_code')
            ->toArray();

        $stats['status_code_distribution'] = $statusStats;

        // Most active endpoints
        $topEndpoints = DB::table('api_gateway_logs')
            ->where('type', 'request')
            ->whereDate('created_at', today())
            ->selectRaw('data->>"$.path" as path, COUNT(*) as count')
            ->groupBy('path')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'path')
            ->toArray();

        $stats['top_endpoints'] = $topEndpoints;

        // Recent errors
        $stats['recent_errors'] = $this->getErrorLogs(null, null, 5);

        return $stats;
    }

    /**
     * Sanitize headers for logging
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        $sanitized = [];

        foreach ($headers as $key => $value) {
            $headerName = strtolower($key);
            
            if (in_array($headerName, $sensitiveHeaders)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = is_array($value) ? $value[0] : $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize response headers
     */
    protected function sanitizeResponseHeaders(array $headers): array
    {
        $sensitiveHeaders = ['set-cookie', 'x-auth-token'];
        $sanitized = [];

        foreach ($headers as $key => $value) {
            $headerName = strtolower($key);
            
            if (in_array($headerName, $sensitiveHeaders)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = is_array($value) ? $value[0] : $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize request body for logging
     */
    protected function sanitizeRequestBody(string $body): string
    {
        // Truncate very large bodies
        if (strlen($body) > 10000) {
            $body = substr($body, 0, 10000) . '...[TRUNCATED]';
        }

        // Try to parse as JSON and sanitize sensitive fields
        $data = json_decode($body, true);
        if ($data && is_array($data)) {
            $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth'];
            $data = $this->sanitizeSensitiveData($data, $sensitiveFields);
            return json_encode($data);
        }

        return $body;
    }

    /**
     * Recursively sanitize sensitive data
     */
    protected function sanitizeSensitiveData(array $data, array $sensitiveFields): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeSensitiveData($value, $sensitiveFields);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Get request snapshot for error logging
     */
    protected function getRequestSnapshot(Request $request): array
    {
        return [
            'method' => $request->method(),
            'path' => $request->path(),
            'query' => $request->query(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * Clean up old logs
     */
    public function cleanup(int $daysToKeep = 7): void
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        DB::table('api_gateway_logs')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        // Clear cache
        Cache::forget("{$this->prefix}:recent");
    }

    /**
     * Export logs to external system
     */
    public function exportLogs(string $startDate, string $endDate, string $format = 'json'): array
    {
        $logs = DB::table('api_gateway_logs')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get()
            ->map(function ($log) {
                $log->data = json_decode($log->data, true);
                return $log;
            });

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($logs);
            case 'json':
            default:
                return $logs->toArray();
        }
    }

    /**
     * Export logs to CSV format
     */
    protected function exportToCsv($logs): array
    {
        $csvData = [];
        $headers = ['timestamp', 'type', 'request_id', 'method', 'path', 'status_code', 'client_ip'];
        
        foreach ($logs as $log) {
            $data = $log->data;
            $csvData[] = [
                $log->created_at,
                $log->type,
                $log->request_id,
                $data['method'] ?? '',
                $data['path'] ?? '',
                $data['status_code'] ?? '',
                $data['client_ip'] ?? '',
            ];
        }
        
        return [
            'headers' => $headers,
            'data' => $csvData,
        ];
    }
}