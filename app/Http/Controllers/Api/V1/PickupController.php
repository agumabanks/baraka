<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePickupRequest;
use App\Http\Resources\Api\V1\PickupRequestResource;
use App\Models\PickupRequest;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Pickup Requests",
 *     description="API Endpoints for pickup request management"
 * )
 */
class PickupController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/pickups",
     *     summary="Create pickup request",
     *     description="Create a new pickup request for shipments",
     *     operationId="createPickup",
     *     tags={"Pickup Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StorePickupRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pickup request created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pickup request created"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pickup_request", ref="#/components/schemas/PickupRequest")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StorePickupRequest $request)
    {
        $pickupRequest = PickupRequest::create([
            'merchant_id' => auth()->user()->merchant->id,
            'pickup_date' => $request->pickup_date,
            'pickup_time' => $request->pickup_time,
            'contact_person' => $request->contact_person,
            'contact_phone' => $request->contact_phone,
            'address' => $request->address,
            'instructions' => $request->instructions,
            'status' => 'pending',
        ]);

        return $this->responseWithSuccess('Pickup request created successfully', [
            'pickup_request' => new PickupRequestResource($pickupRequest),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pickups",
     *     summary="List pickup requests",
     *     description="Retrieve paginated list of pickup requests for the authenticated user",
     *     operationId="getPickups",
     *     tags={"Pickup Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by pickup request status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pickup requests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pickup_requests", type="array", @OA\Items(ref="#/components/schemas/PickupRequest"))
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = PickupRequest::where('merchant_id', auth()->user()->merchant->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $pickupRequests = $query->orderBy('created_at', 'desc')
                               ->paginate(20);

        return $this->responseWithSuccess('Pickup requests retrieved successfully', [
            'pickup_requests' => PickupRequestResource::collection($pickupRequests),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/pickups/{pickup}",
     *     summary="Get pickup request details",
     *     description="Retrieve detailed information about a specific pickup request",
     *     operationId="getPickup",
     *     tags={"Pickup Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="pickup",
     *         in="path",
     *         description="Pickup Request ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pickup request retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pickup_request", ref="#/components/schemas/PickupRequest")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not owner"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pickup request not found"
     *     )
     * )
     */
    public function show(PickupRequest $pickup)
    {
        // Check if user owns this pickup request
        if ($pickup->merchant->user_id !== auth()->id()) {
            return $this->responseWithError('Not authorized to view this pickup request', [], 403);
        }

        return $this->responseWithSuccess('Pickup request retrieved successfully', [
            'pickup_request' => new PickupRequestResource($pickup),
        ]);
    }
}