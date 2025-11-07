<?php

namespace App\Jobs;

use App\Services\WebhookManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for sending webhook notifications
 */
class WebhookNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $eventType,
        private array $data,
        private array $metadata = []
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookManagementService $webhookService): void
    {
        Log::info('Processing webhook notification', [
            'event_type' => $this->eventType,
            'metadata' => $this->metadata
        ]);

        try {
            $webhookService->sendEvent($this->eventType, $this->data, $this->metadata);
            
            Log::info('Webhook notification sent successfully', [
                'event_type' => $this->eventType,
                'metadata' => $this->metadata
            ]);
            
        } catch (\Exception $e) {
            Log::error('Webhook notification failed', [
                'event_type' => $this->eventType,
                'error' => $e->getMessage(),
                'metadata' => $this->metadata
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook notification job permanently failed', [
            'event_type' => $this->eventType,
            'error' => $exception->getMessage(),
            'metadata' => $this->metadata
        ]);
    }
}