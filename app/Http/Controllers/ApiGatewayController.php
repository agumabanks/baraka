<?php

namespace App\Http\Controllers;

use App\Services\ApiGateway\ApiGatewayService;
use App\Services\ApiGateway\ApiGatewayContext;
use App\Services\ApiGateway\Monitoring\MetricsCollector;
use App\Services\ApiGateway\Monitoring\LogCollector;
use App\Services\ApiGateway\CircuitBreakerService;
use App\Models\ApiGateway\ApiRoute;
use App\Models\ApiGateway\ApiVersion;
use App\Models\ApiGateway\RateLimitRule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ApiGatewayController extends Controller
{
    protected $gatewayService;
    protected $metricsCollector;
    protected $logCollector;
    protected $circuitBreaker;

    public function __construct()
    {
        $this->gatewayService = new ApiGatewayService();
        $this->metricsCollector = new MetricsCollector();
        $this->logCollector = new LogCollector();
        $this->circuitBreaker = new CircuitBreakerService();
    }

    /**
     * Handle API requests through the gateway
     */
    public function handleRequest(Request $request): Response
    {
        return $this->gatewayService->processRequest($request);
    }

    /**
     * Get API Gateway health status
     */
    public function health(Request $request): \Illuminate\Http\JsonResponse
    {
        $stats = $this->gatewayService->getStatistics();
        $circuitBreakerStatus = $this->circuitBreaker->getStatus();
        
        $healthData = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'gateway' => [
                'version' => config('api_gateway.version', '1.0.0'),
                'status' => 'operational',
                'uptime' => $this->getUptime(),
            ],
            'services' => $this->getServiceHealthStatus(),
            'circuit_breakers' => $circuitBreakerStatus,
            'statistics' => $stats,
            'dependencies' => $this->checkDependencies(),
        ];

        // Determine overall health
        $isHealthy = $this->isGatewayHealthy($healthData);
        
        if (!$isHealthy) {
            return response()->json($healthData, 503);
        }

        return response()->json($healthData);
    }

    /**
     * Get API Gateway statistics
     */
    public function statistics(Request $request): \Illuminate\Http\JsonResponse
    {
        $period = $request->get('period', '24h');
        
        $stats = [
            'gateway_statistics' => $this->gatewayService->getStatistics(),
            'metrics_summary' => $this->metricsCollector->getSummary(),
            'log_statistics' => $this->logCollector->getStatistics(),
            'circuit_breaker_stats' => $this->circuitBreaker->getStatistics(),
            'top_endpoints' => $this->metricsCollector->getTopEndpoints(10),
            'slowest_endpoints' => $this->metricsCollector->getSlowestEndpoints(10),
            'error_analysis' => $this->getErrorAnalysis($period),
            'performance_metrics' => $this->getPerformanceMetrics($period),
        ];

        return response()->json($stats);
    }

    /**
     * Get circuit breaker status
     */
    public function circuitBreakers(Request $request): \Illuminate\Http\JsonResponse
    {
        $status = $this->circuitBreaker->getStatus();
        $services = $this->circuitBreaker->getServicesNeedingAttention();
        
        return response()->json([
            'circuit_breakers' => $status,
            'services_needing_attention' => $services,
            'total_services' => count($status),
        ]);
    }

    /**
     * Reset circuit breaker
     */
    public function resetCircuitBreaker(Request $request, string $service): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make(['service' => $service], [
            'service' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->circuitBreaker->reset($service);

        return response()->json([
            'message' => "Circuit breaker reset for service: {$service}",
            'service' => $service,
        ]);
    }

    /**
     * Get recent logs
     */
    public function logs(Request $request): \Illuminate\Http\JsonResponse
    {
        $type = $request->get('type', 'all');
        $limit = min($request->get('limit', 50), 100);
        
        if ($type === 'all') {
            $logs = $this->logCollector->getRecentLogs($limit);
        } else {
            $logs = $this->logCollector->getLogsForRange(
                $type, 
                now()->subDay()->toISOString(), 
                now()->toISOString(), 
                $limit
            );
        }

        return response()->json([
            'logs' => $logs,
            'total' => count($logs),
            'type' => $type,
        ]);
    }

    /**
     * Get error logs
     */
    public function errorLogs(Request $request): \Illuminate\Http\JsonResponse
    {
        $limit = min($request->get('limit', 100), 500);
        $errors = $this->logCollector->getErrorLogs(null, null, $limit);

        return response()->json([
            'errors' => $errors,
            'total' => count($errors),
        ]);
    }

    /**
     * Get API routes
     */
    public function routes(Request $request): \Illuminate\Http\JsonResponse
    {
        $version = $request->get('version');
        $service = $request->get('service');
        $active = $request->get('active', true);

        $query = ApiRoute::with('version', 'rateLimitRules');
        
        if ($version) {
            $query->forVersion($version);
        }
        
        if ($service) {
            $query->forService($service);
        }
        
        if ($active !== null) {
            $query->when($active, function ($q) {
                $q->active();
            });
        }

        $routes = $query->orderBy('path')->get()->map(function ($route) {
            return [
                'id' => $route->id,
                'path' => $route->path,
                'methods' => $route->methods,
                'target_service' => $route->target_service,
                'version' => $route->version->version ?? null,
                'description' => $route->description,
                'is_active' => $route->is_active,
                'load_balanced' => $route->load_balanced,
                'target_services' => $route->getTargetServices(),
                'statistics' => $route->getStatistics(),
            ];
        });

        return response()->json(['routes' => $routes]);
    }

    /**
     * Create new API route
     */
    public function createRoute(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string|unique:api_routes,path',
            'methods' => 'required|array|min:1',
            'methods.*' => 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'target_service' => 'required|string',
            'version_id' => 'nullable|exists:api_versions,id',
            'description' => 'nullable|string',
            'timeout' => 'nullable|integer|min:1',
            'auth_type' => 'nullable|string',
            'rate_limit_config' => 'nullable|array',
            'transform_config' => 'nullable|array',
            'validation_config' => 'nullable|array',
            'load_balanced' => 'nullable|boolean',
            'target_services' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $route = $this->gatewayService->registerRoute($request->all());

        return response()->json([
            'message' => 'API route created successfully',
            'route' => $route,
        ], 201);
    }

    /**
     * Update API route
     */
    public function updateRoute(Request $request, string $path): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'methods' => 'sometimes|array|min:1',
            'methods.*' => 'in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'target_service' => 'sometimes|string',
            'description' => 'nullable|string',
            'timeout' => 'nullable|integer|min:1',
            'auth_type' => 'nullable|string',
            'rate_limit_config' => 'nullable|array',
            'transform_config' => 'nullable|array',
            'validation_config' => 'nullable|array',
            'load_balanced' => 'nullable|boolean',
            'target_services' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $success = $this->gatewayService->updateRoute($path, $request->all());

        if (!$success) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        return response()->json(['message' => 'API route updated successfully']);
    }

    /**
     * Delete API route
     */
    public function deleteRoute(Request $request, string $path): \Illuminate\Http\JsonResponse
    {
        $route = ApiRoute::where('path', $path)->first();
        
        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        $route->delete();
        
        // Clear cache
        $this->gatewayService->clearCaches();

        return response()->json(['message' => 'API route deleted successfully']);
    }

    /**
     * Get rate limit rules
     */
    public function rateLimitRules(Request $request): \Illuminate\Http\JsonResponse
    {
        $routeId = $request->get('route_id');
        
        $query = RateLimitRule::with('route');
        
        if ($routeId) {
            $query->where('api_route_id', $routeId);
        }

        $rules = $query->active()->ordered()->get();

        return response()->json(['rate_limit_rules' => $rules]);
    }

    /**
     * Get rate limit breaches
     */
    public function rateLimitBreaches(Request $request): \Illuminate\Http\JsonResponse
    {
        $limit = min($request->get('limit', 100), 1000);
        $startDate = $request->get('start_date', now()->subDay()->toISOString());
        $endDate = $request->get('end_date', now()->toISOString());

        $breaches = DB::table('api_rate_limit_breaches')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json(['breaches' => $breaches]);
    }

    /**
     * Clear API Gateway caches
     */
    public function clearCaches(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->gatewayService->clearCaches();
        Cache::flush();

        return response()->json(['message' => 'API Gateway caches cleared successfully']);
    }

    /**
     * Get system uptime
     */
    protected function getUptime(): int
    {
        // In a real implementation, you'd track when the service started
        return now()->diffInSeconds(now());
    }

    /**
     * Get service health status
     */
    protected function getServiceHealthStatus(): array
    {
        $services = config('api_gateway.services');
        $status = [];

        foreach ($services as $key => $service) {
            $status[$key] = $this->circuitBreaker->checkServiceHealth($key);
        }

        return $status;
    }

    /**
     * Check system dependencies
     */
    protected function checkDependencies(): array
    {
        return [
            'database' => $this->checkDatabaseConnection(),
            'cache' => $this->checkCacheConnection(),
            'queue' => $this->checkQueueConnection(),
        ];
    }

    /**
     * Check database connection
     */
    protected function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'response_time' => 0];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    /**
     * Check cache connection
     */
    protected function checkCacheConnection(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', 'ok', 10);
            $exists = Cache::has('health_check');
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => $exists ? 'healthy' : 'unhealthy',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    /**
     * Check queue connection
     */
    protected function checkQueueConnection(): array
    {
        try {
            // Simple check for queue connectivity
            $queueSize = DB::table('jobs')->count();
            return ['status' => 'healthy', 'queue_size' => $queueSize];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if gateway is healthy
     */
    protected function isGatewayHealthy(array $healthData): bool
    {
        // Check if any circuit breaker is in a critical state
        foreach ($healthData['circuit_breakers'] as $service => $status) {
            if ($status['state'] === 'open' && $status['failure_count'] > 10) {
                return false;
            }
        }

        // Check dependencies
        foreach ($healthData['dependencies'] as $dep => $status) {
            if ($status['status'] === 'unhealthy') {
                return false;
            }
        }

        return true;
    }

    /**
     * Get error analysis for a period
     */
    protected function getErrorAnalysis(string $period): array
    {
        $startDate = $this->getStartDateForPeriod($period);
        $endDate = now();

        return [
            'total_errors' => DB::table('api_gateway_logs')
                ->where('type', 'error')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'error_rate' => $this->metricsCollector->getSummary()['error_rate'] ?? 0,
            'common_errors' => $this->getCommonErrors($startDate, $endDate),
        ];
    }

    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(string $period): array
    {
        $startDate = $this->getStartDateForPeriod($period);
        
        return [
            'avg_response_time' => $this->metricsCollector->getSummary()['avg_response_time'] ?? 0,
            'requests_per_minute' => $this->getRequestsPerMinute($startDate),
            'cache_hit_rate' => $this->getCacheHitRate(),
        ];
    }

    /**
     * Get start date for period
     */
    protected function getStartDateForPeriod(string $period): string
    {
        return match ($period) {
            '1h' => now()->subHour(),
            '24h', '1d' => now()->subDay(),
            '7d', '1w' => now()->subWeek(),
            '30d', '1m' => now()->subMonth(),
            default => now()->subDay(),
        };
    }

    /**
     * Get common errors
     */
    protected function getCommonErrors(string $startDate, string $endDate): array
    {
        return DB::table('api_gateway_logs')
            ->where('type', 'error')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('data->"$.error_type" as error_type, COUNT(*) as count')
            ->groupBy('error_type')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Get requests per minute
     */
    protected function getRequestsPerMinute(string $startDate): int
    {
        $totalRequests = DB::table('api_gateway_logs')
            ->where('type', 'request')
            ->where('created_at', '>=', $startDate)
            ->count();

        $minutes = now()->diffInMinutes($startDate);
        
        return $minutes > 0 ? intval($totalRequests / $minutes) : 0;
    }

    /**
     * Get cache hit rate
     */
    protected function getCacheHitRate(): float
    {
        // Simplified cache hit rate calculation
        return 85.0; // Placeholder
    }
}