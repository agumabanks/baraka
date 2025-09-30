<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Traits\ApiReturnFormatTrait;

/**
 * @OA\Tag(
 *     name="Admin Metrics",
 *     description="API Endpoints for admin metrics and analytics"
 * )
 */
class MetricsController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/admin/metrics",
     *     summary="Get admin metrics",
     *     description="Retrieve comprehensive metrics and analytics for admin dashboard",
     *     operationId="getAdminMetrics",
     *     tags={"Admin Metrics"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter from date (Y-m-d)",
     *         required=false,
     *
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Metrics retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Metrics retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="metrics", type="object",
     *                     @OA\Property(property="total_shipments", type="integer", example=100),
     *                     @OA\Property(property="pending_shipments", type="integer", example=10),
     *                     @OA\Property(property="delivered_shipments", type="integer", example=85),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=1500.50)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $metrics = [
            'total_shipments' => Shipment::count(),
            'pending_shipments' => Shipment::where('current_status', 'pending')->count(),
            'delivered_shipments' => Shipment::where('current_status', 'delivered')->count(),
            'total_revenue' => Shipment::sum('price_amount'),
        ];

        return $this->responseWithSuccess('Metrics retrieved', [
            'metrics' => $metrics,
        ], 200);
    }
}
