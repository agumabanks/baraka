<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DispatchBoardService
{
    /**
     * Get dispatch board for a branch and date
     */
    public function getDispatchBoard(Branch $branch, Carbon $date): array
    {
        $workers = $this->getBranchWorkers($branch);
        $unassignedShipments = $this->getUnassignedShipments($branch, $date);
        $loadBalancingMetrics = $this->getLoadBalancingMetrics($branch, $date);

        return [
            'branch' => $branch,
            'date' => $date->toDateString(),
            'workers' => $workers,
            'unassigned_shipments' => $unassignedShipments,
            'load_balancing' => $loadBalancingMetrics,
            'summary' => [
                'total_workers' => $workers->count(),
                'active_workers' => $workers->where('is_active', true)->count(),
                'unassigned_shipments_count' => $unassignedShipments->count(),
                'total_capacity' => $workers->sum('capacity'),
                'current_load' => $workers->sum('current_load'),
                'utilization_rate' => $this->calculateOverallUtilization($workers),
            ],
        ];
    }

    /**
     * Get workers for a branch with their current workload
     */
    private function getBranchWorkers(Branch $branch): Collection
    {
        return $branch->activeWorkers()
            ->with(['user', 'assignedShipments' => function ($query) {
                $query->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery']);
            }])
            ->get()
            ->map(function ($worker) {
                $activeShipments = $worker->assignedShipments;
                $capacity = $this->getWorkerCapacity($worker);

                return [
                    'id' => $worker->id,
                    'name' => $worker->full_name,
                    'role' => $worker->role,
                    'is_active' => $worker->isCurrentlyActive,
                    'current_load' => $activeShipments->count(),
                    'capacity' => $capacity,
                    'utilization_rate' => $capacity > 0 ? round(($activeShipments->count() / $capacity) * 100, 1) : 0,
                    'assigned_shipments' => $activeShipments->map(function ($shipment) {
                        return [
                            'id' => $shipment->id,
                            'tracking_number' => $shipment->tracking_number,
                            'customer_name' => $shipment->customer->name ?? 'Unknown',
                            'destination' => $shipment->destBranch->name ?? 'Unknown',
                            'status' => $shipment->current_status,
                            'priority' => $shipment->priority ?? 1,
                            'assigned_at' => $shipment->assigned_at?->toISOString(),
                        ];
                    }),
                    'next_available' => $this->getWorkerNextAvailable($worker),
                ];
            });
    }

    /**
     * Get unassigned shipments for a branch
     */
    private function getUnassignedShipments(Branch $branch, Carbon $date): Collection
    {
        return Shipment::where(function ($query) use ($branch) {
                $query->where('origin_branch_id', $branch->id)
                      ->orWhere('dest_branch_id', $branch->id);
            })
            ->whereNull('assigned_worker_id')
            ->whereIn('current_status', ['pending', 'ready_for_assignment'])
            ->whereDate('created_at', '>=', $date->startOfDay())
            ->with(['customer', 'originBranch', 'destBranch'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get()
            ->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'customer_name' => $shipment->customer->name ?? 'Unknown',
                    'origin' => $shipment->originBranch->name ?? 'Unknown',
                    'destination' => $shipment->destBranch->name ?? 'Unknown',
                    'service_level' => $shipment->service_level,
                    'priority' => $shipment->priority ?? 1,
                    'created_at' => $shipment->created_at->toISOString(),
                    'expected_delivery' => $shipment->expected_delivery_date?->toISOString(),
                    'is_urgent' => $this->isShipmentUrgent($shipment),
                ];
            });
    }

    /**
     * Get load balancing metrics for a branch
     */
    private function getLoadBalancingMetrics(Branch $branch, Carbon $date): array
    {
        $workers = $branch->activeWorkers()->with('assignedShipments')->get();

        $workerLoads = $workers->map(function ($worker) {
            $activeShipments = $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();

            return [
                'worker_id' => $worker->id,
                'load' => $activeShipments,
                'capacity' => $this->getWorkerCapacity($worker),
            ];
        });

        $totalCapacity = $workerLoads->sum('capacity');
        $totalLoad = $workerLoads->sum('load');
        $averageLoad = $workers->count() > 0 ? $totalLoad / $workers->count() : 0;

        // Calculate load distribution variance
        $loadVariance = $workerLoads->map(function ($worker) use ($averageLoad) {
            return pow($worker['load'] - $averageLoad, 2);
        })->avg();

        return [
            'total_capacity' => $totalCapacity,
            'total_load' => $totalLoad,
            'average_load' => round($averageLoad, 1),
            'load_variance' => round($loadVariance, 2),
            'utilization_rate' => $totalCapacity > 0 ? round(($totalLoad / $totalCapacity) * 100, 1) : 0,
            'balancing_status' => $this->getBalancingStatus($loadVariance, $averageLoad),
            'recommendations' => $this->getLoadBalancingRecommendations($workerLoads),
        ];
    }

    /**
     * Assign shipment to worker
     */
    public function assignShipmentToWorker(Shipment $shipment, BranchWorker $worker): array
    {
        // Validate assignment
        if (!$worker->isCurrentlyActive) {
            return [
                'success' => false,
                'message' => 'Worker is not currently active',
            ];
        }

        if (!$worker->canPerform('assign_shipments')) {
            return [
                'success' => false,
                'message' => 'Worker does not have permission to handle shipments',
            ];
        }

        $currentLoad = $worker->assignedShipments()
            ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->count();

        $capacity = $this->getWorkerCapacity($worker);

        if ($currentLoad >= $capacity) {
            return [
                'success' => false,
                'message' => 'Worker has reached maximum capacity',
            ];
        }

        DB::beginTransaction();
        try {
            $shipment->update([
                'assigned_worker_id' => $worker->id,
                'current_status' => 'assigned',
                'assigned_at' => now(),
            ]);

            // Log the assignment
            activity()
                ->performedOn($shipment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'worker_id' => $worker->id,
                    'worker_name' => $worker->full_name,
                    'assigned_by' => auth()->user()->name,
                ])
                ->log("Shipment assigned to worker: {$worker->full_name}");

            DB::commit();

            return [
                'success' => true,
                'message' => 'Shipment assigned successfully',
                'assignment' => [
                    'shipment_id' => $shipment->id,
                    'worker_id' => $worker->id,
                    'worker_name' => $worker->full_name,
                    'assigned_at' => $shipment->assigned_at->toISOString(),
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shipment assignment failed', [
                'shipment_id' => $shipment->id,
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to assign shipment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reassign shipment to different worker
     */
    public function reassignShipment(Shipment $shipment, BranchWorker $newWorker): array
    {
        $oldWorker = $shipment->assignedWorker;

        if (!$oldWorker) {
            return [
                'success' => false,
                'message' => 'Shipment is not currently assigned to any worker',
            ];
        }

        DB::beginTransaction();
        try {
            $result = $this->assignShipmentToWorker($shipment, $newWorker);

            if (!$result['success']) {
                DB::rollBack();
                return $result;
            }

            // Log reassignment
            activity()
                ->performedOn($shipment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_worker' => $oldWorker->full_name,
                    'new_worker' => $newWorker->full_name,
                    'reassigned_by' => auth()->user()->name,
                ])
                ->log("Shipment reassigned from {$oldWorker->full_name} to {$newWorker->full_name}");

            DB::commit();

            return [
                'success' => true,
                'message' => 'Shipment reassigned successfully',
                'reassignment' => [
                    'shipment_id' => $shipment->id,
                    'old_worker' => $oldWorker->full_name,
                    'new_worker' => $newWorker->full_name,
                    'reassigned_at' => now()->toISOString(),
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shipment reassignment failed', [
                'shipment_id' => $shipment->id,
                'old_worker_id' => $oldWorker->id,
                'new_worker_id' => $newWorker->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reassign shipment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get worker workload details
     */
    public function getWorkerWorkload(BranchWorker $worker): array
    {
        $activeShipments = $worker->assignedShipments()
            ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->with(['customer', 'originBranch', 'destBranch'])
            ->get();

        $capacity = $this->getWorkerCapacity($worker);
        $currentLoad = $activeShipments->count();

        return [
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->full_name,
                'role' => $worker->role,
                'branch' => $worker->branch->name,
            ],
            'workload' => [
                'current_load' => $currentLoad,
                'capacity' => $capacity,
                'utilization_rate' => $capacity > 0 ? round(($currentLoad / $capacity) * 100, 1) : 0,
                'available_slots' => max(0, $capacity - $currentLoad),
            ],
            'active_shipments' => $activeShipments->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'customer_name' => $shipment->customer->name ?? 'Unknown',
                    'destination' => $shipment->destBranch->name ?? 'Unknown',
                    'status' => $shipment->current_status,
                    'priority' => $shipment->priority ?? 1,
                    'assigned_at' => $shipment->assigned_at?->toISOString(),
                    'expected_delivery' => $shipment->expected_delivery_date?->toISOString(),
                ];
            }),
            'performance' => $worker->getPerformanceMetrics(),
        ];
    }

    /**
     * Auto-assign shipments using load balancing algorithm
     */
    public function autoAssignShipments(Branch $branch, int $maxAssignments = 10): array
    {
        $unassignedShipments = $this->getUnassignedShipments($branch, now());
        $workers = $branch->activeWorkers()->get();

        $assignments = [];
        $errors = [];

        foreach ($unassignedShipments->take($maxAssignments) as $shipment) {
            $bestWorker = $this->findBestWorkerForShipment($shipment, $workers);

            if ($bestWorker) {
                $result = $this->assignShipmentToWorker($shipment, $bestWorker);
                if ($result['success']) {
                    $assignments[] = $result['assignment'];
                } else {
                    $errors[] = [
                        'shipment_id' => $shipment->id,
                        'error' => $result['message'],
                    ];
                }
            } else {
                $errors[] = [
                    'shipment_id' => $shipment->id,
                    'error' => 'No suitable worker available',
                ];
            }
        }

        return [
            'assignments' => $assignments,
            'errors' => $errors,
            'summary' => [
                'total_processed' => count($assignments) + count($errors),
                'successful_assignments' => count($assignments),
                'failed_assignments' => count($errors),
            ],
        ];
    }

    /**
     * Find best worker for shipment based on load balancing
     */
    private function findBestWorkerForShipment(Shipment $shipment, Collection $workers): ?BranchWorker
    {
        $suitableWorkers = $workers->filter(function ($worker) use ($shipment) {
            return $worker->canPerform('deliver_shipments') &&
                   $worker->isCurrentlyActive &&
                   $this->getWorkerCapacity($worker) > $worker->assignedShipments()
                       ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                       ->count();
        });

        if ($suitableWorkers->isEmpty()) {
            return null;
        }

        // Find worker with lowest utilization rate
        return $suitableWorkers->sortBy(function ($worker) {
            $currentLoad = $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();
            $capacity = $this->getWorkerCapacity($worker);
            return $capacity > 0 ? $currentLoad / $capacity : 1;
        })->first();
    }

    /**
     * Get worker capacity based on role
     */
    private function getWorkerCapacity(BranchWorker $worker): int
    {
        return match($worker->role) {
            'dispatcher' => 50,
            'driver' => 15,
            'supervisor' => 30,
            'warehouse_worker' => 25,
            'customer_service' => 20,
            default => 10,
        };
    }

    /**
     * Get worker next available time
     */
    private function getWorkerNextAvailable(BranchWorker $worker): ?string
    {
        $lastShipment = $worker->assignedShipments()
            ->where('current_status', 'delivered')
            ->latest('delivered_at')
            ->first();

        if (!$lastShipment || !$lastShipment->delivered_at) {
            return now()->toISOString();
        }

        // Assume 30 minutes between deliveries
        return $lastShipment->delivered_at->addMinutes(30)->toISOString();
    }

    /**
     * Check if shipment is urgent
     */
    private function isShipmentUrgent(Shipment $shipment): bool
    {
        // High priority shipments are urgent
        if (($shipment->priority ?? 1) >= 3) {
            return true;
        }

        // Shipments with expected delivery today are urgent
        if ($shipment->expected_delivery_date &&
            $shipment->expected_delivery_date->isToday()) {
            return true;
        }

        // Express service level is urgent
        if ($shipment->service_level === 'express') {
            return true;
        }

        return false;
    }

    /**
     * Calculate overall utilization rate
     */
    private function calculateOverallUtilization(Collection $workers): float
    {
        if ($workers->isEmpty()) {
            return 0.0;
        }

        $totalCapacity = $workers->sum('capacity');
        $totalLoad = $workers->sum('current_load');

        return $totalCapacity > 0 ? round(($totalLoad / $totalCapacity) * 100, 1) : 0.0;
    }

    /**
     * Get balancing status
     */
    private function getBalancingStatus(float $variance, float $averageLoad): string
    {
        if ($variance < 1 && $averageLoad < 10) {
            return 'well_balanced';
        } elseif ($variance < 4) {
            return 'moderately_balanced';
        } else {
            return 'needs_rebalancing';
        }
    }

    /**
     * Get load balancing recommendations
     */
    private function getLoadBalancingRecommendations(Collection $workerLoads): array
    {
        $recommendations = [];

        $overloadedWorkers = $workerLoads->filter(function ($worker) {
            return ($worker['load'] / $worker['capacity']) > 0.8;
        });

        $underloadedWorkers = $workerLoads->filter(function ($worker) {
            return ($worker['load'] / $worker['capacity']) < 0.3;
        });

        if ($overloadedWorkers->count() > 0) {
            $recommendations[] = 'Reassign shipments from overloaded workers to available workers';
        }

        if ($underloadedWorkers->count() > 0) {
            $recommendations[] = 'Assign more shipments to underutilized workers';
        }

        if ($recommendations === []) {
            $recommendations[] = 'Workload is well balanced';
        }

        return $recommendations;
    }
}