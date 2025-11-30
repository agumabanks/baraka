<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;
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
            ->with(['scanEvents', 'originBranch', 'destBranch', 'trackerEvents' => function ($q) {
                $q->orderByDesc('recorded_at')->limit(25);
            }])
            ->first();

        if (! $shipment) {
            return $this->responseWithError('Shipment not found', [], 404);
        }

        return $this->responseWithSuccess('Tracking information retrieved', [
            'shipment' => $this->buildPayload($shipment),
        ]);
    }

    public function publicShow(Request $request, $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $shipment = Shipment::where('public_token', $token)
            ->with(['originBranch', 'destBranch', 'trackerEvents' => function ($q) {
                $q->orderByDesc('recorded_at')->limit(10);
            }])
            ->firstOrFail();

        $branchId = (int) $request->get('branch');
        if ($branchId && ! in_array($branchId, [$shipment->origin_branch_id, $shipment->dest_branch_id], true)) {
            abort(403, 'Branch mismatch');
        }

        return $this->responseWithSuccess('Tracking information retrieved', [
            'shipment' => $this->buildPayload($shipment),
        ]);
    }

    private function buildPayload(Shipment $shipment): array
    {
        $hasParcels = \Illuminate\Support\Facades\Schema::hasTable('parcels')
            && \Illuminate\Support\Facades\Schema::hasColumn('parcels', 'shipment_id');

        if ($hasParcels) {
            $shipment->loadMissing('parcels');
            $totalWeight = $shipment->parcels->sum('weight_kg');
            $totalParcels = $shipment->parcels->count();
        } else {
            $totalWeight = null;
            $totalParcels = null;
        }

        return [
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'current_status' => $shipment->current_status,
            'origin_branch' => $shipment->originBranch?->name,
            'dest_branch' => $shipment->destBranch?->name,
            'total_weight' => $totalWeight,
            'total_parcels' => $totalParcels,
            'last_scan' => $shipment->last_scan,
            'scan_events' => $shipment->scanEvents->map(function ($event) {
                return [
                    'type' => $event->type,
                    'occurred_at' => $event->occurred_at,
                    'location' => $event->location,
                ];
            }),
            'tracker_events' => $shipment->trackerEvents?->map(function ($event) {
                return [
                    'tracker_id' => $event->tracker_id,
                    'temperature_c' => $event->temperature_c,
                    'battery_percent' => $event->battery_percent,
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                    'recorded_at' => $event->recorded_at,
                ];
            }) ?? [],
            'created_at' => $shipment->created_at,
            'public_link' => url()->signedRoute('public.track', [
                'token' => $shipment->public_token,
                'branch' => $shipment->origin_branch_id,
            ]),
        ];
    }
}
