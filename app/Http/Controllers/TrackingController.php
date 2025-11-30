<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\RealTimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    protected RealTimeTrackingService $trackingService;

    public function __construct(RealTimeTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Show the public tracking page
     */
    public function index(): View
    {
        return view('tracking.index');
    }

    /**
     * Track a shipment by tracking number or reference
     */
    public function track(Request $request): View|JsonResponse
    {
        $request->validate([
            'tracking_number' => 'required|string|max:100',
        ]);

        $trackingNumber = trim($request->input('tracking_number'));
        
        // Search by tracking number, waybill, or customer reference
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->orWhere('waybill_number', $trackingNumber)
            ->orWhere('barcode', $trackingNumber)
            ->first();

        if (!$shipment) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipment not found. Please check your tracking number.',
                ], 404);
            }

            return view('tracking.index', [
                'error' => 'Shipment not found. Please check your tracking number.',
                'tracking_number' => $trackingNumber,
            ]);
        }

        // Get tracking data
        $trackingData = $this->trackingService->getTrackingData($shipment);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $trackingData,
            ]);
        }

        return view('tracking.show', [
            'shipment' => $shipment,
            'trackingData' => $trackingData,
        ]);
    }

    /**
     * Show tracking details by tracking number (direct URL)
     */
    public function show(string $trackingNumber): View
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->orWhere('waybill_number', $trackingNumber)
            ->orWhere('barcode', $trackingNumber)
            ->orWhere('public_token', $trackingNumber)
            ->first();

        if (!$shipment) {
            return view('tracking.not-found', [
                'tracking_number' => $trackingNumber,
            ]);
        }

        $trackingData = $this->trackingService->getTrackingData($shipment);

        return view('tracking.show', [
            'shipment' => $shipment,
            'trackingData' => $trackingData,
        ]);
    }

    /**
     * Get tracking data via API (for AJAX updates)
     */
    public function getTrackingData(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->orWhere('waybill_number', $trackingNumber)
            ->orWhere('public_token', $trackingNumber)
            ->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found',
            ], 404);
        }

        $trackingData = $this->trackingService->getTrackingData($shipment);

        return response()->json([
            'success' => true,
            'data' => $trackingData,
        ]);
    }

    /**
     * Subscribe to tracking notifications
     */
    public function subscribeNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'tracking_number' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'notification_types' => 'required|array',
            'notification_types.*' => 'in:status_change,out_for_delivery,delivered,exception',
        ]);

        $shipment = Shipment::where('tracking_number', $request->tracking_number)->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found',
            ], 404);
        }

        // Store notification preferences in shipment metadata
        $metadata = $shipment->metadata ?? [];
        $metadata['notification_subscriptions'] = $metadata['notification_subscriptions'] ?? [];
        
        $subscription = [
            'email' => $request->email,
            'phone' => $request->phone,
            'types' => $request->notification_types,
            'subscribed_at' => now()->toIso8601String(),
        ];
        
        // Check if already subscribed
        $existingIndex = null;
        foreach ($metadata['notification_subscriptions'] as $index => $sub) {
            if ($sub['email'] === $request->email || $sub['phone'] === $request->phone) {
                $existingIndex = $index;
                break;
            }
        }
        
        if ($existingIndex !== null) {
            $metadata['notification_subscriptions'][$existingIndex] = $subscription;
        } else {
            $metadata['notification_subscriptions'][] = $subscription;
        }

        $shipment->update(['metadata' => $metadata]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to tracking notifications',
        ]);
    }
}
