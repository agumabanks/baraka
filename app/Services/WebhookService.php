<?php

namespace App\Services;

use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function dispatch(string $eventType, array $payload): void
    {
        $endpoints = WebhookEndpoint::active()
            ->where(function ($query) use ($eventType) {
                // Check if endpoint is subscribed to this event
                $query->whereJsonContains('events', $eventType)
                    ->orWhereJsonContains('events', '*');
            })
            ->get();

        foreach ($endpoints as $endpoint) {
            if (!$endpoint->isHealthy()) {
                Log::warning('Webhook endpoint is unhealthy, skipping', [
                    'endpoint_id' => $endpoint->id,
                    'failures' => $endpoint->failure_count,
                ]);
                continue;
            }

            $this->queueDelivery($endpoint, $eventType, $payload);
        }
    }

    public function queueDelivery(WebhookEndpoint $endpoint, string $eventType, array $payload): WebhookDelivery
    {
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => $eventType,
            'payload' => $payload,
            'attempts' => 0,
            'next_retry_at' => now(),
        ]);

        dispatch(new \App\Jobs\DeliverWebhook($delivery));

        return $delivery;
    }

    public function deliver(WebhookDelivery $delivery): bool
    {
        try {
            $endpoint = $delivery->webhookEndpoint;
            $payload = json_encode($delivery->payload);
            $signature = $endpoint->generateSignature($payload);

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Event-Type' => $delivery->event_type,
                    'X-Delivery-ID' => $delivery->id,
                    'X-Timestamp' => now()->toIso8601String(),
                ])
                ->post($endpoint->url, $delivery->payload);

            $delivery->update([
                'attempts' => $delivery->attempts + 1,
                'http_status' => $response->status(),
                'response' => $response->json() ?? ['raw' => $response->body()],
            ]);

            if ($response->successful()) {
                $delivery->update(['delivered_at' => now()]);
                $endpoint->update(['failure_count' => 0]);
                Log::info('Webhook delivered successfully', [
                    'delivery_id' => $delivery->id,
                    'endpoint_id' => $endpoint->id,
                ]);
                return true;
            }

            $this->scheduleRetry($delivery, $endpoint);
            return false;
        } catch (\Throwable $e) {
            Log::error('Webhook delivery failed', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            $this->scheduleRetry($delivery, $delivery->webhookEndpoint);
            return false;
        }
    }

    private function scheduleRetry(WebhookDelivery $delivery, WebhookEndpoint $endpoint): void
    {
        $retryPolicy = $endpoint->retry_policy;
        $maxAttempts = $retryPolicy['max_attempts'] ?? 5;
        $attempts = $delivery->attempts + 1;

        if ($attempts >= $maxAttempts) {
            $delivery->update(['failed_at' => now()]);
            $endpoint->update(['failure_count' => $endpoint->failure_count + 1]);
            Log::error('Webhook delivery permanently failed', [
                'delivery_id' => $delivery->id,
                'endpoint_id' => $endpoint->id,
                'attempts' => $attempts,
            ]);
            return;
        }

        $backoffMultiplier = $retryPolicy['backoff_multiplier'] ?? 2;
        $initialDelay = $retryPolicy['initial_delay'] ?? 60;
        $maxDelay = $retryPolicy['max_delay'] ?? 3600;

        $delay = min($initialDelay * ($backoffMultiplier ** ($attempts - 1)), $maxDelay);

        $delivery->update(['next_retry_at' => now()->addSeconds($delay)]);
    }
}
