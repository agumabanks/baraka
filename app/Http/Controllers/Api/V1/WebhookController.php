<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private WebhookService $webhookService)
    {
    }

    public function registerWebhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'events' => 'required|array',
            'name' => 'nullable|string|max:255',
        ]);

        $endpoint = WebhookEndpoint::create([
            'name' => $validated['name'] ?? 'Webhook ' . now()->format('Y-m-d H:i'),
            'url' => $validated['url'],
            'events' => $validated['events'],
            'active' => true,
        ]);

        return response()->json([
            'success' => true,
            'webhook_id' => $endpoint->id,
            'secret_key' => $endpoint->secret_key,
        ], 201);
    }

    public function getWebhookEvents(): JsonResponse
    {
        $events = [
            'shipment.created',
            'shipment.updated',
            'shipment.delivered',
            'shipment.exception',
            'pricing.quote_generated',
            'contract.created',
            'promotion.applied',
            'system.backup_completed',
        ];

        return response()->json(['events' => $events]);
    }

    public function testWebhook(int $id): JsonResponse
    {
        try {
            $endpoint = WebhookEndpoint::findOrFail($id);
            $this->webhookService->queueDelivery($endpoint, 'webhook.test', [
                'timestamp' => now()->toIso8601String(),
                'test' => true,
            ]);

            return response()->json(['success' => true, 'message' => 'Test webhook queued']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateWebhook(Request $request, int $id): JsonResponse
    {
        $endpoint = WebhookEndpoint::findOrFail($id);
        $validated = $request->validate([
            'url' => 'url',
            'events' => 'array',
            'active' => 'boolean',
        ]);

        $endpoint->update($validated);

        return response()->json(['success' => true, 'webhook' => $endpoint]);
    }

    public function deleteWebhook(int $id): JsonResponse
    {
        WebhookEndpoint::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function handlePricingEvents(Request $request): JsonResponse
    {
        Log::info('Pricing webhook received', ['payload' => $request->all()]);
        return response()->json(['success' => true]);
    }

    public function handleContractEvents(Request $request): JsonResponse
    {
        Log::info('Contract webhook received', ['payload' => $request->all()]);
        return response()->json(['success' => true]);
    }

    public function handlePromotionEvents(Request $request): JsonResponse
    {
        Log::info('Promotion webhook received', ['payload' => $request->all()]);
        return response()->json(['success' => true]);
    }

    public function handleSystemEvents(Request $request): JsonResponse
    {
        Log::info('System webhook received', ['payload' => $request->all()]);
        return response()->json(['success' => true]);
    }
}
