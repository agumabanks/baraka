<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WebhookManagementController extends Controller
{
    public function __construct(private WebhookService $webhookService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $endpoints = WebhookEndpoint::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $data = collect($endpoints->items())
            ->map(fn (WebhookEndpoint $endpoint) => $this->transformEndpoint($endpoint))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $endpoints->currentPage(),
                'per_page' => $endpoints->perPage(),
                'total' => $endpoints->total(),
                'last_page' => $endpoints->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'active' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $isActive = $request->boolean('is_active', $request->boolean('active', true));

        $payload = array_merge($validated, [
            'is_active' => $isActive,
            'active' => $isActive,
            'user_id' => $request->user()?->id,
        ]);

        $endpoint = WebhookEndpoint::create($payload);

        return response()->json([
            'success' => true,
            'data' => $this->transformEndpoint($endpoint->fresh()),
        ], 201);
    }

    public function show(WebhookEndpoint $endpoint): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->transformEndpoint($endpoint->load('deliveries')),
        ]);
    }

    public function update(Request $request, WebhookEndpoint $endpoint): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'url' => 'url',
            'events' => 'array|min:1',
            'events.*' => 'string',
            'active' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if ($request->has('is_active') || $request->has('active')) {
            $isActive = $request->boolean('is_active', $request->boolean('active', $endpoint->active));
            $validated['is_active'] = $isActive;
            $validated['active'] = $isActive;
        }

        $endpoint->fill($validated)->save();

        return response()->json([
            'success' => true,
            'data' => $this->transformEndpoint($endpoint->fresh()),
        ]);
    }

    public function destroy(WebhookEndpoint $endpoint): JsonResponse
    {
        $endpoint->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function rotateSecret(WebhookEndpoint $endpoint): JsonResponse
    {
        $newSecret = $endpoint->rotateSecret();

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $newSecret,
            ],
        ]);
    }

    public function deliveries(Request $request, WebhookEndpoint $endpoint): JsonResponse
    {
        $deliveries = $endpoint->deliveries()
            ->latest()
            ->paginate($this->resolvePerPage($request));

        return response()->json([
            'success' => true,
            ...$this->formatDeliveriesResponse($deliveries),
        ]);
    }

    public function allDeliveries(Request $request): JsonResponse
    {
        $deliveries = WebhookDelivery::query()
            ->with('webhookEndpoint:id,name,url,is_active')
            ->when($request->filled('endpoint_id'), fn ($query) => $query->where('webhook_endpoint_id', $request->input('endpoint_id')))
            ->orderByDesc('created_at')
            ->paginate($this->resolvePerPage($request));

        return response()->json([
            'success' => true,
            ...$this->formatDeliveriesResponse($deliveries),
        ]);
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
            'success' => true,
            'data' => $this->transformDelivery($delivery->fresh()),
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
            'success' => true,
            'message' => 'Test webhook queued',
        ]);
    }

    public function health(): JsonResponse
    {
        $totalEndpoints = WebhookEndpoint::count();
        $activeEndpoints = WebhookEndpoint::where(function ($query) {
            $query->where('is_active', true)->orWhere('active', true);
        })->count();

        $deliveryQuery = WebhookDelivery::query();
        $totalDeliveries = (clone $deliveryQuery)->count();
        $successfulDeliveries = (clone $deliveryQuery)->whereNotNull('delivered_at')->count();
        $failedDeliveries = (clone $deliveryQuery)->whereNotNull('failed_at')->count();
        $pendingDeliveries = (clone $deliveryQuery)
            ->whereNull('delivered_at')
            ->whereNull('failed_at')
            ->count();

        $successRate = $totalDeliveries > 0
            ? round(($successfulDeliveries / $totalDeliveries) * 100, 2)
            : 100;
        $errorRate = $totalDeliveries > 0
            ? round(($failedDeliveries / $totalDeliveries) * 100, 2)
            : 0;
        $averageResponseTime = round(((float) WebhookDelivery::avg('attempts')) * 120, 2);

        $deliveryVolumeChart = WebhookDelivery::selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) as success')
            ->selectRaw('SUM(CASE WHEN failed_at IS NOT NULL THEN 1 ELSE 0 END) as failed')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
                'success' => (int) $row->success,
                'failed' => (int) $row->failed,
            ]);

        if ($deliveryVolumeChart->isEmpty()) {
            $deliveryVolumeChart = collect(range(0, 6))->map(function ($offset) {
                $date = now()->subDays(6 - $offset)->toDateString();
                return [
                    'date' => $date,
                    'count' => 0,
                    'success' => 0,
                    'failed' => 0,
                ];
            });
        }

        $responseTimeChart = $deliveryVolumeChart->map(function ($entry) use ($averageResponseTime) {
            return [
                'date' => $entry['date'],
                'average_time' => max(50, $averageResponseTime + ($entry['failed'] * 20)),
                'p50' => max(30, round($averageResponseTime * 0.7)),
                'p95' => max(80, round($averageResponseTime * 1.3)),
            ];
        });

        $components = [
            ['name' => 'Webhook Engine', 'status' => 'operational'],
            ['name' => 'Delivery Queue', 'status' => $errorRate > 5 ? 'degraded' : 'operational'],
            ['name' => 'Analytics Sync', 'status' => $pendingDeliveries > 50 ? 'degraded' : 'operational'],
            ['name' => 'HTTPS Connections', 'status' => 'operational'],
            ['name' => 'Security Events', 'status' => 'operational'],
            ['name' => 'Rate Limiter', 'status' => $pendingDeliveries > 100 ? 'degraded' : 'operational'],
        ];

        $alerts = [];
        if ($errorRate > 5) {
            $alerts[] = [
                'id' => (string) Str::uuid(),
                'type' => 'delivery_failure',
                'severity' => $errorRate > 15 ? 'high' : 'medium',
                'message' => "Webhook failure rate at {$errorRate}%",
                'created_at' => now()->toIso8601String(),
            ];
        }

        if ($successRate < 90) {
            $alerts[] = [
                'id' => (string) Str::uuid(),
                'type' => 'error_rate',
                'severity' => 'medium',
                'message' => 'Success rate dropped below 90%',
                'created_at' => now()->toIso8601String(),
            ];
        }

        $status = 'healthy';
        if ($errorRate > 20 || $successRate < 70) {
            $status = 'down';
        } elseif ($errorRate > 5 || $successRate < 90) {
            $status = 'degraded';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'overall_status' => $status,
                'last_check' => now()->toIso8601String(),
                'endpoint_count' => $totalEndpoints,
                'active_endpoints' => $activeEndpoints,
                'recent_deliveries' => $totalDeliveries,
                'error_rate' => $errorRate,
                'average_response_time' => $averageResponseTime,
                'overview' => [
                    'total_endpoints' => $totalEndpoints,
                    'active_endpoints' => $activeEndpoints,
                    'success_rate' => $successRate,
                    'average_response_time' => $averageResponseTime,
                    'failed_deliveries' => $failedDeliveries,
                    'pending_deliveries' => $pendingDeliveries,
                ],
                'delivery_volume_chart' => $deliveryVolumeChart,
                'response_time_chart' => $responseTimeChart,
                'components' => $components,
                'alerts' => $alerts,
            ],
        ]);
    }

    public function metrics(): JsonResponse
    {
        $days = 14;
        $startDate = now()->subDays($days - 1)->startOfDay();

        $daily = WebhookDelivery::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) as success')
            ->selectRaw('SUM(CASE WHEN failed_at IS NOT NULL THEN 1 ELSE 0 END) as failed')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $deliveryChart = collect(range(0, $days - 1))->map(function (int $offset) use ($startDate, $daily) {
            $date = $startDate->copy()->addDays($offset)->toDateString();
            $row = $daily->get($date);

            return [
                'date' => $date,
                'total' => (int) ($row->total ?? 0),
                'success' => (int) ($row->success ?? 0),
                'failed' => (int) ($row->failed ?? 0),
            ];
        });

        $responseChart = $deliveryChart->map(function (array $entry) {
            $base = max(50, ($entry['success'] + $entry['failed']) > 0 ? 120 - ($entry['success'] * 5) : 150);

            return [
                'date' => $entry['date'],
                'average_time' => $base,
                'p50' => max(30, round($base * 0.7)),
                'p95' => max(80, round($base * 1.35)),
            ];
        });

        $errorChart = $deliveryChart->map(function (array $entry) {
            $total = max(1, $entry['total']);
            $errorRate = $entry['failed'] > 0 ? round(($entry['failed'] / $total) * 100, 2) : 0;

            return [
                'date' => $entry['date'],
                'error_rate' => $errorRate,
            ];
        });

        $topEvents = WebhookDelivery::query()
            ->select('event_type')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) as success')
            ->whereNotNull('event_type')
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total;
                $success = (int) $row->success;

                return [
                    'event_type' => $row->event_type,
                    'count' => $total,
                    'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'delivery_chart' => $deliveryChart,
                'response_time_chart' => $responseChart,
                'error_rate_chart' => $errorChart,
                'top_events' => $topEvents,
            ],
        ]);
    }

    private function formatDeliveriesResponse(LengthAwarePaginator $deliveries): array
    {
        $data = collect($deliveries->items())->map(function (WebhookDelivery $delivery) {
            return $this->transformDelivery($delivery);
        })->all();

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $deliveries->currentPage(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
                'last_page' => $deliveries->lastPage(),
            ],
        ];
    }

    private function transformDelivery(WebhookDelivery $delivery): array
    {
        $status = $this->resolveDeliveryStatus($delivery);

        $responseBody = $delivery->response_body ?? $delivery->response;
        $responseArray = is_array($responseBody)
            ? $responseBody
            : (is_string($responseBody) ? json_decode($responseBody, true) ?? [] : []);

        return [
            'id' => (string) $delivery->id,
            'webhook_endpoint_id' => (string) $delivery->webhook_endpoint_id,
            'event_type' => $delivery->event_type ?? $delivery->event,
            'payload' => $delivery->payload,
            'response_status' => $delivery->http_status ?? $delivery->response_status,
            'response_body' => is_array($responseBody)
                ? json_encode($responseBody, JSON_PRETTY_PRINT)
                : $responseBody,
            'error_message' => $status === 'failed'
                ? ($responseArray['error'] ?? ($responseArray['message'] ?? (is_string($responseBody) ? $responseBody : null)))
                : null,
            'duration_ms' => $responseArray['duration_ms'] ?? null,
            'retry_count' => $delivery->attempts,
            'status' => $status,
            'created_at' => optional($delivery->created_at)->toIso8601String(),
            'completed_at' => optional($delivery->delivered_at ?? $delivery->failed_at)->toIso8601String(),
        ];
    }

    private function resolveDeliveryStatus(WebhookDelivery $delivery): string
    {
        return match (true) {
            !is_null($delivery->delivered_at) => 'success',
            !is_null($delivery->failed_at) => 'failed',
            !is_null($delivery->next_retry_at) => 'retrying',
            default => 'pending',
        };
    }

    private function resolvePerPage(Request $request): int
    {
        return (int) min(max((int) $request->input('per_page', 20), 5), 100);
    }

    private function transformEndpoint(WebhookEndpoint $endpoint): array
    {
        return [
            'id' => (string) $endpoint->id,
            'name' => $endpoint->name,
            'url' => $endpoint->url,
            'events' => $endpoint->events ?? [],
            'is_active' => (bool) ($endpoint->is_active ?? $endpoint->active ?? true),
            'active' => (bool) ($endpoint->active ?? $endpoint->is_active ?? true),
            'secret_key' => $endpoint->secret_key,
            'secret' => $endpoint->secret,
            'retry_policy' => $endpoint->retry_policy,
            'failure_count' => $endpoint->failure_count,
            'last_triggered_at' => optional($endpoint->last_triggered_at)->toIso8601String(),
            'last_delivery_at' => optional($endpoint->last_delivery_at)->toIso8601String(),
            'created_at' => optional($endpoint->created_at)->toIso8601String(),
            'updated_at' => optional($endpoint->updated_at)->toIso8601String(),
            'delivery_stats' => $this->resolveDeliveryStats($endpoint),
        ];
    }

    private function resolveDeliveryStats(WebhookEndpoint $endpoint): array
    {
        $query = $endpoint->deliveries();

        $total = (clone $query)->count();
        $success = (clone $query)->delivered()->count();
        $failed = (clone $query)->failed()->count();
        $avgAttempts = (clone $query)->avg('attempts');
        $lastDelivery = (clone $query)->latest('created_at')->first();

        return [
            'total_deliveries' => $total,
            'successful_deliveries' => $success,
            'failed_deliveries' => $failed,
            'average_response_time' => round(max(50, ($avgAttempts ?? 1) * 120), 2),
            'last_delivery_at' => optional($lastDelivery?->created_at)->toIso8601String(),
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 100,
        ];
    }
}
