<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\RealTimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ShipmentTrackingController extends Controller
{
    protected $trackingService;

    public function __construct(RealTimeTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Show tracking dashboard
     */
    public function dashboard(): View
    {
        // Get active shipments for real-time tracking
        $activeShipments = Shipment::whereNotIn('status', ['delivered', 'cancelled', 'returned'])
            ->with(['originBranch', 'destBranch'])
            ->latest()
            ->limit(50)
            ->get();

        $stats = [
            'in_transit' => Shipment::where('status', 'in_transit')->count(),
            'out_for_delivery' => Shipment::where('status', 'out_for_delivery')->count(),
            'delayed' => Shipment::where('expected_delivery_date', '<', now())
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->count(),
            'on_time' => Shipment::where('expected_delivery_date', '>=', now())
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->count(),
        ];

        return view('admin.shipments.tracking-dashboard', compact('activeShipments', 'stats'));
    }

    /**
     * Get tracking data for a specific shipment (API)
     */
    public function getTrackingData(Request $request, Shipment $shipment): JsonResponse
    {
        try {
            $data = $this->trackingService->getTrackingData($shipment);
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve tracking data',
            ], 500);
        }
    }

    /**
     * Get multiple shipments tracking data (for live dashboard)
     */
    public function getMultipleTracking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'exists:shipments,id',
        ]);

        try {
            $data = $this->trackingService->getMultipleShipmentsTracking($validated['shipment_ids']);
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve tracking data',
            ], 500);
        }
    }

    /**
     * Show detailed tracking page for a single shipment
     */
    public function show(Shipment $shipment): View
    {
        $trackingData = $this->trackingService->getTrackingData($shipment);
        
        return view('admin.shipments.tracking-detail', compact('shipment', 'trackingData'));
    }

    /**
     * Refresh tracking data (invalidate cache and return fresh data)
     */
    public function refresh(Shipment $shipment): JsonResponse
    {
        $this->trackingService->invalidateCache($shipment);
        
        return $this->getTrackingData(request(), $shipment);
    }
}
