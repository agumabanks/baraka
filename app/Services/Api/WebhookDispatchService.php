<?php

namespace App\Services\Api;

use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use App\Models\Shipment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WebhookDispatchService
 * 
 * Event-driven webhook delivery:
 * - Automatic dispatch on events
 * - Signature verification
 * - Retry handling
 * - Delivery logging
 */
class WebhookDispatchService
{
    /**
     * Dispatch webhook for event
     */
    public function dispatch(string $event, array $payload, ?int $customerId = null): array
    {
        $subscriptions = WebhookSubscription::active()
            ->forEvent($event)
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->get();

        $results = [];

        foreach ($subscriptions as $subscription) {
            $delivery = $this->createDelivery($subscription, $event, $payload);
            $result = $this->sendWebhook($delivery);
            $results[] = $result;
        }

        return [
            'event' => $event,
            'subscriptions_notified' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Dispatch shipment event
     */
    public function dispatchShipmentEvent(Shipment $shipment, string $event): array
    {
        $payload = $this->buildShipmentPayload($shipment, $event);
        
        return $this->dispatch(
            "shipment.{$event}",
            $payload,
            $shipment->customer_id
        );
    }

    /**
     * Create delivery record
     */
    protected function createDelivery(WebhookSubscription $subscription, string $event, array $payload): WebhookDelivery
    {
        return WebhookDelivery::create([
            'subscription_id' => $subscription->id,
            'event' => $event,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }

    /**
     * Send webhook request
     */
    public function sendWebhook(WebhookDelivery $delivery): array
    {
        $subscription = $delivery->subscription;
        $payload = json_encode($delivery->payload);
        $timestamp = time();

        // Build headers
        $headers = array_merge($subscription->headers ?? [], [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $delivery->event,
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $subscription->generateSignature($payload, $timestamp),
            'User-Agent' => 'Baraka-Webhooks/1.0',
        ]);

        $startTime = microtime(true);

        try {
            $response = Http::timeout($subscription->timeout_seconds)
                ->withHeaders($headers)
                ->withBody($payload, 'application/json')
                ->post($subscription->url);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $delivery->markSuccess(
                    $response->status(),
                    $response->body(),
                    $responseTime
                );

                return [
                    'success' => true,
                    'delivery_id' => $delivery->id,
                    'response_code' => $response->status(),
                    'response_time_ms' => $responseTime,
                ];
            }

            $delivery->markFailed(
                $response->status(),
                $response->body(),
                $responseTime
            );

            return [
                'success' => false,
                'delivery_id' => $delivery->id,
                'response_code' => $response->status(),
                'error' => 'Non-2xx response',
            ];

        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $delivery->markFailed(null, $e->getMessage(), $responseTime);

            Log::error('Webhook delivery failed', [
                'delivery_id' => $delivery->id,
                'url' => $subscription->url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retry failed deliveries
     */
    public function retryPendingDeliveries(): array
    {
        $deliveries = WebhookDelivery::needsRetry()
            ->with('subscription')
            ->limit(100)
            ->get();

        $results = [];

        foreach ($deliveries as $delivery) {
            if (!$delivery->subscription->is_active) {
                $delivery->update(['status' => 'failed', 'error_message' => 'Subscription disabled']);
                continue;
            }

            $results[] = $this->sendWebhook($delivery);
        }

        return [
            'processed' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Build shipment payload
     */
    protected function buildShipmentPayload(Shipment $shipment, string $event): array
    {
        return [
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
            'event' => $event,
            'event_time' => now()->toIso8601String(),
            'data' => [
                'origin' => [
                    'branch_id' => $shipment->origin_branch_id,
                    'branch_name' => $shipment->originBranch?->name,
                ],
                'destination' => [
                    'branch_id' => $shipment->dest_branch_id,
                    'branch_name' => $shipment->destBranch?->name,
                ],
                'customer' => [
                    'id' => $shipment->customer_id,
                    'name' => $shipment->customer?->name,
                ],
                'shipping_cost' => $shipment->shipping_cost,
                'cod_amount' => $shipment->cod_amount,
                'payment_type' => $shipment->payment_type,
                'created_at' => $shipment->created_at?->toIso8601String(),
                'delivered_at' => $shipment->delivered_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(WebhookSubscription $subscription): array
    {
        $testPayload = [
            'event' => 'test',
            'message' => 'This is a test webhook from Baraka',
            'timestamp' => now()->toIso8601String(),
        ];

        $delivery = $this->createDelivery($subscription, 'test', $testPayload);
        return $this->sendWebhook($delivery);
    }

    /**
     * Get delivery statistics
     */
    public function getStatistics(?int $subscriptionId = null, int $days = 7): array
    {
        $query = WebhookDelivery::where('created_at', '>=', now()->subDays($days));

        if ($subscriptionId) {
            $query->where('subscription_id', $subscriptionId);
        }

        $total = (clone $query)->count();
        $success = (clone $query)->where('status', 'success')->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();

        $avgResponseTime = (clone $query)
            ->where('status', 'success')
            ->avg('response_time_ms');

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 1) : 0,
            'avg_response_time_ms' => round($avgResponseTime ?? 0),
        ];
    }
}
