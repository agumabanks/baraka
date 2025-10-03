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
            ->take(8)
            ->values();

        $loadBalancing = $dispatchSnapshot['load_balancing'] ?? [];

        $exceptions = $this->exceptionService
            ->getActiveExceptions([
                'branch_id' => $hubBranch?->id,
            ])
            ->take(8)
            ->values();

        $kpiSummary = $this->controlTowerService->getOperationalKPIs();

        $notifications = [];
        if ($request->user()) {
            $notifications = $this->notificationService
                ->getUnreadNotifications($request->user())
                ->take(10)
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
                ],
                'kpis' => $kpiSummary,
                'notifications' => $notifications,
            ],
        ]);
    }
}

