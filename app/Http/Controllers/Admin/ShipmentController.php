<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ScanEvent;
use App\Models\Bag;
use App\Models\Route;
use App\Models\PodProof;
use App\Services\ShipmentService;
use App\Services\RouteOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    protected $shipmentService;
    protected $routeOptimizationService;

    public function __construct(
        ShipmentService $shipmentService,
        RouteOptimizationService $routeOptimizationService
    ) {
        $this->shipmentService = $shipmentService;
        $this->routeOptimizationService = $routeOptimizationService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Shipment::class);

        $query = Shipment::with(['originBranch', 'destBranch', 'customer', 'currentScanEvent']);

        // Enhanced filters
        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($sq) use ($q) {
                $sq->where('id', $q)
                    ->orWhere('tracking', 'like', "%$q%")
                    ->orWhere('reference_number', 'like', "%$q%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('current_status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($branchId = $request->input('branch')) {
            $query->where(function ($sq) use ($branchId) {
                $sq->where('origin_branch_id', $branchId)
                    ->orWhere('dest_branch_id', $branchId);
            });
        }

        if ($customerId = $request->input('customer')) {
            $query->where('customer_id', $customerId);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($exception = $request->boolean('exception')) {
            $query->where('has_exception', true);
        }

        // Branch filtering for non-admin users
        $user = $request->user();
        if (! $user->hasRole('hq_admin') && ! is_null($user->hub_id)) {
            $query->where(function ($q) use ($user) {
                $q->where('origin_branch_id', $user->hub_id)
                    ->orWhere('dest_branch_id', $user->hub_id);
            });
        }

        $shipments = $query->latest()->paginate(25)->withQueryString();

        return view('backend.admin.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment)
    {
        $this->authorize('view', $shipment);

        $shipment->load([
            'customer',
            'originBranch',
            'destBranch',
            'scanEvents' => function ($query) {
                $query->latest();
            },
            'bags',
            'podProofs',
            'assignedDriver'
        ]);

        return view('backend.admin.shipments.show', compact('shipment'));
    }

    public function create()
    {
        $this->authorize('create', Shipment::class);

        return view('backend.admin.shipments.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Shipment::class);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'origin_branch_id' => 'required|exists:branches,id',
            'dest_branch_id' => 'required|exists:branches,id',
            'service_type' => 'required|string',
            'weight' => 'required|numeric|min:0',
            'dimensions' => 'nullable|array',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        try {
            $shipment = $this->shipmentService->createShipment($validated, $request->user());

            return redirect()->route('admin.shipments.show', $shipment)
                ->with('success', 'Shipment created successfully');
        } catch (\Exception $e) {
            Log::error('Shipment creation failed', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()->withInput()->withErrors(['error' => 'Failed to create shipment']);
        }
    }

    public function edit(Shipment $shipment)
    {
        $this->authorize('update', $shipment);

        return view('backend.admin.shipments.edit', compact('shipment'));
    }

    public function update(Request $request, Shipment $shipment)
    {
        $this->authorize('update', $shipment);

        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'service_type' => 'sometimes|string',
            'weight' => 'sometimes|numeric|min:0',
            'dimensions' => 'nullable|array',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        try {
            $this->shipmentService->updateShipment($shipment, $validated);

            return redirect()->route('admin.shipments.show', $shipment)
                ->with('success', 'Shipment updated successfully');
        } catch (\Exception $e) {
            Log::error('Shipment update failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to update shipment']);
        }
    }

    public function labels(Shipment $shipment)
    {
        $this->authorize('view', $shipment);

        try {
            $pdf = $this->shipmentService->generateLabels($shipment);

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="shipment-labels.pdf"');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate labels'], 500);
        }
    }

    // Enhanced methods for ERP functionality

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorize('update', Shipment::class);

        $validated = $request->validate([
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'exists:shipments,id',
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->shipmentService->bulkUpdateStatus(
                $validated['shipment_ids'],
                $validated['status'],
                $validated['notes'],
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => "Updated {$result['updated']} shipments",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk status update failed', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json(['error' => 'Bulk update failed'], 500);
        }
    }

    public function assignDriver(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $shipment);

        $validated = $request->validate([
            'driver_id' => 'required|exists:delivery_man,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->shipmentService->assignToDriver($shipment, $validated['driver_id'], $validated['notes']);

            return response()->json(['success' => true, 'message' => 'Driver assigned successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to assign driver'], 500);
        }
    }

    public function bulkAssignDriver(Request $request): JsonResponse
    {
        $this->authorize('update', Shipment::class);

        $validated = $request->validate([
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'exists:shipments,id',
            'driver_id' => 'required|exists:delivery_man,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->shipmentService->bulkAssignToDriver(
                $validated['shipment_ids'],
                $validated['driver_id'],
                $validated['notes']
            );

            return response()->json([
                'success' => true,
                'message' => "Assigned {$result['assigned']} shipments to driver",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bulk assignment failed'], 500);
        }
    }

    public function optimizeRoutes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Shipment::class);

        $validated = $request->validate([
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'exists:shipments,id',
            'optimization_criteria' => 'nullable|array',
        ]);

        try {
            $optimizedRoute = $this->routeOptimizationService->optimizeRoute(
                $validated['shipment_ids'],
                $validated['optimization_criteria'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $optimizedRoute
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Route optimization failed'], 500);
        }
    }

    public function exceptions(Request $request)
    {
        $this->authorize('viewAny', Shipment::class);

        $query = Shipment::where('has_exception', true)
            ->with(['customer', 'originBranch', 'destBranch']);

        if ($type = $request->input('type')) {
            $query->where('exception_type', $type);
        }

        if ($severity = $request->input('severity')) {
            $query->where('exception_severity', $severity);
        }

        $exceptions = $query->latest()->paginate(25);

        return view('backend.admin.shipments.exceptions', compact('exceptions'));
    }

    public function resolveException(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $shipment);

        $validated = $request->validate([
            'resolution' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->shipmentService->resolveException($shipment, $validated['resolution'], $validated['notes']);

            return response()->json(['success' => true, 'message' => 'Exception resolved']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to resolve exception'], 500);
        }
    }

    public function podVerification(Request $request, Shipment $shipment)
    {
        $this->authorize('view', $shipment);

        $podProofs = $shipment->podProofs()->latest()->get();

        return view('backend.admin.shipments.pod-verification', compact('shipment', 'podProofs'));
    }

    public function verifyPod(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $shipment);

        $validated = $request->validate([
            'verification_method' => 'required|in:signature,qr_code,photo,manual',
            'verification_data' => 'required',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->shipmentService->verifyPod($shipment, $validated);

            return response()->json(['success' => true, 'message' => 'POD verified successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'POD verification failed'], 500);
        }
    }

    public function scanEvents(Request $request, Shipment $shipment)
    {
        $this->authorize('view', $shipment);

        $scanEvents = $shipment->scanEvents()->with('user')->latest()->paginate(50);

        return view('backend.admin.shipments.scan-events', compact('shipment', 'scanEvents'));
    }

    public function addScanEvent(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $shipment);

        $validated = $request->validate([
            'event_type' => 'required|string',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $scanEvent = $this->shipmentService->addScanEvent($shipment, $validated, $request->user());

            return response()->json([
                'success' => true,
                'data' => $scanEvent,
                'message' => 'Scan event added successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add scan event'], 500);
        }
    }

    public function bags(Request $request, Shipment $shipment)
    {
        $this->authorize('view', $shipment);

        $bags = $shipment->bags()->with('route')->latest()->get();

        return view('backend.admin.shipments.bags', compact('shipment', 'bags'));
    }

    public function assignToBag(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $shipment);

        $validated = $request->validate([
            'bag_id' => 'required|exists:bags,id',
        ]);

        try {
            $this->shipmentService->assignToBag($shipment, $validated['bag_id']);

            return response()->json(['success' => true, 'message' => 'Shipment assigned to bag']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to assign to bag'], 500);
        }
    }

    public function manifests(Request $request)
    {
        $this->authorize('viewAny', Shipment::class);

        $query = Bag::with(['route', 'shipments']);

        if ($routeId = $request->input('route')) {
            $query->where('route_id', $routeId);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        $manifests = $query->latest()->paginate(25);

        return view('backend.admin.shipments.manifests', compact('manifests'));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Shipment::class);

        $query = Shipment::query();

        // Apply same filters as index
        $this->applyFilters($query, $request);

        try {
            $filePath = $this->shipmentService->exportShipments($query);

            return response()->download($filePath)->deleteFileAfterSend();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Export failed']);
        }
    }

    protected function applyFilters($query, Request $request)
    {
        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($sq) use ($q) {
                $sq->where('id', $q)
                    ->orWhere('tracking', 'like', "%$q%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('current_status', $status);
        }

        if ($branchId = $request->input('branch')) {
            $query->where(function ($sq) use ($branchId) {
                $sq->where('origin_branch_id', $branchId)
                    ->orWhere('dest_branch_id', $branchId);
            });
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $user = $request->user();
        if (! $user->hasRole('hq_admin') && ! is_null($user->hub_id)) {
            $query->where(function ($q) use ($user) {
                $q->where('origin_branch_id', $user->hub_id)
                    ->orWhere('dest_branch_id', $user->hub_id);
            });
        }
    }
}
