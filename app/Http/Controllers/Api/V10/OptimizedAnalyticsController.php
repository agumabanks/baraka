<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsPerformanceMonitoringService;
use App\Services\OptimizedBranchAnalyticsService;
use App\Services\OptimizedBranchCapacityService;
use App\Models\Backend\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class OptimizedAnalyticsController extends Controller
{
    public function __construct(
        private OptimizedBranchAnalyticsService $analyticsService,
        private OptimizedBranchCapacityService $capacityService,
        private AnalyticsPerformanceMonitoringService $performanceService
    ) {}

    /**
     * List available branches for analytics dashboards
     */
    public function listAvailableBranches(): JsonResponse
    {
        $branches = Branch::active()
            ->select(['id', 'name', 'code', 'type'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    /**
     * Get enhanced branch performance analytics
     */
    public function getBranchPerformanceAnalytics(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $branchId = $request->query('branch_id');
            $days = $request->query('days', 30);
            
            // Record performance metrics
            $memoryBefore = memory_get_usage(true);
            
            if ($branchId) {
                $branch = \App\Models\Backend\Branch::findOrFail($branchId);
                $analytics = $this->analyticsService->getBranchPerformanceAnalytics($branch, $days);
            } else {
                // Batch processing for all branches
                $branchIds = \App\Models\Backend\Branch::active()
                    ->pluck('id')
                    ->toArray();
                $analytics = $this->analyticsService->getBatchBranchAnalytics($branchIds, $days);
            }
            
            $memoryAfter = memory_get_usage(true);
            $executionTime = (microtime(true) - $startTime) * 1000;
            $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
            
            // Record performance metrics
            $this->performanceService->recordMetrics(
                'analytics_query',
                $executionTime,
                $memoryUsed,
                is_array($analytics) ? count($analytics) : 1,
                $branchId ? (int) $branchId : null,
                'analytics:branch:' . ($branchId ?? 'all')
            );
            
            return response()->json([
                'success' => true,
                'data' => $analytics,
                'performance' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'memory_usage_mb' => round($memoryUsed, 2),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Enhanced analytics query failed', [
                'error' => $e->getMessage(),
                'branch_id' => $branchId ?? null,
                'days' => $days ?? 30,
                'execution_time' => (microtime(true) - $startTime) * 1000
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time analytics for a branch
     */
    public function getRealTimeAnalytics(string $branchId): JsonResponse
    {
        try {
            $branch = \App\Models\Backend\Branch::findOrFail($branchId);
            $realTimeData = $this->analyticsService->getRealTimeAnalytics($branch);
            
            // Store in Redis for real-time dashboard access
            Redis::setex(
                "realtime:branch:{$branchId}",
                300, // 5 minutes TTL
                json_encode($realTimeData)
            );
            
            return response()->json([
                'success' => true,
                'data' => $realTimeData,
                'cached' => false,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Real-time analytics failed', [
                'error' => $e->getMessage(),
                'branch_id' => $branchId
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get enhanced capacity analysis
     */
    public function getCapacityAnalysis(string $branchId, Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $branch = \App\Models\Backend\Branch::findOrFail($branchId);
            $days = $request->query('days', 30);
            $includeOptimization = $request->query('include_optimization', true);
            
            // Get capacity analysis
            $capacityData = $this->capacityService->getCapacityAnalysis($branch, $days);
            
            // Add intelligent optimization if requested
            if ($includeOptimization) {
                $capacityData['intelligent_optimization'] = $this->capacityService->getIntelligentResourceAllocation($branch);
                $capacityData['predictive_planning'] = $this->capacityService->getPredictiveCapacityPlanning($branch, 90);
                $capacityData['dynamic_thresholds'] = $this->capacityService->getDynamicThresholds($branch);
            }
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Record performance metrics
            $this->performanceService->recordMetrics(
                'capacity_calculation',
                $executionTime,
                null,
                1,
                $branchId,
                'capacity:branch:' . $branchId
            );
            
            return response()->json([
                'success' => true,
                'data' => $capacityData,
                'performance' => [
                    'execution_time_ms' => round($executionTime, 2),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Capacity analysis failed', [
                'error' => $e->getMessage(),
                'branch_id' => $branchId,
                'days' => $days ?? 30,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get performance analytics
     */
    public function getPerformanceAnalytics(Request $request): JsonResponse
    {
        try {
            $hours = $request->query('hours', 24);
            $analytics = $this->performanceService->getPerformanceAnalytics($hours);
            
            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Performance analytics failed', [
                'error' => $e->getMessage(),
                'hours' => $hours ?? 24,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time performance monitoring
     */
    public function getRealTimePerformance(): JsonResponse
    {
        try {
            $performanceData = $this->performanceService->getRealTimePerformance();
            
            return response()->json([
                'success' => true,
                'data' => $performanceData,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Real-time performance failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get optimization recommendations
     */
    public function getOptimizationRecommendations(): JsonResponse
    {
        try {
            $recommendations = $this->performanceService->getOptimizationRecommendations();
            
            return response()->json([
                'success' => true,
                'data' => $recommendations,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Optimization recommendations failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Precompute analytics data
     */
    public function precomputeAnalytics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'branch_ids' => 'required|array',
                'branch_ids.*' => 'exists:branches,id',
                'days' => 'integer|min:1|max:365',
                'priority' => 'string|in:low,normal,high,urgent',
            ]);
            
            $branchIds = $validated['branch_ids'];
            $days = $validated['days'] ?? 30;
            $priority = $validated['priority'] ?? 'normal';
            
            // Dispatch precomputation job
            $job = new \App\Jobs\PrecomputeAnalyticsJob($branchIds, $days);
            if ($priority === 'high' || $priority === 'urgent') {
                $job->onQueue('analytics-high-priority');
            } elseif ($priority === 'low') {
                $job->onQueue('analytics-low-priority');
            } else {
                $job->onQueue('analytics');
            }
            
            dispatch($job);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'job_id' => $job->jobId,
                    'branch_count' => count($branchIds),
                    'days' => $days,
                    'priority' => $priority,
                    'queued_at' => now()->toISOString(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Analytics precomputation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear analytics cache
     */
    public function clearAnalyticsCache(Request $request): JsonResponse
    {
        try {
            $branchId = $request->query('branch_id');
            
            if ($branchId) {
                $branch = \App\Models\Backend\Branch::findOrFail($branchId);
                $this->analyticsService->clearCache($branch);
                $this->capacityService->clearCache($branch);
            } else {
                // Clear all cache
                $patterns = ['analytics:*', 'capacity:*', 'realtime:*'];
                foreach ($patterns as $pattern) {
                    $keys = Redis::keys($pattern);
                    if (!empty($keys)) {
                        Redis::del($keys);
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'cleared' => true,
                    'branch_id' => $branchId,
                    'cleared_at' => now()->toISOString(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cache clear failed', [
                'error' => $e->getMessage(),
                'branch_id' => $branchId ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'checks' => [],
            ];
            
            // Check Redis connection
            try {
                Redis::ping();
                $health['checks'][] = [
                    'name' => 'Redis',
                    'status' => 'healthy',
                    'response_time' => 10,
                    'message' => 'Connection successful',
                ];
            } catch (\Exception $e) {
                $health['status'] = 'degraded';
                $health['checks'][] = [
                    'name' => 'Redis',
                    'status' => 'unhealthy',
                    'response_time' => null,
                    'message' => $e->getMessage(),
                ];
            }
            
            // Check database connection
            try {
                DB::connection()->getPdo();
                $health['checks'][] = [
                    'name' => 'Database',
                    'status' => 'healthy',
                    'response_time' => 15,
                    'message' => 'Connection successful',
                ];
            } catch (\Exception $e) {
                $health['status'] = 'unhealthy';
                $health['checks'][] = [
                    'name' => 'Database',
                    'status' => 'unhealthy',
                    'response_time' => null,
                    'message' => $e->getMessage(),
                ];
            }
            
            // Check queue system
            try {
                $queueSize = Redis::llen('queues:analytics') ?? 0;
                $health['checks'][] = [
                    'name' => 'Analytics Queue',
                    'status' => $queueSize < 100 ? 'healthy' : 'degraded',
                    'response_time' => 5,
                    'message' => "Queue size: {$queueSize}",
                ];
            } catch (\Exception $e) {
                $health['checks'][] = [
                    'name' => 'Analytics Queue',
                    'status' => 'unhealthy',
                    'response_time' => null,
                    'message' => $e->getMessage(),
                ];
            }
            
            if ($health['status'] === 'unhealthy') {
                return response()->json([
                    'success' => true,
                    'data' => $health,
                ], 503);
            }
            
            return response()->json([
                'success' => true,
                'data' => $health,
            ]);
            
        } catch (\Exception $e) {
            Log::error('System health check failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batch analytics for multiple branches
     */
    public function getBatchBranchAnalytics(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $validated = $request->validate([
                'branch_ids' => 'required|array',
                'branch_ids.*' => 'exists:branches,id',
                'days' => 'integer|min:1|max:365',
            ]);
            
            $branchIds = $validated['branch_ids'];
            $days = $validated['days'] ?? 30;
            
            // Process batch analytics
            $analytics = $this->analyticsService->getBatchBranchAnalytics($branchIds, $days);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Record performance metrics
            $this->performanceService->recordMetrics(
                'batch_analytics',
                $executionTime,
                null,
                count($branchIds),
                null,
                'analytics:batch:' . $days . 'd'
            );
            
            return response()->json([
                'success' => true,
                'data' => $analytics,
                'performance' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'branches_processed' => count($branchIds),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Batch analytics failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get materialized snapshot
     */
    public function getMaterializedSnapshot(string $branchId, string $date): JsonResponse
    {
        try {
            $snapshot = DB::table('analytics_materialized_snapshots')
                ->where('branch_id', $branchId)
                ->where('snapshot_date', $date)
                ->first();
            
            if (!$snapshot) {
                return response()->json([
                    'success' => false,
                    'error' => 'Snapshot not found',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $snapshot,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Materialized snapshot fetch failed', [
                'error' => $e->getMessage(),
                'branch_id' => $branchId,
                'date' => $date,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}