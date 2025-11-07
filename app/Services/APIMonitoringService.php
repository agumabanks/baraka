<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * API Performance and Monitoring Service
 * 
 * Provides comprehensive monitoring, analytics, and alerting for the pricing API
 * including performance metrics, error tracking, rate limiting analytics, and
 * business intelligence reporting.
 */
class APIMonitoringService
{
    // Performance thresholds
    private const PERFORMANCE_THRESHOLDS = [
        'response_time' => [
            'p95' => 1000,  // 95th percentile should be under 1 second
            'p99' => 2000,  // 99th percentile should be under 2 seconds
            'max' => 5000,  // Maximum acceptable response time
        ],
        'error_rate' => [
            'warning' => 1.0,    // Warn if error rate exceeds 1%
            'critical' => 5.0,   // Critical if error rate exceeds 5%
        ],
        'rate_limit_hits' => [
            'warning' => 10.0,   // Warn if rate limit hits exceed 10% of requests
            'critical' => 25.0,  // Critical if rate limit hits exceed 25%
        ],
    ];

    // Monitoring cache keys
    private const CACHE_PREFIX = 'api_monitoring:';
    private const CACHE_TTL = [
        'realtime' => 300,      // 5 minutes for real-time metrics
        'hourly' => 3600,       // 1 hour for hourly aggregates
        'daily' => 86400,       // 24 hours for daily aggregates
    ];

    public function __construct(
        private WebhookManagementService $webhookService
    ) {}

    /**
     * Record API request metrics
     */
    public function recordRequest(array $requestData): void
    {
        $timestamp = now();
        $endpoint = $requestData['endpoint'] ?? 'unknown';
        $customerId = $requestData['customer_id'] ?? null;
        $responseTime = $requestData['response_time'] ?? 0;
        $statusCode = $requestData['status_code'] ?? 500;
        $isError = $statusCode >= 400;
        $isRateLimitHit = $statusCode === 429;

        try {
            // Store real-time metrics
            $this->storeRealTimeMetrics($endpoint, $responseTime, $statusCode, $customerId, $timestamp);

            // Store error details if applicable
            if ($isError) {
                $this->storeErrorDetails($requestData, $timestamp);
            }

            // Store rate limit information
            if ($isRateLimitHit) {
                $this->storeRateLimitEvent($requestData, $timestamp);
            }

            // Check for performance issues
            $this->checkPerformanceThresholds($endpoint, $responseTime, $statusCode);

            // Update customer usage patterns
            if ($customerId) {
                $this->updateCustomerUsagePatterns($customerId, $endpoint, $timestamp);
            }

        } catch (\Exception $e) {
            Log::error('Failed to record API request metrics', [
                'error' => $e->getMessage(),
                'request_data' => $requestData,
            ]);
        }
    }

