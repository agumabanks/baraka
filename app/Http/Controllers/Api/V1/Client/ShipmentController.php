<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CancelShipmentRequest;
use App\Http\Requests\Api\V1\StoreShipmentRequest;
use App\Http\Resources\Api\V1\ShipmentResource;
use App\Models\Shipment;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Client Shipments",
 *     description="API Endpoints for client shipment management"
 * )
 */
class ShipmentController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/shipments",
     *     summary="List user's shipments",
     *     description="Retrieve paginated list of authenticated user's shipments with optional filters",
     *     operationId="getClientShipments",
     *     tags={"Client Shipments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by shipment status",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query for tracking number or reference",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shipments retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Shipments retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="shipments", type="array", @OA\Items(ref="#/components/schemas/Shipment"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Shipment::where('customer_id', Auth::id());

        if ($request->status) {
            $query->where('current_status', $request->status);
        }

        if ($request->q) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%'.$request->q.'%')
                    ->orWhereHas('parcels', function ($parcelQuery) use ($request) {
                        $parcelQuery->where('sscc', 'like', '%'.$request->q.'%');
                    });
            });
        }

        $shipments = $query->with(['parcels', 'originBranch', 'destBranch'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->responseWithSuccess('Shipments retrieved', [
            'shipments' => ShipmentResource::collection($shipments),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/shipments/{id}",
     *     summary="Get shipment details",
     *     description="Retrieve detailed information about a specific shipment",
     *     operationId="getClientShipment",
     *     tags={"Client Shipments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shipment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shipment retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Shipment retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="shipment", ref="#/components/schemas/Shipment")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not owner"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipment not found"
     *     )
     * )
     */
    public function show(Shipment $shipment)
    {
        $this->authorize('view', $shipment);

        $shipment->load(['parcels', 'scanEvents', 'originBranch', 'destBranch']);

        return $this->responseWithSuccess('Shipment retrieved', [
            'shipment' => new ShipmentResource($shipment),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/shipments",
     *     summary="Create new shipment",
     *     description="Create a new shipment for the authenticated user",
     *     operationId="createClientShipment",
     *     tags={"Client Shipments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/StoreShipmentRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Shipment created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Shipment created"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="shipment", ref="#/components/schemas/Shipment")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StoreShipmentRequest $request)
    {
        $shipment = Shipment::create([
            'customer_id' => Auth::id(),
            'origin_branch_id' => $request->origin_branch_id,
            'dest_branch_id' => $request->dest_branch_id,
            'service_level' => $request->service_level,
            'incoterm' => $request->incoterm,
            'price_amount' => $request->price_amount,
            'currency' => $request->currency,
            'metadata' => $request->metadata,
            'created_by' => Auth::id(),
        ]);

        // TODO: Create parcels, calculate pricing, etc. based on existing domain logic

        return $this->responseWithSuccess('Shipment created', [
            'shipment' => new ShipmentResource($shipment),
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/shipments/{id}/cancel",
     *     summary="Cancel shipment",
     *     description="Cancel a shipment if it hasn't been processed yet",
     *     operationId="cancelClientShipment",
     *     tags={"Client Shipments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shipment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="reason", type="string", example="Customer requested cancellation")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shipment cancelled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Shipment cancelled")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - cannot cancel"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipment not found"
     *     )
     * )
     */
    public function cancel(CancelShipmentRequest $request, Shipment $shipment)
    {
        $this->authorize('cancel', $shipment);

        $shipment->update([
            'current_status' => 'cancelled',
            'metadata' => array_merge($shipment->metadata ?? [], [
                'cancelled_at' => now(),
                'cancel_reason' => $request->reason,
            ]),
        ]);

        return $this->responseWithSuccess('Shipment cancelled');
    }
}
