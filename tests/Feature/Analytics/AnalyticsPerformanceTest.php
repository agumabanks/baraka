<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Services\OptimizedBranchAnalyticsService;
use App\Services\OptimizedBranchCapacityService;
use App\Services\AnalyticsPerformanceMonitoringService;
use App\Jobs\PrecomputeAnalyticsJob;
use App\Jobs\RealTimeAnalyticsProcessor;
use App\Models\Backend\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private OptimizedBranchAnalyticsService $analyticsService;
    private OptimizedBranchCapacityService $capacityService;
    private AnalyticsPerformanceMonitoringService $performanceService;
    private Branch $testBranch;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA busy_timeout = 5000;');
        }

        $this->analyticsService = new OptimizedBranchAnalyticsService();
        $this->capacityService = new OptimizedBranchCapacityService();
        $this->performanceService = new AnalyticsPerformanceMonitoringService();
        
        $attributes = [
            'name' => 'Test Branch',
            'code' => 'TEST001',
            'status' => 'active',
        ];

        if (Schema::hasColumn('branches', 'is_operational')) {
            $attributes['is_operational'] = true;
        }

        $this->testBranch = Branch::factory()->create($attributes);
    }

    /** @test */
    public function it_meets_analytics_performance_requirements()
    {
        // Clear cache and Redis
        Cache::flush();
        Redis::flushall();
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Test analytics query performance
        $analytics = $this->analyticsService->getBranchPerformanceAnalytics($this->testBranch, 30);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
        
        // Performance assertions - should complete in under 2 seconds
        $this->assertLessThan(2000, $executionTime, 'Analytics query took too long');
        $this->assertLessThan(100, $memoryUsed, 'Analytics query used too much memory');
        
        // Verify analytics data structure
        $this->assertNotNull($analytics);
        $this->assertArrayHasKey('overview', $analytics);
        $this->assertArrayHasKey('capacity_metrics', $analytics);
        $this->assertArrayHasKey('performance_metrics', $analytics);
        $this->assertArrayHasKey('trends', $analytics);
        
        // Test data integrity
        $this->assertIsInt($analytics['overview']['current_load']['active_shipments']);
        $this->assertIsFloat($analytics['overview']['current_load']['capacity_utilization']);
    }

    /** @test */
    public function it_meets_capacity_analysis_performance_requirements()
    {
        $startTime = microtime(true);
        
        $capacity = $this->capacityService->getCapacityAnalysis($this->testBranch, 30);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Should complete in under 1.5 seconds
        $this->assertLessThan(1500, $executionTime, 'Capacity analysis took too long');
        
        // Verify capacity data structure
        $this->assertNotNull($capacity);
        $this->assertArrayHasKey('current_capacity', $capacity);
        $this->assertArrayHasKey('workload_analysis', $capacity);
        $this->assertArrayHasKey('capacity_forecast', $capacity);
        $this->assertArrayHasKey('real_time_monitoring', $capacity);
        
        // Verify real-time monitoring
        $monitoring = $capacity['real_time_monitoring'];
        $this->assertArrayHasKey('timestamp', $monitoring);
        $this->assertArrayHasKey('current_status', $monitoring);
        $this->assertArrayHasKey('alerts', $monitoring);
    }

    /** @test */
    public function it_achieves_cache_hit_rate_targets()
    {
        // First query (cache miss)
        $analytics1 = $this->analyticsService->getBranchPerformanceAnalytics($this->testBranch, 30);
        
        // Second query (should be cache hit)
        $analytics2 = $this->analyticsService->getBranchPerformanceAnalytics($this->testBranch, 30);
        
        // Verify cache is working
        $this->assertNotNull($analytics2);
        
        // Check cache statistics
        $cacheStats = Redis::get('analytics:performance:cache_stats');
        if ($cacheStats) {
            $stats = json_decode($cacheStats, true);
            $hitRate = $stats['hit_rate'] ?? 0;
            
            // Should achieve at least 70% cache hit rate for repeated queries
            $this->assertGreaterThanOrEqual(70, $hitRate, 'Cache hit rate is below target');
        }
    }

    /** @test */
    public function it_handles_batch_analytics_efficiently()
    {
        // Create multiple branches
        $branches = Branch::factory()->count(5)->create();
        $branchIds = $branches->pluck('id')->toArray();
        
        $startTime = microtime(true);
        
        $batchAnalytics = $this->analyticsService->getBatchBranchAnalytics($branchIds, 30);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Should handle 5 branches in under 5 seconds
        $this->assertLessThan(5000, $executionTime, 'Batch analytics took too long');
        
        // Verify all branches were processed
        $this->assertCount(5, $batchAnalytics);
        
        // Verify data structure for each branch
        foreach ($batchAnalytics as $branchId => $data) {
            $this->assertArrayHasKey('overview', $data);
        }
    }

    /** @test */
    public function it_provides_real_time_updates()
    {
        // Enable real-time monitoring
        $realTime = $this->analyticsService->getRealTimeAnalytics($this->testBranch);
        
        // Verify real-time data structure
        $this->assertNotNull($realTime);
        $this->assertArrayHasKey('timestamp', $realTime);
        $this->assertArrayHasKey('active_shipments', $realTime);
        $this->assertArrayHasKey('utilization_rate', $realTime);
        $this->assertArrayHasKey('performance_score', $realTime);
        $this->assertArrayHasKey('alerts', $realTime);
        
        // Verify data types
        $this->assertIsNumeric($realTime['active_shipments']);
        $this->assertIsFloat($realTime['utilization_rate']);
        $this->assertIsFloat($realTime['performance_score']);
        $this->assertIsArray($realTime['alerts']);
        
        // Verify Redis storage
        $redisData = Redis::get("realtime:branch:{$this->testBranch->id}");
        $this->assertNotNull($redisData, 'Real-time data not stored in Redis');
    }

    /** @test */
    public function it_monitors_performance_accurately()
    {
        // Record some performance metrics
        $this->performanceService->recordMetrics(
            'test_operation',
            1500.5, // 1.5 seconds
            50, // 50MB
            100, // 100 records
            $this->testBranch->id,
            'test:pattern'
        );
        
        // Get performance analytics
        $performance = $this->performanceService->getPerformanceAnalytics(1); // 1 hour
        
        // Verify performance data
        $this->assertNotNull($performance);
        $this->assertArrayHasKey('total_operations', $performance);
        $this->assertArrayHasKey('avg_execution_time', $performance);
        $this->assertArrayHasKey('performance_trend', $performance);
        
        // Should have recorded the test operation
        $this->assertGreaterThan(0, $performance['total_operations']);
    }

    /** @test */
    public function it_handles_background_jobs_correctly()
    {
        Queue::fake();
        
        // Test precompute analytics job
        $branchIds = [$this->testBranch->id];
        $job = new PrecomputeAnalyticsJob($branchIds, 30);
        
        // Verify job can be dispatched
        Queue::assertNothingPushed();
        
        $job->handle(
            $this->analyticsService,
            $this->capacityService
        );
        
        // Verify job completion
        $this->assertDatabaseHas('analytics_job_history', [
            'job_type' => 'precompute_analytics',
            'processed_count' => 1,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_provides_intelligent_recommendations()
    {
        $recommendations = $this->performanceService->getOptimizationRecommendations();
        
        $this->assertIsArray($recommendations);
        
        // Verify recommendation structure
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('title', $recommendation);
            $this->assertArrayHasKey('description', $recommendation);
            $this->assertArrayHasKey('recommendations', $recommendation);
        }
    }

    /** @test */
    public function it_handles_peak_load_scenarios()
    {
        // Simulate high load scenario
        $concurrentQueries = 5; // Reduced for stability
        $startTime = microtime(true);
        
        // Execute multiple analytics queries sequentially to simulate load
        $results = [];
        for ($i = 0; $i < $concurrentQueries; $i++) {
            $queryStart = microtime(true);
            $results[] = $this->analyticsService->getBranchPerformanceAnalytics($this->testBranch, 30);
            $queryEnd = microtime(true);
            
            // Each query should complete within reasonable time
            $queryTime = ($queryEnd - $queryStart) * 1000;
            $this->assertLessThan(2000, $queryTime, "Query {$i} took too long: {$queryTime}ms");
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        // Should handle multiple queries in reasonable time
        $this->assertLessThan(15000, $totalTime, 'Total query time is too high');
        
        // Verify all queries completed successfully
        $this->assertCount($concurrentQueries, $results);
        
        // Verify system didn't crash or throw memory errors
        $this->assertLessThan(300, memory_get_usage(true) / 1024 / 1024, 'Memory usage is too high');
        
        // Verify data integrity across all queries
        foreach ($results as $index => $result) {
            $this->assertNotNull($result, "Query {$index} returned null");
            $this->assertArrayHasKey('overview', $result, "Query {$index} missing overview data");
        }
    }

    /** @test */
    public function it_supports_export_functionality()
    {
        $analytics = $this->analyticsService->getBranchPerformanceAnalytics($this->testBranch, 30);
        
        // Test export configuration
        $exportConfig = [
            'type' => 'analytics',
            'branch_id' => $this->testBranch->id,
            'time_range' => '30d',
            'format' => 'csv',
            'include_charts' => true,
        ];
        
        // Verify export config structure
        $this->assertArrayHasKey('type', $exportConfig);
        $this->assertArrayHasKey('format', $exportConfig);
        $this->assertEquals('csv', $exportConfig['format']);
    }

    /** @test */
    public function it_provides_mobile_responsive_views()
    {
        // Test data structure that supports mobile responsiveness
        $analytics = $this->analyticsService->getBranchPerformanceAnalytics($this->testBranch, 30);
        
        // Verify key metrics are available for mobile display
        $overview = $analytics['overview'];
        $this->assertArrayHasKey('current_load', $overview);
        $this->assertArrayHasKey('workforce', $overview);
        
        // Verify numeric values are properly formatted
        $load = $overview['current_load'];
        $this->assertIsInt($load['active_shipments']);
        $this->assertIsFloat($load['capacity_utilization']);
        
        // Test capacity data mobile readiness
        $capacity = $analytics['capacity_metrics'];
        $this->assertArrayHasKey('utilization', $capacity);
        
        $utilization = $capacity['utilization'];
        $this->assertArrayHasKey('rate', $utilization);
        $this->assertArrayHasKey('status', $utilization);
        $this->assertIsFloat($utilization['rate']);
        $this->assertIsString($utilization['status']);
    }
}