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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrecomputeAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 3600; // 1 hour
    public $backoff = [30, 60, 120]; // 30s, 1m, 2m

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $branchIds,
        private int $days = 30,
        private ?string $jobId = null
    ) {
        $this->jobId = $this->jobId ?? uniqid('analytics_', true);
        $this->onQueue('analytics');
    }

    /**
     * Execute the job.
     */
    public function handle(
        OptimizedBranchAnalyticsService $analyticsService,
        OptimizedBranchCapacityService $capacityService
    ): void {
        $startTime = microtime(true);
        $totalBranches = count($this->branchIds);
        $processedBranches = 0;
        $errors = [];

        Log::info('Starting analytics precomputation', [
            'job_id' => $this->jobId,
            'branch_count' => $totalBranches,
            'days' => $this->days
        ]);

        try {
            // Process branches in chunks to avoid memory issues
            $chunks = array_chunk($this->branchIds, 10);
            
            foreach ($chunks as $chunk) {
                foreach ($chunk as $branchId) {
                    try {
                        // Pre-compute analytics data
                        $analyticsService->getBranchPerformanceAnalytics(
                            $this->getBranchById($branchId), 
                            $this->days
                        );

                        // Pre-compute capacity data
                        $capacityService->getCapacityAnalysis(
                            $this->getBranchById($branchId), 
                            $this->days
                        );

                        // Create materialized snapshots
                        $this->createMaterializedSnapshot($branchId, $this->days);
                        
                        $processedBranches++;
                        $progress = ($processedBranches / $totalBranches) * 100;
                        
                        Log::info("Analytics precomputation progress", [
                            'job_id' => $this->jobId,
                            'branch_id' => $branchId,
                            'progress' => round($progress, 2) . '%',
                            'processed' => $processedBranches,
                            'total' => $totalBranches
                        ]);

                    } catch (\Exception $e) {
                        $errors[] = [
                            'branch_id' => $branchId,
                            'error' => $e->getMessage()
                        ];
                        
                        Log::error('Analytics precomputation error for branch', [
                            'job_id' => $this->jobId,
                            'branch_id' => $branchId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Clear memory between chunks
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            // Store completion metadata
            $this->storeJobCompletion($totalBranches, $processedBranches, $errors, $startTime);

            Log::info('Analytics precomputation completed', [
                'job_id' => $this->jobId,
                'total_branches' => $totalBranches,
                'processed_branches' => $processedBranches,
                'error_count' => count($errors),
                'execution_time' => (microtime(true) - $startTime) . ' seconds'
            ]);

        } catch (\Exception $e) {
            Log::error('Analytics precomputation job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Analytics precomputation job failed permanently', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Create materialized snapshot for quick dashboard loading
     */
    private function createMaterializedSnapshot(int $branchId, int $days): void
    {
        $branch = $this->getBranchById($branchId);
        $snapshotData = [
            'branch_id' => $branchId,
            'snapshot_date' => now()->toDateString(),
            'data_period_days' => $days,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Store in materialized view table
        DB::table('analytics_materialized_snapshots')->updateOrInsert(
            ['branch_id' => $branchId, 'snapshot_date' => now()->toDateString()],
            $snapshotData
        );
    }

    /**
     * Store job completion metadata
     */
    private function storeJobCompletion(int $total, int $processed, array $errors, float $startTime): void
    {
        DB::table('analytics_job_history')->insert([
            'job_id' => $this->jobId,
            'job_type' => 'precompute_analytics',
            'branch_count' => $total,
            'processed_count' => $processed,
            'error_count' => count($errors),
            'errors' => json_encode($errors),
            'execution_time_seconds' => (microtime(true) - $startTime),
            'status' => empty($errors) ? 'completed' : 'completed_with_errors',
            'created_at' => now(),
        ]);
    }

    /**
     * Get branch by ID
     */
    private function getBranchById(int $branchId)
    {
        return \App\Models\Backend\Branch::findOrFail($branchId);
    }
}