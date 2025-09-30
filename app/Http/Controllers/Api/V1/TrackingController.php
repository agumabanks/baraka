<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Traits\ApiReturnFormatTrait;

/**
 * @OA\Tag(
 *     name="Public Tracking",
 *     description="API Endpoints for public shipment tracking"
 * )
 */
class TrackingController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/tracking/{token}",
     *     summary="Track shipment by public token",
     *     description="Retrieve public tracking information for a shipment using its public token",
     *     operationId="trackShipment",
     *     tags={"Public Tracking"},
     *
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Public tracking token",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tracking information retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tracking information retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="shipment", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="tracking_number", type="string", example="SSCC123456789"),
     *                     @OA\Property(property="current_status", type="string", example="in_transit"),
     *                     @OA\Property(property="origin_branch", type="object"),
     *                     @OA\Property(property="dest_branch", type="object"),
     *                     @OA\Property(property="total_weight", type="number", format="float", example=2.5),
     *                     @OA\Property(property="total_parcels", type="integer", example=1),
     *                     @OA\Property(property="last_scan", type="object"),
     *                     @OA\Property(property="scan_events", type="array", @OA\Items(
     *                         @OA\Property(property="type", type="string", example="out_for_delivery"),
     *                         @OA\Property(property="occurred_at", type="string", format="date-time"),
     *                         @OA\Property(property="location", type="string", example="New York Hub")
     *                     ))
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Shipment not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Shipment not found")
     *         )
     *     )
     * )
     */
    public function show($token)
    {
        $shipment = Shipment::where('public_token', $token)
            ->with(['parcels', 'scanEvents', 'originBranch', 'destBranch'])
            ->first();

        if (! $shipment) {
            return $this->responseWithError('Shipment not found', [], 404);
        }

        return $this->responseWithSuccess('Tracking information retrieved', [
            'shipment' => [
                'id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'current_status' => $shipment->current_status,
                'origin_branch' => $shipment->originBranch?->name,
                'dest_branch' => $shipment->destBranch?->name,
                'total_weight' => $shipment->total_weight,
                'total_parcels' => $shipment->total_parcels,
                'last_scan' => $shipment->last_scan,
                'scan_events' => $shipment->scanEvents->map(function ($event) {
                    return [
                        'type' => $event->type,
                        'occurred_at' => $event->occurred_at,
                        'location' => $event->location,
                    ];
                }),
                'created_at' => $shipment->created_at,
            ],
        ]);
    }
}