    /**
     * Get real-time API performance metrics
     */
    public function getRealtimeMetrics(array $filters = []): array
    {
        $cacheKey = self::CACHE_PREFIX . 'realtime:' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL['realtime'], function() use ($filters) {
            $timeRange = $filters['time_range'] ?? 300; // 5 minutes default
            $startTime = now()->subSeconds($timeRange);

            // Basic request metrics
            $query = DB::table('api_request_logs')
                ->where('created_at', '>=', $startTime);

            $this->applyFilters($query, $filters);

            $totalRequests = $query->count();
            $errorRequests = $query->where('status_code', '>=', 400)->count();
            $rateLimitRequests = $query->where('status_code', 429)->count();

            // Response time statistics
            $responseTimes = $query->where('response_time', '>', 0)->pluck('response_time');
            $avgResponseTime = $responseTimes->avg() ?? 0;
            $p95ResponseTime = $responseTimes->sort()->median() ?? 0;
            $maxResponseTime = $responseTimes->max() ?? 0;

            // Endpoint distribution
            $endpointStats = $query
                ->selectRaw('endpoint, COUNT(*) as request_count, AVG(response_time) as avg_response_time')
                ->groupBy('endpoint')
                ->get()
                ->keyBy('endpoint');

            // Customer usage patterns
            $customerUsage = $query
                ->whereNotNull('customer_id')
                ->selectRaw('customer_id, COUNT(*) as request_count, AVG(response_time) as avg_response_time')
                ->groupBy('customer_id')
                ->get()
                ->keyBy('customer_id');

            return [
                'timestamp' => now()->toISOString(),
                'time_range_seconds' => $timeRange,
                'summary' => [
                    'total_requests' => $totalRequests,
                    'error_rate' => $totalRequests > 0 ? ($errorRequests / $totalRequests) * 100 : 0,
                    'rate_limit_rate' => $totalRequests > 0 ? ($rateLimitRequests / $totalRequests) * 100 : 0,
                    'average_response_time' => round($avgResponseTime, 2),
                    'p95_response_time' => round($p95ResponseTime, 2),
                    'max_response_time' => round($maxResponseTime, 2),
                ],
                'endpoints' => $endpointStats,
                'customer_usage' => $customerUsage,
                'health_status' => $this->calculateHealthStatus($totalRequests, $errorRequests, $avgResponseTime),
            ];
        });
    }

    /**
     * Get hourly performance analytics
     */
    public function getHourlyAnalytics(int $hours = 24): array
    {
        $startTime = now()->subHours($hours);
        
        $analytics = DB::table('api_request_logs')
            ->where('created_at', '>=', $startTime)
            ->selectRaw('
                DATE_TRUNC("hour", created_at) as hour,
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                COUNT(CASE WHEN status_code = 429 THEN 1 END) as rate_limit_requests
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'period' => [
                'start' => $startTime->toISOString(),
                'end' => now()->toISOString(),
                'hours' => $hours,
            ],
            'hourly_data' => $analytics->map(function ($row) {
                return [
                    'hour' => $row->hour,
                    'total_requests' => $row->total_requests,
                    'error_rate' => $row->total_requests > 0 ? 
                        round(($row->error_requests / $row->total_requests) * 100, 2) : 0,
                    'rate_limit_rate' => $row->total_requests > 0 ? 
                        round(($row->rate_limit_requests / $row->total_requests) * 100, 2) : 0,
                    'avg_response_time' => round($row->avg_response_time, 2),
                    'max_response_time' => round($row->max_response_time, 2),
                ];
            }),
            'trends' => $this->calculateTrends($analytics),
        ];
    }

    /**
     * Get customer analytics and insights
     */
    public function getCustomerAnalytics(int $customerId = null): array
    {
        $cacheKey = self::CACHE_PREFIX . 'customer:' . ($customerId ?? 'all');
        
        return Cache::remember($cacheKey, self::CACHE_TTL['hourly'], function() use ($customerId) {
            $query = DB::table('api_request_logs')
                ->where('created_at', '>=', now()->subDays(30));

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $customerStats = $query
                ->selectRaw('
                    customer_id,
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
                    AVG(response_time) as avg_response_time,
                    AVG(CASE WHEN status_code < 400 THEN response_time END) as success_response_time
                ')
                ->groupBy('customer_id')
                ->get();

            // API usage patterns
            $usagePatterns = $query
                ->selectRaw('
                    customer_id,
                    endpoint,
                    DATE_TRUNC("day", created_at) as date,
                    COUNT(*) as daily_requests
                ')
                ->groupBy('customer_id', 'endpoint', 'date')
                ->get();

            // Performance by endpoint for customers
            $endpointPerformance = $query
                ->selectRaw('
                    customer_id,
                    endpoint,
                    COUNT(*) as request_count,
                    AVG(response_time) as avg_response_time,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as errors
                ')
                ->whereNotNull('customer_id')
                ->groupBy('customer_id', 'endpoint')
                ->get();

            return [
                'customer_stats' => $customerStats,
                'usage_patterns' => $usagePatterns,
                'endpoint_performance' => $endpointPerformance,
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get system health overview
     */
    public function getSystemHealth(): array
    {
        $metrics = $this->getRealtimeMetrics(['time_range' => 300]); // Last 5 minutes
        
        // Database health
        $dbHealth = $this->checkDatabaseHealth();
        
        // Cache health
        $cacheHealth = $this->checkCacheHealth();
        
        // External service health
        $externalHealth = $this->checkExternalServicesHealth();
        
        // Overall health calculation
        $overallHealth = $this->calculateOverallHealth($metrics, $dbHealth, $cacheHealth, $externalHealth);

        return [
            'timestamp' => now()->toISOString(),
            'overall_status' => $overallHealth['status'],
            'score' => $overallHealth['score'],
            'api_metrics' => $metrics['summary'],
            'system_components' => [
                'database' => $dbHealth,
                'cache' => $cacheHealth,
                'external_services' => $externalHealth,
            ],
            'alerts' => $this->generateAlerts($metrics, $dbHealth, $cacheHealth, $externalHealth),
            'recommendations' => $this->generateRecommendations($metrics),
        ];
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(string $period = '24h', array $filters = []): array
    {
        $timeframe = $this->parsePeriod($period);
        
        $query = DB::table('api_request_logs')
            ->where('created_at', '>=', $timeframe['start'])
            ->where('created_at', '<=', $timeframe['end']);
        
        $this->applyFilters($query, $filters);

        $report = [
            'period' => $period,
            'generated_at' => now()->toISOString(),
            'summary' => $this->generateSummaryStats($query),
            'performance_analysis' => $this->generatePerformanceAnalysis($query),
            'error_analysis' => $this->generateErrorAnalysis($query),
            'customer_insights' => $this->generateCustomerInsights($query),
            'recommendations' => $this->generatePerformanceRecommendations($query),
        ];

        return $report;
    }

    /**
     * Store real-time metrics
     */
    private function storeRealTimeMetrics(string $endpoint, float $responseTime, int $statusCode, ?int $customerId, Carbon $timestamp): void
    {
        DB::table('api_request_logs')->insert([
            'endpoint' => $endpoint,
            'response_time' => $responseTime,
            'status_code' => $statusCode,
            'customer_id' => $customerId,
            'created_at' => $timestamp,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->header('User-Agent'),
        ]);

        // Update real-time aggregation
        $this->updateRealTimeAggregation($endpoint, $responseTime, $statusCode, $timestamp);
    }

    private function updateRealTimeAggregation(string $endpoint, float $responseTime, int $statusCode, Carbon $timestamp): void
    {
        $minuteKey = $timestamp->format('Y-m-d-H-i');
        $cacheKey = self::CACHE_PREFIX . "realtime_agg:{$endpoint}:{$minuteKey}";

        $existing = Cache::get($cacheKey, [
            'total_requests' => 0,
            'error_requests' => 0,
            'total_response_time' => 0,
            'max_response_time' => 0,
        ]);

        $existing['total_requests']++;
        $existing['total_response_time'] += $responseTime;
        $existing['max_response_time'] = max($existing['max_response_time'], $responseTime);

        if ($statusCode >= 400) {
            $existing['error_requests']++;
        }

        Cache::put($cacheKey, $existing, self::CACHE_TTL['realtime']);
    }

    private function storeErrorDetails(array $requestData, Carbon $timestamp): void
    {
        DB::table('api_error_logs')->insert([
            'endpoint' => $requestData['endpoint'] ?? 'unknown',
            'error_code' => $requestData['status_code'] ?? 500,
            'error_message' => $requestData['error_message'] ?? 'Unknown error',
            'customer_id' => $requestData['customer_id'] ?? null,
            'request_data' => json_encode($requestData),
            'created_at' => $timestamp,
            'ip_address' => request()?->ip(),
        ]);
    }

    private function storeRateLimitEvent(array $requestData, Carbon $timestamp): void
    {
        DB::table('api_rate_limit_logs')->insert([
            'endpoint' => $requestData['endpoint'] ?? 'unknown',
            'customer_id' => $requestData['customer_id'] ?? null,
            'limit_type' => $requestData['limit_type'] ?? 'unknown',
            'created_at' => $timestamp,
            'ip_address' => request()?->ip(),
        ]);
    }

    private function checkPerformanceThresholds(string $endpoint, float $responseTime, int $statusCode): void
    {
        $alerts = [];

        // Response time alerts
        if ($responseTime > self::PERFORMANCE_THRESHOLDS['response_time']['max']) {
            $alerts[] = [
                'type' => 'performance',
                'severity' => 'critical',
                'message' => "Response time exceeded maximum threshold for {$endpoint}",
                'value' => $responseTime,
                'threshold' => self::PERFORMANCE_THRESHOLDS['response_time']['max'],
            ];
        }

        // Error rate alerts will be checked in aggregation
        if ($statusCode >= 500) {
            $alerts[] = [
                'type' => 'error',
                'severity' => 'critical',
                'message' => "Server error on endpoint {$endpoint}",
                'status_code' => $statusCode,
            ];
        }

        // Send alerts if any
        foreach ($alerts as $alert) {
            $this->sendAlert($alert);
        }
    }

    private function updateCustomerUsagePatterns(int $customerId, string $endpoint, Carbon $timestamp): void
    {
        $key = self::CACHE_PREFIX . "customer_pattern:{$customerId}";
        $patterns = Cache::get($key, []);

        if (!isset($patterns[$endpoint])) {
            $patterns[$endpoint] = 0;
        }
        $patterns[$endpoint]++;

        Cache::put($key, $patterns, self::CACHE_TTL['hourly']);

        // Update daily usage counts
        $dailyKey = self::CACHE_PREFIX . "daily_usage:{$customerId}:{$timestamp->format('Y-m-d')}";
        $dailyUsage = Cache::get($dailyKey, 0) + 1;
        Cache::put($dailyKey, $dailyUsage, 86400);
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['endpoint'])) {
            $query->where('endpoint', 'like', '%' . $filters['endpoint'] . '%');
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['status_code'])) {
            $query->where('status_code', $filters['status_code']);
        }

        if (isset($filters['time_range'])) {
            $query->where('created_at', '>=', now()->subSeconds($filters['time_range']));
        }
    }

    private function calculateHealthStatus(int $totalRequests, int $errorRequests, float $avgResponseTime): string
    {
        $errorRate = $totalRequests > 0 ? ($errorRequests / $totalRequests) * 100 : 0;
        
        if ($avgResponseTime > self::PERFORMANCE_THRESHOLDS['response_time']['p95'] || 
            $errorRate > self::PERFORMANCE_THRESHOLDS['error_rate']['critical']) {
            return 'critical';
        } elseif ($avgResponseTime > self::PERFORMANCE_THRESHOLDS['response_time']['p99'] || 
                   $errorRate > self::PERFORMANCE_THRESHOLDS['error_rate']['warning']) {
            return 'warning';
        }
        
        return 'healthy';
    }

    private function checkDatabaseHealth(): array
    {
        $startTime = microtime(true);
        
        try {
            DB::connection()->getPdo();
            $connectionTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => $connectionTime < 100 ? 'healthy' : 'degraded',
                'response_time_ms' => round($connectionTime, 2),
                'last_check' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    private function checkCacheHealth(): array
    {
        $startTime = microtime(true);
        $testKey = 'api_health_check_' . uniqid();
        
        try {
            Cache::put($testKey, 'test', 60);
            $testValue = Cache::get($testKey);
            Cache::forget($testKey);
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => $responseTime < 50 && $testValue === 'test' ? 'healthy' : 'degraded',
                'response_time_ms' => round($responseTime, 2),
                'last_check' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    private function checkExternalServicesHealth(): array
    {
        $services = [
            'dynamic_pricing' => $this->checkServiceHealth([$this, 'checkDynamicPricingHealth']),
            'carrier_rates' => $this->checkServiceHealth([$this, 'checkCarrierRatesHealth']),
        ];

        $healthyServices = count(array_filter($services, fn($s) => $s['status'] === 'healthy'));
        $totalServices = count($services);

        return [
            'status' => $healthyServices === $totalServices ? 'healthy' : 
                       ($healthyServices > 0 ? 'degraded' : 'unhealthy'),
            'services' => $services,
            'healthy_count' => $healthyServices,
            'total_count' => $totalServices,
            'last_check' => now()->toISOString(),
        ];
    }

    private function checkServiceHealth(callable $healthCheck): array
    {
        try {
            $result = $healthCheck();
            return array_merge(['status' => 'healthy'], $result);
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    private function checkDynamicPricingHealth(): array
    {
        // This would check the DynamicPricingService availability
        $startTime = microtime(true);
        
        try {
            // Simulate a simple calculation
            $result = app(DynamicPricingService::class)
                ->calculateInstantQuote('US', 'CA', ['weight_kg' => 1], 'standard', null, 'USD');
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'response_time_ms' => round($responseTime, 2),
                'test_result' => 'success',
            ];
        } catch (\Exception $e) {
            throw new \Exception('Dynamic pricing service unavailable: ' . $e->getMessage());
        }
    }

    private function checkCarrierRatesHealth(): array
    {
        // This would check external carrier rate services
        $startTime = microtime(true);
        
        // Simulate external service check
        $responseTime = (microtime(true) - $startTime) * 1000;
        
        return [
            'response_time_ms' => round($responseTime, 2),
            'test_result' => 'success',
        ];
    }

    private function calculateOverallHealth(array $metrics, array $dbHealth, array $cacheHealth, array $externalHealth): array
    {
        $scores = [];
        
        // API performance score
        $apiScore = 100;
        if ($metrics['summary']['error_rate'] > 1) $apiScore -= 20;
        if ($metrics['summary']['average_response_time'] > 1000) $apiScore -= 15;
        if ($metrics['summary']['rate_limit_rate'] > 10) $apiScore -= 10;
        $scores['api'] = max(0, $apiScore);
        
        // System component scores
        $scores['database'] = $dbHealth['status'] === 'healthy' ? 100 : 
                              ($dbHealth['status'] === 'degraded' ? 70 : 0);
        $scores['cache'] = $cacheHealth['status'] === 'healthy' ? 100 : 
                          ($cacheHealth['status'] === 'degraded' ? 70 : 0);
        $scores['external'] = $externalHealth['status'] === 'healthy' ? 100 : 
                             ($externalHealth['status'] === 'degraded' ? 70 : 0);
        
        $overallScore = array_sum($scores) / count($scores);
        
        return [
            'status' => $overallScore >= 90 ? 'healthy' : 
                       ($overallScore >= 70 ? 'degraded' : 'unhealthy'),
            'score' => round($overallScore, 1),
            'component_scores' => $scores,
        ];
    }

    private function generateAlerts(array $metrics, array $dbHealth, array $cacheHealth, array $externalHealth): array
    {
        $alerts = [];

        // API performance alerts
        if ($metrics['summary']['error_rate'] > self::PERFORMANCE_THRESHOLDS['error_rate']['critical']) {
            $alerts[] = [
                'type' => 'critical',
                'category' => 'api',
                'message' => 'Error rate exceeds critical threshold',
                'value' => $metrics['summary']['error_rate'],
                'threshold' => self::PERFORMANCE_THRESHOLDS['error_rate']['critical'],
            ];
        }

        // System health alerts
        if ($dbHealth['status'] === 'unhealthy') {
            $alerts[] = [
                'type' => 'critical',
                'category' => 'database',
                'message' => 'Database connection failed',
            ];
        }

        if ($cacheHealth['status'] === 'unhealthy') {
            $alerts[] = [
                'type' => 'critical',
                'category' => 'cache',
                'message' => 'Cache system unavailable',
            ];
        }

        return $alerts;
    }

    private function generateRecommendations(array $metrics): array
    {
        $recommendations = [];

        if ($metrics['summary']['average_response_time'] > 1000) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Consider optimizing high-latency endpoints',
                'action' => 'Review and optimize endpoints with high response times',
            ];
        }

        if ($metrics['summary']['error_rate'] > 1) {
            $recommendations[] = [
                'type' => 'reliability',
                'priority' => 'high',
                'message' => 'Address high error rate',
                'action' => 'Investigate and fix error patterns in application logs',
            ];
        }

        if ($metrics['summary']['rate_limit_rate'] > 10) {
            $recommendations[] = [
                'type' => 'capacity',
                'priority' => 'medium',
                'message' => 'Consider increasing rate limits for high-usage customers',
                'action' => 'Review rate limit configurations and consider customer tier adjustments',
            ];
        }

        return $recommendations;
    }

    private function parsePeriod(string $period): array
    {
        return match($period) {
            '1h' => ['start' => now()->subHour(), 'end' => now()],
            '24h' => ['start' => now()->subDay(), 'end' => now()],
            '7d' => ['start' => now()->subDays(7), 'end' => now()],
            '30d' => ['start' => now()->subDays(30), 'end' => now()],
            default => ['start' => now()->subDay(), 'end' => now()],
        };
    }

    private function sendAlert(array $alert): void
    {
        // Log the alert
        Log::warning('API Alert Generated', $alert);

        // Send to webhook if configured
        try {
            $this->webhookService->sendAlertWebhook($alert);
        } catch (\Exception $e) {
            Log::error('Failed to send alert webhook', [
                'alert' => $alert,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function calculateTrends($data): array
    {
        // Calculate trends for key metrics
        return [
            'request_trend' => 'stable', // Would implement actual trend calculation
            'error_rate_trend' => 'stable',
            'response_time_trend' => 'stable',
        ];
    }

    private function generateSummaryStats($query): array
    {
        $totalRequests = $query->count();
        $errorRequests = $query->where('status_code', '>=', 400)->count();
        $avgResponseTime = $query->avg('response_time') ?? 0;

        return [
            'total_requests' => $totalRequests,
            'error_rate' => $totalRequests > 0 ? round(($errorRequests / $totalRequests) * 100, 2) : 0,
            'average_response_time' => round($avgResponseTime, 2),
        ];
    }

    private function generatePerformanceAnalysis($query): array
    {
        return [
            'top_slow_endpoints' => [],
            'response_time_distribution' => [],
            'bottlenecks' => [],
        ];
    }

    private function generateErrorAnalysis($query): array
    {
        return [
            'error_by_type' => [],
            'error_trends' => [],
            'most_frequent_errors' => [],
        ];
    }

    private function generateCustomerInsights($query): array
    {
        return [
            'top_customers' => [],
            'customer_usage_patterns' => [],
            'segment_analysis' => [],
        ];
    }

    private function generatePerformanceRecommendations($query): array
    {
        return [
            'optimization_opportunities' => [],
            'capacity_planning' => [],
            'error_resolution' => [],
        ];
    }
}