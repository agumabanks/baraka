<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\BranchWorker;
use App\Models\DriverAssignment;
use App\Models\Backend\Branch;
use App\Models\Backend\Vehicle;
use App\Enums\BranchWorkerRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * ShipmentAssignmentService
 * 
 * AI-powered shipment assignment engine that considers:
 * - Driver workload and current assignments
 * - Geographic proximity to shipment origin
 * - Driver skills and certifications
 * - Historical performance metrics
 * - Vehicle capacity and availability
 * - Priority-based assignment
 * - Workload balancing across team
 */
class ShipmentAssignmentService
{
    protected RouteOptimizationService $routeService;
    protected GeofencingService $geofencingService;

    public function __construct(
        RouteOptimizationService $routeService,
        GeofencingService $geofencingService
    ) {
        $this->routeService = $routeService;
        $this->geofencingService = $geofencingService;
    }

    /**
     * Auto-assign a shipment to the best available driver
     */
    public function autoAssign(Shipment $shipment, array $options = []): array
    {
        // Get available drivers for this branch
        $branchId = $shipment->origin_branch_id;
        $availableDrivers = $this->getAvailableDrivers($branchId, $options['date'] ?? today());

        if ($availableDrivers->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No available drivers for assignment',
                'shipment_id' => $shipment->id,
            ];
        }

        // Score each driver
        $scoredDrivers = $this->scoreDriversForShipment($shipment, $availableDrivers, $options);

        if (empty($scoredDrivers)) {
            return [
                'success' => false,
                'message' => 'No suitable drivers found',
                'shipment_id' => $shipment->id,
            ];
        }

        // Select best driver
        $bestDriver = $scoredDrivers[0];

