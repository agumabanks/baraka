<?php

namespace App\Http\Controllers\Branch;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\BranchContext;
use App\Services\RealTimeTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    use ResolvesBranch;

    public function __construct(
        protected RealTimeTrackingService $trackingService
    ) {}

    /**
     * Tracking dashboard with live shipment overview
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Out for delivery today
        $outForDelivery = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })
            ->where('current_status', ShipmentStatus::OUT_FOR_DELIVERY->value)
            ->with(['destBranch:id,name,code', 'assignedWorker.user:id,name'])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        // In transit
        $inTransit = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })
            ->whereIn('current_status', [
                ShipmentStatus::LINEHAUL_DEPARTED->value,
                ShipmentStatus::LINEHAUL_ARRIVED->value,
                ShipmentStatus::AT_ORIGIN_HUB->value,
                ShipmentStatus::AT_DESTINATION_HUB->value,
                'IN_TRANSIT',
            ])
            ->with(['originBranch:id,name,code', 'destBranch:id,name,code'])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        // Delayed shipments
        $delayed = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })
            ->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now())
            ->whereNotIn('current_status', [
                ShipmentStatus::DELIVERED->value,
                ShipmentStatus::CANCELLED->value,
                ShipmentStatus::RETURNED->value,
            ])
            ->with(['originBranch:id,name,code', 'destBranch:id,name,code'])
            ->orderBy('expected_delivery_date')
            ->limit(20)
            ->get();

        // Recent deliveries today
        $recentDeliveries = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->whereDate('delivered_at', today())
            ->latest('delivered_at')
            ->limit(12)
            ->get(['id', 'tracking_number', 'delivered_at']);

        // Stats
        $stats = [
            'in_transit' => $inTransit->count(),
            'out_for_delivery' => $outForDelivery->count(),
            'delayed' => $delayed->count(),
            'on_time' => Shipment::where(function ($q) use ($branch) {
                    $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                })
                ->whereNotNull('expected_delivery_date')
                ->where('expected_delivery_date', '>=', now())
                ->whereNotIn('current_status', ['DELIVERED', 'CANCELLED', 'RETURNED'])
                ->count(),
            'avg_transit_hours' => $this->getAverageTransitTime($branch),
        ];

        return view('branch.tracking.dashboard', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'outForDelivery' => $outForDelivery,
            'inTransit' => $inTransit,
            'delayed' => $delayed,
            'recentDeliveries' => $recentDeliveries,
            'stats' => $stats,
        ]);
    }

    /**
     * Quick track multiple shipments
     */
    public function quickTrack(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $validated = $request->validate([
            'tracking_numbers' => 'required|array|max:20',
            'tracking_numbers.*' => 'required|string|max:100',
        ]);

        $shipments = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })
            ->whereIn('tracking_number', $validated['tracking_numbers'])
            ->with(['originBranch:id,name,code', 'destBranch:id,name,code'])
            ->get();

        $results = $shipments->map(function ($shipment) {
            return [
                'id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'current_status' => $shipment->current_status,
                'origin_branch' => $shipment->originBranch?->name,
                'dest_branch' => $shipment->destBranch?->name,
                'created_at' => $shipment->created_at?->format('M d, H:i'),
                'expected_delivery' => $shipment->expected_delivery_date?->format('M d, H:i'),
                'delivered_at' => $shipment->delivered_at?->format('M d, H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'shipments' => $results,
        ]);
    }

    /**
     * Get detailed tracking data for a single shipment
     */
    public function show(Request $request, Shipment $shipment): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if (!in_array($branch->id, [$shipment->origin_branch_id, $shipment->dest_branch_id])) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $data = $this->trackingService->getTrackingData($shipment);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Refresh tracking data
     */
    public function refresh(Request $request, Shipment $shipment): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if (!in_array($branch->id, [$shipment->origin_branch_id, $shipment->dest_branch_id])) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $this->trackingService->invalidateCache($shipment);
        $data = $this->trackingService->getTrackingData($shipment);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get average transit time in hours
     */
    private function getAverageTransitTime($branch): float
    {
        $avg = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->whereNotNull('picked_up_at')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_hours')
            ->value('avg_hours');

        return round($avg ?? 0, 1);
    }
}
