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
}
