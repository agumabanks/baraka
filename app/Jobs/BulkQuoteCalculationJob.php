<?php

namespace App\Jobs;

use App\Services\DynamicPricingService;
use App\Services\CompetitorPriceSyncService;
use App\Services\FuelIndexUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Job for processing bulk quote calculations
 */
class BulkQuoteCalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $shipmentRequests,
        private ?int $customerId = null,
        private string $currency = 'USD',
        private ?string $jobId = null
    ) {
        $this->jobId = $jobId ?? uniqid('bulk_quote_', true);
        $this->onQueue('bulk-quotes');
    }

    /**
     * Execute the job.
     */
    public function handle(DynamicPricingService $pricingService): void
    {
        Log::info('Starting bulk quote calculation job', [
            'job_id' => $this->jobId,
            'request_count' => count($this->shipmentRequests)
        ]);

        try {
            $results = $pricingService->generateBulkQuotes($this->shipmentRequests);
            
            // Store results in cache with job ID
            \Illuminate\Support\Facades\Cache::put(
                "bulk_quote_results_{$this->jobId}",
                $results,
                now()->addHours(24)
            );

            Log::info('Bulk quote calculation job completed', [
                'job_id' => $this->jobId,
                'successful_quotes' => $results['successful_quotes'],
                'failed_quotes' => $results['failed_quotes'],
                'total_processing_time_ms' => $results['total_processing_time_ms']
            ]);

            // Dispatch webhook notification
            dispatch(new WebhookNotificationJob(
                'bulk_quote.completed',
                $results,
                ['job_id' => $this->jobId, 'customer_id' => $this->customerId]
            ));

        } catch (\Exception $e) {
            Log::error('Bulk quote calculation job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk quote calculation job permanently failed', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage()
        ]);

        // Dispatch failure notification
        dispatch(new WebhookNotificationJob(
            'bulk_quote.failed',
            [
                'job_id' => $this->jobId,
                'error' => $exception->getMessage(),
                'customer_id' => $this->customerId
            ],
            ['job_id' => $this->jobId, 'customer_id' => $this->customerId]
        ));
    }
}