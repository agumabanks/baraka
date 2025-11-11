<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public WebhookDelivery $delivery)
    {
        $this->onQueue('webhooks');
        $this->tries = 5;
        $this->timeout = 30;
    }

    public function handle(WebhookService $service): void
    {
        $service->deliver($this->delivery);
    }

    public function failed(\Throwable $exception): void
    {
        $this->delivery->update([
            'failed_at' => now(),
        ]);
    }
}
