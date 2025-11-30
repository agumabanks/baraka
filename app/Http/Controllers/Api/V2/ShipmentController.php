<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ScanEvent;
use App\Services\Api\WebhookDispatchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * API v2 Shipment Controller
 * 
 * RESTful API for shipment management:
 * - CRUD operations
 * - Status updates
 * - Tracking
 * - Batch operations
 */
class ShipmentController extends Controller
{
    protected WebhookDispatchService $webhookService;

    public function __construct(WebhookDispatchService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * List shipments
     * GET /api/v2/shipments
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shipment::query();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('tracking_number')) {
            $query->where('tracking_number', 'like', "%{$request->tracking_number}%");
        }

        if ($request->has('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }

        if ($request->has('created_to')) {
            $query->where('created_at', '<=', $request->created_to);
        }

        // Pagination
        $perPage = min($request->input('per_page', 25), 100);
        $shipments = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->apiResponse([
            'shipments' => $shipments->items(),
            'pagination' => [
                'total' => $shipments->total(),
                'per_page' => $shipments->perPage(),
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
            ],
        ]);
    }

    /**
     * Get single shipment
     * GET /api/v2/shipments/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::with(['customer', 'originBranch', 'destBranch', 'scanEvents'])
            ->where('id', $id)
            ->orWhere('tracking_number', $id)
            ->first();

        if (!$shipment) {
            return $this->errorResponse('Shipment not found', 404);
        }

        return $this->apiResponse([
            'shipment' => $this->formatShipment($shipment),
        ]);
    }

    /**
     * Create shipment
     * POST /api/v2/shipments
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'origin_branch_id' => 'required|exists:branches,id',
            'dest_branch_id' => 'required|exists:branches,id',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:50',
            'recipient_address' => 'required|string',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'payment_type' => 'required|in:prepaid,cod',
            'cod_amount' => 'required_if:payment_type,cod|numeric|min:0',
            'description' => 'nullable|string',
            'special_instructions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $shipment = Shipment::create(array_merge($validator->validated(), [
            'tracking_number' => Shipment::generateTrackingNumber(),
            'status' => 'pending',
        ]));

        // Dispatch webhook
        $this->webhookService->dispatchShipmentEvent($shipment, 'created');

        return $this->apiResponse([
            'shipment' => $this->formatShipment($shipment->fresh()),
        ], 201);
    }

    /**
     * Update shipment
     * PUT /api/v2/shipments/{id}
     */
    public function update(Request $request, Shipment $shipment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_name' => 'sometimes|string|max:255',
            'recipient_phone' => 'sometimes|string|max:50',
            'recipient_address' => 'sometimes|string',
            'weight' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $shipment->update($validator->validated());

        return $this->apiResponse([
            'shipment' => $this->formatShipment($shipment->fresh()),
        ]);
    }

    /**
     * Update shipment status
     * POST /api/v2/shipments/{id}/status
     */
    public function updateStatus(Request $request, Shipment $shipment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,picked_up,in_transit,out_for_delivery,delivered,cancelled,returned',
            'notes' => 'nullable|string',
            'location' => 'nullable|array',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $oldStatus = $shipment->status;
        $newStatus = $request->status;

        $shipment->update(['status' => $newStatus]);

        // Create scan event
        ScanEvent::create([
            'shipment_id' => $shipment->id,
            'scan_type' => $newStatus,
            'location' => $request->notes,
            'latitude' => $request->input('location.latitude'),
            'longitude' => $request->input('location.longitude'),
            'scanned_by' => auth()->id(),
        ]);

        // Dispatch webhook
        $this->webhookService->dispatchShipmentEvent($shipment, $newStatus);

        return $this->apiResponse([
            'shipment' => $this->formatShipment($shipment->fresh()),
            'previous_status' => $oldStatus,
        ]);
    }

    /**
     * Get tracking history
     * GET /api/v2/shipments/{id}/tracking
     */
    public function tracking(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::with('scanEvents')
            ->where('id', $id)
            ->orWhere('tracking_number', $id)
            ->first();

        if (!$shipment) {
            return $this->errorResponse('Shipment not found', 404);
        }

        return $this->apiResponse([
            'tracking_number' => $shipment->tracking_number,
            'current_status' => $shipment->status,
            'events' => $shipment->scanEvents->map(fn($event) => [
                'type' => $event->scan_type,
                'location' => $event->location,
                'timestamp' => $event->created_at->toIso8601String(),
                'coordinates' => $event->latitude ? [
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                ] : null,
            ])->toArray(),
        ]);
    }

    /**
     * Calculate shipping rate
     * POST /api/v2/shipments/rate
     */
    public function calculateRate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_branch_id' => 'required|exists:branches,id',
            'dest_branch_id' => 'required|exists:branches,id',
            'weight' => 'required|numeric|min:0',
            'dimensions' => 'nullable|array',
            'service_type' => 'nullable|in:standard,express,same_day',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Basic rate calculation (would be more complex in production)
        $baseRate = 5.00;
        $weightRate = $request->weight * 0.50;
        $serviceMultiplier = match ($request->service_type ?? 'standard') {
            'express' => 1.5,
            'same_day' => 2.0,
            default => 1.0,
        };

        $rate = ($baseRate + $weightRate) * $serviceMultiplier;

        return $this->apiResponse([
            'rate' => round($rate, 2),
            'currency' => 'USD',
            'breakdown' => [
                'base_rate' => $baseRate,
                'weight_charge' => $weightRate,
                'service_multiplier' => $serviceMultiplier,
            ],
            'estimated_delivery' => match ($request->service_type ?? 'standard') {
                'same_day' => 'Today',
                'express' => '1-2 business days',
                default => '3-5 business days',
            },
        ]);
    }

