<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignDriverRequest;
use App\Http\Resources\Api\V1\ShipmentResource;
use App\Models\Shipment;
use App\Models\DeliveryMan;
use App\Models\Task;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Dispatch",
 *     description="API Endpoints for dispatch and optimization"
 * )
 */
class DispatchController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/dispatch/assign",
     *     summary="Assign driver to shipment",
     *     description="Manually assign a driver to a shipment",
     *     operationId="assignDriver",
     *     tags={"Dispatch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AssignDriverRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Driver assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Driver assigned"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="shipment", ref="#/components/schemas/Shipment")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - admin access required"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function assign(AssignDriverRequest $request)
    {
        $shipment = Shipment::findOrFail($request->shipment_id);
        $driver = DeliveryMan::findOrFail($request->driver_id);

        // Update shipment with driver
        $shipment->update(['driver_id' => $driver->id]);

        // Create task for the driver
        Task::create([
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'type' => 'delivery',
            'status' => 'assigned',
            'priority' => $request->priority ?? 'normal',
            'scheduled_at' => $request->scheduled_at,
            'metadata' => [
                'assigned_by' => auth()->id(),
                'assignment_notes' => $request->notes,
            ],
        ]);

        return $this->responseWithSuccess('Driver assigned successfully', [
            'shipment' => new ShipmentResource($shipment->fresh()),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/dispatch/optimize",
     *     summary="Optimize dispatch routes",
     *     description="Queue a job to optimize driver assignments and routes",
     *     operationId="optimizeDispatch",
     *     tags={"Dispatch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="hub_id", type="integer", description="Hub ID to optimize"),
     *             @OA\Property(property="date", type="string", format="date", description="Date to optimize")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Optimization queued successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Optimization queued"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="job_id", type="string", example="job-123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - admin access required"
     *     )
     * )
     */
    public function optimize(Request $request)
    {
        // TODO: Queue optimization job
        $jobId = 'opt-' . uniqid();

        // For now, return success
        return $this->responseWithSuccess('Optimization queued successfully', [
            'job_id' => $jobId,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dispatch/unassigned",
     *     summary="Get unassigned shipments",
     *     description="Retrieve shipments that need driver assignment",
     *     operationId="getUnassignedShipments",
     *     tags={"Dispatch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="hub_id",
     *         in="query",
     *         description="Filter by hub",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unassigned shipments retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="shipments", type="array", @OA\Items(ref="#/components/schemas/Shipment"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - admin access required"
     *     )
     * )
     */
    public function unassigned(Request $request)
    {
        $query = Shipment::whereNull('driver_id')
            ->whereIn('current_status', ['created', 'handed_over', 'arrive']);

        if ($request->hub_id) {
            $query->where(function ($q) use ($request) {
                $q->where('origin_branch_id', $request->hub_id)
                  ->orWhere('dest_branch_id', $request->hub_id);
            });
        }

        $shipments = $query->with(['customer', 'originBranch', 'destBranch'])
                          ->orderBy('created_at', 'asc')
                          ->paginate(20);

        return $this->responseWithSuccess('Unassigned shipments retrieved', [
            'shipments' => ShipmentResource::collection($shipments),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dispatch/drivers",
     *     summary="Get available drivers",
     *     description="Retrieve list of available drivers for assignment",
     *     operationId="getAvailableDrivers",
     *     tags={"Dispatch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Available drivers retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="drivers", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="current_tasks", type="integer"),
     *                     @OA\Property(property="location", type="object")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - admin access required"
     *     )
     * )
     */
    public function drivers()
    {
        $drivers = DeliveryMan::with(['user', 'currentLocation'])
            ->withCount(['tasks' => function ($query) {
                $query->whereIn('status', ['assigned', 'in_progress']);
            }])
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->user->name,
                    'phone' => $driver->user->mobile,
                    'current_tasks' => $driver->tasks_count,
                    'location' => $driver->currentLocation,
                    'is_available' => $driver->tasks_count < 5, // Max 5 concurrent tasks
                ];
            });

        return $this->responseWithSuccess('Available drivers retrieved', [
            'drivers' => $drivers,
        ]);
    }
}