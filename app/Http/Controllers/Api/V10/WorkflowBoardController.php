<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Services\ControlTowerService;
use App\Services\DispatchBoardService;
use App\Services\ExceptionTowerService;
use App\Services\OperationsNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowBoardController extends Controller
{
    public function __construct(
        protected DispatchBoardService $dispatchService,
        protected ExceptionTowerService $exceptionService,
        protected ControlTowerService $controlTowerService,
        protected OperationsNotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $hubBranch = Branch::where('is_hub', true)->first() ?? Branch::active()->first();
        $dispatchSnapshot = null;

        if ($hubBranch) {
            $dispatchSnapshot = $this->dispatchService->getDispatchBoard($hubBranch, Carbon::now());
        }

        $unassignedShipments = collect($dispatchSnapshot['unassigned_shipments'] ?? [])
            ->take(12)
            ->values();

        $loadBalancing = $dispatchSnapshot['load_balancing'] ?? [];
        $driverQueues = collect($dispatchSnapshot['driver_queues'] ?? [])
            ->map(function ($queue) {
                return [
                    'worker_id' => $queue['worker_id'] ?? null,
                    'worker_name' => $queue['worker_name'] ?? null,
                    'assigned_shipments' => $queue['assigned_shipments'] ?? 0,
                    'capacity' => $queue['capacity'] ?? null,
                    'utilization' => $queue['utilization'] ?? null,
                ];
            })
            ->values();

        $exceptions = $this->exceptionService
            ->getActiveExceptions([
                'branch_id' => $hubBranch?->id,
            ])
            ->take(12)
            ->values();

        $windowEnd = Carbon::now();
        $windowStart = (clone $windowEnd)->subDays(7);

        $exceptionMetrics = $this->exceptionService->getExceptionMetrics(
            $windowStart,
            $windowEnd
        );

        $kpiSummary = $this->controlTowerService->getOperationalKPIs();
        $shipmentMetrics = $this->controlTowerService->getShipmentMetrics($windowStart, $windowEnd);
        $workerUtilization = $this->controlTowerService->getWorkerUtilization();

        $notifications = [];
        if ($request->user()) {
            $notifications = $this->notificationService
                ->getUnreadNotifications($request->user())
                ->take(15)
                ->values()
                ->all();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'hub_branch' => $hubBranch ? [
                    'id' => $hubBranch->id,
                    'name' => $hubBranch->name,
                    'code' => $hubBranch->code,
                    'type' => $hubBranch->type,
                ] : null,
                'queues' => [
                    'unassigned_shipments' => $unassignedShipments,
                    'exceptions' => $exceptions,
                    'load_balancing' => $loadBalancing,
                    'driver_queues' => $driverQueues,
                ],
                'dispatch_snapshot' => $dispatchSnapshot,
                'kpis' => $kpiSummary,
                'shipment_metrics' => $shipmentMetrics,
                'worker_utilization' => $workerUtilization,
                'exception_metrics' => $exceptionMetrics,
                'notifications' => $notifications,
            ],
        ]);
    }
}

