<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignDriverRequest;
use App\Jobs\OptimizeRoutes;
use App\Models\DeliveryMan;
use App\Models\Shipment;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Dispatch",
 *     description="API Endpoints for shipment dispatch and driver assignment"
 * )
 */
class DispatchController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/dispatch/assign/{shipment}",
     *     summary="Assign driver to shipment",
     *     description="Assign a driver to a specific shipment",
     *     operationId="assignDriver",
     *     tags={"Dispatch"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="shipment",
     *         in="path",
     *         description="Shipment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"driver_id"},
     *
     *             @OA\Property(property="driver_id", type="integer", description="Driver ID to assign")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Driver assigned successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Driver assigned")
     *         )
     *     )
     * )
     */
    public function assign(AssignDriverRequest $request, Shipment $shipment)
    {
        $this->authorize('update', $shipment);

        $driver = DeliveryMan::findOrFail($request->driver_id);

        // Assign driver to shipment
        $shipment->update(['assigned_driver_id' => $driver->id]);

        return $this->responseWithSuccess('Driver assigned', [], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/dispatch/optimize",
     *     summary="Optimize routes",
     *     description="Queue route optimization for the user's shipments",
     *     operationId="optimizeRoutes",
     *     tags={"Dispatch"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Route optimization queued successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Route optimization queued")
     *         )
     *     )
     * )
     */
    public function optimize(Request $request)
    {
        // Queue optimization job
        OptimizeRoutes::dispatch(auth()->id());

        return $this->responseWithSuccess('Route optimization queued', [], 200);
    }
}
