<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class AnalyticsPerformanceMonitoringService
{
    private const PERFORMANCE_CACHE_KEY = 'analytics:performance:metrics';
    private const ALERT_THRESHOLDS = [
        'execution_time_ms' => [
            'warning' => 2000, // 2 seconds
            'critical' => 5000, // 5 seconds
        ],
        'memory_usage_mb' => [
            'warning' => 256, // 256MB
            'critical' => 512, // 512MB
        ],
        'cache_hit_rate' => [
            'warning' => 85, // 85%
            'critical' => 70, // 70%
        ],
    ];

    /**
     * Record performance metrics for analytics operations
     */
    public function recordMetrics(
        string $operationType,
        float $executionTimeMs,
        int $memoryUsageMb = null,
        int $recordsProcessed = 0,
        int $branchId = null,
        string $cacheKeyPattern = null
    ): void {
        $metrics = [
            'operation_type' => $operationType,
            'branch_id' => $branchId,
            'execution_time_ms' => $executionTimeMs,
            'memory_usage_mb' => $memoryUsageMb,
            'records_processed' => $recordsProcessed,
            'cache_hit_rate' => $this->calculateCacheHitRate($cacheKeyPattern),
            'cache_key_pattern' => $cacheKeyPattern,
            'measured_at' => now(),
            'timestamp' => now()->timestamp,
        ];

        // Store in database
        $this->storeMetricsInDatabase($metrics);

        // Store in Redis for real-time monitoring
        $this->storeMetricsInRedis($metrics);

        // Check for performance alerts
        $this->checkPerformanceAlerts($metrics);

        // Update aggregate performance data
        $this->updateAggregatePerformance($operationType, $executionTimeMs, $memoryUsageMb);
    }

    /**
     * Get performance analytics for dashboard
     */
    public function getPerformanceAnalytics(int $hours = 24): array
    {
        $cacheKey = "performance:analytics:last_{$hours}h";
        
        return Cache::remember($cacheKey, 300, function() use ($hours) {
            $since = now()->subHours($hours);
            
            $metrics = DB::table('analytics_performance_metrics')
                ->where('measured_at', '>=', $since)
                ->get();
            
            if ($metrics->isEmpty()) {
                return [
                    'total_operations' => 0,
                    'avg_execution_time' => 0,
                    'avg_memory_usage' => 0,
                    'cache_hit_rate' => 0,
                    'alerts_count' => 0,
                    'performance_trend' => [],
                    'bottlenecks' => [],
                ];
            }
            
            return [
                'total_operations' => $metrics->count(),
                'avg_execution_time' => round($metrics->avg('execution_time_ms'), 2),
                'avg_memory_usage' => round($metrics->avg('memory_usage_mb'), 2),
                'cache_hit_rate' => round($metrics->avg('cache_hit_rate'), 2),
                'max_execution_time' => round($metrics->max('execution_time_ms'), 2),
                'min_execution_time' => round($metrics->min('execution_time_ms'), 2),
                'operations_by_type' => $metrics->groupBy('operation_type')->map->count(),
                'performance_trend' => $this->getPerformanceTrend($metrics, $hours),
                'bottlenecks' => $this->identifyBottlenecks($metrics),
                'memory_usage_trend' => $this->getMemoryUsageTrend($metrics),
                'cache_performance' => $this->getCachePerformance($metrics),
            ];
        });
    }

    /**
     * Get real-time performance monitoring data
     */
    public function getRealTimePerformance(): array
    {
        $cacheKey = 'performance:realtime:current';
        
        return Cache::remember($cacheKey, 60, function() {
            $recentMetrics = DB::table('analytics_performance_metrics')
                ->where('measured_at', '>=', now()->subMinutes(5))
                ->orderBy('measured_at', 'desc')
                ->limit(100)
                ->get();
            
            return [
                'timestamp' => now()->toISOString(),
                'active_operations' => $recentMetrics->count(),
                'recent_performance' => $recentMetrics->map(function($metric) {
                    return [
                        'operation' => $metric->operation_type,
                        'execution_time' => $metric->execution_time_ms,
                        'memory_usage' => $metric->memory_usage_mb,
                        'cache_hit_rate' => $metric->cache_hit_rate,
                        'time' => $metric->measured_at,
                    ];
                })->values(),
                'system_health' => $this->getSystemHealthScore($recentMetrics),
                'active_alerts' => $this->getActivePerformanceAlerts(),
            ];
        });
    }

    /**
     * Generate performance optimization recommendations
     */
    public function getOptimizationRecommendations(): array
    {
        $analytics = $this->getPerformanceAnalytics(24);
        $recommendations = [];

        // Execution time recommendations
        if ($analytics['avg_execution_time'] > self::ALERT_THRESHOLDS['execution_time_ms']['warning']) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'category' => 'execution_time',
                'title' => 'Slow Analytics Queries Detected',
                'description' => "Average execution time of {$analytics['avg_execution_time']}ms exceeds optimal threshold",
                'impact' => 'User experience degradation, dashboard loading delays',
                'recommendations' => [
                    'Review and optimize database queries',
                    'Implement query result caching',
                    'Consider database indexing improvements',
                    'Implement pagination for large datasets',
                ],
            ];
        }

        // Memory usage recommendations
        if ($analytics['avg_memory_usage'] > self::ALERT_THRESHOLDS['memory_usage_mb']['warning']) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'medium',
                'category' => 'memory_usage',
                'title' => 'High Memory Usage in Analytics',
                'description' => "Average memory usage of {$analytics['avg_memory_usage']}MB indicates potential memory leaks",
                'impact' => 'System instability, potential out-of-memory errors',
                'recommendations' => [
                    'Review data aggregation algorithms',
                    'Implement streaming for large datasets',
                    'Clear unused cache entries regularly',
                    'Optimize data structures and algorithms',
                ],
            ];
        }

        // Cache performance recommendations
        if ($analytics['cache_hit_rate'] < self::ALERT_THRESHOLDS['cache_hit_rate']['warning']) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'medium',
                'category' => 'caching',
                'title' => 'Low Cache Hit Rate',
                'description' => "Cache hit rate of {$analytics['cache_hit_rate']}% is below optimal threshold",
                'impact' => 'Increased database load, slower response times',
                'recommendations' => [
                    'Review cache key patterns and TTL values',
                    'Increase cache coverage for frequently accessed data',
                    'Implement cache warming strategies',
                    'Optimize cache invalidation logic',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Get capacity utilization metrics for performance monitoring
     */
    public function getCapacityUtilizationMetrics(int $branchId = null): array
    {
        $cacheKey = "capacity:utilization:" . ($branchId ?? 'all');
        
        return Cache::remember($cacheKey, 300, function() use ($branchId) {
            $query = DB::table('analytics_performance_metrics')
                ->where('measured_at', '>=', now()->subHours(1))
                ->where('operation_type', 'like', '%capacity%');
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            
            $metrics = $query->get();
            
            return [
                'capacity_operations' => $metrics->count(),
                'avg_processing_time' => round($metrics->avg('execution_time_ms'), 2),
                'max_processing_time' => round($metrics->max('execution_time_ms'), 2),
                'utilization_calculations_per_minute' => round($metrics->count() / 60, 2),
                'peak_processing_times' => $this->getPeakProcessingTimes($metrics),
                'efficiency_trend' => $this->getEfficiencyTrend($metrics),
            ];
        });
    }

    /**
     * Store metrics in database
     */
    private function storeMetricsInDatabase(array $metrics): void
    {
        DB::table('analytics_performance_metrics')->insert($metrics);
        
        // Clean old records (keep last 7 days)
        $cutoffDate = now()->subDays(7);
        DB::table('analytics_performance_metrics')
            ->where('measured_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Store metrics in Redis for real-time access
     */
    private function storeMetricsInRedis(array $metrics): void
    {
        // Store current performance snapshot
        Redis::setex('performance:current', 300, json_encode($metrics));
        
        // Add to performance timeline
        Redis::lpush('performance:timeline', json_encode($metrics));
        Redis::ltrim('performance:timeline', 0, 999); // Keep last 1000 entries
    }

    /**
     * Check for performance alerts
     */
    private function checkPerformanceAlerts(array $metrics): void
    {
        $alerts = [];
        
        // Check execution time thresholds
        if ($metrics['execution_time_ms'] > self::ALERT_THRESHOLDS['execution_time_ms']['critical']) {
            $alerts[] = [
                'type' => 'performance',
                'severity' => 'critical',
                'title' => 'Critical Performance Degradation',
                'description' => "Operation took {$metrics['execution_time_ms']}ms",
                'metric' => 'execution_time',
                'value' => $metrics['execution_time_ms'],
                'threshold' => self::ALERT_THRESHOLDS['execution_time_ms']['critical'],
            ];
        } elseif ($metrics['execution_time_ms'] > self::ALERT_THRESHOLDS['execution_time_ms']['warning']) {
            $alerts[] = [
                'type' => 'performance',
                'severity' => 'warning',
                'title' => 'Performance Warning',
                'description' => "Operation took {$metrics['execution_time_ms']}ms",
                'metric' => 'execution_time',
                'value' => $metrics['execution_time_ms'],
                'threshold' => self::ALERT_THRESHOLDS['execution_time_ms']['warning'],
            ];
        }
        
        // Check memory usage thresholds
        if ($metrics['memory_usage_mb'] && $metrics['memory_usage_mb'] > self::ALERT_THRESHOLDS['memory_usage_mb']['critical']) {
            $alerts[] = [
                'type' => 'memory',
                'severity' => 'critical',
                'title' => 'High Memory Usage',
                'description' => "Operation used {$metrics['memory_usage_mb']}MB",
                'metric' => 'memory_usage',
                'value' => $metrics['memory_usage_mb'],
                'threshold' => self::ALERT_THRESHOLDS['memory_usage_mb']['critical'],
            ];
        }
        
        // Store alerts if any were generated
        if (!empty($alerts)) {
            $this->storeAlerts($alerts, $metrics['branch_id'] ?? null);
        }
    }

    /**
     * Store performance alerts
     */
    private function storeAlerts(array $alerts, ?int $branchId = null): void
    {
        foreach ($alerts as $alert) {
            DB::table('analytics_alerts')->insert([
                'branch_id' => $branchId,
                'alert_type' => $alert['type'],
                'severity' => $alert['severity'],
                'title' => $alert['title'],
                'description' => $alert['description'],
                'metric_data' => $alert,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateCacheHitRate(?string $cacheKeyPattern): float
    {
        if (!$cacheKeyPattern) return 0;
        
        $hits = Redis::get("cache:analytics:hits:{$cacheKeyPattern}") ?? 0;
        $misses = Redis::get("cache:analytics:misses:{$cacheKeyPattern}") ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    /**
     * Update aggregate performance data
     */
    private function updateAggregatePerformance(string $operationType, float $executionTime, ?int $memoryUsage): void
    {
        $key = "aggregate:performance:{$operationType}";
        $current = Redis::get($key);
        
        $data = $current ? json_decode($current, true) : [
            'count' => 0,
            'total_execution_time' => 0,
            'total_memory_usage' => 0,
            'last_updated' => now(),
        ];
        
        $data['count']++;
        $data['total_execution_time'] += $executionTime;
        if ($memoryUsage) {
            $data['total_memory_usage'] += $memoryUsage;
        }
        $data['last_updated'] = now()->toISOString();
        
        Redis::setex($key, 3600, json_encode($data)); // 1 hour TTL
    }

    /**
     * Get performance trend over time
     */
    private function getPerformanceTrend($metrics, int $hours): array
    {
        $intervalMinutes = max(1, intval($hours * 60 / 24)); // 24 data points
        $trend = [];
        
        $start = now()->subHours($hours);
        $end = now();
        
        $current = clone $start;
        while ($current <= $end) {
            $intervalEnd = clone $current;
            $intervalEnd->addMinutes($intervalMinutes);
            
            $intervalMetrics = $metrics->filter(function($metric) use ($current, $intervalEnd) {
                return $metric->measured_at >= $current && $metric->measured_at < $intervalEnd;
            });
            
            $trend[] = [
                'time' => $current->format('H:i'),
                'avg_execution_time' => $intervalMetrics->count() > 0 
                    ? round($intervalMetrics->avg('execution_time_ms'), 2) 
                    : 0,
                'operation_count' => $intervalMetrics->count(),
            ];
            
            $current = $intervalEnd;
        }
        
        return $trend;
    }

    /**
     * Identify performance bottlenecks
     */
    private function identifyBottlenecks($metrics): array
    {
        $operationsByType = $metrics->groupBy('operation_type');
        $bottlenecks = [];
        
        foreach ($operationsByType as $type => $typeMetrics) {
            $avgTime = $typeMetrics->avg('execution_time_ms');
            $operationCount = $typeMetrics->count();
            
            if ($avgTime > self::ALERT_THRESHOLDS['execution_time_ms']['warning']) {
                $bottlenecks[] = [
                    'operation_type' => $type,
                    'avg_execution_time' => round($avgTime, 2),
                    'operation_count' => $operationCount,
                    'impact_score' => round($avgTime * $operationCount, 2),
                    'recommendation' => $this->getOptimizationRecommendation($type),
                ];
            }
        }
        
        // Sort by impact score descending
        usort($bottlenecks, function($a, $b) {
            return $b['impact_score'] <=> $a['impact_score'];
        });
        
        return array_slice($bottlenecks, 0, 5); // Return top 5
    }

    /**
     * Get optimization recommendation for operation type
     */
    private function getOptimizationRecommendation(string $operationType): string
    {
        return match($operationType) {
            'analytics_query' => 'Review query structure and add database indexes',
            'capacity_calculation' => 'Optimize capacity calculation algorithms and cache results',
            'dashboard_load' => 'Implement data pagination and virtual scrolling',
            'batch_processing' => 'Use chunked processing and background jobs',
            default => 'Review and optimize this operation type',
        };
    }

    /**
     * Get system health score
     */
    private function getSystemHealthScore($metrics): array
    {
        $avgExecutionTime = $metrics->avg('execution_time_ms') ?? 0;
        $avgMemoryUsage = $metrics->avg('memory_usage_mb') ?? 0;
        $cacheHitRate = $metrics->avg('cache_hit_rate') ?? 0;
        
        // Calculate health score (0-100)
        $timeScore = max(0, 100 - ($avgExecutionTime / 100)); // 100ms = 0 penalty
        $memoryScore = max(0, 100 - ($avgMemoryUsage / 10)); // 1GB = 0 penalty
        $cacheScore = max(0, $cacheHitRate); // Cache hit rate directly
        
        $overallScore = round(($timeScore + $memoryScore + $cacheScore) / 3, 1);
        
        return [
            'overall_score' => $overallScore,
            'execution_time_score' => round($timeScore, 1),
            'memory_score' => round($memoryScore, 1),
            'cache_score' => round($cacheScore, 1),
            'status' => $overallScore >= 80 ? 'healthy' : ($overallScore >= 60 ? 'warning' : 'critical'),
        ];
    }

    /**
     * Get active performance alerts
     */
    private function getActivePerformanceAlerts(): array
    {
        return DB::table('analytics_alerts')
            ->where('created_at', '>=', now()->subHours(1))
            ->where('resolved_at', null)
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->alert_type,
                    'severity' => $alert->severity,
                    'title' => $alert->title,
                    'description' => $alert->description,
                    'time' => $alert->created_at,
                ];
            })
            ->toArray();
    }

    // Additional helper methods for advanced analytics
    private function getMemoryUsageTrend($metrics): array { return []; }
    private function getCachePerformance($metrics): array { return []; }
    private function getPeakProcessingTimes($metrics): array { return []; }
    private function getEfficiencyTrend($metrics): array { return []; }
}
