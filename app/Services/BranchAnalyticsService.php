<?php

namespace App\Services;

use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\BranchWorker;
use App\Models\Shipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BranchAnalyticsService
{
    /**
     * Get comprehensive branch performance analytics
     */
    public function getBranchPerformanceAnalytics(Branch $branch, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'overview' => $this->getBranchOverview($branch),
            'capacity_metrics' => $this->getCapacityMetrics($branch),
            'performance_metrics' => $this->getPerformanceMetrics($branch, $startDate),
            'financial_metrics' => $this->getFinancialMetrics($branch, $startDate),
            'operational_efficiency' => $this->getOperationalEfficiency($branch, $startDate),
            'trends' => $this->getPerformanceTrends($branch, $days),
            'comparisons' => $this->getBranchComparisons($branch),
            'recommendations' => $this->getPerformanceRecommendations($branch),
        ];
    }

    /**
     * Get branch overview statistics
     */
    private function getBranchOverview(Branch $branch): array
    {
        return [
            'branch_info' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'type' => $branch->type,
                'is_hub' => $branch->is_hub,
                'status' => $branch->status,
                'hierarchy_level' => $branch->hierarchy_level,
            ],
            'workforce' => [
                'total_workers' => $branch->branchWorkers()->count(),
                'active_workers' => $branch->activeWorkers()->count(),
                'has_manager' => $branch->branchManager ? true : false,
                'manager_name' => $branch->branchManager?->user?->name,
            ],
            'current_load' => [
                'active_shipments' => $this->getActiveShipmentsCount($branch),
                'pending_tasks' => $this->getPendingTasksCount($branch),
                'capacity_utilization' => $branch->getCapacityMetrics()['utilization_rate'] ?? 0,
            ],
        ];
    }

    /**
     * Get detailed capacity metrics
     */
    private function getCapacityMetrics(Branch $branch): array
    {
        $activeWorkers = $branch->activeWorkers()->count();
        $activeShipments = $this->getActiveShipmentsCount($branch);

        // Calculate capacity based on worker roles and typical workloads
        $capacityByRole = $this->calculateCapacityByRole($branch);
        $totalCapacity = $capacityByRole['total_capacity'];
        $utilizationRate = $totalCapacity > 0 ? ($activeShipments / $totalCapacity) * 100 : 0;

        return [
            'workforce_capacity' => $capacityByRole,
            'current_workload' => [
                'active_shipments' => $activeShipments,
                'pending_pickups' => $this->getPendingPickupsCount($branch),
                'outstanding_deliveries' => $this->getOutstandingDeliveriesCount($branch),
            ],
            'utilization' => [
                'rate' => round($utilizationRate, 2),
                'status' => $this->getUtilizationStatus($utilizationRate),
                'thresholds' => [
                    'optimal_min' => 60,
                    'optimal_max' => 85,
                    'warning_threshold' => 90,
                    'critical_threshold' => 100,
                ],
            ],
            'capacity_forecast' => $this->getCapacityForecast($branch),
        ];
    }

    /**
     * Calculate capacity by worker role
     */
    private function calculateCapacityByRole(Branch $branch): array
    {
        $workersByRole = $branch->activeWorkers()->select('role')->get()->groupBy('role');

        $capacityByRole = [];
        $totalCapacity = 0;

        foreach ($workersByRole as $role => $workers) {
            $roleCapacity = $this->getCapacityForRole($role) * $workers->count();
            $capacityByRole[$role] = [
                'count' => $workers->count(),
                'capacity_per_worker' => $this->getCapacityForRole($role),
                'total_capacity' => $roleCapacity,
            ];
            $totalCapacity += $roleCapacity;
        }

        return array_merge($capacityByRole, ['total_capacity' => $totalCapacity]);
    }

    /**
     * Get capacity for specific role (shipments per day)
     */
    private function getCapacityForRole(string $role): int
    {
        return match($role) {
            'dispatcher' => 50,    // High volume coordination
            'driver' => 15,        // Delivery focused
            'supervisor' => 30,    // Management + operations
            'warehouse_worker' => 25, // Processing focused
            'customer_service' => 20, // Support focused
            default => 10,
        };
    }

    /**
     * Get performance metrics for the branch
     */
    private function getPerformanceMetrics(Branch $branch, Carbon $startDate): array
    {
        $shipments = $this->getBranchShipments($branch, $startDate);

        $totalShipments = $shipments->count();
        $deliveredShipments = $shipments->where('current_status', 'delivered')->count();
        $onTimeDeliveries = $this->calculateOnTimeDeliveries($shipments);

        $deliveryRate = $totalShipments > 0 ? ($deliveredShipments / $totalShipments) * 100 : 0;
        $onTimeRate = $deliveredShipments > 0 ? ($onTimeDeliveries / $deliveredShipments) * 100 : 0;

        return [
            'shipment_performance' => [
                'total_shipments' => $totalShipments,
                'delivered_shipments' => $deliveredShipments,
                'delivery_success_rate' => round($deliveryRate, 2),
                'on_time_delivery_rate' => round($onTimeRate, 2),
                'average_delivery_time' => $this->calculateAverageDeliveryTime($shipments),
            ],
            'quality_metrics' => [
                'customer_satisfaction' => $this->getCustomerSatisfactionScore($branch, $startDate),
                'complaint_rate' => $this->getComplaintRate($branch, $startDate),
                'return_rate' => $this->getReturnRate($branch, $startDate),
            ],
            'efficiency_metrics' => [
                'processing_time' => $this->getAverageProcessingTime($branch, $startDate),
                'resource_utilization' => $this->getResourceUtilization($branch, $startDate),
                'cost_per_shipment' => $this->getCostPerShipment($branch, $startDate),
            ],
        ];
    }

    /**
     * Get financial metrics for the branch
     */
    private function getFinancialMetrics(Branch $branch, Carbon $startDate): array
    {
        // This would integrate with actual financial data
        // For now, providing placeholder structure
        return [
            'revenue' => [
                'total_revenue' => $this->calculateBranchRevenue($branch, $startDate),
                'revenue_per_shipment' => $this->calculateRevenuePerShipment($branch, $startDate),
                'revenue_trend' => $this->getRevenueTrend($branch, $startDate),
            ],
            'costs' => [
                'operational_costs' => $this->calculateOperationalCosts($branch, $startDate),
                'labor_costs' => $this->calculateLaborCosts($branch, $startDate),
                'overhead_costs' => $this->calculateOverheadCosts($branch, $startDate),
            ],
            'profitability' => [
                'gross_profit' => $this->calculateGrossProfit($branch, $startDate),
                'net_profit' => $this->calculateNetProfit($branch, $startDate),
                'profit_margin' => $this->calculateProfitMargin($branch, $startDate),
            ],
        ];
    }

    /**
     * Get operational efficiency metrics
     */
    private function getOperationalEfficiency(Branch $branch, Carbon $startDate): array
    {
        return [
            'workflow_efficiency' => [
                'average_processing_time' => $this->getAverageProcessingTime($branch, $startDate),
                'bottleneck_analysis' => $this->analyzeBottlenecks($branch, $startDate),
                'automation_level' => $this->calculateAutomationLevel($branch),
            ],
            'resource_efficiency' => [
                'worker_productivity' => $this->calculateWorkerProductivity($branch, $startDate),
                'asset_utilization' => $this->calculateAssetUtilization($branch, $startDate),
                'space_utilization' => $this->calculateSpaceUtilization($branch),
            ],
            'service_quality' => [
                'first_time_resolution' => $this->getFirstTimeResolutionRate($branch, $startDate),
                'customer_wait_time' => $this->getAverageCustomerWaitTime($branch, $startDate),
                'service_level_agreement' => $this->getSLAAchievementRate($branch, $startDate),
            ],
        ];
    }

    /**
     * Get performance trends over time
     */
    private function getPerformanceTrends(Branch $branch, int $days): array
    {
        $trends = [];
        $periods = $this->getTrendPeriods($days);

        foreach ($periods as $period) {
            $trends[] = [
                'period' => $period['label'],
                'start_date' => $period['start']->format('Y-m-d'),
                'end_date' => $period['end']->format('Y-m-d'),
                'metrics' => $this->getPeriodMetrics($branch, $period['start'], $period['end']),
            ];
        }

        return $trends;
    }

    /**
     * Get branch comparisons with peers
     */
    private function getBranchComparisons(Branch $branch): array
    {
        $peerBranches = $this->getPeerBranches($branch);

        $comparisons = [];
        foreach ($peerBranches as $peer) {
            $comparisons[] = [
                'branch' => [
                    'id' => $peer->id,
                    'name' => $peer->name,
                    'type' => $peer->type,
                ],
                'metrics' => $this->getBranchOverview($peer),
                'performance' => $this->getPerformanceMetrics($peer, now()->subDays(30)),
            ];
        }

        return [
            'peer_comparison' => $comparisons,
            'rankings' => $this->getBranchRankings($branch, $peerBranches),
        ];
    }

    /**
     * Get performance recommendations
     */
    private function getPerformanceRecommendations(Branch $branch): array
    {
        $recommendations = [];
        $metrics = $branch->getCapacityMetrics();

        // Capacity recommendations
        $utilizationRate = $metrics['utilization_rate'] ?? 0;
        if ($utilizationRate < 50) {
            $recommendations[] = [
                'type' => 'capacity',
                'priority' => 'medium',
                'title' => 'Underutilized Capacity',
                'description' => 'Branch capacity is underutilized. Consider expanding service area or optimizing workforce.',
                'action_items' => [
                    'Review service coverage areas',
                    'Consider cross-training workers',
                    'Evaluate peak hour scheduling',
                ],
            ];
        } elseif ($utilizationRate > 90) {
            $recommendations[] = [
                'type' => 'capacity',
                'priority' => 'high',
                'title' => 'Overloaded Capacity',
                'description' => 'Branch is operating near capacity limits. Immediate action required.',
                'action_items' => [
                    'Hire additional staff',
                    'Implement overtime scheduling',
                    'Review workflow bottlenecks',
                ],
            ];
        }

        // Performance recommendations based on KPIs
        $performance = $this->getPerformanceMetrics($branch, now()->subDays(30));
        $onTimeRate = $performance['shipment_performance']['on_time_delivery_rate'] ?? 0;

        if ($onTimeRate < 85) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'title' => 'Delivery Performance Improvement Needed',
                'description' => 'On-time delivery rate is below target. Process improvements required.',
                'action_items' => [
                    'Review delivery routing optimization',
                    'Implement GPS tracking for drivers',
                    'Streamline pickup processes',
                ],
            ];
        }

        return $recommendations;
    }

    // Helper Methods

    private function getActiveShipmentsCount(Branch $branch): int
    {
        return $branch->originShipments()
            ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->count();
    }

    private function getPendingTasksCount(Branch $branch): int
    {
        // This would integrate with a task system
        return 0; // Placeholder
    }

    private function getPendingPickupsCount(Branch $branch): int
    {
        return $branch->originShipments()
            ->where('current_status', 'pending_pickup')
            ->count();
    }

    private function getOutstandingDeliveriesCount(Branch $branch): int
    {
        return $branch->destShipments()
            ->whereIn('current_status', ['in_transit', 'out_for_delivery'])
            ->count();
    }

    private function getUtilizationStatus(float $rate): string
    {
        if ($rate < 50) return 'underutilized';
        if ($rate < 80) return 'optimal';
        if ($rate < 95) return 'high';
        return 'critical';
    }

    private function getCapacityForecast(Branch $branch): array
    {
        // Simple forecasting based on recent trends
        $recentShipments = $this->getBranchShipments($branch, now()->subDays(7))->count();
        $avgDaily = $recentShipments / 7;

        return [
            'current_trend' => round($avgDaily, 1),
            'forecast_7_days' => round($avgDaily * 7, 0),
            'forecast_30_days' => round($avgDaily * 30, 0),
            'recommended_capacity' => round($avgDaily * 1.2, 0), // 20% buffer
        ];
    }

    private function getBranchShipments(Branch $branch, Carbon $startDate): Collection
    {
        return $branch->originShipments()
            ->where('created_at', '>=', $startDate)
            ->get();
    }

    private function calculateOnTimeDeliveries(Collection $shipments): int
    {
        return $shipments->where('current_status', 'delivered')
            ->filter(function ($shipment) {
                return $shipment->delivered_at &&
                       $shipment->delivered_at <= $shipment->expected_delivery_date;
            })
            ->count();
    }

    private function calculateAverageDeliveryTime(Collection $shipments): ?float
    {
        $deliveredShipments = $shipments->where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('created_at');

        if ($deliveredShipments->isEmpty()) {
            return null;
        }

        $totalHours = $deliveredShipments->sum(function ($shipment) {
            return $shipment->created_at->diffInHours($shipment->delivered_at);
        });

        return round($totalHours / $deliveredShipments->count(), 1);
    }

    // Placeholder methods for metrics that would integrate with actual systems
    private function getCustomerSatisfactionScore(Branch $branch, Carbon $startDate): float { return 85.5; }
    private function getComplaintRate(Branch $branch, Carbon $startDate): float { return 2.3; }
    private function getReturnRate(Branch $branch, Carbon $startDate): float { return 1.8; }
    private function getAverageProcessingTime(Branch $branch, Carbon $startDate): float { return 45.2; }
    private function getResourceUtilization(Branch $branch, Carbon $startDate): float { return 78.5; }
    private function getCostPerShipment(Branch $branch, Carbon $startDate): float { return 12.50; }
    private function calculateBranchRevenue(Branch $branch, Carbon $startDate): float { return 125000.00; }
    private function calculateRevenuePerShipment(Branch $branch, Carbon $startDate): float { return 25.00; }
    private function getRevenueTrend(Branch $branch, Carbon $startDate): array { return ['growth' => 8.5, 'trend' => 'increasing']; }
    private function calculateOperationalCosts(Branch $branch, Carbon $startDate): float { return 87500.00; }
    private function calculateLaborCosts(Branch $branch, Carbon $startDate): float { return 45000.00; }
    private function calculateOverheadCosts(Branch $branch, Carbon $startDate): float { return 12500.00; }
    private function calculateGrossProfit(Branch $branch, Carbon $startDate): float { return 37500.00; }
    private function calculateNetProfit(Branch $branch, Carbon $startDate): float { return 25000.00; }
    private function calculateProfitMargin(Branch $branch, Carbon $startDate): float { return 20.0; }
    private function analyzeBottlenecks(Branch $branch, Carbon $startDate): array { return ['pickup_process' => 'high', 'delivery_routing' => 'medium']; }
    private function calculateAutomationLevel(Branch $branch): float { return 65.0; }
    private function calculateWorkerProductivity(Branch $branch, Carbon $startDate): float { return 85.5; }
    private function calculateAssetUtilization(Branch $branch, Carbon $startDate): float { return 72.3; }
    private function calculateSpaceUtilization(Branch $branch): float { return 68.7; }
    private function getFirstTimeResolutionRate(Branch $branch, Carbon $startDate): float { return 78.5; }
    private function getAverageCustomerWaitTime(Branch $branch, Carbon $startDate): float { return 12.3; }
    private function getSLAAchievementRate(Branch $branch, Carbon $startDate): float { return 92.1; }

    private function getTrendPeriods(int $days): array
    {
        $periods = [];
        $periodLength = max(1, $days / 6); // Divide into 6 periods

        for ($i = 5; $i >= 0; $i--) {
            $endDate = now()->subDays($i * $periodLength);
            $startDate = $endDate->copy()->subDays($periodLength);

            $periods[] = [
                'label' => $startDate->format('M j') . ' - ' . $endDate->format('M j'),
                'start' => $startDate,
                'end' => $endDate,
            ];
        }

        return $periods;
    }

    private function getPeriodMetrics(Branch $branch, Carbon $start, Carbon $end): array
    {
        $shipments = $branch->originShipments()
            ->whereBetween('created_at', [$start, $end])
            ->get();

        return [
            'total_shipments' => $shipments->count(),
            'delivered_shipments' => $shipments->where('current_status', 'delivered')->count(),
            'on_time_rate' => $this->calculateOnTimeDeliveries($shipments),
        ];
    }

    private function getPeerBranches(Branch $branch): Collection
    {
        return Branch::active()
            ->where('type', $branch->type)
            ->where('id', '!=', $branch->id)
            ->limit(5)
            ->get();
    }

    private function getBranchRankings(Branch $branch, Collection $peers): array
    {
        // This would implement ranking logic based on various metrics
        return [
            'efficiency_rank' => 3,
            'total_branches_compared' => $peers->count() + 1,
            'performance_percentile' => 75,
        ];
    }
}