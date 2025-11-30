<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use App\Services\Api\WebhookDispatchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Webhook Management API
 */
class WebhookController extends Controller
{
    protected WebhookDispatchService $webhookService;

    public function __construct(WebhookDispatchService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * List webhooks
     * GET /api/v2/webhooks
     */
    public function index(Request $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        $webhooks = WebhookSubscription::where(function ($q) use ($apiKey) {
                $q->where('user_id', $apiKey->user_id)
                  ->orWhere('customer_id', $apiKey->customer_id);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'webhooks' => $webhooks->map(fn($w) => $this->formatWebhook($w)),
            ],
        ]);
    }

    /**
     * Create webhook
     * POST /api/v2/webhooks
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', WebhookSubscription::availableEvents()) . ',*',
            'headers' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Validation failed', 'details' => $validator->errors()],
            ], 422);
        }

        $apiKey = $request->attributes->get('api_key');

        $webhook = WebhookSubscription::createWithSecret(array_merge($validator->validated(), [
            'user_id' => $apiKey->user_id,
            'customer_id' => $apiKey->customer_id,
        ]));

        return response()->json([
            'success' => true,
            'data' => [
                'webhook' => $this->formatWebhook($webhook),
                'secret' => $webhook->secret, // Only shown once
            ],
        ], 201);
    }

    /**
     * Get webhook details
     * GET /api/v2/webhooks/{id}
     */
    public function show(Request $request, WebhookSubscription $webhook): JsonResponse
    {
        $this->authorizeWebhook($request, $webhook);

        $stats = $this->webhookService->getStatistics($webhook->id);

        return response()->json([
            'success' => true,
            'data' => [
                'webhook' => $this->formatWebhook($webhook),
                'statistics' => $stats,
            ],
        ]);
    }

    /**
     * Update webhook
     * PUT /api/v2/webhooks/{id}
     */
    public function update(Request $request, WebhookSubscription $webhook): JsonResponse
    {
        $this->authorizeWebhook($request, $webhook);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string|in:' . implode(',', WebhookSubscription::availableEvents()) . ',*',
            'headers' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Validation failed', 'details' => $validator->errors()],
            ], 422);
        }

        $webhook->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => ['webhook' => $this->formatWebhook($webhook->fresh())],
        ]);
    }

    /**
     * Delete webhook
     * DELETE /api/v2/webhooks/{id}
     */
    public function destroy(Request $request, WebhookSubscription $webhook): JsonResponse
    {
        $this->authorizeWebhook($request, $webhook);

        $webhook->delete();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Webhook deleted'],
        ]);
    }

    /**
     * Test webhook
     * POST /api/v2/webhooks/{id}/test
     */
    public function test(Request $request, WebhookSubscription $webhook): JsonResponse
    {
        $this->authorizeWebhook($request, $webhook);

        $result = $this->webhookService->testWebhook($webhook);

        return response()->json([
            'success' => $result['success'],
            'data' => $result,
        ]);
    }

    /**
     * Get webhook deliveries
     * GET /api/v2/webhooks/{id}/deliveries
     */
    public function deliveries(Request $request, WebhookSubscription $webhook): JsonResponse
    {
        $this->authorizeWebhook($request, $webhook);

        $deliveries = WebhookDelivery::where('subscription_id', $webhook->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => [
                'deliveries' => $deliveries->items(),
                'pagination' => [
                    'total' => $deliveries->total(),
                    'current_page' => $deliveries->currentPage(),
                    'last_page' => $deliveries->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Retry delivery
     * POST /api/v2/webhooks/deliveries/{delivery}/retry
     */
    public function retryDelivery(Request $request, WebhookDelivery $delivery): JsonResponse
    {
        $this->authorizeWebhook($request, $delivery->subscription);

        if ($delivery->status !== 'failed') {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Only failed deliveries can be retried'],
            ], 400);
        }

        $delivery->update([
            'status' => 'pending',
            'next_retry_at' => now(),
            'attempts' => 0,
        ]);

        $result = $this->webhookService->sendWebhook($delivery);

        return response()->json([
            'success' => $result['success'],
            'data' => $result,
        ]);
    }

    /**
     * Get available events
     * GET /api/v2/webhooks/events
     */
    public function events(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'events' => WebhookSubscription::availableEvents(),
            ],
        ]);
    }

    /**
     * Rotate webhook secret
     * POST /api/v2/webhooks/{id}/rotate-secret
     */
    public function rotateSecret(Request $request, WebhookSubscription $webhook): JsonResponse
    {
        $this->authorizeWebhook($request, $webhook);

        $newSecret = 'whsec_' . \Illuminate\Support\Str::random(32);
        $webhook->update(['secret' => $newSecret]);

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $newSecret,
                'message' => 'Secret rotated. Update your webhook handler with the new secret.',
            ],
        ]);
    }

    /**
     * Format webhook for response
     */
    protected function formatWebhook(WebhookSubscription $webhook): array
    {
        return [
            'id' => $webhook->id,
            'name' => $webhook->name,
            'url' => $webhook->url,
            'events' => $webhook->events,
            'is_active' => $webhook->is_active,
            'consecutive_failures' => $webhook->consecutive_failures,
            'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
            'created_at' => $webhook->created_at->toIso8601String(),
        ];
    }

    /**
     * Authorize webhook access
     */
    protected function authorizeWebhook(Request $request, WebhookSubscription $webhook): void
    {
        $apiKey = $request->attributes->get('api_key');

        if ($webhook->user_id !== $apiKey->user_id && $webhook->customer_id !== $apiKey->customer_id) {
            abort(403, 'Not authorized to access this webhook');
        }
    }
}
