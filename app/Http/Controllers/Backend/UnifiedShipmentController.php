<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Services\UnifiedShipmentWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UnifiedShipmentController extends Controller
{
    protected UnifiedShipmentWorkflowService $workflowService;

    public function __construct(UnifiedShipmentWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Process shipment workflow action
     */
    public function processWorkflow(Request $request, Shipment $shipment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:create,pickup,transfer_to_hub,hub_processing,transfer_to_destination,delivery,exception,return',
            'worker_id' => 'nullable|exists:branch_workers,id',
            'notes' => 'nullable|string|max:1000',
            'exception_type' => 'nullable|string|in:damage,lost,wrong_address,refused,other',
            'severity' => 'nullable|string|in:low,medium,high',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->workflowService->processShipmentWorkflow(
                $shipment,
                $request->action,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Workflow action processed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process workflow action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipment workflow status
     */
    public function getWorkflowStatus(Shipment $shipment): JsonResponse
    {
        try {
            $status = $this->workflowService->getShipmentWorkflowStatus($shipment);

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get workflow status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get HUB sortation shipments
     */
    public function getHubSortation(): JsonResponse
    {
        try {
            $shipments = $this->workflowService->getHubSortationShipments();

            return response()->json([
                'success' => true,
                'data' => [
                    'shipments' => $shipments,
                    'total' => $shipments->count(),
                    'grouped_by_destination' => $shipments->groupBy('dest_branch_id')->map(function ($group) {
                        return [
                            'destination' => $group->first()->destBranch->name,
                            'count' => $group->count(),
                            'priority_breakdown' => $group->groupBy('priority')->map->count()
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get HUB sortation data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process HUB sortation
     */
    public function processSortation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'exists:shipments,id',
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
            $result = $this->workflowService->processHubSortation(
                $request->shipment_ids,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'HUB sortation processed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process HUB sortation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inter-branch transfers
     */
    public function getInterBranchTransfers(): JsonResponse
    {
        try {
            $transfers = $this->workflowService->getInterBranchTransfers();

            return response()->json([
                'success' => true,
                'data' => [
                    'transfers' => $transfers,
                    'total' => $transfers->count(),
                    'grouped_by_route' => $transfers->groupBy(function ($transfer) {
                        return $transfer->originBranch->name . ' → ' . $transfer->destBranch->name;
                    })->map->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get inter-branch transfers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process inter-branch transfer
     */
    public function processTransfer(Request $request, Shipment $shipment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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
            $result = $this->workflowService->processInterBranchTransfer(
                $shipment,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Inter-branch transfer processed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get worker assignments for branch
     */
    public function getWorkerAssignments(Branch $branch): JsonResponse
    {
        try {
            $assignments = $this->workflowService->getWorkerAssignments($branch);

            return response()->json([
                'success' => true,
                'data' => [
                    'branch' => $branch,
                    'assignments' => $assignments,
                    'summary' => [
                        'total_workers' => count($assignments),
                        'active_workers' => collect($assignments)->where('active_shipments', '>', 0)->count(),
                        'total_capacity' => collect($assignments)->sum('capacity'),
                        'total_active_shipments' => collect($assignments)->sum('active_shipments'),
                        'avg_utilization' => collect($assignments)->avg('utilization_rate')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get worker assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign shipment to worker
     */
    public function assignToWorker(Request $request, Shipment $shipment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|exists:branch_workers,id',
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
            $worker = BranchWorker::findOrFail($request->worker_id);

            // Validate worker can perform this assignment
            if (!$worker->canPerform('deliver_shipments')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Worker does not have permission to deliver shipments'
                ], 403);
            }

            // Check worker capacity
            $currentAssignments = $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();

            $capacity = $this->workflowService->getWorkerCapacity($worker);

            if ($currentAssignments >= $capacity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Worker has reached maximum capacity'
                ], 422);
            }

            // Update shipment
            $shipment->update([
                'assigned_worker_id' => $worker->id,
                'current_status' => 'assigned',
                'assigned_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'shipment' => $shipment->load(['assignedWorker.user']),
                    'worker' => $worker->load('user'),
                    'message' => 'Shipment assigned to worker successfully'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign shipment to worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workflow analytics
     */
    public function getWorkflowAnalytics(Request $request): JsonResponse
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
            $analytics = $this->workflowService->getWorkflowAnalytics($days);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get workflow analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workflow alerts
     */
    public function getWorkflowAlerts(): JsonResponse
    {
        try {
            $analytics = $this->workflowService->getWorkflowAnalytics(7); // Last 7 days for alerts

            $alerts = [];

            // Check for stuck shipments
            if (isset($analytics['workflow_bottlenecks'])) {
                foreach ($analytics['workflow_bottlenecks'] as $bottleneck) {
                    $alerts[] = [
                        'type' => 'bottleneck',
                        'severity' => $bottleneck['type'] === 'stuck_shipments' ? 'high' : 'medium',
                        'title' => ucfirst(str_replace('_', ' ', $bottleneck['type'])),
                        'message' => $bottleneck['description'],
                        'count' => $bottleneck['count'] ?? null,
                        'rate' => $bottleneck['rate'] ?? null,
                    ];
                }
            }

            // Check low on-time delivery rate
            if ($analytics['on_time_delivery_rate'] < 80) {
                $alerts[] = [
                    'type' => 'performance',
                    'severity' => 'medium',
                    'title' => 'Low On-Time Delivery Rate',
                    'message' => "Current on-time delivery rate is {$analytics['on_time_delivery_rate']}%",
                    'rate' => $analytics['on_time_delivery_rate'],
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'alerts' => $alerts,
                    'total_alerts' => count($alerts),
                    'severity_breakdown' => collect($alerts)->groupBy('severity')->map->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get workflow alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workflow recommendations
     */
    public function getWorkflowRecommendations(): JsonResponse
    {
        try {
            $analytics = $this->workflowService->getWorkflowAnalytics(30);

            $recommendations = [];

            // Branch efficiency recommendations
            if (isset($analytics['branch_efficiency'])) {
                foreach ($analytics['branch_efficiency'] as $branch) {
                    if ($branch['on_time_rate'] < 85) {
                        $recommendations[] = [
                            'type' => 'branch_optimization',
                            'priority' => 'high',
                            'title' => "Optimize {$branch['branch']} Performance",
                            'description' => "Branch has {$branch['on_time_rate']}% on-time delivery rate",
                            'action' => 'Review branch processes and resource allocation',
                        ];
                    }
                }
            }

            // Capacity recommendations
            $hubSummary = $this->workflowService->getHubDistributionSummary();
            if (isset($hubSummary['processing_capacity'])) {
                $capacity = $hubSummary['processing_capacity'];
                if ($capacity['utilization_rate'] > 90) {
                    $recommendations[] = [
                        'type' => 'capacity_planning',
                        'priority' => 'high',
                        'title' => 'HUB Capacity Overloaded',
                        'description' => "HUB utilization at {$capacity['utilization_rate']}%",
                        'action' => 'Consider adding more workers or optimizing processes',
                    ];
                }
            }

            // Workflow bottleneck recommendations
            if (isset($analytics['workflow_bottlenecks'])) {
                foreach ($analytics['workflow_bottlenecks'] as $bottleneck) {
                    $recommendations[] = [
                        'type' => 'process_improvement',
                        'priority' => $bottleneck['type'] === 'stuck_shipments' ? 'critical' : 'medium',
                        'title' => 'Address ' . ucfirst(str_replace('_', ' ', $bottleneck['type'])),
                        'description' => $bottleneck['description'],
                        'action' => 'Review and resolve workflow bottlenecks',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'total_recommendations' => count($recommendations),
                    'priority_breakdown' => collect($recommendations)->groupBy('priority')->map->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get workflow recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get HUB distribution summary
     */
    public function getHubDistributionSummary(): JsonResponse
    {
        try {
            $summary = $this->workflowService->getHubDistributionSummary();

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get HUB distribution summary: ' . $e->getMessage()
            ], 500);
        }
    }
}
