<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\HubRoute;
use App\Models\DriverAssignment;
use App\Models\BranchWorker;
use App\Models\Backend\Hub;
use App\Enums\BranchWorkerRole;
use App\Services\EnhancedRouteOptimizationService;
use App\Services\HubRoutingService;
use App\Services\ShipmentAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DispatchController extends Controller
{
    protected EnhancedRouteOptimizationService $routeService;
    protected HubRoutingService $hubRoutingService;
    protected ShipmentAssignmentService $assignmentService;

    public function __construct(
        EnhancedRouteOptimizationService $routeService,
        HubRoutingService $hubRoutingService,
        ShipmentAssignmentService $assignmentService
    ) {
        $this->routeService = $routeService;
        $this->hubRoutingService = $hubRoutingService;
        $this->assignmentService = $assignmentService;
    }

    /**
     * Dispatch dashboard
     */
    public function index(): View
    {
        $stats = [
            'unassigned_shipments' => Shipment::whereNull('assigned_worker_id')
                ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
                ->count(),
            'active_drivers' => BranchWorker::active()
                ->whereIn('role', [
                    BranchWorkerRole::COURIER->value,
                    BranchWorkerRole::DRIVER->value,
                    'DELIVERY', // legacy values that may still exist
                    'RIDER',
                ])
                ->count(),
            'todays_assignments' => DriverAssignment::whereDate('assignment_date', today())->count(),
            'pending_routes' => DriverAssignment::whereDate('assignment_date', today())
                ->where('status', 'pending')
                ->count(),
        ];

        return view('admin.dispatch.index', compact('stats'));
    }

    /**
     * Optimize route for selected shipments
     */
    public function optimizeRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'exists:shipments,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:branch_workers,id',
            'strategy' => 'nullable|string|in:auto,2opt,3opt,genetic_2opt,simulated_annealing,nearest_neighbor',
            'use_traffic' => 'nullable|boolean',
        ]);

        $result = $this->routeService->optimizeRouteEnhanced(
            $validated['shipment_ids'],
            $validated['vehicle_id'] ?? null,
            $validated['driver_id'] ?? null,
            [
                'strategy' => $validated['strategy'] ?? 'auto',
                'use_traffic' => $validated['use_traffic'] ?? true,
            ]
        );

        return response()->json($result);
    }

    /**
     * Auto-assign a shipment
     */
    public function autoAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $result = $this->assignmentService->autoAssign($shipment);

        return response()->json($result);
    }

    /**
     * Bulk auto-assign shipments
     */
    public function bulkAutoAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'exists:shipments,id',
        ]);

        $result = $this->assignmentService->bulkAutoAssign($validated['shipment_ids']);

        return response()->json($result);
    }

    /**
     * Manual assignment
     */
    public function manualAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
            'driver_id' => 'required|exists:branch_workers,id',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $result = $this->assignmentService->assignShipmentToDriver(
            $shipment,
            $validated['driver_id']
        );

        return response()->json($result);
    }

    /**
     * Get assignment suggestions
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'nullable|date',
        ]);

        $date = $validated['date'] ? \Carbon\Carbon::parse($validated['date']) : today();
        $suggestions = $this->assignmentService->getSuggestions($validated['branch_id'], $date);

        return response()->json($suggestions);
    }

    /**
     * Get workload distribution
     */
    public function getWorkloadDistribution(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'nullable|date',
        ]);

        $date = $validated['date'] ? \Carbon\Carbon::parse($validated['date']) : today();
        $distribution = $this->assignmentService->getWorkloadDistribution($validated['branch_id'], $date);

        return response()->json($distribution);
    }

    /**
     * Rebalance workload
     */
    public function rebalanceWorkload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $result = $this->assignmentService->rebalanceWorkload($validated['branch_id']);

        return response()->json($result);
    }

    /**
     * Find optimal hub route
     */
    public function findHubRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_id' => 'required|exists:hubs,id',
            'destination_id' => 'required|exists:hubs,id',
            'service_level' => 'nullable|string|in:express,standard,economy',
            'optimize_for' => 'nullable|string|in:cost,time,distance',
            'weight_kg' => 'nullable|numeric|min:0',
            'volume_cbm' => 'nullable|numeric|min:0',
        ]);

        $result = $this->hubRoutingService->findOptimalRoute(
            $validated['origin_id'],
            $validated['destination_id'],
            $validated['service_level'] ?? 'standard',
            [
                'optimize_for' => $validated['optimize_for'] ?? 'cost',
                'weight_kg' => $validated['weight_kg'] ?? 1,
                'volume_cbm' => $validated['volume_cbm'] ?? 0.01,
            ]
        );

        return response()->json($result);
    }

    /**
     * Get hub capacity
     */
    public function getHubCapacity(int $hubId): JsonResponse
    {
        $capacity = $this->hubRoutingService->getHubCapacity($hubId);

        return response()->json($capacity);
    }

    /**
     * Find alternative hub with better capacity
     */
    public function findAlternativeHub(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_hub_id' => 'required|exists:hubs,id',
            'destination_hub_id' => 'required|exists:hubs,id',
            'service_level' => 'nullable|string',
        ]);

        $alternative = $this->hubRoutingService->findAlternativeHub(
            $validated['origin_hub_id'],
            $validated['destination_hub_id'],
            $validated['service_level'] ?? 'standard'
        );

        return response()->json([
            'success' => $alternative !== null,
            'alternative' => $alternative,
        ]);
    }

    /**
     * Rebalance hub loads
     */
    public function rebalanceHubs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hub_ids' => 'required|array|min:2',
            'hub_ids.*' => 'exists:hubs,id',
        ]);

        $result = $this->hubRoutingService->rebalanceHubLoads($validated['hub_ids']);

        return response()->json($result);
    }

    /**
     * Get hub routes
     */
    public function getHubRoutes(int $hubId): JsonResponse
    {
        $routes = $this->hubRoutingService->getHubRoutes($hubId);

        return response()->json($routes);
    }

    /**
     * Dynamic re-route
     */
    public function dynamicReroute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_route' => 'required|array',
            'add_shipments' => 'nullable|array',
            'remove_shipments' => 'nullable|array',
            'traffic_delay' => 'nullable|array',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        $changes = array_filter([
            'add_shipments' => $validated['add_shipments'] ?? null,
            'remove_shipments' => $validated['remove_shipments'] ?? null,
            'traffic_delay' => $validated['traffic_delay'] ?? null,
        ]);

        $vehicleConstraints = null;
        if ($validated['vehicle_id'] ?? null) {
            $vehicleConstraints = $this->routeService->getVehicleConstraints($validated['vehicle_id']);
        }

        $result = $this->routeService->dynamicReroute(
            $validated['current_route'],
            $changes,
            $vehicleConstraints
        );

        return response()->json($result);
    }

    /**
     * Hub routes management view
     */
    public function hubRoutes(): View
    {
        $hubs = Hub::where('status', 'active')->get();
        $routes = HubRoute::with(['originHub', 'destinationHub'])
            ->orderBy('origin_hub_id')
            ->paginate(50);

        return view('admin.dispatch.hub-routes', compact('hubs', 'routes'));
    }

    /**
     * Store new hub route
     */
    public function storeHubRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_hub_id' => 'required|exists:hubs,id',
            'destination_hub_id' => 'required|exists:hubs,id|different:origin_hub_id',
            'distance_km' => 'required|numeric|min:0',
            'transit_time_hours' => 'required|integer|min:1',
            'base_cost' => 'required|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'cost_per_cbm' => 'nullable|numeric|min:0',
            'service_level' => 'required|string|in:express,standard,economy',
            'transport_mode' => 'required|string|in:road,air,rail,sea',
            'departure_days' => 'nullable|array',
            'departure_time' => 'nullable|date_format:H:i',
            'cutoff_time' => 'nullable|date_format:H:i',
        ]);

        $route = HubRoute::create($validated);

        return response()->json([
            'success' => true,
            'route' => $route->load(['originHub', 'destinationHub']),
        ], 201);
    }

    /**
     * Update hub route
     */
    public function updateHubRoute(Request $request, int $id): JsonResponse
    {
        $route = HubRoute::findOrFail($id);

        $validated = $request->validate([
            'distance_km' => 'nullable|numeric|min:0',
            'transit_time_hours' => 'nullable|integer|min:1',
            'base_cost' => 'nullable|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'cost_per_cbm' => 'nullable|numeric|min:0',
            'service_level' => 'nullable|string|in:express,standard,economy',
            'transport_mode' => 'nullable|string|in:road,air,rail,sea',
            'is_active' => 'nullable|boolean',
            'congestion_factor' => 'nullable|numeric|min:0.5|max:3',
        ]);

        $route->update($validated);

        return response()->json([
            'success' => true,
            'route' => $route->load(['originHub', 'destinationHub']),
        ]);
    }

    /**
     * Delete hub route
     */
    public function deleteHubRoute(int $id): JsonResponse
    {
        $route = HubRoute::findOrFail($id);
        $route->delete();

        return response()->json(['success' => true]);
    }
}
