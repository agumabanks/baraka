<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Enums\ShipmentStatus;
use App\Models\BranchAlert;
use App\Models\Shipment;
use App\Models\WhLocation;
use App\Services\WarehouseService;
use App\Support\BranchCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    use ResolvesBranch;

    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $locations = BranchCache::rememberBoard($branch->id, 'warehouse', function () use ($branch) {
            return WhLocation::query()
                ->where('branch_id', $branch->id)
                ->orderBy('code')
                ->get();
        });

        $inbound = Shipment::query()
            ->where('dest_branch_id', $branch->id)
            ->whereIn('current_status', [ShipmentStatus::LINEHAUL_ARRIVED->value, ShipmentStatus::AT_DESTINATION_HUB->value ?? 'AT_DESTINATION_HUB'])
            ->count();

        $outbound = Shipment::query()
            ->where('origin_branch_id', $branch->id)
            ->whereIn('current_status', [ShipmentStatus::AT_ORIGIN_HUB->value, ShipmentStatus::BAGGED->value])
            ->count();

        $alerts = BranchAlert::query()
            ->where('branch_id', $branch->id)
            ->where('alert_type', 'WAREHOUSE')
            ->latest()
            ->limit(5)
            ->get();

        return view('branch.warehouse', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'locations' => $locations,
            'inbound' => $inbound,
            'outbound' => $outbound,
            'alerts' => $alerts,
        ]);
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'code' => 'required|string|max:32',
            'type' => 'required|string|max:60',
            'capacity' => 'nullable|integer',
            'status' => 'nullable|string|max:40',
        ]);

        WhLocation::create([
            'branch_id' => $branch->id,
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'capacity' => $data['capacity'] ?? null,
            'status' => $data['status'] ?? 'ACTIVE',
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Location added.');
    }

    public function updateLocation(Request $request, WhLocation $location): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($location->branch_id === $branch->id, 403);

        $data = $request->validate([
            'status' => 'nullable|string|max:40',
            'capacity' => 'nullable|integer',
            'parent_id' => 'nullable|exists:wh_locations,id',
            'barcode' => 'nullable|string|unique:wh_locations,barcode,' . $location->id,
        ]);

        $location->fill($data);
        $location->save();

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Location updated.');
    }

    /**
     * View inventory in a location
     */
    public function inventory(Request $request, WhLocation $location): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        
        abort_unless($location->branch_id === $user->branch_id, 403);

        $parcels = $this->warehouseService->getInventory($location, $request->boolean('recursive', true));

        return view('branch.warehouse.inventory', compact('location', 'parcels'));
    }

    /**
     * Scan a parcel to move it (Move/Putaway)
     */
    public function scanMove(Request $request)
    {
        $request->validate([
            'parcel_barcode' => 'required|string',
            'location_barcode' => 'required|string',
        ]);

        try {
            $parcel = \App\Models\Parcel::where('barcode', $request->parcel_barcode)->firstOrFail();
            $location = WhLocation::where('barcode', $request->location_barcode)
                ->where('branch_id', $request->user()->branch_id)
                ->firstOrFail();

            $this->warehouseService->moveParcel($parcel, $location, $request->user());

            return response()->json([
                'success' => true,
                'message' => "Parcel moved to {$location->code}",
                'location' => $location->code
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function picking(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Mock pick lists data (replace with actual DB table when created)
        $pickLists = collect();

        // Get shipments ready for picking
        $readyShipments = Shipment::query()
            ->where('origin_branch_id', $branch->id)
            ->whereIn('current_status', [ShipmentStatus::READY_FOR_DISPATCH->value, ShipmentStatus::PROCESSING->value])
            ->with(['customer:id,name', 'destBranch:id,name'])
            ->latest()
            ->limit(50)
            ->get();

        $workers = \App\Models\Backend\BranchWorker::query()
            ->with('user:id,name')
            ->where('branch_id', $branch->id)
            ->where('status', 1)
            ->get();

        // Stats
        $pendingCount = 0;
        $inProgressCount = 0;
        $completedTodayCount = 0;
        $totalItemsCount = 0;

        return view('branch.warehouse_picking', compact(
            'branch',
            'pickLists',
            'readyShipments',
            'workers',
            'pendingCount',
            'inProgressCount',
            'completedTodayCount',
            'totalItemsCount'
        ));
    }

    /**
     * Process picking action
     */
    public function processPicking(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'exists:shipments,id',
            'worker_id' => 'nullable|exists:branch_workers,id',
        ]);

        $count = Shipment::where('origin_branch_id', $branch->id)
            ->whereIn('id', $data['shipment_ids'])
            ->update([
                'current_status' => ShipmentStatus::READY_FOR_DISPATCH->value,
                'picked_at' => now(),
                'picked_by' => $user->id,
                'assigned_worker_id' => $data['worker_id'] ?? null,
            ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', "{$count} shipments marked as picked.");
    }

    /**
     * Inventory overview with location tracking
     */
    public function inventoryOverview(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Get parcels/shipments in warehouse
        $inWarehouse = Shipment::query()
            ->where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->whereIn('current_status', [
                ShipmentStatus::AT_ORIGIN_HUB->value,
                ShipmentStatus::AT_DESTINATION_HUB->value,
                ShipmentStatus::BAGGED->value,
                ShipmentStatus::LINEHAUL_ARRIVED->value,
            ])
            ->with(['customer:id,name', 'originBranch:id,name,code', 'destBranch:id,name,code'])
            ->latest()
            ->paginate(20);

        // Location summary
        $locationSummary = WhLocation::where('branch_id', $branch->id)
            ->withCount(['shipments' => function ($q) {
                $q->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB', 'BAGGED']);
            }])
            ->get()
            ->groupBy('type');

        // Age analysis
        $ageAnalysis = [
            '0-24h' => Shipment::where(function ($q) use ($branch) {
                    $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                })
                ->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB'])
                ->where('updated_at', '>=', now()->subDay())
                ->count(),
            '24-48h' => Shipment::where(function ($q) use ($branch) {
                    $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                })
                ->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB'])
                ->whereBetween('updated_at', [now()->subDays(2), now()->subDay()])
                ->count(),
            '48h+' => Shipment::where(function ($q) use ($branch) {
                    $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                })
                ->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB'])
                ->where('updated_at', '<', now()->subDays(2))
                ->count(),
        ];

        return view('branch.warehouse.inventory_overview', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'inWarehouse' => $inWarehouse,
            'locationSummary' => $locationSummary,
            'ageAnalysis' => $ageAnalysis,
        ]);
    }

    /**
     * Receiving dock management
     */
    public function receiving(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Expected arrivals (inbound in transit)
        $expectedArrivals = Shipment::query()
            ->where('dest_branch_id', $branch->id)
            ->whereIn('current_status', [
                ShipmentStatus::LINEHAUL_DEPARTED->value,
                ShipmentStatus::IN_TRANSIT->value,
            ])
            ->with(['originBranch:id,name,code', 'customer:id,name'])
            ->orderBy('expected_arrival_at')
            ->limit(50)
            ->get();

        // Recently received today
        $receivedToday = Shipment::query()
            ->where('dest_branch_id', $branch->id)
            ->where('current_status', ShipmentStatus::LINEHAUL_ARRIVED->value)
            ->whereDate('updated_at', today())
            ->count();

        // Pending processing (arrived but not put away)
        $pendingPutaway = Shipment::query()
            ->where('dest_branch_id', $branch->id)
            ->where('current_status', ShipmentStatus::LINEHAUL_ARRIVED->value)
            ->whereNull('warehouse_location_id')
            ->with(['originBranch:id,name,code', 'customer:id,name'])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        // Available receiving locations
        $receivingLocations = WhLocation::where('branch_id', $branch->id)
            ->where('type', 'RECEIVING')
            ->where('status', 'ACTIVE')
            ->get();

        return view('branch.warehouse.receiving', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'expectedArrivals' => $expectedArrivals,
            'receivedToday' => $receivedToday,
            'pendingPutaway' => $pendingPutaway,
            'receivingLocations' => $receivingLocations,
        ]);
    }

    /**
     * Process receiving scan
     */
    public function processReceiving(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'tracking_number' => 'required|string',
            'location_id' => 'nullable|exists:wh_locations,id',
            'condition' => 'nullable|string|in:good,damaged,partial',
            'notes' => 'nullable|string|max:500',
        ]);

        $shipment = Shipment::where('tracking_number', $data['tracking_number'])
            ->where('dest_branch_id', $branch->id)
            ->first();

        if (!$shipment) {
            return back()->with('error', 'Shipment not found or not destined for this branch.');
        }

        $shipment->update([
            'current_status' => ShipmentStatus::LINEHAUL_ARRIVED->value,
            'warehouse_location_id' => $data['location_id'],
            'receiving_condition' => $data['condition'] ?? 'good',
            'receiving_notes' => $data['notes'],
            'received_at' => now(),
            'received_by' => $user->id,
        ]);

        // Create scan event
        \App\Models\ScanEvent::create([
            'shipment_id' => $shipment->id,
            'scan_type' => 'RECEIVED',
            'branch_id' => $branch->id,
            'location_id' => $data['location_id'],
            'scanned_by' => $user->id,
            'scanned_at' => now(),
            'notes' => $data['notes'],
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', "Shipment {$shipment->tracking_number} received successfully.");
    }

    /**
     * Dispatch view (alias for dispatchStaging)
     */
    public function dispatchView(Request $request): View
    {
        return $this->dispatchStaging($request);
    }

    /**
     * Dispatch staging area
     */
    public function dispatchStaging(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Ready for dispatch
        $readyForDispatch = Shipment::query()
            ->where('origin_branch_id', $branch->id)
            ->whereIn('current_status', [
                ShipmentStatus::BAGGED->value,
                ShipmentStatus::READY_FOR_DISPATCH->value,
            ])
            ->with(['destBranch:id,name,code', 'customer:id,name', 'assignedWorker.user:id,name'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->paginate(20);

        // Dispatched today
        $dispatchedToday = Shipment::query()
            ->where('origin_branch_id', $branch->id)
            ->whereIn('current_status', [
                ShipmentStatus::LINEHAUL_DEPARTED->value,
                ShipmentStatus::OUT_FOR_DELIVERY->value,
            ])
            ->whereDate('dispatched_at', today())
            ->count();

        // By destination hub
        $byDestination = Shipment::query()
            ->where('origin_branch_id', $branch->id)
            ->whereIn('current_status', ['BAGGED', 'READY_FOR_DISPATCH'])
            ->selectRaw('dest_branch_id, COUNT(*) as count')
            ->groupBy('dest_branch_id')
            ->with('destBranch:id,name,code')
            ->get();

        // Available vehicles/couriers
        $availableCouriers = \App\Models\Backend\BranchWorker::query()
            ->where('branch_id', $branch->id)
            ->where('status', 1)
            ->whereIn('role', [\App\Enums\BranchWorkerRole::COURIER, \App\Enums\BranchWorkerRole::DRIVER])
            ->with('user:id,name')
            ->get();

        return view('branch.warehouse.dispatch', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'readyForDispatch' => $readyForDispatch,
            'dispatchedToday' => $dispatchedToday,
            'byDestination' => $byDestination,
            'availableCouriers' => $availableCouriers,
        ]);
    }

    /**
     * Process dispatch
     */
    public function processDispatch(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'exists:shipments,id',
            'worker_id' => 'nullable|exists:branch_workers,id',
            'dispatch_type' => 'required|in:linehaul,delivery',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $newStatus = $data['dispatch_type'] === 'linehaul' 
            ? ShipmentStatus::LINEHAUL_DEPARTED->value
            : ShipmentStatus::OUT_FOR_DELIVERY->value;

        Shipment::whereIn('id', $data['shipment_ids'])
            ->where('origin_branch_id', $branch->id)
            ->update([
                'current_status' => $newStatus,
                'dispatched_at' => now(),
                'dispatched_by' => $user->id,
                'assigned_worker_id' => $data['worker_id'],
            ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', count($data['shipment_ids']) . ' shipments dispatched successfully.');
    }

    /**
     * Warehouse zones management
     */
    public function zones(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $zones = WhLocation::where('branch_id', $branch->id)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->withCount(['shipments' => function ($sq) {
                    $sq->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB', 'BAGGED']);
                }]);
            }])
            ->withCount(['shipments' => function ($q) {
                $q->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB', 'BAGGED']);
            }])
            ->orderBy('code')
            ->get();

        $zoneTypes = ['RECEIVING', 'STORAGE', 'STAGING', 'DISPATCH', 'RETURNS', 'CUSTOMS', 'COLD_STORAGE'];

        return view('branch.warehouse.zones', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'zones' => $zones,
            'zoneTypes' => $zoneTypes,
        ]);
    }

    /**
     * Store new zone
     */
    public function storeZone(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'code' => 'required|string|max:32',
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:60',
            'capacity' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|exists:wh_locations,id',
            'temperature_controlled' => 'boolean',
            'priority' => 'nullable|integer',
        ]);

        WhLocation::create([
            'branch_id' => $branch->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'type' => $data['type'],
            'capacity' => $data['capacity'],
            'parent_id' => $data['parent_id'],
            'temperature_controlled' => $data['temperature_controlled'] ?? false,
            'priority' => $data['priority'] ?? 0,
            'status' => 'ACTIVE',
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Zone created successfully.');
    }

    /**
     * Cycle count management
     */
    public function cycleCount(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Get locations for cycle counting
        $locations = WhLocation::where('branch_id', $branch->id)
            ->where('status', 'ACTIVE')
            ->withCount(['shipments' => function ($q) {
                $q->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB', 'BAGGED']);
            }])
            ->orderBy('last_counted_at', 'asc')
            ->get();

        // Recent counts
        $recentCounts = \Illuminate\Support\Facades\DB::table('cycle_counts')
            ->where('branch_id', $branch->id)
            ->orderByDesc('counted_at')
            ->limit(20)
            ->get();

        // Discrepancies
        $discrepancies = \Illuminate\Support\Facades\DB::table('cycle_counts')
            ->where('branch_id', $branch->id)
            ->where('discrepancy', '!=', 0)
            ->orderByDesc('counted_at')
            ->limit(10)
            ->get();

        return view('branch.warehouse.cycle_count', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'locations' => $locations,
            'recentCounts' => $recentCounts,
            'discrepancies' => $discrepancies,
        ]);
    }

    /**
     * Store cycle count result
     */
    public function storeCycleCount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'location_id' => 'required|exists:wh_locations,id',
            'expected_count' => 'required|integer|min:0',
            'actual_count' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $discrepancy = $data['actual_count'] - $data['expected_count'];

        \Illuminate\Support\Facades\DB::table('cycle_counts')->insert([
            'branch_id' => $branch->id,
            'location_id' => $data['location_id'],
            'expected_count' => $data['expected_count'],
            'actual_count' => $data['actual_count'],
            'discrepancy' => $discrepancy,
            'counted_by' => $user->id,
            'counted_at' => now(),
            'notes' => $data['notes'],
            'created_at' => now(),
        ]);

        // Update location last counted
        WhLocation::where('id', $data['location_id'])->update([
            'last_counted_at' => now(),
        ]);

        BranchCache::flushForBranch($branch->id);

        $message = $discrepancy === 0 
            ? 'Cycle count completed. No discrepancies found.'
            : "Cycle count completed. Discrepancy of {$discrepancy} items found.";

        return back()->with($discrepancy === 0 ? 'success' : 'warning', $message);
    }

    /**
     * Capacity (alias for capacityReport)
     */
    public function capacity(Request $request): View
    {
        return $this->capacityReport($request);
    }

    /**
     * Capacity utilization report
     */
    public function capacityReport(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Locations with capacity info
        $locations = WhLocation::where('branch_id', $branch->id)
            ->whereNotNull('capacity')
            ->where('capacity', '>', 0)
            ->withCount(['shipments' => function ($q) {
                $q->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB', 'BAGGED']);
            }])
            ->get()
            ->map(function ($loc) {
                $loc->utilization = $loc->capacity > 0 
                    ? round(($loc->shipments_count / $loc->capacity) * 100, 1) 
                    : 0;
                return $loc;
            });

        // Overall capacity
        $totalCapacity = $locations->sum('capacity');
        $totalUsed = $locations->sum('shipments_count');
        $overallUtilization = $totalCapacity > 0 ? round(($totalUsed / $totalCapacity) * 100, 1) : 0;

        // Critical zones (>80% capacity)
        $criticalZones = $locations->filter(fn($l) => $l->utilization > 80);

        // Historical trend (last 7 days)
        $historicalTrend = collect(range(6, 0))->map(function ($daysAgo) use ($branch) {
            $date = now()->subDays($daysAgo)->toDateString();
            return [
                'date' => $date,
                'count' => Shipment::where(function ($q) use ($branch) {
                        $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                    })
                    ->whereIn('current_status', ['AT_ORIGIN_HUB', 'AT_DESTINATION_HUB', 'BAGGED'])
                    ->whereDate('updated_at', '<=', $date)
                    ->count(),
            ];
        });

        return view('branch.warehouse.capacity', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'locations' => $locations,
            'totalCapacity' => $totalCapacity,
            'totalUsed' => $totalUsed,
            'overallUtilization' => $overallUtilization,
            'criticalZones' => $criticalZones,
            'historicalTrend' => $historicalTrend,
        ]);
    }
}
