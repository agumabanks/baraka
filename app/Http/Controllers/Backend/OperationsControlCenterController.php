<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\User;
use App\Services\DispatchBoardService;
use App\Services\ExceptionTowerService;
use App\Services\AssetManagementService;
use App\Services\ControlTowerService;
use App\Services\OperationsNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OperationsControlCenterController extends Controller
{
    protected DispatchBoardService $dispatchService;
    protected ExceptionTowerService $exceptionService;
    protected AssetManagementService $assetService;
    protected ControlTowerService $controlTowerService;
    protected OperationsNotificationService $notificationService;

    public function __construct(
        DispatchBoardService $dispatchService,
        ExceptionTowerService $exceptionService,
        AssetManagementService $assetService,
        ControlTowerService $controlTowerService,
        OperationsNotificationService $notificationService
    ) {
        $this->dispatchService = $dispatchService;
        $this->exceptionService = $exceptionService;
        $this->assetService = $assetService;
        $this->controlTowerService = $controlTowerService;
        $this->notificationService = $notificationService;
    }

    // ===============================
    // DISPATCH BOARD ENDPOINTS
    // ===============================

    /**
     * Get dispatch board for branch and date
     */
    public function getDispatchBoard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = $request->branch_id ? Branch::findOrFail($request->branch_id) : null;
            $date = $request->date ? Carbon::parse($request->date) : now();

            $dispatchBoard = $this->dispatchService->getDispatchBoard($branch, $date);

            return response()->json([
                'success' => true,
                'data' => $dispatchBoard
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dispatch board: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign shipment to worker
     */
    public function assignShipmentToWorker(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'worker_id' => 'required|exists:branch_workers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $shipment = Shipment::findOrFail($request->shipment_id);
            $worker = BranchWorker::findOrFail($request->worker_id);

            $result = $this->dispatchService->assignShipmentToWorker($shipment, $worker);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign shipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reassign shipment to different worker
     */
    public function reassignShipment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'new_worker_id' => 'required|exists:branch_workers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $shipment = Shipment::findOrFail($request->shipment_id);
            $newWorker = BranchWorker::findOrFail($request->new_worker_id);

            $result = $this->dispatchService->reassignShipment($shipment, $newWorker);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign shipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unassigned shipments
     */
    public function getUnassignedShipments(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = $request->branch_id ? Branch::findOrFail($request->branch_id) : null;
            $date = now();

            $unassignedShipments = $this->dispatchService->getUnassignedShipments($branch, $date);

            return response()->json([
                'success' => true,
                'data' => $unassignedShipments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unassigned shipments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get worker workload
     */
    public function getWorkerWorkload(Request $request, BranchWorker $worker): JsonResponse
    {
        try {
            $workload = $this->dispatchService->getWorkerWorkload($worker);

            return response()->json([
                'success' => true,
                'data' => $workload
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get worker workload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get load balancing metrics
     */
    public function getLoadBalancingMetrics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = $request->branch_id ? Branch::findOrFail($request->branch_id) : null;
            $date = now();

            $metrics = $this->dispatchService->getLoadBalancingMetrics($branch, $date);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get load balancing metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===============================
    // EXCEPTION TOWER ENDPOINTS
    // ===============================

    /**
     * Get active exceptions
     */
    public function getActiveExceptions(Request $request): JsonResponse
    {
        try {
            $exceptions = $this->exceptionService->getActiveExceptions($request->all());

            return response()->json([
                'success' => true,
                'data' => $exceptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get active exceptions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new exception
     */
    public function createException(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'type' => 'required|string',
            'severity' => 'nullable|string|in:low,medium,high',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $shipment = Shipment::findOrFail($request->shipment_id);
            $result = $this->exceptionService->createException($shipment, $request->all());

            return response()->json($result, $result['success'] ? 201 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign exception to resolver
     */
    public function assignExceptionToResolver(Request $request, Shipment $shipment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resolver_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $resolver = User::findOrFail($request->resolver_id);
            $result = $this->exceptionService->assignExceptionToResolver($shipment, $resolver);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign exception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update exception status
     */
    public function updateExceptionStatus(Request $request, Shipment $shipment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:investigating,resolved,escalated,closed',
            'resolution_notes' => 'nullable|string|max:2000',
            'new_shipment_status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->exceptionService->updateExceptionStatus(
                $shipment,
                $request->status,
                $request->only(['resolution_notes', 'new_shipment_status'])
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exception status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get exception metrics
     */
    public function getExceptionMetrics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->subDays(30);
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : now();

            $metrics = $this->exceptionService->getExceptionMetrics($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get exception metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get priority exceptions
     */
    public function getPriorityExceptions(): JsonResponse
    {
        try {
            $exceptions = $this->exceptionService->getPriorityExceptions();

            return response()->json([
                'success' => true,
                'data' => $exceptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get priority exceptions: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===============================
    // ASSET MANAGEMENT ENDPOINTS
    // ===============================

    /**
     * Get asset status overview
     */
    public function getAssetStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = $request->branch_id ? Branch::findOrFail($request->branch_id) : null;
            $status = $this->assetService->getAssetStatus($branch);

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get asset status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vehicle utilization metrics
     */
    public function getVehicleUtilization(Request $request, $vehicleId): JsonResponse
    {
        $validator = Validator::make(array_merge($request->all(), ['vehicle_id' => $vehicleId]), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $vehicle = \App\Models\Backend\Vehicle::findOrFail($vehicleId);
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();

            $utilization = $this->assetService->getVehicleUtilization($vehicle, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $utilization
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vehicle utilization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get maintenance schedule
     */
    public function getMaintenanceSchedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : now();
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->addDays(30);
            $branch = $request->branch_id ? Branch::findOrFail($request->branch_id) : null;

            $schedule = $this->assetService->getMaintenanceSchedule($startDate, $endDate, $branch);

            return response()->json([
                'success' => true,
                'data' => $schedule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get maintenance schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fuel consumption data
     */
    public function getFuelConsumption(Request $request, $vehicleId): JsonResponse
    {
        $validator = Validator::make(array_merge($request->all(), ['vehicle_id' => $vehicleId]), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'month' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $vehicle = \App\Models\Backend\Vehicle::findOrFail($vehicleId);
            $month = $request->month ? Carbon::parse($request->month) : now();

            $consumption = $this->assetService->getFuelConsumption($vehicle, $month);

            return response()->json([
                'success' => true,
                'data' => $consumption
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get fuel consumption: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get asset metrics
     */
    public function getAssetMetrics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = $request->branch_id ? Branch::findOrFail($request->branch_id) : null;
            $metrics = $this->assetService->getAssetMetrics($branch);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get asset metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available vehicles
     */
    public function getAvailableVehicles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = Branch::findOrFail($request->branch_id);
            $date = $request->date ? Carbon::parse($request->date) : now();

            $vehicles = $this->assetService->getAvailableVehicles($branch, $date);

            return response()->json([
                'success' => true,
                'data' => $vehicles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available vehicles: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===============================
    // CONTROL TOWER ENDPOINTS
    // ===============================

    /**
     * Get operational KPIs
     */
    public function getOperationalKPIs(): JsonResponse
    {
        try {
            $kpis = $this->controlTowerService->getOperationalKPIs();

            return response()->json([
                'success' => true,
                'data' => $kpis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get operational KPIs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branch performance metrics
     */
    public function getBranchPerformance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = $request->branch_id ? Branch::findOrFail($request->branch_id) : null;
            $performance = $this->controlTowerService->getBranchPerformance($branch);

            return response()->json([
                'success' => true,
                'data' => $performance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get branch performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get worker utilization metrics
     */
    public function getWorkerUtilization(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = $request->branch_id ? Branch::findOrFail($request->branch_id) : null;
            $utilization = $this->controlTowerService->getWorkerUtilization($branch);

            return response()->json([
                'success' => true,
                'data' => $utilization
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get worker utilization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipment metrics
     */
    public function getShipmentMetrics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->subDays(30);
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : now();

            $metrics = $this->controlTowerService->getShipmentMetrics($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get shipment metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get operational alerts
     */
    public function getAlerts(): JsonResponse
    {
        try {
            $alerts = $this->controlTowerService->getAlerts();

            return response()->json([
                'success' => true,
                'data' => $alerts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get operational alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get operational trends
     */
    public function getOperationalTrends(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $days = $request->get('days', 30);
            $trends = $this->controlTowerService->getOperationalTrends($days);

            return response()->json([
                'success' => true,
                'data' => $trends
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get operational trends: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===============================
    // NOTIFICATION ENDPOINTS
    // ===============================

    /**
     * Get user notifications
     */
    public function getUserNotifications(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $notifications = $this->notificationService->getUnreadNotifications($user);

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = auth()->user();
            $result = $this->notificationService->markNotificationAsRead($user, $notificationId);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadNotificationCount(): JsonResponse
    {
        try {
            $user = auth()->user();
            $unreadCount = $this->notificationService->getUnreadNotifications($user)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread notification count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $result = $this->notificationService->updateUserNotificationPreferences($user, $request->all());

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notification preferences updated' : 'Failed to update preferences'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification history
     */
    public function getNotificationHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $days = $request->get('days', 7);
            $history = $this->notificationService->getNotificationHistory($user, $days);

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification history: ' . $e->getMessage()
            ], 500);
        }
    }
}