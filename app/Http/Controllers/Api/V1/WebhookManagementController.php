<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WebhookManagementController extends Controller
{
    public function __construct(private WebhookService $webhookService)
    {
    }

    public function index(): JsonResponse
    {
        $endpoints = WebhookEndpoint::paginate(20);
        return response()->json($endpoints);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'active' => 'boolean',
        ]);

        $endpoint = WebhookEndpoint::create($validated);

        return response()->json($endpoint, 201);
    }

    public function show(WebhookEndpoint $endpoint): JsonResponse
    {
        return response()->json($endpoint->load('deliveries'));
    }

    public function update(Request $request, WebhookEndpoint $endpoint): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'url' => 'url',
            'events' => 'array|min:1',
            'active' => 'boolean',
        ]);

        $endpoint->update($validated);

        return response()->json($endpoint);
    }

    public function destroy(WebhookEndpoint $endpoint): JsonResponse
    {
        $endpoint->delete();
        return response()->json(null, 204);
    }

    public function rotateSecret(WebhookEndpoint $endpoint): JsonResponse
    {
        $newSecret = $endpoint->rotateSecret();

        return response()->json([
            'message' => 'Secret rotated successfully',
            'new_secret' => $newSecret,
        ]);
    }

    public function deliveries(WebhookEndpoint $endpoint): JsonResponse
    {
        $deliveries = $endpoint->deliveries()
            ->latest()
            ->paginate(20);

        return response()->json($deliveries);
    }

    public function retryDelivery(WebhookDelivery $delivery): JsonResponse
    {
        if ($delivery->delivered_at !== null) {
            return response()->json([
                'error' => 'Cannot retry already delivered webhook',
            ], 400);
        }

        $delivery->update([
            'attempts' => 0,
            'next_retry_at' => now(),
            'failed_at' => null,
        ]);

        dispatch(new \App\Jobs\DeliverWebhook($delivery));

        return response()->json([
            'message' => 'Delivery queued for retry',
            'delivery' => $delivery,
        ]);
    }

    public function testWebhook(WebhookEndpoint $endpoint): JsonResponse
    {
        $testPayload = [
            'event_type' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'test' => true,
        ];

        $this->webhookService->queueDelivery($endpoint, 'webhook.test', $testPayload);

        return response()->json([
            'message' => 'Test webhook queued',
        ]);
    }

    public function health(): JsonResponse
    {
        $endpoints = WebhookEndpoint::all();
        $health = [];

        foreach ($endpoints as $endpoint) {
            $recentDeliveries = $endpoint->deliveries()
                ->where('created_at', '>=', now()->subHours(24))
                ->get();

            $health[] = [
                'endpoint_id' => $endpoint->id,
                'name' => $endpoint->name,
                'url' => $endpoint->url,
                'is_healthy' => $endpoint->isHealthy(),
                'failure_count' => $endpoint->failure_count,
                'last_triggered_at' => $endpoint->last_triggered_at,
                'recent_success_rate' => $recentDeliveries->count() > 0
                    ? round($recentDeliveries->where('delivered_at', '!=', null)->count() / $recentDeliveries->count() * 100, 2)
                    : 0,
            ];
        }

        return response()->json($health);
    }
}
