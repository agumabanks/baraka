<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PromotionEngineService;
use App\Services\PromotionAnalyticsService;
use App\Services\MilestoneTrackingService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Promotion Webhook Controller
 * 
 * Handles webhook endpoints for third-party integration including:
 * - Promotion activation/deactivation events
 * - Milestone achievement notifications
 * - ROI threshold breach alerts
 * - Usage analytics reporting
 * - External system integrations
 */
class PromotionWebhookController extends Controller
{
    private const WEBHOOK_SECRET_HEADER = 'X-Webhook-Secret';
    private const WEBHOOK_SIGNATURE_HEADER = 'X-Webhook-Signature';
    private const MAX_RETRY_ATTEMPTS = 3;
    private const WEBHOOK_TIMEOUT = 30;

    public function __construct(
        private PromotionEngineService $promotionEngine,
        private PromotionAnalyticsService $analyticsService,
        private MilestoneTrackingService $milestoneService,
        private NotificationService $notificationService
    ) {}

    /**
     * Handle promotion activation webhook
     */
    public function promotionActivated(Request $request): JsonResponse
    {
        try {
            $webhookData = $this->validateWebhookRequest($request, 'promotion_activated');
            
            if (!$webhookData['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook request',
                    'errors' => $webhookData['errors']
                ], 401);
            }

            $data = $webhookData['data'];
            
            // Process promotion activation
            $this->processPromotionActivation($data);

            // Send notifications to subscribed endpoints
            $this->broadcastPromotionEvent('promotion_activated', $data);

            // Log the webhook event
            $this->logWebhookEvent('promotion_activated', $data, $request);

            return response()->json([
                'success' => true,
                'message' => 'Promotion activation webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Promotion activation webhook failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process promotion activation webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle milestone achievement webhook
     */
    public function milestoneAchieved(Request $request): JsonResponse
    {
        try {
            $webhookData = $this->validateWebhookRequest($request, 'milestone_achieved');
            
            if (!$webhookData['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook request',
                    'errors' => $webhookData['errors']
                ], 401);
            }

            $data = $webhookData['data'];
            
            // Process milestone achievement
            $this->processMilestoneAchievement($data);

            // Send celebration notifications
            $this->sendMilestoneNotifications($data);

            // Broadcast to external systems
            $this->broadcastMilestoneEvent('milestone_achieved', $data);

            // Log the webhook event
            $this->logWebhookEvent('milestone_achieved', $data, $request);

            return response()->json([
                'success' => true,
                'message' => 'Milestone achievement webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Milestone achievement webhook failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process milestone achievement webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle ROI threshold breach webhook
     */
    public function roiThresholdBreach(Request $request): JsonResponse
    {
        try {
            $webhookData = $this->validateWebhookRequest($request, 'roi_threshold_breach');
            
            if (!$webhookData['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook request',
                    'errors' => $webhookData['errors']
                ], 401);
            }

            $data = $webhookData['data'];
            
            // Process ROI threshold breach
            $this->processROIThresholdBreach($data);

            // Send alerts to stakeholders
            $this->sendROIAlerts($data);

            // Log the webhook event
            $this->logWebhookEvent('roi_threshold_breach', $data, $request);

            return response()->json([
                'success' => true,
                'message' => 'ROI threshold breach webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('ROI threshold breach webhook failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process ROI threshold breach webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle promotion usage analytics webhook
     */
    public function usageAnalytics(Request $request): JsonResponse
    {
        try {
            $webhookData = $this->validateWebhookRequest($request, 'usage_analytics');
            
            if (!$webhookData['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook request',
                    'errors' => $webhookData['errors']
                ], 401);
            }

            $data = $webhookData['data'];
            
            // Process usage analytics
            $this->processUsageAnalytics($data);

            // Update dashboards
            $this->updateAnalyticsDashboards($data);

            // Log the webhook event
            $this->logWebhookEvent('usage_analytics', $data, $request);

            return response()->json([
                'success' => true,
                'message' => 'Usage analytics webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Usage analytics webhook failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process usage analytics webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle promotion expiry webhook
     */
    public function promotionExpired(Request $request): JsonResponse
    {
        try {
            $webhookData = $this->validateWebhookRequest($request, 'promotion_expired');
            
            if (!$webhookData['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook request',
                    'errors' => $webhookData['errors']
                ], 401);
            }

            $data = $webhookData['data'];
            
            // Process promotion expiry
            $this->processPromotionExpiry($data);

            // Send expiry notifications
            $this->sendExpiryNotifications($data);

            // Broadcast to external systems
            $this->broadcastPromotionEvent('promotion_expired', $data);

            // Log the webhook event
            $this->logWebhookEvent('promotion_expired', $data, $request);

            return response()->json([
                'success' => true,
                'message' => 'Promotion expiry webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Promotion expiry webhook failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process promotion expiry webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle bulk promotion webhook events
     */
    public function bulkPromotionEvent(Request $request): JsonResponse
    {
        try {
            $webhookData = $this->validateWebhookRequest($request, 'bulk_promotion_event');
            
            if (!$webhookData['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook request',
                    'errors' => $webhookData['errors']
                ], 401);
            }

            $data = $webhookData['data'];
            $eventType = $data['event_type'] ?? 'unknown';
            
            // Process bulk events
            $result = $this->processBulkPromotionEvent($data);

            // Log the webhook event
            $this->logWebhookEvent('bulk_promotion_event', $data, $request);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Bulk promotion event processed successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk promotion event webhook failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk promotion event webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register webhook endpoint for external systems
     */
    public function registerWebhook(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'url' => 'required|url',
                'events' => 'required|array',
                'events.*' => 'string|in:promotion_activated,milestone_achieved,roi_threshold_breach,usage_analytics,promotion_expired',
                'secret' => 'required|string|min:10',
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
            ]);

            $validatedData = $request->all();
            
            // Store webhook registration
            $webhookId = $this->storeWebhookRegistration($validatedData);

            // Test webhook connectivity
            $connectivityTest = $this->testWebhookConnectivity($validatedData['url']);

            return response()->json([
                'success' => true,
                'data' => [
                    'webhook_id' => $webhookId,
                    'connectivity_test' => $connectivityTest,
                    'registered_events' => $validatedData['events']
                ],
                'message' => 'Webhook registered successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook registration failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update webhook configuration
     */
    public function updateWebhook(Request $request, string $webhookId): JsonResponse
    {
        try {
            $request->validate([
                'url' => 'nullable|url',
                'events' => 'nullable|array',
                'events.*' => 'string|in:promotion_activated,milestone_achieved,roi_threshold_breach,usage_analytics,promotion_expired',
                'secret' => 'nullable|string|min:10',
                'name' => 'nullable|string|max:100',
                'description' => 'nullable|string|max:500',
                'active' => 'nullable|boolean',
            ]);

            $validatedData = $request->all();
            
            // Update webhook registration
            $result = $this->updateWebhookRegistration($webhookId, $validatedData);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook not found or update failed'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Webhook updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook update failed', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List registered webhooks
     */
    public function listWebhooks(): JsonResponse
    {
        try {
            $webhooks = $this->getRegisteredWebhooks();

            return response()->json([
                'success' => true,
                'data' => $webhooks,
                'message' => 'Webhooks retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook list retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve webhooks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test webhook delivery
     */
    public function testWebhook(Request $request, string $webhookId): JsonResponse
    {
        try {
            // Get webhook configuration
            $webhook = $this->getWebhookById($webhookId);
            
            if (!$webhook) {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook not found'
                ], 404);
            }

            // Send test payload
            $testPayload = $this->createTestPayload();
            $deliveryResult = $this->deliverWebhook($webhook, $testPayload);

            return response()->json([
                'success' => true,
                'data' => $deliveryResult,
                'message' => 'Webhook test completed'
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook test failed', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to test webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get webhook delivery logs
     */
    public function getWebhookLogs(Request $request, string $webhookId): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:100',
                'offset' => 'nullable|integer|min:0',
                'status' => 'nullable|in:success,failed,pending',
            ]);

            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);
            $status = $request->input('status');

            $logs = $this->getWebhookDeliveryLogs($webhookId, $limit, $offset, $status);

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Webhook logs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook logs retrieval failed', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve webhook logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods

    private function validateWebhookRequest(Request $request, string $eventType): array
    {
        $secret = $request->header(self::WEBHOOK_SECRET_HEADER);
        $signature = $request->header(self::WEBHOOK_SIGNATURE_HEADER);
        
        if (!$secret) {
            return [
                'valid' => false,
                'errors' => ['Missing webhook secret header']
            ];
        }

        // Verify webhook exists and is active
        $webhook = \DB::table('webhook_endpoints')
            ->where('url', $request->url())
            ->where('secret', $secret)
            ->where('active', true)
            ->first();

        if (!$webhook) {
            return [
                'valid' => false,
                'errors' => ['Invalid or inactive webhook configuration']
            ];
        }

        // Verify signature if provided
        if ($signature && !$this->verifySignature($request, $signature, $secret)) {
            return [
                'valid' => false,
                'errors' => ['Invalid webhook signature']
            ];
        }

        // Check if event type is subscribed
        $subscribedEvents = json_decode($webhook->events, true) ?? [];
        if (!in_array($eventType, $subscribedEvents)) {
            return [
                'valid' => false,
                'errors' => ['Event type not subscribed']
            ];
        }

        $data = $request->all();
        $data['webhook_id'] = $webhook->id;
        $data['webhook_name'] = $webhook->name;

        return [
            'valid' => true,
            'data' => $data
        ];
    }

    private function verifySignature(Request $request, string $signature, string $secret): bool
    {
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    private function processPromotionActivation(array $data): void
    {
        $promotionId = $data['promotion_id'] ?? null;
        $activationData = $data['activation_data'] ?? [];

        Log::info('Processing promotion activation', [
            'promotion_id' => $promotionId,
            'activation_data' => $activationData
        ]);

        // Additional processing logic can be added here
    }

    private function processMilestoneAchievement(array $data): void
    {
        $customerId = $data['customer_id'] ?? null;
        $milestoneData = $data['milestone_data'] ?? [];

        Log::info('Processing milestone achievement', [
            'customer_id' => $customerId,
            'milestone_data' => $milestoneData
        ]);

        // Additional processing logic can be added here
    }

    private function processROIThresholdBreach(array $data): void
    {
        $promotionId = $data['promotion_id'] ?? null;
        $roiData = $data['roi_data'] ?? [];
        $breachType = $data['breach_type'] ?? 'unknown';

        Log::info('Processing ROI threshold breach', [
            'promotion_id' => $promotionId,
            'breach_type' => $breachType,
            'roi_data' => $roiData
        ]);

        // Additional processing logic can be added here
    }

    private function processUsageAnalytics(array $data): void
    {
        $analyticsData = $data['analytics_data'] ?? [];
        $timeframe = $data['timeframe'] ?? '1d';

        Log::info('Processing usage analytics', [
            'timeframe' => $timeframe,
            'analytics_data' => $analyticsData
        ]);

        // Additional processing logic can be added here
    }

    private function processPromotionExpiry(array $data): void
    {
        $promotionId = $data['promotion_id'] ?? null;
        $expiryData = $data['expiry_data'] ?? [];

        Log::info('Processing promotion expiry', [
            'promotion_id' => $promotionId,
            'expiry_data' => $expiryData
        ]);

        // Additional processing logic can be added here
    }

    private function processBulkPromotionEvent(array $data): array
    {
        $eventType = $data['event_type'] ?? 'unknown';
        $events = $data['events'] ?? [];

        $results = [];
        foreach ($events as $event) {
            try {
                // Process individual event
                $this->processIndividualEvent($eventType, $event);
                $results[] = [
                    'event_id' => $event['id'] ?? null,
                    'status' => 'success'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'event_id' => $event['id'] ?? null,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'total_events' => count($events),
            'successful' => collect($results)->where('status', 'success')->count(),
            'failed' => collect($results)->where('status', 'failed')->count(),
            'results' => $results
        ];
    }

    private function processIndividualEvent(string $eventType, array $eventData): void
    {
        match($eventType) {
            'promotion_activated' => $this->processPromotionActivation($eventData),
            'milestone_achieved' => $this->processMilestoneAchievement($eventData),
            'roi_threshold_breach' => $this->processROIThresholdBreach($eventData),
            'usage_analytics' => $this->processUsageAnalytics($eventData),
            'promotion_expired' => $this->processPromotionExpiry($eventData),
            default => Log::warning('Unknown bulk event type', ['event_type' => $eventType])
        };
    }

    private function broadcastPromotionEvent(string $eventType, array $data): void
    {
        $subscribers = $this->getEventSubscribers($eventType);
        
        foreach ($subscribers as $subscriber) {
            try {
                $this->deliverWebhook($subscriber, array_merge($data, [
                    'event_type' => $eventType,
                    'timestamp' => now()->toISOString()
                ]));
            } catch (\Exception $e) {
                Log::error('Failed to deliver webhook to subscriber', [
                    'subscriber_id' => $subscriber['id'],
                    'event_type' => $eventType,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function broadcastMilestoneEvent(string $eventType, array $data): void
    {
        $subscribers = $this->getEventSubscribers($eventType);
        
        foreach ($subscribers as $subscriber) {
            try {
                $this->deliverWebhook($subscriber, array_merge($data, [
                    'event_type' => $eventType,
                    'timestamp' => now()->toISOString()
                ]));
            } catch (\Exception $e) {
                Log::error('Failed to deliver milestone webhook', [
                    'subscriber_id' => $subscriber['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function sendMilestoneNotifications(array $data): void
    {
        // Send celebration notifications
        $customerId = $data['customer_id'] ?? null;
        $milestoneData = $data['milestone_data'] ?? [];

        if ($customerId && !empty($milestoneData)) {
            $this->notificationService->sendCustomerPromotionAlert(
                $customerId,
                $milestoneData['promotion'] ?? null,
                'milestone_celebration',
                $data
            );
        }
    }

    private function sendROIAlerts(array $data): void
    {
        $promotionId = $data['promotion_id'] ?? null;
        $roiData = $data['roi_data'] ?? [];
        $breachType = $data['breach_type'] ?? 'unknown';

        if ($promotionId) {
            $promotion = \App\Models\PromotionalCampaign::find($promotionId);
            if ($promotion) {
                $this->notificationService->sendRoiAlert(
                    $promotion,
                    $breachType,
                    $roiData
                );
            }
        }
    }

    private function sendExpiryNotifications(array $data): void
    {
        $promotionId = $data['promotion_id'] ?? null;
        
        if ($promotionId) {
            $promotion = \App\Models\PromotionalCampaign::find($promotionId);
            if ($promotion) {
                $this->notificationService->sendPromotionExpiryNotifications(
                    $promotion,
                    $data
                );
            }
        }
    }

    private function updateAnalyticsDashboards(array $data): void
    {
        // Update real-time analytics dashboards
        Log::info('Updating analytics dashboards', ['data' => $data]);
    }

    private function getEventSubscribers(string $eventType): array
    {
        return \DB::table('webhook_endpoints')
            ->where('active', true)
            ->get()
            ->filter(function ($webhook) use ($eventType) {
                $events = json_decode($webhook->events, true) ?? [];
                return in_array($eventType, $events);
            })
            ->map(function ($webhook) {
                return [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'secret' => $webhook->secret,
                    'name' => $webhook->name
                ];
            })
            ->toArray();
    }

    private function deliverWebhook(array $webhook, array $payload): array
    {
        $signature = hash_hmac('sha256', json_encode($payload), $webhook['secret']);
        
        try {
            $response = Http::timeout(self::WEBHOOK_TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $payload['event_type'] ?? 'unknown'
                ])
                ->post($webhook['url'], $payload);

            $success = $response->successful();
            
            // Log delivery attempt
            $this->logWebhookDelivery($webhook, $payload, $response, $success);

            return [
                'success' => $success,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'delivered_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            $this->logWebhookDelivery($webhook, $payload, null, false, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'delivered_at' => now()->toISOString()
            ];
        }
    }

    private function storeWebhookRegistration(array $data): string
    {
        $webhookId = uniqid('webhook_');
        
        \DB::table('webhook_endpoints')->insert([
            'id' => $webhookId,
            'name' => $data['name'],
            'url' => $data['url'],
            'secret' => $data['secret'],
            'events' => json_encode($data['events']),
            'description' => $data['description'] ?? null,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $webhookId;
    }

    private function updateWebhookRegistration(string $webhookId, array $data): ?array
    {
        $updateData = [];
        
        if (isset($data['url'])) $updateData['url'] = $data['url'];
        if (isset($data['events'])) $updateData['events'] = json_encode($data['events']);
        if (isset($data['secret'])) $updateData['secret'] = $data['secret'];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];
        if (isset($data['active'])) $updateData['active'] = $data['active'];
        
        $updateData['updated_at'] = now();

        $affected = \DB::table('webhook_endpoints')
            ->where('id', $webhookId)
            ->update($updateData);

        if ($affected > 0) {
            return \DB::table('webhook_endpoints')->where('id', $webhookId)->first();
        }

        return null;
    }

    private function testWebhookConnectivity(string $url): array
    {
        try {
            $response = Http::timeout(10)->get($url);
            
            return [
                'reachable' => $response->successful(),
                'status_code' => $response->status(),
                'response_time_ms' => $response->transferStats ? round($response->transferStats->getTotalTime() * 1000) : null
            ];
        } catch (\Exception $e) {
            return [
                'reachable' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function getRegisteredWebhooks(): array
    {
        return \DB::table('webhook_endpoints')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($webhook) {
                return [
                    'id' => $webhook->id,
                    'name' => $webhook->name,
                    'url' => $webhook->url,
                    'events' => json_decode($webhook->events, true) ?? [],
                    'description' => $webhook->description,
                    'active' => $webhook->active,
                    'created_at' => $webhook->created_at,
                    'updated_at' => $webhook->updated_at
                ];
            })
            ->toArray();
    }

    private function getWebhookById(string $webhookId): ?array
    {
        $webhook = \DB::table('webhook_endpoints')->where('id', $webhookId)->first();
        
        if ($webhook) {
            return [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'secret' => $webhook->secret,
                'events' => json_decode($webhook->events, true) ?? [],
                'description' => $webhook->description,
                'active' => $webhook->active
            ];
        }

        return null;
    }

    private function createTestPayload(): array
    {
        return [
            'event_type' => 'test',
            'timestamp' => now()->toISOString(),
            'data' => [
                'test' => true,
                'message' => 'This is a test webhook payload'
            ]
        ];
    }

    private function getWebhookDeliveryLogs(string $webhookId, int $limit, int $offset, ?string $status): array
    {
        $query = \DB::table('webhook_delivery_logs')
            ->where('webhook_id', $webhookId)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event_type' => $log->event_type,
                    'status' => $log->status,
                    'status_code' => $log->status_code,
                    'response_body' => $log->response_body,
                    'error_message' => $log->error_message,
                    'attempts' => $log->attempts,
                    'created_at' => $log->created_at
                ];
            })
            ->toArray();
    }

    private function logWebhookEvent(string $eventType, array $data, Request $request): void
    {
        Log::info("Webhook event processed", [
            'event_type' => $eventType,
            'data' => $data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }

    private function logWebhookDelivery(array $webhook, array $payload, $response, bool $success, string $error = null): void
    {
        \DB::table('webhook_delivery_logs')->insert([
            'webhook_id' => $webhook['id'],
            'event_type' => $payload['event_type'] ?? 'unknown',
            'status' => $success ? 'success' : 'failed',
            'status_code' => $response ? $response->status() : null,
            'response_body' => $response ? $response->body() : null,
            'error_message' => $error,
            'attempts' => 1,
            'created_at' => now()
        ]);
    }
}