<?php

namespace App\Jobs;

use App\Services\OptimizedBranchAnalyticsService;
use App\Services\OptimizedBranchCapacityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RealTimeAnalyticsProcessor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 2;

    /**
     * Execute real-time analytics processing every minute
     */
    public function handle(
        OptimizedBranchAnalyticsService $analyticsService,
        OptimizedBranchCapacityService $capacityService
    ): void {
        $startTime = microtime(true);

        try {
            // Process all active branches
            $activeBranches = $this->getActiveBranches();
            $processedCount = 0;

            foreach ($activeBranches as $branch) {
                try {
                    // Update real-time metrics
                    $this->updateRealTimeMetrics($analyticsService, $capacityService, $branch);
                    
                    // Check for threshold violations
                    $this->checkThresholdViolations($branch);
                    
                    // Update Redis cache for dashboard
                    $this->updateRedisCache($branch, $analyticsService, $capacityService);
                    
                    $processedCount++;
                    
                } catch (\Exception $e) {
                    Log::error('Real-time processing error for branch', [
                        'branch_id' => $branch->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Store processing metadata
            $this->storeProcessingMetadata($processedCount, $activeBranches->count(), $startTime);

            Log::info('Real-time analytics processing completed', [
                'processed_branches' => $processedCount,
                'total_branches' => $activeBranches->count(),
                'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
            ]);

        } catch (\Exception $e) {
            Log::error('Real-time analytics processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update real-time metrics for a branch
     */
    private function updateRealTimeMetrics($analyticsService, $capacityService, $branch): void
    {
        // Get real-time analytics
        $realTimeAnalytics = $analyticsService->getRealTimeAnalytics($branch);
        
        // Get capacity monitoring
        $capacityMonitoring = $capacityService->getRealTimeMonitoring($branch);
        
        // Combine metrics
        $combinedMetrics = array_merge($realTimeAnalytics, $capacityMonitoring);
        
        // Store in Redis for immediate access
        Redis::setex(
            "realtime:branch:metrics:{$branch->id}",
            120, // 2 minutes TTL
            json_encode($combinedMetrics)
        );
        
        // Publish to WebSocket for live dashboard updates
        $this->publishToWebSocket($branch->id, $combinedMetrics);
    }

    /**
     * Check for threshold violations and generate alerts
     */
    private function checkThresholdViolations($branch): void
    {
        $analyticsService = app(OptimizedBranchAnalyticsService::class);
        $capacityService = app(OptimizedBranchCapacityService::class);
        
        $utilization = $analyticsService->getRealTimeAnalytics($branch)['utilization_rate'] ?? 0;
        $thresholds = $capacityService->getDynamicThresholds($branch);
        
        $violations = [];
        
        // Check utilization thresholds
        if ($utilization > $thresholds['critical_threshold']) {
            $violations[] = [
                'type' => 'critical_capacity',
                'message' => 'Critical capacity threshold exceeded',
                'value' => $utilization,
                'threshold' => $thresholds['critical_threshold'],
                'severity' => 'critical',
            ];
        } elseif ($utilization > $thresholds['warning_threshold']) {
            $violations[] = [
                'type' => 'high_capacity',
                'message' => 'High capacity threshold exceeded',
                'value' => $utilization,
                'threshold' => $thresholds['warning_threshold'],
                'severity' => 'warning',
            ];
        }
        
        // Store violations
        if (!empty($violations)) {
            Redis::setex(
                "alerts:branch:{$branch->id}",
                3600, // 1 hour TTL
                json_encode($violations)
            );
        }
    }

    /**
     * Update Redis cache for dashboard consumption
     */
    private function updateRedisCache($branch, $analyticsService, $capacityService): void
    {
        // Update main dashboard cache
        $dashboardData = [
            'branch_id' => $branch->id,
            'timestamp' => now()->toISOString(),
            'utilization' => $analyticsService->getRealTimeAnalytics($branch)['utilization_rate'] ?? 0,
            'active_shipments' => $analyticsService->getRealTimeAnalytics($branch)['active_shipments'] ?? 0,
            'performance_score' => $analyticsService->getRealTimeAnalytics($branch)['performance_score'] ?? 0,
            'capacity_status' => $capacityService->getRealTimeMonitoring($branch)['current_status'] ?? 'unknown',
        ];
        
        Redis::setex(
            "dashboard:branch:{$branch->id}",
            180, // 3 minutes TTL
            json_encode($dashboardData)
        );
        
        // Update aggregate metrics for overview dashboard
        $this->updateAggregateMetrics($dashboardData);
    }

    /**
     * Update aggregate metrics for overview dashboard
     */
    private function updateAggregateMetrics(array $branchData): void
    {
        $aggregateKey = 'dashboard:aggregate:metrics';
        $currentAggregate = Redis::get($aggregateKey);
        
        if ($currentAggregate) {
            $aggregate = json_decode($currentAggregate, true);
        } else {
            $aggregate = [
                'total_branches' => 0,
                'active_branches' => 0,
                'total_utilization' => 0,
                'total_shipments' => 0,
                'avg_performance' => 0,
                'last_updated' => now()->toISOString(),
            ];
        }
        
        // Update aggregate values
        $aggregate['total_branches'] = ($aggregate['total_branches'] ?? 0) + 1;
        $aggregate['active_branches'] = ($aggregate['active_branches'] ?? 0) + 1;
        $aggregate['total_utilization'] = ($aggregate['total_utilization'] ?? 0) + $branchData['utilization'];
        $aggregate['total_shipments'] = ($aggregate['total_shipments'] ?? 0) + $branchData['active_shipments'];
        $aggregate['avg_performance'] = ($aggregate['avg_performance'] ?? 0) + $branchData['performance_score'];
        $aggregate['last_updated'] = now()->toISOString();
        
        Redis::setex($aggregateKey, 180, json_encode($aggregate));
    }

    /**
     * Publish metrics to WebSocket for real-time updates
     */
    private function publishToWebSocket(int $branchId, array $metrics): void
    {
        $message = [
            'type' => 'real_time_update',
            'branch_id' => $branchId,
            'data' => $metrics,
            'timestamp' => now()->toISOString(),
        ];
        
        // Store in Redis pub/sub channel
        Redis::publish('analytics:realtime', json_encode($message));
    }

    /**
     * Store processing metadata for monitoring
     */
    private function storeProcessingMetadata(int $processed, int $total, float $startTime): void
    {
        $metadata = [
            'processed_branches' => $processed,
            'total_branches' => $total,
            'success_rate' => $total > 0 ? ($processed / $total) * 100 : 0,
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'timestamp' => now()->toISOString(),
        ];
        
        // Store in Redis for dashboard access
        Redis::setex(
            'analytics:processing:metadata',
            600, // 10 minutes TTL
            json_encode($metadata)
        );
    }

    /**
     * Get all active branches
     */
    private function getActiveBranches()
    {
        return \App\Models\Backend\Branch::where('status', 'active')
            ->where('is_operational', true)
            ->get();
    }
}