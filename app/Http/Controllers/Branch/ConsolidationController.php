<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\Consolidation;
use App\Models\ConsolidationRule;
use App\Models\Shipment;
use App\Services\BranchContext;
use App\Services\ConsolidationService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ConsolidationController
 * 
 * Manages groupage consolidation operations for DHL-style mother/baby shipments
 */
class ConsolidationController extends Controller
{
    /**
     * Display consolidations list
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = BranchContext::currentId();
        
        if (!PermissionService::hasCapability($user, 'consolidation.view')) {
            abort(403, 'Unauthorized to view consolidations');
        }

        $consolidations = Consolidation::where('branch_id', $branchId)
            ->with(['destinationBranch', 'createdBy'])
            ->withCount('babyShipments')
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $statistics = ConsolidationService::getBranchStatistics(
            Branch::find($branchId),
            now()->subMonth(),
            now()
        );

        return view('branch.consolidations.index', compact('consolidations', 'statistics'));
    }

    /**
     * Show consolidation details
     */
    public function show(Consolidation $consolidation)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'consolidation.view')) {
            abort(403, 'Unauthorized to view consolidation');
        }

        $consolidation->load([
            'babyShipments.parcels',
            'babyShipments.client',
            'destinationBranch',
            'createdBy',
            'lockedBy',
            'dispatchedBy',
            'deconsolidationEvents.performedBy',
        ]);

        return view('branch.consolidations.show', compact('consolidation'));
    }

    /**
     * Create new consolidation
     */
    public function create()
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'consolidation.create')) {
            abort(403, 'Unauthorized to create consolidations');
        }

        $branches = Branch::active()->orderBy('name')->get();
        
        return view('branch.consolidations.create', compact('branches'));
    }

    /**
     * Store new consolidation
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'consolidation.create')) {
            abort(403, 'Unauthorized to create consolidations');
        }

        $validated = $request->validate([
            'type' => 'required|in:BBX,LBX',
            'destination' => 'required|string|max:255',
            'destination_branch_id' => 'nullable|exists:branches,id',
            'max_pieces' => 'nullable|integer|min:1',
            'max_weight_kg' => 'nullable|numeric|min:0',
            'max_volume_cbm' => 'nullable|numeric|min:0',
            'cutoff_time' => 'nullable|date|after:now',
            'transport_mode' => 'nullable|string|in:AIR,SEA,ROAD,RAIL',
        ]);

        $branch = Branch::findOrFail(BranchContext::currentId());

        $consolidation = ConsolidationService::createConsolidation(
            $branch,
            $validated['type'],
            $validated['destination'],
            $user,
            $validated
        );

        return redirect()
            ->route('branch.consolidations.show', $consolidation)
            ->with('success', 'Consolidation created successfully');
    }

    /**
     * Add shipment to consolidation
     */
    public function addShipment(Request $request, Consolidation $consolidation)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'consolidation.manage')) {
            abort(403, 'Unauthorized to manage consolidations');
        }

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);

        if (!$consolidation->canAcceptShipment($shipment)) {
            return back()->with('error', 'Consolidation cannot accept this shipment');
        }

        $added = $consolidation->addShipment($shipment, $user);

        if ($added) {
            return back()->with('success', "Shipment {$shipment->tracking_number} added to consolidation");
        }

        return back()->with('error', 'Failed to add shipment to consolidation');
    }

    /**
     * Remove shipment from consolidation
     */
    public function removeShipment(Request $request, Consolidation $consolidation)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'consolidation.manage')) {
            abort(403, 'Unauthorized to manage consolidations');
        }

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);

        $removed = $consolidation->removeShipment($shipment, $user);

        if ($removed) {
            return back()->with('success', "Shipment {$shipment->tracking_number} removed from consolidation");
        }

        return back()->with('error', 'Failed to remove shipment from consolidation');
    }

    /**
     * Lock consolidation
     */
    public function lock(Consolidation $consolidation)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'consolidation.lock')) {
            abort(403, 'Unauthorized to lock consolidations');
        }

        $locked = $consolidation->lock($user);

        if ($locked) {
            return back()->with('success', 'Consolidation locked successfully');
        }

        return back()->with('error', 'Failed to lock consolidation');
    }

    /**
     * Dispatch consolidation
     */
    public function dispatch(Request $request, Consolidation $consolidation)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'consolidation.dispatch')) {
            abort(403, 'Unauthorized to dispatch consolidations');
        }

        $validated = $request->validate([
            'awb_number' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:50',
        ]);

        $dispatched = $consolidation->dispatch(
            $user,
            $validated['awb_number'] ?? null,
            $validated['vehicle_number'] ?? null
        );

        if ($dispatched) {
            return back()->with('success', 'Consolidation dispatched successfully');
        }

        return back()->with('error', 'Failed to dispatch consolidation');
    }

    /**
     * Mark consolidation as arrived
     */
    public function markArrived(Consolidation $consolidation)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'warehouse.receive')) {
            abort(403, 'Unauthorized to mark arrivals');
        }

        $arrived = $consolidation->markArrived($user);

        if ($arrived) {
            return back()->with('success', 'Consolidation marked as arrived');
        }

        return back()->with('error', 'Failed to mark consolidation as arrived');
    }

    /**
     * Start deconsolidation process
     */
    public function startDeconsolidation(Consolidation $consolidation)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'warehouse.receive')) {
            abort(403, 'Unauthorized to deconsolidate');
        }

        $started = $consolidation->startDeconsolidation($user);

        if ($started) {
            return redirect()
                ->route('branch.consolidations.deconsolidate', $consolidation)
                ->with('success', 'Deconsolidation started');
        }

        return back()->with('error', 'Failed to start deconsolidation');
    }

    /**
     * Deconsolidation screen
     */
    public function deconsolidate(Consolidation $consolidation)
    {
        $user = Auth::user();
        
        if (!PermissionService::hasCapability($user, 'warehouse.receive')) {
            abort(403, 'Unauthorized to deconsolidate');
        }

        $consolidation->load([
            'babyShipments.parcels',
            'babyShipments.client',
            'deconsolidationEvents' => function ($query) {
                $query->orderBy('occurred_at', 'desc');
            },
        ]);

        return view('branch.consolidations.deconsolidate', compact('consolidation'));
    }

    /**
     * Scan baby shipment during deconsolidation
     */
    public function scanShipment(Request $request, Consolidation $consolidation)
    {
        $user = Auth::user();
        $branch = Branch::findOrFail(BranchContext::currentId());
        
        if (!PermissionService::hasCapability($user, 'warehouse.receive')) {
            abort(403, 'Unauthorized to scan shipments');
        }

        $validated = $request->validate([
            'tracking_number' => 'required|string',
        ]);

        $shipment = Shipment::where('tracking_number', $validated['tracking_number'])->first();

        if (!$shipment) {
            return response()->json(['success' => false, 'message' => 'Shipment not found'], 404);
        }

        $scanned = ConsolidationService::scanBabyShipment($consolidation, $shipment, $user, $branch);

        if ($scanned) {
            return response()->json([
                'success' => true,
                'message' => 'Shipment scanned successfully',
                'shipment' => $shipment->load('parcels', 'client'),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to scan shipment'], 400);
    }

    /**
     * Release baby shipment
     */
    public function releaseShipment(Request $request, Consolidation $consolidation)
    {
        $user = Auth::user();
        $branch = Branch::findOrFail(BranchContext::currentId());
        
        if (!PermissionService::hasCapability($user, 'warehouse.receive')) {
            abort(403, 'Unauthorized to release shipments');
        }

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);

        $released = ConsolidationService::releaseBabyShipment($consolidation, $shipment, $user, $branch);

        if ($released) {
            return back()->with('success', "Shipment {$shipment->tracking_number} released");
        }

        return back()->with('error', 'Failed to release shipment');
    }

    /**
     * Auto-consolidate eligible shipments
     */
    public function autoConsolidate()
    {
        $user = Auth::user();
        $branch = Branch::findOrFail(BranchContext::currentId());
        
        if (!PermissionService::hasCapability($user, 'consolidation.manage')) {
            abort(403, 'Unauthorized to manage consolidations');
        }

        $results = ConsolidationService::autoConsolidateShipments($branch, $user);

        return back()->with('success', "Auto-consolidated {$results['consolidated']} shipment(s) into {$results['created_consolidations']} consolidation(s)");
    }

    /**
     * Consolidation rules management
     */
    public function rules()
    {
        $user = Auth::user();
        $branchId = BranchContext::currentId();
        
        if (!PermissionService::hasCapability($user, 'settings.branch')) {
            abort(403, 'Unauthorized to manage consolidation rules');
        }

        $rules = ConsolidationRule::forBranch($branchId)
            ->with('destinationBranch')
            ->byPriority()
            ->get();

        return view('branch.consolidations.rules', compact('rules'));
    }
}
