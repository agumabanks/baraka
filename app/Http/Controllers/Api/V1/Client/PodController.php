<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Events\ShipmentStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePodRequest;
use App\Models\ScanEvent;
use App\Models\Shipment;
use App\Traits\ApiReturnFormatTrait;

/**
 * @OA\Tag(
 *     name="POD",
 *     description="API Endpoints for Proof of Delivery management"
 * )
 */
class PodController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/shipments/{id}/pod",
     *     summary="Submit POD",
     *     description="Submit proof of delivery for a shipment",
     *     operationId="submitPOD",
     *     tags={"POD"},
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
     *             required={"otp"},
     *
     *             @OA\Property(property="otp", type="string", description="OTP for verification"),
     *             @OA\Property(property="location", type="string", description="Delivery location"),
     *             @OA\Property(property="notes", type="string", description="Additional notes"),
     *             @OA\Property(property="signature", type="string", format="binary", description="Signature file")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="POD submitted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="POD submitted"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="event", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function store(StorePodRequest $request, Shipment $shipment)
    {
        $this->authorize('pod', $shipment);

        // Update shipment status to delivered
        $shipment->update(['current_status' => \App\Enums\ShipmentStatus::DELIVERED]);

        // Broadcast status update
        broadcast(new ShipmentStatusUpdated($shipment));

        // Create POD event
        $event = ScanEvent::create([
            'shipment_id' => $shipment->id,
            'type' => \App\Enums\ScanType::DELIVERED,
            'occurred_at' => now(),
            'location' => $request->location,
            'notes' => 'POD: '.$request->notes,
        ]);

        // Handle file upload if provided
        if ($request->hasFile('signature')) {
            // Store signature file
            $path = $request->file('signature')->store('pods');
            $event->update(['metadata' => ['signature_path' => $path]]);
        }

        return $this->responseWithSuccess('POD submitted', [
            'event' => $event,
        ], 201);
    }
}
