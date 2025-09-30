<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePickupRequest;
use App\Http\Resources\Api\V1\PickupRequestResource;
use App\Models\PickupRequest;
use App\Traits\ApiReturnFormatTrait;

/**
 * @OA\Tag(
 *     name="Pickups",
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
     *     description="Create a new pickup request",
     *     operationId="createPickup",
     *     tags={"Pickups"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/StorePickupRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Pickup request created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pickup request created"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pickup", ref="#/components/schemas/PickupRequest")
     *             )
     *         )
     *     )
     * )
     */
    public function store(StorePickupRequest $request)
    {
        $pickup = PickupRequest::create([
            'merchant_id' => auth()->id(), // Assuming merchant is the user
            ...$request->validated(),
        ]);

        return $this->responseWithSuccess('Pickup request created', [
            'pickup' => new PickupRequestResource($pickup),
        ], 201);
    }
}
