<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Events\OperationalAlertEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ControlTowerService
{
    protected DispatchBoardService $dispatchService;
    protected ExceptionTowerService $exceptionService;
    protected AssetManagementService $assetService;

    public function __construct(
        DispatchBoardService $dispatchService,
        ExceptionTowerService $exceptionService,
        AssetManagementService $assetService
    ) {
        $this->dispatchService = $dispatchService;
        $this->exceptionService = $exceptionService;
        $this->assetService = $assetService;
    }

    /**
     * Get real-time operational KPIs
     * Note: Some metrics unavailable due to missing database columns
     */
    public function getOperationalKPIs(): array
    {
        return Cache::remember('operational_kpis', 300, function () { // Cache for 5 minutes
            try {
                $now = now();
                $today = $now->toDateString();

                // Shipment metrics
                $totalShipments = Shipment::count();
                $activeShipments = Shipment::whereIn('current_status', [
                    'CREATED', 'HANDED_OVER', 'ARRIVE', 'SORT', 'LOAD', 'DEPART', 
                    'IN_TRANSIT', 'ARRIVE_DEST', 'OUT_FOR_DELIVERY'
                ])->count();

                // Note: delivered_at column doesn't exist, using updated_at as proxy
                $deliveredToday = Shipment::where('current_status', 'DELIVERED')
                    ->whereDate('updated_at', $today)
                    ->count();

                // Exception tracking not yet implemented
                $exceptionsToday = 0;

                // Worker metrics
                $activeWorkers = BranchWorker::where('status', 'active')
                    ->whereNull('unassigned_at')
                    ->count();

                $totalWorkers = BranchWorker::currentlyAssigned()->count();

                // Branch metrics
                $activeBranches = Branch::active()->count();

                // Calculate utilization rates - wrapped in try-catch
                try {
                    $workerUtilization = $this->calculateWorkerUtilization();
                    $branchUtilization = $this->calculateBranchUtilization();
                    $onTimeRate = $this->calculateOnTimeDeliveryRate();
                    $avgProcessingTime = $this->calculateAverageProcessingTime();
                    $exceptionResolutionRate = $this->calculateExceptionResolutionRate();
                    $criticalAlerts = $this->getCriticalAlerts();
                } catch (\Exception $e) {
                    Log::warning('ControlTowerService: Some metrics unavailable - ' . $e->getMessage());
                    $workerUtilization = 0;
                    $branchUtilization = 0;
                    $onTimeRate = 0;
                    $avgProcessingTime = 0;
                    $exceptionResolutionRate = 0;
                    $criticalAlerts = [];
                }

                return [
                    'timestamp' => $now->toISOString(),
                    'shipments' => [
                        'total' => $totalShipments,
                        'active' => $activeShipments,
                        'delivered_today' => $deliveredToday,
                        'exceptions_today' => $exceptionsToday,
                        'active_percentage' => $totalShipments > 0 ? round(($activeShipments / $totalShipments) * 100, 1) : 0,
                    ],
                    'workers' => [
                        'total' => $totalWorkers,
                        'active' => $activeWorkers,
                        'utilization_rate' => $workerUtilization,
                        'active_percentage' => $totalWorkers > 0 ? round(($activeWorkers / $totalWorkers) * 100, 1) : 0,
                    ],
                    'branches' => [
                        'total' => Branch::count(),
                        'active' => $activeBranches,
                        'utilization_rate' => $branchUtilization,
                        'active_percentage' => Branch::count() > 0 ? round(($activeBranches / Branch::count()) * 100, 1) : 0,
                    ],
                    'performance' => [
                        'on_time_delivery_rate' => $onTimeRate,
                        'average_processing_time' => $avgProcessingTime,
                        'exception_resolution_rate' => $exceptionResolutionRate,
                    ],
                    'alerts' => $criticalAlerts,
                ];
            } catch (\Exception $e) {
                // If main query fails, return default KPIs
                Log::error('ControlTowerService: Failed to fetch KPIs - ' . $e->getMessage());
                return [
                    'timestamp' => now()->toISOString(),
                    'shipments' => ['total' => 0, 'active' => 0, 'delivered_today' => 0, 'exceptions_today' => 0, 'active_percentage' => 0],
                    'workers' => ['total' => 0, 'active' => 0, 'utilization_rate' => 0, 'active_percentage' => 0],
                    'branches' => ['total' => 0, 'active' => 0, 'utilization_rate' => 0, 'active_percentage' => 0],
                    'performance' => ['on_time_delivery_rate' => 0, 'average_processing_time' => 0, 'exception_resolution_rate' => 0],
                    'alerts' => [],
                ];
            }
        });
    }

    /**
     * Get branch performance metrics
     */
    public function getBranchPerformance(Branch $branch = null): array
    {
        $query = Branch::with(['originShipments', 'activeWorkers']);

        if ($branch) {
            $query->where('id', $branch->id);
        }

        $branches = $query->active()->get();

        return $branches->map(function ($branch) {
            $shipments = $branch->originShipments;
            $workers = $branch->activeWorkers;

            $deliveredShipments = $shipments->where('current_status', 'delivered');
            $exceptions = $shipments->where('has_exception', true);

            return [
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'type' => $branch->type,
                    'is_hub' => $branch->is_hub,
                ],
                'metrics' => [
                    'total_shipments' => $shipments->count(),
                    'delivered_shipments' => $deliveredShipments->count(),
                    'active_shipments' => $shipments->whereIn('current_status', [
                        'assigned', 'picked_up', 'at_hub', 'in_transit_to_destination', 'out_for_delivery'
                    ])->count(),
                    'exceptions_count' => $exceptions->count(),
                    'active_workers' => $workers->count(),
                    'worker_utilization' => $this->calculateBranchWorkerUtilization($branch),
                ],
                'performance' => [
                    'delivery_rate' => $shipments->count() > 0 ?
                        round(($deliveredShipments->count() / $shipments->count()) * 100, 1) : 0,
                    'exception_rate' => $shipments->count() > 0 ?
                        round(($exceptions->count() / $shipments->count()) * 100, 1) : 0,
                    'on_time_delivery_rate' => $this->calculateBranchOnTimeDeliveryRate($branch),
                    'average_processing_time_hours' => $this->calculateBranchAverageProcessingTime($branch),
                ],
                'capacity' => [
                    'current_load' => $workers->sum(function ($worker) {
                        return $worker->assignedShipments()
                            ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                            ->count();
                    }),
                    'max_capacity' => $workers->sum(function ($worker) {
                        return match($worker->role) {
                            'dispatcher' => 50,
                            'driver' => 15,
                            'supervisor' => 30,
                            'warehouse_worker' => 25,
                            'customer_service' => 20,
                            default => 10,
                        };
                    }),
                    'capacity_utilization' => $this->calculateBranchCapacityUtilization($branch),
                ],
            ];
        })->sortByDesc('metrics.total_shipments')->values()->toArray();
    }

    /**
     * Get worker utilization metrics
     */
    public function getWorkerUtilization(Branch $branch = null): array
    {
        $query = BranchWorker::with(['assignedShipments', 'branch']);

        if ($branch) {
            $query->where('branch_id', $branch->id);
        }

        $workers = $query->currentlyAssigned()->get();

        $workerMetrics = $workers->map(function ($worker) {
            $activeShipments = $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();

            $capacity = match($worker->role) {
                'dispatcher' => 50,
                'driver' => 15,
                'supervisor' => 30,
                'warehouse_worker' => 25,
                'customer_service' => 20,
                default => 10,
            };

            $deliveredToday = $worker->assignedShipments()
                ->where('current_status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count();

            return [
                'worker' => [
                    'id' => $worker->id,
                    'name' => $worker->full_name,
                    'role' => $worker->role,
                    'branch_name' => $worker->branch->name,
                ],
                'utilization' => [
                    'current_load' => $activeShipments,
                    'capacity' => $capacity,
                    'utilization_rate' => $capacity > 0 ? round(($activeShipments / $capacity) * 100, 1) : 0,
                    'status' => $this->getWorkerStatus($activeShipments, $capacity),
                ],
                'performance' => [
                    'delivered_today' => $deliveredToday,
                    'on_time_delivery_rate' => $this->calculateWorkerOnTimeDeliveryRate($worker),
                    'average_delivery_time' => $this->calculateWorkerAverageDeliveryTime($worker),
                ],
            ];
        });

        return [
            'summary' => [
                'total_workers' => $workers->count(),
                'active_workers' => $workerMetrics->where('utilization.status', 'active')->count(),
                'overloaded_workers' => $workerMetrics->where('utilization.status', 'overloaded')->count(),
                'underutilized_workers' => $workerMetrics->where('utilization.status', 'underutilized')->count(),
                'average_utilization' => $workerMetrics->avg('utilization.utilization_rate'),
            ],
            'workers' => $workerMetrics->sortByDesc('utilization.current_load')->values()->toArray(),
        ];
    }

    /**
     * Get shipment metrics
     */
    public function getShipmentMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $shipments = Shipment::whereBetween('created_at', [$startDate, $endDate])->get();

        $statusDistribution = $shipments->groupBy('current_status')->map->count();

        $deliveredShipments = $shipments->where('current_status', 'delivered');
        $exceptions = $shipments->where('has_exception', true);

        $processingTimes = $deliveredShipments->map(function ($shipment) {
            return $shipment->created_at->diffInHours($shipment->delivered_at);
        });

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'volume' => [
                'total_shipments' => $shipments->count(),
                'daily_average' => round($shipments->count() / max(1, $startDate->diffInDays($endDate) + 1), 1),
                'delivered_shipments' => $deliveredShipments->count(),
                'delivery_rate' => $shipments->count() > 0 ?
                    round(($deliveredShipments->count() / $shipments->count()) * 100, 1) : 0,
            ],
            'status_distribution' => $statusDistribution,
            'exceptions' => [
                'total_exceptions' => $exceptions->count(),
                'exception_rate' => $shipments->count() > 0 ?
                    round(($exceptions->count() / $shipments->count()) * 100, 1) : 0,
                'by_type' => $exceptions->groupBy('exception_type')->map->count(),
                'by_severity' => $exceptions->groupBy('exception_severity')->map->count(),
            ],
            'performance' => [
                'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($shipments),
                'average_processing_time_hours' => $processingTimes->avg() ?? 0,
                'median_processing_time_hours' => $processingTimes->median() ?? 0,
                'fastest_delivery_hours' => $processingTimes->min() ?? 0,
                'slowest_delivery_hours' => $processingTimes->max() ?? 0,
            ],
            'trends' => $this->calculateShipmentTrends($shipments, $startDate, $endDate),
        ];
    }

    /**
     * Get critical alerts requiring immediate attention
     */
    public function getAlerts(): array
    {
        $alerts = [];

        // High exception count alert
        $exceptionsToday = Shipment::where('has_exception', true)
            ->whereDate('exception_occurred_at', today())
            ->count();

        if ($exceptionsToday > 10) {
            $alerts[] = [
                'type' => 'high_exception_count',
                'severity' => 'high',
                'title' => 'High Exception Count Today',
                'message' => "{$exceptionsToday} exceptions recorded today",
                'action_required' => 'Review exception tower for resolution',
                'timestamp' => now()->toISOString(),
            ];
        }

        // Worker capacity alerts
        $overloadedWorkers = $this->getOverloadedWorkers();
        if ($overloadedWorkers->count() > 0) {
            $alerts[] = [
                'type' => 'worker_overload',
                'severity' => 'medium',
                'title' => 'Worker Overload Detected',
                'message' => "{$overloadedWorkers->count()} workers are overloaded",
                'action_required' => 'Reassign shipments or add more workers',
                'timestamp' => now()->toISOString(),
            ];
        }

        // SLA breach risk
        $atRiskShipments = $this->getSLABreachRiskShipments();
        if ($atRiskShipments->count() > 0) {
            $alerts[] = [
                'type' => 'sla_breach_risk',
                'severity' => 'high',
                'title' => 'SLA Breach Risk',
                'message' => "{$atRiskShipments->count()} shipments at risk of SLA breach",
                'action_required' => 'Prioritize delivery of at-risk shipments',
                'timestamp' => now()->toISOString(),
            ];
        }

        // Asset maintenance alerts
        $maintenanceAlerts = $this->assetService->checkMaintenanceAlerts();
        foreach ($maintenanceAlerts as $alert) {
            if ($alert['type'] === 'maintenance_overdue') {
                $alerts[] = [
                    'type' => 'asset_maintenance',
                    'severity' => 'medium',
                    'title' => 'Overdue Asset Maintenance',
                    'message' => "Maintenance overdue for {$alert['asset_name']}",
                    'action_required' => 'Schedule maintenance immediately',
                    'timestamp' => now()->toISOString(),
                ];
            }
        }

        // Stuck shipments alert
        $stuckShipments = Shipment::where('updated_at', '<', now()->subHours(24))
            ->whereNotIn('current_status', ['delivered', 'cancelled'])
            ->count();

        if ($stuckShipments > 5) {
            $alerts[] = [
                'type' => 'stuck_shipments',
                'severity' => 'medium',
                'title' => 'Stuck Shipments Detected',
                'message' => "{$stuckShipments} shipments haven't been updated in 24+ hours",
                'action_required' => 'Review and resolve stuck shipments',
                'timestamp' => now()->toISOString(),
            ];
        }

        return $alerts;
    }

    /**
     * Get operational trends
     */
    public function getOperationalTrends(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        $dailyMetrics = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();

            $dayShipments = Shipment::whereDate('created_at', $dateString)->get();
            $dayDeliveries = Shipment::where('current_status', 'delivered')
                ->whereDate('delivered_at', $dateString)
                ->count();
            $dayExceptions = Shipment::where('has_exception', true)
                ->whereDate('exception_occurred_at', $dateString)
                ->count();

            $dailyMetrics[] = [
                'date' => $dateString,
                'shipments_created' => $dayShipments->count(),
                'deliveries' => $dayDeliveries,
                'exceptions' => $dayExceptions,
                'delivery_rate' => $dayShipments->count() > 0 ?
                    round(($dayDeliveries / $dayShipments->count()) * 100, 1) : 0,
            ];

            $currentDate->addDay();
        }

        return [
            'period_days' => $days,
            'daily_metrics' => $dailyMetrics,
            'summary' => [
                'total_shipments' => array_sum(array_column($dailyMetrics, 'shipments_created')),
                'total_deliveries' => array_sum(array_column($dailyMetrics, 'deliveries')),
                'total_exceptions' => array_sum(array_column($dailyMetrics, 'exceptions')),
                'average_daily_shipments' => round(array_sum(array_column($dailyMetrics, 'shipments_created')) / $days, 1),
                'average_delivery_rate' => round(array_sum(array_column($dailyMetrics, 'delivery_rate')) / count($dailyMetrics), 1),
            ],
            'trends' => [
                'shipment_volume_trend' => $this->calculateTrend(array_column($dailyMetrics, 'shipments_created')),
                'delivery_rate_trend' => $this->calculateTrend(array_column($dailyMetrics, 'delivery_rate')),
                'exception_trend' => $this->calculateTrend(array_column($dailyMetrics, 'exceptions')),
            ],
        ];
    }

    /**
     * Calculate worker utilization rate
     */
    private function calculateWorkerUtilization(): float
    {
        $workers = BranchWorker::currentlyAssigned()->get();

        if ($workers->isEmpty()) {
            return 0.0;
        }

        $totalUtilization = $workers->sum(function ($worker) {
            $activeShipments = $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();

            $capacity = match($worker->role) {
                'dispatcher' => 50,
                'driver' => 15,
                'supervisor' => 30,
                'warehouse_worker' => 25,
                'customer_service' => 20,
                default => 10,
            };

            return $capacity > 0 ? ($activeShipments / $capacity) * 100 : 0;
        });

        return round($totalUtilization / $workers->count(), 1);
    }

    /**
     * Calculate branch utilization rate
     */
    private function calculateBranchUtilization(): float
    {
        $branches = Branch::active()->get();

        if ($branches->isEmpty()) {
            return 0.0;
        }

        $totalUtilization = $branches->sum(function ($branch) {
            $workers = $branch->activeWorkers;
            $currentLoad = $workers->sum(function ($worker) {
                return $worker->assignedShipments()
                    ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                    ->count();
            });

            $maxCapacity = $workers->sum(function ($worker) {
                return match($worker->role) {
                    'dispatcher' => 50,
                    'driver' => 15,
                    'supervisor' => 30,
                    'warehouse_worker' => 25,
                    'customer_service' => 20,
                    default => 10,
                };
            });

            return $maxCapacity > 0 ? ($currentLoad / $maxCapacity) * 100 : 0;
        });

        return round($totalUtilization / $branches->count(), 1);
    }

    /**
     * Calculate on-time delivery rate
     */
    private function calculateOnTimeDeliveryRate(Collection $shipments = null): float
    {
        $shipments = $shipments ?? Shipment::where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('expected_delivery_date')
            ->get();

        if ($shipments->isEmpty()) {
            return 0.0;
        }

        $onTimeDeliveries = $shipments->filter(function ($shipment) {
            return $shipment->delivered_at <= $shipment->expected_delivery_date;
        })->count();

        return round(($onTimeDeliveries / $shipments->count()) * 100, 1);
    }

    /**
     * Calculate average processing time
     */
    private function calculateAverageProcessingTime(): float
    {
        $deliveredShipments = Shipment::where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('created_at')
            ->get();

        if ($deliveredShipments->isEmpty()) {
            return 0.0;
        }

        $totalHours = $deliveredShipments->sum(function ($shipment) {
            return $shipment->created_at->diffInHours($shipment->delivered_at);
        });

        return round($totalHours / $deliveredShipments->count(), 1);
    }

    /**
     * Calculate exception resolution rate
     */
    private function calculateExceptionResolutionRate(): float
    {
        $exceptions = Shipment::where('has_exception', true)->get();
        $resolvedExceptions = $exceptions->whereNotNull('exception_resolved_at');

        if ($exceptions->isEmpty()) {
            return 100.0;
        }

        return round(($resolvedExceptions->count() / $exceptions->count()) * 100, 1);
    }

    /**
     * Get critical alerts
     */
    private function getCriticalAlerts(): array
    {
        return $this->getAlerts();
    }

    /**
     * Calculate branch worker utilization
     */
    private function calculateBranchWorkerUtilization(Branch $branch): float
    {
        $workers = $branch->activeWorkers;

        if ($workers->isEmpty()) {
            return 0.0;
        }

        return round($workers->avg(function ($worker) {
            $activeShipments = $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();

            $capacity = match($worker->role) {
                'dispatcher' => 50,
                'driver' => 15,
                'supervisor' => 30,
                'warehouse_worker' => 25,
                'customer_service' => 20,
                default => 10,
            };

            return $capacity > 0 ? ($activeShipments / $capacity) * 100 : 0;
        }), 1);
    }

    /**
     * Calculate branch on-time delivery rate
     */
    private function calculateBranchOnTimeDeliveryRate(Branch $branch): float
    {
        $deliveredShipments = $branch->originShipments()
            ->where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('expected_delivery_date')
            ->get();

        if ($deliveredShipments->isEmpty()) {
            return 0.0;
        }

        $onTimeDeliveries = $deliveredShipments->filter(function ($shipment) {
            return $shipment->delivered_at <= $shipment->expected_delivery_date;
        })->count();

        return round(($onTimeDeliveries / $deliveredShipments->count()) * 100, 1);
    }

    /**
     * Calculate branch average processing time
     */
    private function calculateBranchAverageProcessingTime(Branch $branch): float
    {
        $deliveredShipments = $branch->originShipments()
            ->where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('created_at')
            ->get();

        if ($deliveredShipments->isEmpty()) {
            return 0.0;
        }

        $totalHours = $deliveredShipments->sum(function ($shipment) {
            return $shipment->created_at->diffInHours($shipment->delivered_at);
        });

        return round($totalHours / $deliveredShipments->count(), 1);
    }

    /**
     * Calculate branch capacity utilization
     */
    private function calculateBranchCapacityUtilization(Branch $branch): float
    {
        $workers = $branch->activeWorkers;

        if ($workers->isEmpty()) {
            return 0.0;
        }

        $currentLoad = $workers->sum(function ($worker) {
            return $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();
        });

        $maxCapacity = $workers->sum(function ($worker) {
            return match($worker->role) {
                'dispatcher' => 50,
                'driver' => 15,
                'supervisor' => 30,
                'warehouse_worker' => 25,
                'customer_service' => 20,
                default => 10,
            };
        });

        return $maxCapacity > 0 ? round(($currentLoad / $maxCapacity) * 100, 1) : 0.0;
    }

    /**
     * Get worker status
     */
    private function getWorkerStatus(int $currentLoad, int $capacity): string
    {
        $utilization = $capacity > 0 ? ($currentLoad / $capacity) * 100 : 0;

        if ($utilization >= 90) {
            return 'overloaded';
        } elseif ($utilization <= 30) {
            return 'underutilized';
        } else {
            return 'active';
        }
    }

    /**
     * Calculate worker on-time delivery rate
     */
    private function calculateWorkerOnTimeDeliveryRate(BranchWorker $worker): float
    {
        $deliveredShipments = $worker->assignedShipments()
            ->where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('expected_delivery_date')
            ->get();

        if ($deliveredShipments->isEmpty()) {
            return 0.0;
        }

        $onTimeDeliveries = $deliveredShipments->filter(function ($shipment) {
            return $shipment->delivered_at <= $shipment->expected_delivery_date;
        })->count();

        return round(($onTimeDeliveries / $deliveredShipments->count()) * 100, 1);
    }

    /**
     * Calculate worker average delivery time
     */
    private function calculateWorkerAverageDeliveryTime(BranchWorker $worker): float
    {
        $deliveredShipments = $worker->assignedShipments()
            ->where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('assigned_at')
            ->get();

        if ($deliveredShipments->isEmpty()) {
            return 0.0;
        }

        $totalHours = $deliveredShipments->sum(function ($shipment) {
            return $shipment->assigned_at->diffInHours($shipment->delivered_at);
        });

        return round($totalHours / $deliveredShipments->count(), 1);
    }

    /**
     * Get overloaded workers
     */
    private function getOverloadedWorkers(): Collection
    {
        return BranchWorker::currentlyAssigned()->get()->filter(function ($worker) {
            $activeShipments = $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count();

            $capacity = match($worker->role) {
                'dispatcher' => 50,
                'driver' => 15,
                'supervisor' => 30,
                'warehouse_worker' => 25,
                'customer_service' => 20,
                default => 10,
            };

            return $capacity > 0 && ($activeShipments / $capacity) > 0.9;
        });
    }

    /**
     * Get shipments at risk of SLA breach
     */
    private function getSLABreachRiskShipments(): Collection
    {
        return Shipment::where('expected_delivery_date', '<=', now()->addHours(24))
            ->whereNotIn('current_status', ['delivered', 'cancelled'])
            ->where('has_exception', false)
            ->get();
    }

    /**
     * Calculate shipment trends
     */
    private function calculateShipmentTrends(Collection $shipments, Carbon $startDate, Carbon $endDate): array
    {
        $days = $startDate->diffInDays($endDate) + 1;
        $dailyData = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayShipments = $shipments->filter(function ($shipment) use ($date) {
                return $shipment->created_at->toDateString() === $date->toDateString();
            });

            $dailyData[] = $dayShipments->count();
        }

        return [
            'daily_volume' => $dailyData,
            'trend' => $this->calculateTrend($dailyData),
            'peak_day' => $startDate->copy()->addDays(array_search(max($dailyData), $dailyData)),
            'peak_volume' => max($dailyData),
        ];
    }

    /**
     * Calculate trend direction
     */
    private function calculateTrend(array $data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }

        $firstHalf = array_slice($data, 0, intval(count($data) / 2));
        $secondHalf = array_slice($data, intval(count($data) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $change = (($secondAvg - $firstAvg) / max($firstAvg, 1)) * 100;

        if ($change > 10) {
            return 'increasing';
        } elseif ($change < -10) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
}