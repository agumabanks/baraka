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
use Illuminate\Support\Facades\Log;

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
        try {
            Log::info('WorkflowBoardController: Starting workflow board request', [
                'user_id' => $request->user()?->id,
                'has_auth' => $request->user() !== null,
            ]);

            $hubBranch = Branch::where('is_hub', true)->first() ?? Branch::active()->first();
            
            Log::info('WorkflowBoardController: Hub branch loaded', [
                'branch_id' => $hubBranch?->id,
                'branch_name' => $hubBranch?->name,
            ]);
            
            $dispatchSnapshot = null;

            if ($hubBranch) {
                try {
                    $dispatchSnapshot = $this->dispatchService->getDispatchBoard($hubBranch, Carbon::now());
                    Log::info('WorkflowBoardController: Dispatch snapshot loaded');
                } catch (\Exception $e) {
                    Log::error('WorkflowBoardController: Failed to load dispatch snapshot', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
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

            try {
                $exceptions = $this->exceptionService
                    ->getActiveExceptions([
                        'branch_id' => $hubBranch?->id,
                    ])
                    ->take(12)
                    ->values();
                Log::info('WorkflowBoardController: Exceptions loaded', ['count' => $exceptions->count()]);
            } catch (\Exception $e) {
                Log::error('WorkflowBoardController: Failed to load exceptions', [
                    'error' => $e->getMessage(),
                ]);
                $exceptions = collect();
            }

            $windowEnd = Carbon::now();
            $windowStart = (clone $windowEnd)->subDays(7);

            try {
                $exceptionMetrics = $this->exceptionService->getExceptionMetrics($windowStart, $windowEnd);
                Log::info('WorkflowBoardController: Exception metrics loaded');
            } catch (\Exception $e) {
                Log::error('WorkflowBoardController: Failed to load exception metrics', [
                    'error' => $e->getMessage(),
                ]);
                $exceptionMetrics = [];
            }

            try {
                $kpiSummary = $this->controlTowerService->getOperationalKPIs();
                Log::info('WorkflowBoardController: KPIs loaded');
            } catch (\Exception $e) {
                Log::error('WorkflowBoardController: Failed to load KPIs', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $kpiSummary = [];
            }

            try {
                $shipmentMetrics = $this->controlTowerService->getShipmentMetrics($windowStart, $windowEnd);
                Log::info('WorkflowBoardController: Shipment metrics loaded');
            } catch (\Exception $e) {
                Log::error('WorkflowBoardController: Failed to load shipment metrics', [
                    'error' => $e->getMessage(),
                ]);
                $shipmentMetrics = [];
            }

            try {
                $workerUtilization = $this->controlTowerService->getWorkerUtilization();
                Log::info('WorkflowBoardController: Worker utilization loaded');
            } catch (\Exception $e) {
                Log::error('WorkflowBoardController: Failed to load worker utilization', [
                    'error' => $e->getMessage(),
                ]);
                $workerUtilization = [];
            }

            $notifications = [];
            if ($request->user()) {
                try {
                    $notifications = $this->notificationService
                        ->getUnreadNotifications($request->user())
                        ->take(15)
                        ->values()
                        ->all();
                    Log::info('WorkflowBoardController: Notifications loaded', ['count' => count($notifications)]);
                } catch (\Exception $e) {
                    Log::error('WorkflowBoardController: Failed to load notifications', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('WorkflowBoardController: Successfully completed workflow board request');

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

        } catch (\Exception $e) {
            Log::error('WorkflowBoardController: Fatal error in workflow board request', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load workflow board: ' . $e->getMessage(),
                'data' => [
                    'hub_branch' => null,
                    'queues' => [
                        'unassigned_shipments' => [],
                        'exceptions' => [],
                        'load_balancing' => [],
                        'driver_queues' => [],
                    ],
                    'dispatch_snapshot' => null,
                    'kpis' => [],
                    'shipment_metrics' => [],
                    'worker_utilization' => [],
                    'exception_metrics' => [],
                    'notifications' => [],
                ],
            ], 500);
        }
    }
}

