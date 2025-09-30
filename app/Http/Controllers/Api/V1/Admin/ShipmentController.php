<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateShipmentStatusRequest;
use App\Http\Resources\Api\V1\ShipmentResource;
use App\Jobs\ExportShipments;
use App\Models\Shipment;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin Shipments",
 *     description="API Endpoints for admin shipment management"
 * )
 */
class ShipmentController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/admin/shipments",
     *     summary="List all shipments",
     *     description="Retrieve paginated list of all shipments with optional filters",
     *     operationId="getAdminShipments",
     *     tags={"Admin Shipments"},
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
     *         name="customer_id",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
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
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Shipment::query();

        if ($request->status) {
            $query->where('current_status', $request->status);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        $shipments = $query->paginate($request->per_page ?? 15);

        return $this->responseWithSuccess('Shipments retrieved', [
            'shipments' => ShipmentResource::collection($shipments),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/shipments/{id}",
     *     summary="Get shipment details",
     *     description="Retrieve detailed information about a specific shipment",
     *     operationId="getAdminShipment",
     *     tags={"Admin Shipments"},
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
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - admin access required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shipment not found"
     *     )
     * )
     */
    public function show(Shipment $shipment)
    {
        $shipment->load(['parcels', 'scanEvents', 'originBranch', 'destBranch', 'customer']);

        return $this->responseWithSuccess('Shipment retrieved', [
            'shipment' => new ShipmentResource($shipment),
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/admin/shipments/{id}/status",
     *     summary="Update shipment status",
     *     description="Update the status of a specific shipment",
     *     operationId="updateShipmentStatus",
     *     tags={"Admin Shipments"},
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
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"status"},
     *
     *             @OA\Property(property="status", type="string", description="New shipment status")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shipment status updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Shipment status updated"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="shipment", ref="#/components/schemas/Shipment")
     *             )
     *         )
     *     )
     * )
     */
    public function updateStatus(UpdateShipmentStatusRequest $request, Shipment $shipment)
    {
        $shipment->update(['current_status' => $request->status]);

        return $this->responseWithSuccess('Shipment status updated', [
            'shipment' => new ShipmentResource($shipment),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/shipments/export",
     *     summary="Export shipments",
     *     description="Queue shipment export job",
     *     operationId="exportShipments",
     *     tags={"Admin Shipments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="filters", type="object", description="Export filters")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Export queued successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Export queued")
     *         )
     *     )
     * )
     */
    public function export(Request $request)
    {
        ExportShipments::dispatch($request->all(), auth()->id());

        return $this->responseWithSuccess('Export queued', [], 200);
    }
}