    /**
     * Batch create shipments
     * POST /api/v2/shipments/batch
     */
    public function batchCreate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipments' => 'required|array|max:100',
            'shipments.*.customer_id' => 'required|exists:customers,id',
            'shipments.*.origin_branch_id' => 'required|exists:branches,id',
            'shipments.*.dest_branch_id' => 'required|exists:branches,id',
            'shipments.*.recipient_name' => 'required|string',
            'shipments.*.recipient_phone' => 'required|string',
            'shipments.*.recipient_address' => 'required|string',
            'shipments.*.payment_type' => 'required|in:prepaid,cod',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $created = [];
        $errors = [];

        foreach ($request->shipments as $index => $data) {
            try {
                $shipment = Shipment::create(array_merge($data, [
                    'tracking_number' => Shipment::generateTrackingNumber(),
                    'status' => 'pending',
                ]));

                $created[] = [
                    'index' => $index,
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                ];

                $this->webhookService->dispatchShipmentEvent($shipment, 'created');

            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->apiResponse([
            'created' => $created,
            'errors' => $errors,
            'summary' => [
                'total' => count($request->shipments),
                'successful' => count($created),
                'failed' => count($errors),
            ],
        ], count($errors) > 0 ? 207 : 201);
    }

    /**
     * Cancel shipment
     * POST /api/v2/shipments/{id}/cancel
     */
    public function cancel(Request $request, Shipment $shipment): JsonResponse
    {
        if (in_array($shipment->status, ['delivered', 'cancelled'])) {
            return $this->errorResponse('Cannot cancel shipment in current status', 400);
        }

        $shipment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('reason'),
        ]);

        $this->webhookService->dispatchShipmentEvent($shipment, 'cancelled');

        return $this->apiResponse([
            'shipment' => $this->formatShipment($shipment->fresh()),
        ]);
    }

    /**
     * Format shipment for API response
     */
    protected function formatShipment(Shipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
            'customer' => $shipment->customer ? [
                'id' => $shipment->customer->id,
                'name' => $shipment->customer->name,
            ] : null,
            'origin' => $shipment->originBranch ? [
                'id' => $shipment->originBranch->id,
                'name' => $shipment->originBranch->name,
            ] : null,
            'destination' => $shipment->destBranch ? [
                'id' => $shipment->destBranch->id,
                'name' => $shipment->destBranch->name,
            ] : null,
            'recipient' => [
                'name' => $shipment->recipient_name,
                'phone' => $shipment->recipient_phone,
                'address' => $shipment->recipient_address,
            ],
            'weight' => $shipment->weight,
            'payment_type' => $shipment->payment_type,
            'shipping_cost' => $shipment->shipping_cost,
            'cod_amount' => $shipment->cod_amount,
            'created_at' => $shipment->created_at?->toIso8601String(),
            'delivered_at' => $shipment->delivered_at?->toIso8601String(),
        ];
    }

    /**
     * Standard API response
     */
    protected function apiResponse(array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * Error response
     */
    protected function errorResponse(string $message, int $status = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status,
            ],
        ];

        if ($errors) {
            $response['error']['details'] = $errors;
        }

        return response()->json($response, $status);
    }
}