        // Perform assignment
        return $this->assignShipmentToDriver($shipment, $bestDriver['driver_id'], $options);
    }

    /**
     * Bulk auto-assign multiple shipments
     */
    public function bulkAutoAssign(array $shipmentIds, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($shipmentIds),
        ];

        // Sort shipments by priority (high priority first)
        $shipments = Shipment::whereIn('id', $shipmentIds)
            ->orderByDesc('priority')
            ->orderBy('expected_delivery_date')
            ->get();

        foreach ($shipments as $shipment) {
            $result = $this->autoAssign($shipment, $options);
            
            if ($result['success']) {
                $results['success'][] = [
                    'shipment_id' => $shipment->id,
                    'driver_id' => $result['driver_id'],
                ];
            } else {
                $results['failed'][] = [
                    'shipment_id' => $shipment->id,
                    'reason' => $result['message'],
                ];
            }
        }

        $results['assigned_count'] = count($results['success']);
        $results['failed_count'] = count($results['failed']);

        return $results;
    }

    /**
     * Get available drivers for a branch
     */
    public function getAvailableDrivers(int $branchId, $date = null): Collection
    {
        $date = $date ?? today();

        return BranchWorker::active()
            ->where('branch_id', $branchId)
            ->whereIn('role', [
                BranchWorkerRole::COURIER->value,
                BranchWorkerRole::DRIVER->value,
                'DELIVERY', // legacy values that may still exist
                'RIDER',
            ])
            ->whereDoesntHave('leave', function ($q) use ($date) {
                $q->whereDate('start_date', '<=', $date)
                  ->whereDate('end_date', '>=', $date);
            })
            ->get();
    }

    /**
     * Score drivers for a specific shipment
     */
    protected function scoreDriversForShipment(
        Shipment $shipment,
        Collection $drivers,
        array $options = []
    ): array {
        $scoredDrivers = [];

        foreach ($drivers as $driver) {
            $score = $this->calculateDriverScore($driver, $shipment, $options);
            
            // Only include drivers with positive scores
            if ($score['total'] > 0) {
                $scoredDrivers[] = [
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->user->name ?? $driver->name ?? "Driver {$driver->id}",
                    'total_score' => $score['total'],
                    'breakdown' => $score['breakdown'],
                    'workload' => $score['workload'],
                    'distance' => $score['distance'],
                ];
            }
        }

        // Sort by score (descending)
        usort($scoredDrivers, fn($a, $b) => $b['total_score'] <=> $a['total_score']);

        return $scoredDrivers;
    }

    /**
     * Calculate a driver's score for a shipment
     */
    protected function calculateDriverScore(
        BranchWorker $driver,
        Shipment $shipment,
        array $options = []
    ): array {
        $breakdown = [];
        $totalScore = 100; // Start with base score

        // 1. Workload score (40 points max)
        $workloadScore = $this->calculateWorkloadScore($driver);
        $breakdown['workload'] = $workloadScore;
        $totalScore += $workloadScore;

        // 2. Proximity score (30 points max)
        $proximityScore = $this->calculateProximityScore($driver, $shipment);
        $breakdown['proximity'] = $proximityScore;
        $totalScore += $proximityScore;

        // 3. Performance score (20 points max)
        $performanceScore = $this->calculatePerformanceScore($driver);
        $breakdown['performance'] = $performanceScore;
        $totalScore += $performanceScore;

        // 4. Skills match score (10 points max)
        $skillsScore = $this->calculateSkillsScore($driver, $shipment);
        $breakdown['skills'] = $skillsScore;
        $totalScore += $skillsScore;

        // 5. Priority bonus (for high priority shipments)
        if ($shipment->priority >= 2) {
            $priorityBonus = $this->calculatePriorityBonus($driver, $shipment);
            $breakdown['priority_bonus'] = $priorityBonus;
            $totalScore += $priorityBonus;
        }

        // 6. Capacity check (can disqualify driver)
        $capacityOk = $this->checkDriverCapacity($driver, $shipment);
        if (!$capacityOk) {
            $totalScore = 0; // Disqualify
            $breakdown['capacity'] = 'exceeded';
        }

        return [
            'total' => max(0, $totalScore),
            'breakdown' => $breakdown,
            'workload' => $this->getDriverWorkload($driver),
            'distance' => $this->getDriverDistanceFromShipment($driver, $shipment),
        ];
    }

    /**
     * Calculate workload score (prefer less busy drivers)
     */
    protected function calculateWorkloadScore(BranchWorker $driver): int
    {
        $todayAssignments = DriverAssignment::forDriver($driver->id)
            ->forDate(today())
            ->first();

        if (!$todayAssignments) {
            return 40; // No assignments = full score
        }

        $assignedShipments = $todayAssignments->assigned_shipments;
        $maxShipments = 50; // Max daily shipments

        // Score decreases as assignments increase
        $utilizationPercent = ($assignedShipments / $maxShipments) * 100;
        
        if ($utilizationPercent >= 100) {
            return -100; // Over capacity
        }

        return (int) (40 * (1 - $utilizationPercent / 100));
    }

    /**
     * Calculate proximity score (prefer nearby drivers)
     */
    protected function calculateProximityScore(BranchWorker $driver, Shipment $shipment): int
    {
        $distance = $this->getDriverDistanceFromShipment($driver, $shipment);
        
        if ($distance === null) {
            return 15; // Unknown distance = average score
        }

        // Score based on distance (0-30 points)
        if ($distance <= 2) {
            return 30; // Very close (< 2km)
        } elseif ($distance <= 5) {
            return 25;
        } elseif ($distance <= 10) {
            return 20;
        } elseif ($distance <= 20) {
            return 10;
        }

        return 0; // Far away
    }

    /**
     * Calculate performance score based on historical data
     */
    protected function calculatePerformanceScore(BranchWorker $driver): int
    {
        // Get last 30 days of assignments
        $recentAssignments = DriverAssignment::forDriver($driver->id)
            ->where('assignment_date', '>=', now()->subDays(30))
            ->where('status', 'completed')
            ->get();

        if ($recentAssignments->isEmpty()) {
            return 10; // New driver = average score
        }

        $totalCompleted = $recentAssignments->sum('completed_shipments');
        $totalAssigned = $recentAssignments->sum('assigned_shipments');
        $avgEfficiency = $recentAssignments->avg('efficiency_score') ?? 100;

        // Calculate completion rate
        $completionRate = $totalAssigned > 0 ? ($totalCompleted / $totalAssigned) * 100 : 0;

        // Score based on completion rate and efficiency
        $completionScore = min(10, (int) ($completionRate / 10));
        $efficiencyScore = min(10, (int) ($avgEfficiency / 10));

        return $completionScore + $efficiencyScore;
    }

    /**
     * Calculate skills match score
     */
    protected function calculateSkillsScore(BranchWorker $driver, Shipment $shipment): int
    {
        $driverSkills = $driver->skills ?? [];
        $requiredSkills = [];

        // Determine required skills based on shipment
        if ($shipment->has_dangerous_goods ?? false) {
            $requiredSkills[] = 'hazmat';
        }
        if (($shipment->chargeable_weight_kg ?? 0) > 100) {
            $requiredSkills[] = 'heavy_lifting';
        }
        if ($shipment->requires_refrigeration ?? false) {
            $requiredSkills[] = 'refrigerated';
        }
        if ($shipment->special_instructions) {
            $requiredSkills[] = 'special_handling';
        }

        if (empty($requiredSkills)) {
            return 10; // No special requirements = full score
        }

        // Check how many required skills driver has
        $matchedSkills = array_intersect($requiredSkills, $driverSkills);
        $matchRate = count($matchedSkills) / count($requiredSkills);

        if ($matchRate < 1 && !empty(array_diff($requiredSkills, ['special_handling']))) {
            return -50; // Missing critical skills = penalty
        }

        return (int) (10 * $matchRate);
    }

    /**
     * Calculate priority bonus for high-priority shipments
     */
    protected function calculatePriorityBonus(BranchWorker $driver, Shipment $shipment): int
    {
        // Top performers get priority shipments
        $performanceScore = $this->calculatePerformanceScore($driver);
        
        if ($performanceScore >= 18) {
            return 20; // Top performer bonus
        } elseif ($performanceScore >= 15) {
            return 10;
        }

        return 0;
    }

    /**
     * Check if driver has capacity for the shipment
     */
    protected function checkDriverCapacity(BranchWorker $driver, Shipment $shipment): bool
    {
        $todayAssignment = DriverAssignment::forDriver($driver->id)
            ->forDate(today())
            ->first();

        if (!$todayAssignment) {
            return true; // No assignments yet
        }

        // Check shipment count
        if ($todayAssignment->assigned_shipments >= 50) {
            return false;
        }

        // Check weight capacity
        $maxWeight = 1000; // kg
        $shipmentWeight = $shipment->chargeable_weight_kg ?? $shipment->weight ?? 1;
        
        if (($todayAssignment->assigned_weight_kg + $shipmentWeight) > $maxWeight) {
            return false;
        }

        return true;
    }

    /**
     * Get driver's current workload
     */
    protected function getDriverWorkload(BranchWorker $driver): array
    {
        $assignment = DriverAssignment::forDriver($driver->id)
            ->forDate(today())
            ->first();

        if (!$assignment) {
            return [
                'assigned_shipments' => 0,
                'assigned_weight_kg' => 0,
                'assigned_distance_km' => 0,
                'utilization_percent' => 0,
            ];
        }

        return [
            'assigned_shipments' => $assignment->assigned_shipments,
            'assigned_weight_kg' => $assignment->assigned_weight_kg,
            'assigned_distance_km' => $assignment->assigned_distance_km,
            'utilization_percent' => ($assignment->assigned_shipments / 50) * 100,
        ];
    }

    /**
     * Get distance from driver's current location to shipment origin
     */
    protected function getDriverDistanceFromShipment(BranchWorker $driver, Shipment $shipment): ?float
    {
        // Get driver's current/last known location
        $driverLat = $driver->current_latitude ?? $driver->branch?->latitude;
        $driverLng = $driver->current_longitude ?? $driver->branch?->longitude;

        // Get shipment origin location
        $shipmentLat = $shipment->originBranch?->latitude;
        $shipmentLng = $shipment->originBranch?->longitude;

        if (!$driverLat || !$driverLng || !$shipmentLat || !$shipmentLng) {
            return null;
        }

        return $this->geofencingService->calculateDistance(
            $driverLat,
            $driverLng,
            $shipmentLat,
            $shipmentLng
        ) / 1000; // Convert meters to km
    }

    /**
     * Assign shipment to a specific driver
     */
    public function assignShipmentToDriver(
        Shipment $shipment,
        int $driverId,
        array $options = []
    ): array {
        DB::beginTransaction();

        try {
            $driver = BranchWorker::findOrFail($driverId);
            $date = $options['date'] ?? today();

            // Get or create today's assignment record
            $assignment = DriverAssignment::firstOrCreate(
                [
                    'driver_id' => $driverId,
                    'assignment_date' => $date,
                ],
                [
                    'vehicle_id' => $driver->vehicle_id ?? null,
                    'status' => 'pending',
                    'assigned_shipments' => 0,
                    'assigned_weight_kg' => 0,
                    'assigned_distance_km' => 0,
                    'estimated_duration_minutes' => 0,
                ]
            );

            // Calculate distance to add
            $distanceToAdd = $this->getDriverDistanceFromShipment($driver, $shipment) ?? 5;

            // Update assignment
            $assignment->addShipment($shipment, $distanceToAdd);

            // Update shipment
            $shipment->update([
                'assigned_worker_id' => $driverId,
                'assigned_at' => now(),
            ]);

            DB::commit();

            Log::info('Shipment assigned to driver', [
                'shipment_id' => $shipment->id,
                'driver_id' => $driverId,
                'tracking_number' => $shipment->tracking_number,
            ]);

            return [
                'success' => true,
                'shipment_id' => $shipment->id,
                'driver_id' => $driverId,
                'driver_name' => $driver->user->name ?? $driver->name ?? "Driver {$driverId}",
                'assignment_id' => $assignment->id,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign shipment', [
                'shipment_id' => $shipment->id,
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to assign shipment: ' . $e->getMessage(),
                'shipment_id' => $shipment->id,
            ];
        }
    }

    /**
     * Get optimal assignment suggestions for unassigned shipments
     */
    public function getSuggestions(int $branchId, $date = null): array
    {
        $date = $date ?? today();

        // Get unassigned shipments
        $unassigned = Shipment::where('origin_branch_id', $branchId)
            ->whereNull('assigned_worker_id')
            ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
            ->orderByDesc('priority')
            ->orderBy('expected_delivery_date')
            ->limit(100)
            ->get();

        $suggestions = [];

        foreach ($unassigned as $shipment) {
            $drivers = $this->getAvailableDrivers($branchId, $date);
            $scored = $this->scoreDriversForShipment($shipment, $drivers);

            if (!empty($scored)) {
                $suggestions[] = [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'priority' => $shipment->priority,
                    'suggested_driver' => $scored[0],
                    'alternative_drivers' => array_slice($scored, 1, 2),
                ];
            }
        }

        return [
            'branch_id' => $branchId,
            'date' => $date->toDateString(),
            'total_unassigned' => $unassigned->count(),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Get workload distribution across all drivers
     */
    public function getWorkloadDistribution(int $branchId, $date = null): array
    {
        $date = $date ?? today();

        $drivers = BranchWorker::active()
            ->where('branch_id', $branchId)
            ->whereIn('role', [
                BranchWorkerRole::COURIER->value,
                BranchWorkerRole::DRIVER->value,
                'DELIVERY',
                'RIDER',
            ])
            ->with(['user'])
            ->get();

        $distribution = [];
        $totalAssigned = 0;
        $totalCapacity = count($drivers) * 50; // 50 shipments per driver

        foreach ($drivers as $driver) {
            $workload = $this->getDriverWorkload($driver);
            $totalAssigned += $workload['assigned_shipments'];

            $distribution[] = [
                'driver_id' => $driver->id,
                'driver_name' => $driver->user->name ?? $driver->name ?? "Driver {$driver->id}",
                'assigned_shipments' => $workload['assigned_shipments'],
                'assigned_weight_kg' => $workload['assigned_weight_kg'],
                'utilization_percent' => round($workload['utilization_percent'], 1),
                'status' => $workload['utilization_percent'] >= 90 ? 'full' : 
                           ($workload['utilization_percent'] >= 70 ? 'busy' : 'available'),
            ];
        }

        // Sort by utilization
        usort($distribution, fn($a, $b) => $b['utilization_percent'] <=> $a['utilization_percent']);

        // Calculate balance metrics
        $utilizations = array_column($distribution, 'utilization_percent');
        $avgUtilization = count($utilizations) > 0 ? array_sum($utilizations) / count($utilizations) : 0;
        $stdDev = $this->calculateStdDev($utilizations);

        return [
            'branch_id' => $branchId,
            'date' => $date->toDateString(),
            'total_drivers' => count($drivers),
            'total_assigned_shipments' => $totalAssigned,
            'total_capacity' => $totalCapacity,
            'overall_utilization' => $totalCapacity > 0 ? round(($totalAssigned / $totalCapacity) * 100, 1) : 0,
            'average_utilization' => round($avgUtilization, 1),
            'balance_score' => $this->calculateBalanceScore($stdDev),
            'distribution' => $distribution,
        ];
    }

    /**
     * Rebalance workload across drivers
     */
    public function rebalanceWorkload(int $branchId, $date = null): array
    {
        $date = $date ?? today();
        $distribution = $this->getWorkloadDistribution($branchId, $date);

        // Find overloaded and underloaded drivers
        $overloaded = array_filter($distribution['distribution'], fn($d) => $d['utilization_percent'] > 85);
        $underloaded = array_filter($distribution['distribution'], fn($d) => $d['utilization_percent'] < 50);

        $rebalanced = [];

        foreach ($overloaded as $busy) {
            // Get shipments that could be reassigned
            $shipments = Shipment::where('assigned_worker_id', $busy['driver_id'])
                ->whereDate('assigned_at', $date)
                ->where('status', 'created') // Only reassign not-yet-picked-up
                ->orderBy('priority') // Reassign lower priority first
                ->limit(5)
                ->get();

            foreach ($shipments as $shipment) {
                foreach ($underloaded as $free) {
                    // Check if reassignment makes sense
                    $driver = BranchWorker::find($free['driver_id']);
                    
                    if ($this->checkDriverCapacity($driver, $shipment)) {
                        $rebalanced[] = [
                            'shipment_id' => $shipment->id,
                            'tracking_number' => $shipment->tracking_number,
                            'from_driver' => $busy['driver_name'],
                            'to_driver' => $free['driver_name'],
                            'action' => 'suggested',
                        ];
                        break;
                    }
                }
            }
        }

        return [
            'branch_id' => $branchId,
            'overloaded_drivers' => count($overloaded),
            'underloaded_drivers' => count($underloaded),
            'suggested_reassignments' => $rebalanced,
            'current_balance_score' => $distribution['balance_score'],
        ];
    }

    /**
     * Calculate standard deviation
     */
    protected function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);
        
        return sqrt(array_sum($squaredDiffs) / count($values));
    }

    /**
     * Calculate balance score (higher = more balanced)
     */
    protected function calculateBalanceScore(float $stdDev): int
    {
        // Perfect balance = 100, very unbalanced = 0
        if ($stdDev <= 5) {
            return 100;
        } elseif ($stdDev <= 10) {
            return 90;
        } elseif ($stdDev <= 20) {
            return 70;
        } elseif ($stdDev <= 30) {
            return 50;
        }

        return max(0, 100 - (int) $stdDev);
    }
}
