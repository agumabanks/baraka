<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ShipmentStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreShipmentEventRequest;
use App\Http\Resources\Api\V1\ScanEventResource;
use App\Models\ScanEvent;
use App\Models\Shipment;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Shipment Events",
 *     description="API Endpoints for shipment scan events and tracking"
 * )
 */
class ShipmentEventController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/shipments/{id}/events",
     *     summary="Get shipment events",
     *     description="Retrieve scan events for a specific shipment",
     *     operationId="getShipmentEvents",
     *     tags={"Shipment Events"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shipment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Events retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="events", type="array", @OA\Items(ref="#/components/schemas/ScanEvent"))
     *             )
     *         )
     *     ),
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
    public function index(Shipment $shipment)
    {
        // Check authorization
        if ($shipment->customer_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return $this->responseWithError('Not authorized to view this shipment', [], 403);
        }

        $events = $shipment->scanEvents()
            ->with(['branch', 'user'])
            ->orderBy('occurred_at', 'desc')
            ->get();

        return $this->responseWithSuccess('Events retrieved successfully', [
            'events' => ScanEventResource::collection($events),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/shipments/{id}/events",
     *     summary="Create shipment event",
     *     description="Create a new scan event for a shipment",
     *     operationId="createShipmentEvent",
     *     tags={"Shipment Events"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shipment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreShipmentEventRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Event created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Event created"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="event", ref="#/components/schemas/ScanEvent")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not authorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StoreShipmentEventRequest $request, Shipment $shipment)
    {
        // Check authorization - drivers and admins can create events
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            $driver = \App\Models\Backend\DeliveryMan::where('user_id', $user->id)->first();
            if (!$driver || $shipment->driver_id !== $driver->id) {
                return $this->responseWithError('Not authorized to create events for this shipment', [], 403);
            }
        }

        $event = ScanEvent::create([
            'sscc' => $request->sscc,
            'type' => $request->type,
            'branch_id' => $request->branch_id,
            'user_id' => $user->id,
            'occurred_at' => $request->occurred_at ?? now(),
            'geojson' => $request->location,
            'note' => $request->note,
        ]);

        // Update shipment status based on scan event
        $shipment->updateStatusFromScan($event);

        // Broadcast status change
        broadcast(new ShipmentStatusChanged($shipment, $event));

        return $this->responseWithSuccess('Event created successfully', [
            'event' => new ScanEventResource($event),
        ], 201);
    }
}