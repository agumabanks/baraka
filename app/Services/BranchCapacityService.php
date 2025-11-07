<?php

namespace App\Services;

use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\Shipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BranchCapacityService
{
    /**
     * Get comprehensive capacity analysis for a branch
     */
    public function getCapacityAnalysis(Branch $branch, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'current_capacity' => $this->getCurrentCapacity($branch),
            'workload_analysis' => $this->getWorkloadAnalysis($branch, $startDate),
            'capacity_forecast' => $this->getCapacityForecast($branch, $days),
            'resource_allocation' => $this->getResourceAllocation($branch),
            'bottleneck_analysis' => $this->getBottleneckAnalysis($branch),
            'optimization_recommendations' => $this->getOptimizationRecommendations($branch),
        ];
    }

    /**
     * Get current capacity metrics
     */
    private function getCurrentCapacity(Branch $branch): array
    {
        $activeWorkers = $branch->activeWorkers()->count();
        $currentWorkload = $this->getCurrentWorkload($branch);

        $capacityByRole = $this->calculateCapacityByRole($branch);
        $totalCapacity = $capacityByRole['total_capacity'];
        $utilizationRate = $totalCapacity > 0 ? ($currentWorkload / $totalCapacity) * 100 : 0;

        return [
            'workforce_capacity' => $capacityByRole,
            'current_workload' => $currentWorkload,
            'available_capacity' => max(0, $totalCapacity - $currentWorkload),
            'utilization_rate' => round($utilizationRate, 2),
            'capacity_status' => $this->getCapacityStatus($utilizationRate),
            'peak_capacity_hours' => $this->getPeakCapacityHours($branch),
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
     * Get current workload (active shipments)
     */
    private function getCurrentWorkload(Branch $branch): int
    {
        return $branch->originShipments()
            ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->count();
    }

    /**
     * Get workload analysis over time
     */
    private function getWorkloadAnalysis(Branch $branch, Carbon $startDate): array
    {
        $dailyWorkload = $this->getDailyWorkloadData($branch, $startDate);

        return [
            'daily_patterns' => $dailyWorkload,
            'peak_hours' => $this->analyzePeakHours($dailyWorkload),
            'seasonal_trends' => $this->analyzeSeasonalTrends($dailyWorkload),
            'workload_distribution' => $this->analyzeWorkloadDistribution($branch),
            'efficiency_metrics' => $this->calculateEfficiencyMetrics($branch, $startDate),
        ];
    }

    /**
     * Get daily workload data
     */
    private function getDailyWorkloadData(Branch $branch, Carbon $startDate): Collection
    {
        return $branch->originShipments()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as shipment_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
    }

    /**
     * Analyze peak hours
     */
    private function analyzePeakHours(Collection $dailyWorkload): array
    {
        if ($dailyWorkload->isEmpty()) {
            return [
                'peak_days' => [],
                'average_daily_load' => 0,
                'peak_threshold' => 0,
                'peak_frequency' => 0,
            ];
        }

        $totalShipments = $dailyWorkload->sum('shipment_count');
        $daysCount = $dailyWorkload->count();
        $averageDailyLoad = $daysCount > 0 ? $totalShipments / $daysCount : 0;

        // Identify peak days (above average + 20%)
        $peakThreshold = $averageDailyLoad * 1.2;
        $peakDays = $dailyWorkload->filter(function ($day) use ($peakThreshold) {
            return $day['shipment_count'] > $peakThreshold;
        });

        return [
            'peak_days' => $peakDays->values(),
            'average_daily_load' => round($averageDailyLoad, 1),
            'peak_threshold' => round($peakThreshold, 1),
            'peak_frequency' => $daysCount > 0 ? round(($peakDays->count() / $daysCount) * 100, 1) : 0,
        ];
    }

    /**
     * Analyze seasonal trends
     */
    private function analyzeSeasonalTrends(Collection $dailyWorkload): array
    {
        if ($dailyWorkload->count() < 7) {
            return ['trend' => 'insufficient_data', 'growth_rate' => 0];
        }

        $firstHalf = $dailyWorkload->take(floor($dailyWorkload->count() / 2));
        $secondHalf = $dailyWorkload->skip(floor($dailyWorkload->count() / 2));

        $firstHalfAvg = $firstHalf->avg('shipment_count');
        $secondHalfAvg = $secondHalf->avg('shipment_count');

        $growthRate = $firstHalfAvg > 0 ? (($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg) * 100 : 0;

        return [
            'trend' => $growthRate > 5 ? 'increasing' : ($growthRate < -5 ? 'decreasing' : 'stable'),
            'growth_rate' => round($growthRate, 2),
            'first_half_average' => round($firstHalfAvg, 1),
            'second_half_average' => round($secondHalfAvg, 1),
        ];
    }

    /**
     * Analyze workload distribution
     */
    private function analyzeWorkloadDistribution(Branch $branch): array
    {
        $workers = $branch->activeWorkers()->with('user')->get();

        $workloadByWorker = $workers->map(function ($worker) {
            return [
                'worker_id' => $worker->id,
                'worker_name' => $worker->user->name ?? 'Unknown',
                'role' => $worker->role,
                'assigned_shipments' => $worker->assignedShipments()
                    ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                    ->count(),
                'capacity' => $this->getCapacityForRole($worker->role),
            ];
        });

        $totalAssigned = $workloadByWorker->sum('assigned_shipments');
        $totalCapacity = $workloadByWorker->sum('capacity');

        return [
            'worker_workload' => $workloadByWorker,
            'total_assigned' => $totalAssigned,
            'total_capacity' => $totalCapacity,
            'distribution_efficiency' => $totalCapacity > 0 ? round(($totalAssigned / $totalCapacity) * 100, 1) : 0,
            'workload_balance' => $this->calculateWorkloadBalance($workloadByWorker),
        ];
    }

    /**
     * Calculate workload balance among workers
     */
    private function calculateWorkloadBalance(Collection $workloadByWorker): array
    {
        if ($workloadByWorker->isEmpty()) {
            return ['balance_score' => 0, 'status' => 'no_workers'];
        }

        $utilizationRates = $workloadByWorker->map(function ($worker) {
            $capacity = $worker['capacity'];
            return $capacity > 0 ? ($worker['assigned_shipments'] / $capacity) * 100 : 0;
        });

        $avgUtilization = $utilizationRates->avg();
        $variance = $utilizationRates->map(function ($rate) use ($avgUtilization) {
            return pow($rate - $avgUtilization, 2);
        })->avg();

        $balanceScore = max(0, 100 - sqrt($variance)); // Higher score = better balance

        return [
            'balance_score' => round($balanceScore, 1),
            'average_utilization' => round($avgUtilization, 1),
            'utilization_variance' => round($variance, 2),
            'status' => $balanceScore > 80 ? 'well_balanced' : ($balanceScore > 60 ? 'moderately_balanced' : 'unbalanced'),
        ];
    }

    /**
     * Calculate efficiency metrics
     */
    private function calculateEfficiencyMetrics(Branch $branch, Carbon $startDate): array
    {
        $shipments = $branch->originShipments()
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalShipments = $shipments->count();
        $deliveredShipments = $shipments->where('current_status', 'delivered')->count();

        $avgProcessingTime = $this->calculateAverageProcessingTime($shipments);
        $throughputRate = $this->calculateThroughputRate($branch, $startDate);

        return [
            'total_shipments_processed' => $totalShipments,
            'delivery_success_rate' => $totalShipments > 0 ? round(($deliveredShipments / $totalShipments) * 100, 1) : 0,
            'average_processing_time_hours' => $avgProcessingTime,
            'throughput_rate_per_day' => $throughputRate,
            'efficiency_score' => $this->calculateEfficiencyScore($totalShipments, $avgProcessingTime, $throughputRate),
        ];
    }

    /**
     * Calculate average processing time
     */
    private function calculateAverageProcessingTime(Collection $shipments): float
    {
        $processedShipments = $shipments->where('current_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNotNull('created_at');

        if ($processedShipments->isEmpty()) {
            return 0;
        }

        $totalHours = $processedShipments->sum(function ($shipment) {
            return $shipment->created_at->diffInHours($shipment->delivered_at);
        });

        return round($totalHours / $processedShipments->count(), 1);
    }

    /**
     * Calculate throughput rate
     */
    private function calculateThroughputRate(Branch $branch, Carbon $startDate): float
    {
        $days = now()->diffInDays($startDate) ?: 1;
        $totalProcessed = $branch->originShipments()
            ->where('created_at', '>=', $startDate)
            ->where('current_status', 'delivered')
            ->count();

        return round($totalProcessed / $days, 1);
    }

    /**
     * Calculate efficiency score
     */
    private function calculateEfficiencyScore(int $totalShipments, float $avgProcessingTime, float $throughputRate): float
    {
        // Simple efficiency score based on processing time and throughput
        $timeScore = max(0, 100 - ($avgProcessingTime * 5)); // Faster processing = higher score
        $throughputScore = min(100, $throughputRate * 2); // Higher throughput = higher score

        return round(($timeScore + $throughputScore) / 2, 1);
    }

    /**
     * Get capacity forecast
     */
    private function getCapacityForecast(Branch $branch, int $days): array
    {
        $historicalData = $this->getDailyWorkloadData($branch, now()->subDays($days));

        if ($historicalData->isEmpty()) {
            return [
                'forecast_available' => false,
                'reason' => 'insufficient_historical_data',
            ];
        }

        $avgDailyLoad = $historicalData->avg('shipment_count');
        $trend = $this->analyzeSeasonalTrends($historicalData);

        // Simple linear forecasting
        $growthFactor = 1 + ($trend['growth_rate'] / 100);
        $forecastedLoad = $avgDailyLoad * $growthFactor;

        $currentCapacity = $this->getCurrentCapacity($branch)['workforce_capacity']['total_capacity'];

        return [
            'forecast_available' => true,
            'historical_average' => round($avgDailyLoad, 1),
            'forecasted_daily_load' => round($forecastedLoad, 1),
            'forecasted_weekly_load' => round($forecastedLoad * 7, 0),
            'forecasted_monthly_load' => round($forecastedLoad * 30, 0),
            'current_capacity' => $currentCapacity,
            'capacity_gap' => round($forecastedLoad - $currentCapacity, 1),
            'recommended_capacity' => round($forecastedLoad * 1.2, 0), // 20% buffer
            'confidence_level' => $this->calculateForecastConfidence($historicalData),
        ];
    }

    /**
     * Calculate forecast confidence level
     */
    private function calculateForecastConfidence(Collection $historicalData): string
    {
        $dataPoints = $historicalData->count();

        if ($dataPoints < 7) return 'low';
        if ($dataPoints < 14) return 'medium';
        return 'high';
    }

    /**
     * Get resource allocation recommendations
     */
    private function getResourceAllocation(Branch $branch): array
    {
        $currentCapacity = $this->getCurrentCapacity($branch);
        $workloadAnalysis = $this->getWorkloadAnalysis($branch, now()->subDays(30));

        return [
            'current_allocation' => $currentCapacity['workforce_capacity'],
            'recommended_allocation' => $this->calculateRecommendedAllocation($branch, $workloadAnalysis),
            'skill_gaps' => $this->identifySkillGaps($branch),
            'training_needs' => $this->identifyTrainingNeeds($branch),
            'resource_utilization' => $this->analyzeResourceUtilization($branch),
        ];
    }

    /**
     * Calculate recommended allocation
     */
    private function calculateRecommendedAllocation(Branch $branch, array $workloadAnalysis): array
    {
        $avgDailyLoad = $workloadAnalysis['daily_patterns']->avg('shipment_count') ?? 0;
        $peakLoad = $workloadAnalysis['peak_hours']['peak_days']->max('shipment_count') ?? $avgDailyLoad;

        // Calculate required capacity for different scenarios
        $normalCapacity = ceil($avgDailyLoad * 1.2); // 20% buffer
        $peakCapacity = ceil($peakLoad * 1.3); // 30% buffer for peaks

        return [
            'normal_operations' => $this->capacityToWorkerAllocation($normalCapacity),
            'peak_operations' => $this->capacityToWorkerAllocation($peakCapacity),
            'recommended_base_allocation' => $this->capacityToWorkerAllocation($normalCapacity),
            'peak_time_supplement' => $this->capacityToWorkerAllocation($peakCapacity - $normalCapacity),
        ];
    }

    /**
     * Convert capacity number to worker allocation
     */
    private function capacityToWorkerAllocation(int $capacity): array
    {
        $roles = [
            'dispatcher' => ['capacity' => 50, 'count' => 0],
            'supervisor' => ['capacity' => 30, 'count' => 0],
            'warehouse_worker' => ['capacity' => 25, 'count' => 0],
            'driver' => ['capacity' => 15, 'count' => 0],
            'customer_service' => ['capacity' => 20, 'count' => 0],
        ];

        // Prioritize roles by efficiency (dispatchers first, then supervisors, etc.)
        $rolePriority = ['dispatcher', 'supervisor', 'warehouse_worker', 'customer_service', 'driver'];

        $remainingCapacity = $capacity;

        foreach ($rolePriority as $role) {
            if ($remainingCapacity <= 0) break;

            $roleCapacity = $roles[$role]['capacity'];
            $needed = ceil($remainingCapacity / $roleCapacity);
            $roles[$role]['count'] = $needed;
            $remainingCapacity -= ($needed * $roleCapacity);
        }

        return $roles;
    }

    /**
     * Identify skill gaps
     */
    private function identifySkillGaps(Branch $branch): array
    {
        $workers = $branch->activeWorkers()->get();
        $requiredSkills = $this->getRequiredSkillsForBranch($branch);

        $skillGaps = [];

        foreach ($requiredSkills as $skill => $required) {
            $available = $workers->filter(function ($worker) use ($skill) {
                return in_array($skill, $worker->skills ?? []);
            })->count();

            if ($available < $required) {
                $skillGaps[$skill] = [
                    'required' => $required,
                    'available' => $available,
                    'gap' => $required - $available,
                    'severity' => $required - $available > 2 ? 'high' : 'medium',
                ];
            }
        }

        return $skillGaps;
    }

    /**
     * Get required skills for branch
     */
    private function getRequiredSkillsForBranch(Branch $branch): array
    {
        // This would be based on branch type and operations
        $baseSkills = [
            'package_handling' => 3,
            'customer_service' => 2,
            'vehicle_operation' => 2,
        ];

        if ($branch->is_hub) {
            $baseSkills['supervision'] = 2;
            $baseSkills['coordination'] = 3;
        }

        return $baseSkills;
    }

    /**
     * Identify training needs
     */
    private function identifyTrainingNeeds(Branch $branch): array
    {
        $workers = $branch->activeWorkers()->get();

        $trainingNeeds = [];

        foreach ($workers as $worker) {
            $missingSkills = $this->getMissingSkillsForWorker($worker);

            if (!empty($missingSkills)) {
                $trainingNeeds[] = [
                    'worker_id' => $worker->id,
                    'worker_name' => $worker->user->name ?? 'Unknown',
                    'missing_skills' => $missingSkills,
                    'priority' => count($missingSkills) > 2 ? 'high' : 'medium',
                ];
            }
        }

        return $trainingNeeds;
    }

    /**
     * Get missing skills for a worker
     */
    private function getMissingSkillsForWorker(BranchWorker $worker): array
    {
        $requiredSkills = ['package_handling', 'customer_service'];
        $workerSkills = $worker->skills ?? [];

        return array_diff($requiredSkills, $workerSkills);
    }

    /**
     * Analyze resource utilization
     */
    private function analyzeResourceUtilization(Branch $branch): array
    {
        $workers = $branch->activeWorkers()->get();

        $utilizationByRole = $workers->groupBy('role')->map(function ($roleWorkers, $role) {
            $totalCapacity = $roleWorkers->count() * $this->getCapacityForRole($role);
            $totalAssigned = $roleWorkers->sum(function ($worker) {
                return $worker->assignedShipments()
                    ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                    ->count();
            });

            return [
                'role' => $role,
                'worker_count' => $roleWorkers->count(),
                'total_capacity' => $totalCapacity,
                'utilized_capacity' => $totalAssigned,
                'utilization_rate' => $totalCapacity > 0 ? round(($totalAssigned / $totalCapacity) * 100, 1) : 0,
            ];
        });

        return $utilizationByRole->values()->toArray();
    }

    /**
     * Get bottleneck analysis
     */
    private function getBottleneckAnalysis(Branch $branch): array
    {
        $capacity = $this->getCurrentCapacity($branch);
        $workload = $this->getWorkloadAnalysis($branch, now()->subDays(7));

        $bottlenecks = [];

        // Check for capacity bottlenecks
        if ($capacity['utilization_rate'] > 90) {
            $bottlenecks[] = [
                'type' => 'capacity',
                'severity' => 'high',
                'description' => 'Branch operating near maximum capacity',
                'impact' => 'Reduced service quality and delays',
                'recommendation' => 'Increase workforce or redistribute workload',
            ];
        }

        // Check for role-specific bottlenecks
        $capacityByRole = $capacity['workforce_capacity'];
        foreach ($capacityByRole as $role => $data) {
            if (is_array($data) && isset($data['total_capacity'])) {
                $roleUtilization = $data['total_capacity'] > 0 ?
                    ($this->getCurrentWorkload($branch) / $data['total_capacity']) * 100 : 0;

                if ($roleUtilization > 95) {
                    $bottlenecks[] = [
                        'type' => 'role_specific',
                        'role' => $role,
                        'severity' => 'high',
                        'description' => "Critical shortage of {$role} resources",
                        'impact' => 'Operations severely impacted',
                        'recommendation' => "Urgently hire or reassign {$role} personnel",
                    ];
                }
            }
        }

        return $bottlenecks;
    }

    /**
     * Get optimization recommendations
     */
    private function getOptimizationRecommendations(Branch $branch): array
    {
        $recommendations = [];
        $capacity = $this->getCurrentCapacity($branch);
        $workloadBalance = $this->analyzeWorkloadDistribution($branch)['workload_balance'];

        // Capacity optimization
        if ($capacity['utilization_rate'] < 60) {
            $recommendations[] = [
                'category' => 'capacity',
                'priority' => 'medium',
                'title' => 'Optimize Workforce Size',
                'description' => 'Current capacity utilization is low. Consider optimizing workforce allocation.',
                'actions' => [
                    'Review staffing levels',
                    'Consider cross-training for better flexibility',
                    'Evaluate service area expansion',
                ],
            ];
        }

        // Workload balancing
        if ($workloadBalance['balance_score'] < 70) {
            $recommendations[] = [
                'category' => 'workload_balance',
                'priority' => 'high',
                'title' => 'Improve Workload Distribution',
                'description' => 'Workload is unevenly distributed among workers.',
                'actions' => [
                    'Implement better task assignment algorithms',
                    'Cross-train workers for flexibility',
                    'Monitor individual performance metrics',
                ],
            ];
        }

        // Process optimization
        $efficiency = $this->calculateEfficiencyMetrics($branch, now()->subDays(30));
        if ($efficiency['efficiency_score'] < 70) {
            $recommendations[] = [
                'category' => 'process_optimization',
                'priority' => 'medium',
                'title' => 'Streamline Operations',
                'description' => 'Operational efficiency can be improved.',
                'actions' => [
                    'Review and optimize workflows',
                    'Implement automation where possible',
                    'Provide additional training',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Get capacity status
     */
    private function getCapacityStatus(float $utilizationRate): string
    {
        if ($utilizationRate < 50) return 'underutilized';
        if ($utilizationRate < 80) return 'optimal';
        if ($utilizationRate < 95) return 'high';
        return 'critical';
    }

    /**
     * Get peak capacity hours
     */
    private function getPeakCapacityHours(Branch $branch): array
    {
        // This would analyze historical data to determine peak hours
        // For now, return default business hours
        return [
            'weekday_peak' => ['start' => '09:00', 'end' => '17:00'],
            'weekend_peak' => ['start' => '10:00', 'end' => '16:00'],
        ];
    }
}