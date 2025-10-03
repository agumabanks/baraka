<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SettlementEngineService
{
    /**
     * Calculate branch settlement for a period
     */
    public function calculateBranchSettlement(Branch $branch, Carbon $startDate, Carbon $endDate): array
    {
        // Get all shipments handled by this branch
        $originShipments = $this->getBranchOriginShipments($branch, $startDate, $endDate);
        $destinationShipments = $this->getBranchDestinationShipments($branch, $startDate, $endDate);

        // Calculate revenue components
        $shippingRevenue = $this->calculateShippingRevenue($originShipments);
        $codRevenue = $this->calculateCODRevenue($destinationShipments);

        // Calculate costs
        $operationalCosts = $this->calculateOperationalCosts($branch, $startDate, $endDate);
        $hubFees = $this->calculateHubFees($branch, $originShipments);
        $interBranchFees = $this->calculateInterBranchFees($branch, $originShipments, $destinationShipments);

        // Calculate net settlement
        $totalRevenue = $shippingRevenue + $codRevenue;
        $totalCosts = $operationalCosts + $hubFees + $interBranchFees;
        $netSettlement = $totalRevenue - $totalCosts;

        return [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'type' => $branch->type,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'revenue' => [
                'shipping_revenue' => $shippingRevenue,
                'cod_revenue' => $codRevenue,
                'total_revenue' => $totalRevenue,
            ],
            'costs' => [
                'operational_costs' => $operationalCosts,
                'hub_fees' => $hubFees,
                'inter_branch_fees' => $interBranchFees,
                'total_costs' => $totalCosts,
            ],
            'settlement' => [
                'net_amount' => $netSettlement,
                'settlement_type' => $netSettlement >= 0 ? 'payable_to_branch' : 'receivable_from_branch',
                'settlement_amount' => abs($netSettlement),
            ],
            'breakdown' => [
                'origin_shipments_count' => $originShipments->count(),
                'destination_shipments_count' => $destinationShipments->count(),
                'total_shipments' => $originShipments->count() + $destinationShipments->count(),
            ],
        ];
    }

    /**
     * Calculate worker settlement for a period
     */
    public function calculateWorkerSettlement(BranchWorker $worker, Carbon $startDate, Carbon $endDate): array
    {
        // Get worker's shipments
        $assignedShipments = $this->getWorkerAssignedShipments($worker, $startDate, $endDate);

        // Calculate earnings
        $baseEarnings = $this->calculateWorkerBaseEarnings($worker, $assignedShipments);
        $performanceBonus = $this->calculatePerformanceBonus($worker, $assignedShipments);
        $codCommission = $this->calculateCODCommission($worker, $assignedShipments);

        // Calculate deductions
        $deductions = $this->calculateWorkerDeductions($worker, $startDate, $endDate);

        // Calculate net settlement
        $totalEarnings = $baseEarnings + $performanceBonus + $codCommission;
        $netSettlement = $totalEarnings - $deductions;

        return [
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->full_name,
                'role' => $worker->role,
                'branch' => $worker->branch->name,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'earnings' => [
                'base_earnings' => $baseEarnings,
                'performance_bonus' => $performanceBonus,
                'cod_commission' => $codCommission,
                'total_earnings' => $totalEarnings,
            ],
            'deductions' => [
                'total_deductions' => $deductions,
                'breakdown' => $this->getDeductionsBreakdown($worker, $startDate, $endDate),
            ],
            'settlement' => [
                'net_amount' => $netSettlement,
                'settlement_type' => $netSettlement >= 0 ? 'payable_to_worker' : 'receivable_from_worker',
                'settlement_amount' => abs($netSettlement),
            ],
            'performance' => [
                'shipments_delivered' => $assignedShipments->where('current_status', 'delivered')->count(),
                'total_shipments' => $assignedShipments->count(),
                'delivery_rate' => $assignedShipments->count() > 0
                    ? ($assignedShipments->where('current_status', 'delivered')->count() / $assignedShipments->count()) * 100
                    : 0,
                'average_rating' => $this->calculateWorkerAverageRating($worker, $startDate, $endDate),
            ],
        ];
    }

    /**
     * Get branch origin shipments
     */
    private function getBranchOriginShipments(Branch $branch, Carbon $startDate, Carbon $endDate): Collection
    {
        return Shipment::where('origin_branch_id', $branch->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'assignedWorker'])
            ->get();
    }

    /**
     * Get branch destination shipments
     */
    private function getBranchDestinationShipments(Branch $branch, Carbon $startDate, Carbon $endDate): Collection
    {
        return Shipment::where('dest_branch_id', $branch->id)
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->whereNotNull('delivered_at')
            ->with(['customer', 'assignedWorker'])
            ->get();
    }

    /**
     * Get worker assigned shipments
     */
    private function getWorkerAssignedShipments(BranchWorker $worker, Carbon $startDate, Carbon $endDate): Collection
    {
        return $worker->assignedShipments()
            ->whereBetween('assigned_at', [$startDate, $endDate])
            ->with(['customer', 'originBranch', 'destBranch'])
            ->get();
    }

    /**
     * Calculate shipping revenue
     */
    private function calculateShippingRevenue(Collection $shipments): float
    {
        $rateService = app(RateCardManagementService::class);

        return $shipments->sum(function ($shipment) use ($rateService) {
            $rateCalculation = $rateService->calculateShippingRate($shipment);
            return $rateCalculation['grand_total'];
        });
    }

    /**
     * Calculate COD revenue
     */
    private function calculateCODRevenue(Collection $shipments): float
    {
        return $shipments->sum(function ($shipment) {
            return $shipment->codCollections()->sum('collected_amount');
        });
    }

    /**
     * Calculate operational costs
     */
    private function calculateOperationalCosts(Branch $branch, Carbon $startDate, Carbon $endDate): float
    {
        // Calculate worker salaries
        $workerCosts = $this->calculateWorkerCosts($branch, $startDate, $endDate);

        // Calculate vehicle and fuel costs
        $vehicleCosts = $this->calculateVehicleCosts($branch, $startDate, $endDate);

        // Calculate facility costs
        $facilityCosts = $this->calculateFacilityCosts($branch, $startDate, $endDate);

        return $workerCosts + $vehicleCosts + $facilityCosts;
    }

    /**
     * Calculate worker costs
     */
    private function calculateWorkerCosts(Branch $branch, Carbon $startDate, Carbon $endDate): float
    {
        $activeWorkers = $branch->activeWorkers()->with('user')->get();
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;

        return $activeWorkers->sum(function ($worker) use ($daysInPeriod) {
            $dailyRate = ($worker->hourly_rate ?? 15) * 8; // Assume 8 hours per day
            return $dailyRate * $daysInPeriod;
        });
    }

    /**
     * Calculate vehicle costs
     */
    private function calculateVehicleCosts(Branch $branch, Carbon $startDate, Carbon $endDate): float
    {
        // Simplified vehicle cost calculation
        // In a real implementation, this would pull from vehicle maintenance and fuel records
        $vehicleCount = 5; // Assume 5 vehicles per branch
        $dailyVehicleCost = 50; // $50 per vehicle per day
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;

        return $vehicleCount * $dailyVehicleCost * $daysInPeriod;
    }

    /**
     * Calculate facility costs
     */
    private function calculateFacilityCosts(Branch $branch, Carbon $startDate, Carbon $endDate): float
    {
        // Simplified facility cost calculation
        $dailyFacilityCost = 200; // $200 per day for facility operations
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;

        return $dailyFacilityCost * $daysInPeriod;
    }

    /**
     * Calculate HUB fees
     */
    private function calculateHubFees(Branch $branch, Collection $shipments): float
    {
        if ($branch->is_hub) {
            return 0; // HUB doesn't pay HUB fees
        }

        // HUB fee is 5% of shipping revenue for shipments going through HUB
        $hubShipments = $shipments->filter(function ($shipment) {
            return $shipment->transfer_hub_id !== null;
        });

        $rateService = app(RateCardManagementService::class);
        $hubRevenue = $hubShipments->sum(function ($shipment) use ($rateService) {
            $rateCalculation = $rateService->calculateShippingRate($shipment);
            return $rateCalculation['grand_total'];
        });

        return $hubRevenue * 0.05; // 5% HUB fee
    }

    /**
     * Calculate inter-branch fees
     */
    private function calculateInterBranchFees(Branch $branch, Collection $originShipments, Collection $destinationShipments): float
    {
        // Calculate fees for shipments originating from other branches
        $incomingShipments = $destinationShipments->filter(function ($shipment) use ($branch) {
            return $shipment->origin_branch_id !== $branch->id;
        });

        $rateService = app(RateCardManagementService::class);
        $incomingRevenue = $incomingShipments->sum(function ($shipment) use ($rateService) {
            $rateCalculation = $rateService->calculateShippingRate($shipment);
            return $rateCalculation['grand_total'];
        });

        // Inter-branch fee is 2% of revenue
        return $incomingRevenue * 0.02;
    }

    /**
     * Calculate worker base earnings
     */
    private function calculateWorkerBaseEarnings(BranchWorker $worker, Collection $shipments): float
    {
        $hourlyRate = $worker->hourly_rate ?? 15;
        $hoursWorked = $this->calculateWorkerHoursWorked($worker, $shipments);

        return $hourlyRate * $hoursWorked;
    }

    /**
     * Calculate worker hours worked
     */
    private function calculateWorkerHoursWorked(BranchWorker $worker, Collection $shipments): float
    {
        // Simplified calculation: assume 8 hours per day for workers with assigned shipments
        $workDays = $shipments->groupBy(function ($shipment) {
            return $shipment->assigned_at?->toDateString();
        })->count();

        return $workDays * 8; // 8 hours per work day
    }

    /**
     * Calculate performance bonus
     */
    private function calculatePerformanceBonus(BranchWorker $worker, Collection $shipments): float
    {
        $deliveredShipments = $shipments->where('current_status', 'delivered');
        $deliveryRate = $shipments->count() > 0
            ? ($deliveredShipments->count() / $shipments->count()) * 100
            : 0;

        // Bonus based on delivery rate
        if ($deliveryRate >= 95) {
            $bonusRate = 0.10; // 10% bonus for 95%+ delivery rate
        } elseif ($deliveryRate >= 90) {
            $bonusRate = 0.05; // 5% bonus for 90-94% delivery rate
        } else {
            $bonusRate = 0.00; // No bonus below 90%
        }

        $baseEarnings = $this->calculateWorkerBaseEarnings($worker, $shipments);

        return $baseEarnings * $bonusRate;
    }

    /**
     * Calculate COD commission
     */
    private function calculateCODCommission(BranchWorker $worker, Collection $shipments): float
    {
        $codCollections = 0;

        foreach ($shipments as $shipment) {
            $codCollections += $shipment->codCollections()
                ->where('collector_id', $worker->id)
                ->sum('collected_amount');
        }

        // COD commission is 1% of collected amount
        return $codCollections * 0.01;
    }

    /**
     * Calculate worker deductions
     */
    private function calculateWorkerDeductions(BranchWorker $worker, Carbon $startDate, Carbon $endDate): float
    {
        // Calculate tax deductions (simplified)
        $baseEarnings = $this->calculateWorkerBaseEarnings($worker, $this->getWorkerAssignedShipments($worker, $startDate, $endDate));
        $taxRate = 0.15; // 15% tax rate

        return $baseEarnings * $taxRate;
    }

    /**
     * Get deductions breakdown
     */
    private function getDeductionsBreakdown(BranchWorker $worker, Carbon $startDate, Carbon $endDate): array
    {
        $baseEarnings = $this->calculateWorkerBaseEarnings($worker, $this->getWorkerAssignedShipments($worker, $startDate, $endDate));

        return [
            'income_tax' => $baseEarnings * 0.15,
            'social_security' => $baseEarnings * 0.062,
            'medicare' => $baseEarnings * 0.0145,
        ];
    }

    /**
     * Calculate worker average rating
     */
    private function calculateWorkerAverageRating(BranchWorker $worker, Carbon $startDate, Carbon $endDate): float
    {
        // Simplified rating calculation
        // In a real implementation, this would pull from customer feedback
        $deliveredShipments = $this->getWorkerAssignedShipments($worker, $startDate, $endDate)
            ->where('current_status', 'delivered')
            ->count();

        $totalShipments = $this->getWorkerAssignedShipments($worker, $startDate, $endDate)->count();

        if ($totalShipments === 0) {
            return 0.0;
        }

        $deliveryRate = ($deliveredShipments / $totalShipments) * 100;

        // Convert delivery rate to star rating (out of 5)
        if ($deliveryRate >= 98) {
            return 5.0;
        } elseif ($deliveryRate >= 95) {
            return 4.5;
        } elseif ($deliveryRate >= 90) {
            return 4.0;
        } elseif ($deliveryRate >= 85) {
            return 3.5;
        } else {
            return 3.0;
        }
    }

    /**
     * Process branch settlement payment
     */
    public function processBranchSettlement(Branch $branch, Carbon $startDate, Carbon $endDate, array $paymentData): array
    {
        $settlement = $this->calculateBranchSettlement($branch, $startDate, $endDate);

        if ($settlement['settlement']['net_amount'] <= 0) {
            return [
                'success' => false,
                'message' => 'No payment due to branch',
            ];
        }

        DB::beginTransaction();
        try {
            // Create settlement record
            $settlementRecord = $branch->settlements()->create([
                'settlement_period_start' => $startDate,
                'settlement_period_end' => $endDate,
                'total_revenue' => $settlement['revenue']['total_revenue'],
                'total_costs' => $settlement['costs']['total_costs'],
                'net_amount' => $settlement['settlement']['net_amount'],
                'payment_amount' => $settlement['settlement']['settlement_amount'],
                'payment_method' => $paymentData['method'] ?? 'bank_transfer',
                'payment_reference' => $paymentData['reference'] ?? null,
                'payment_date' => now(),
                'processed_by' => auth()->user()->id ?? null,
                'status' => 'completed',
                'settlement_data' => $settlement,
                'metadata' => $paymentData,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Branch settlement processed successfully',
                'settlement' => $settlementRecord,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Branch settlement processing failed', [
                'branch_id' => $branch->id,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process branch settlement: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process worker settlement payment
     */
    public function processWorkerSettlement(BranchWorker $worker, Carbon $startDate, Carbon $endDate, array $paymentData): array
    {
        $settlement = $this->calculateWorkerSettlement($worker, $startDate, $endDate);

        if ($settlement['settlement']['net_amount'] <= 0) {
            return [
                'success' => false,
                'message' => 'No payment due to worker',
            ];
        }

        DB::beginTransaction();
        try {
            // Create settlement record
            $settlementRecord = $worker->settlements()->create([
                'settlement_period_start' => $startDate,
                'settlement_period_end' => $endDate,
                'total_earnings' => $settlement['earnings']['total_earnings'],
                'total_deductions' => $settlement['deductions']['total_deductions'],
                'net_amount' => $settlement['settlement']['net_amount'],
                'payment_amount' => $settlement['settlement']['settlement_amount'],
                'payment_method' => $paymentData['method'] ?? 'direct_deposit',
                'payment_reference' => $paymentData['reference'] ?? null,
                'payment_date' => now(),
                'processed_by' => auth()->user()->id ?? null,
                'status' => 'completed',
                'settlement_data' => $settlement,
                'metadata' => $paymentData,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Worker settlement processed successfully',
                'settlement' => $settlementRecord,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Worker settlement processing failed', [
                'worker_id' => $worker->id,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process worker settlement: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate settlement report
     */
    public function generateSettlementReport(Carbon $startDate, Carbon $endDate): array
    {
        $branches = Branch::active()->get();
        $branchSettlements = [];

        foreach ($branches as $branch) {
            $settlement = $this->calculateBranchSettlement($branch, $startDate, $endDate);
            $branchSettlements[] = $settlement;
        }

        $totalRevenue = collect($branchSettlements)->sum('revenue.total_revenue');
        $totalCosts = collect($branchSettlements)->sum('costs.total_costs');
        $netSettlement = $totalRevenue - $totalCosts;

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_branches' => count($branchSettlements),
                'total_revenue' => $totalRevenue,
                'total_costs' => $totalCosts,
                'net_settlement' => $netSettlement,
                'branches_payable' => collect($branchSettlements)->where('settlement.settlement_type', 'payable_to_branch')->count(),
                'branches_receivable' => collect($branchSettlements)->where('settlement.settlement_type', 'receivable_from_branch')->count(),
            ],
            'branch_settlements' => $branchSettlements,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get settlement history for branch
     */
    public function getBranchSettlementHistory(Branch $branch, int $months = 12): Collection
    {
        return $branch->settlements()
            ->where('created_at', '>=', now()->subMonths($months))
            ->orderBy('settlement_period_end', 'desc')
            ->get()
            ->map(function ($settlement) {
                return [
                    'id' => $settlement->id,
                    'period' => $settlement->settlement_period_start->format('M Y'),
                    'revenue' => $settlement->total_revenue,
                    'costs' => $settlement->total_costs,
                    'net_amount' => $settlement->net_amount,
                    'payment_date' => $settlement->payment_date?->toDateString(),
                    'status' => $settlement->status,
                ];
            });
    }

    /**
     * Get settlement history for worker
     */
    public function getWorkerSettlementHistory(BranchWorker $worker, int $months = 12): Collection
    {
        return $worker->settlements()
            ->where('created_at', '>=', now()->subMonths($months))
            ->orderBy('settlement_period_end', 'desc')
            ->get()
            ->map(function ($settlement) {
                return [
                    'id' => $settlement->id,
                    'period' => $settlement->settlement_period_start->format('M Y'),
                    'earnings' => $settlement->total_earnings,
                    'deductions' => $settlement->total_deductions,
                    'net_amount' => $settlement->net_amount,
                    'payment_date' => $settlement->payment_date?->toDateString(),
                    'status' => $settlement->status,
                ];
            });
    }
}