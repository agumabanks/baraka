<?php

namespace App\Services\Finance;

use App\Models\BranchSettlement;
use App\Models\Backend\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Shipment;
use App\Enums\ShipmentStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * BranchSettlementService
 * 
 * Handles Branch to HQ settlement flow:
 * - Settlement generation based on branch activity
 * - Revenue and expense aggregation
 * - Approval workflow
 * - Settlement processing
 */
class BranchSettlementService
{
    /**
     * Generate settlement for a branch for a given period
     */
    public function generateSettlement(
        int $branchId,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $currency = 'USD'
    ): BranchSettlement {
        return DB::transaction(function () use ($branchId, $periodStart, $periodEnd, $currency) {
            $branch = Branch::findOrFail($branchId);
            
            // Check for overlapping settlements
            $existing = BranchSettlement::where('branch_id', $branchId)
                ->where(function ($q) use ($periodStart, $periodEnd) {
                    $q->whereBetween('period_start', [$periodStart, $periodEnd])
                        ->orWhereBetween('period_end', [$periodStart, $periodEnd]);
                })
                ->whereIn('status', ['submitted', 'approved', 'settled'])
                ->exists();

            if ($existing) {
                throw new \Exception('A settlement already exists for this period');
            }

            // Calculate revenue from delivered shipments
            $shipmentRevenue = $this->calculateShipmentRevenue($branchId, $periodStart, $periodEnd);
            
            // Calculate COD collected
            $codData = $this->calculateCodCollected($branchId, $periodStart, $periodEnd);
            
            // Calculate expenses
            $expenses = $this->calculateExpenses($branchId, $periodStart, $periodEnd);
            
            // Calculate net position
            $totalRevenue = $shipmentRevenue['total'];
            $totalCod = $codData['total'];
            $totalExpenses = $expenses['total'];
            
            // Net amount = Revenue retained by branch
            // COD collected goes to HQ (after deducting branch commission)
            $branchCommissionRate = 0.15; // 15% commission to branch
            $branchCommission = $totalCod * $branchCommissionRate;
            
            $netAmount = $totalRevenue - $totalExpenses + $branchCommission;
            $amountDueToHq = $totalCod - $branchCommission; // COD to remit to HQ
            $amountDueFromHq = max(0, -$netAmount); // If branch owes HQ

            $settlement = BranchSettlement::create([
                'branch_id' => $branchId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'total_shipment_revenue' => $shipmentRevenue['total'],
                'total_cod_collected' => $codData['total'],
                'shipment_count' => $shipmentRevenue['count'],
                'cod_shipment_count' => $codData['count'],
                'total_expenses' => $expenses['total'],
                'driver_payments' => $expenses['driver_payments'],
                'operational_costs' => $expenses['operational'],
                'net_amount' => $netAmount,
                'amount_due_to_hq' => $amountDueToHq,
                'amount_due_from_hq' => $amountDueFromHq,
                'currency' => $currency,
                'status' => 'draft',
                'breakdown' => [
                    'revenue' => $shipmentRevenue,
                    'cod' => $codData,
                    'expenses' => $expenses,
                    'commission_rate' => $branchCommissionRate,
                    'branch_commission' => $branchCommission,
                ],
            ]);

            return $settlement->fresh('branch');
        });
    }

    /**
     * Calculate shipment revenue for period
     */
    protected function calculateShipmentRevenue(int $branchId, Carbon $start, Carbon $end): array
    {
        $shipments = Shipment::where('origin_branch_id', $branchId)
            ->whereBetween('delivered_at', [$start, $end])
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->get();

        return [
            'total' => $shipments->sum('price_amount'),
            'count' => $shipments->count(),
            'by_service' => $shipments->groupBy('service_level')->map(fn($g) => [
                'count' => $g->count(),
                'revenue' => $g->sum('price_amount'),
            ])->toArray(),
        ];
    }

    /**
     * Calculate COD collected for period
     */
    protected function calculateCodCollected(int $branchId, Carbon $start, Carbon $end): array
    {
        // COD payments collected by this branch
        $payments = Payment::where('payment_method', 'cod')
            ->whereHas('shipment', function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->get();

        return [
            'total' => $payments->sum('amount'),
            'count' => $payments->count(),
        ];
    }

    /**
     * Calculate expenses for period
     */
    protected function calculateExpenses(int $branchId, Carbon $start, Carbon $end): array
    {
        // This would integrate with an expense tracking system
        // For now, return placeholder structure
        return [
            'total' => 0,
            'driver_payments' => 0,
            'operational' => 0,
            'fuel' => 0,
            'maintenance' => 0,
        ];
    }

    /**
     * Get branch financial summary
     */
    public function getBranchSummary(int $branchId, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $start = $start ?? now()->startOfMonth();
        $end = $end ?? now()->endOfMonth();

        // Revenue
        $revenue = Shipment::where('origin_branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('price_amount');

        // Delivered revenue
        $deliveredRevenue = Shipment::where('origin_branch_id', $branchId)
            ->whereBetween('delivered_at', [$start, $end])
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->sum('price_amount');

        // COD collected
        $codCollected = Payment::where('payment_method', 'cod')
            ->whereHas('shipment', function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        // Pending invoices
        $pendingInvoices = Invoice::where('branch_id', $branchId)
            ->whereIn('status', ['PENDING', 'SENT', 'OVERDUE'])
            ->sum('total_amount');

        // Settlements
        $pendingSettlements = BranchSettlement::where('branch_id', $branchId)
            ->whereIn('status', ['submitted', 'approved'])
            ->sum('amount_due_to_hq');

        $settledThisPeriod = BranchSettlement::where('branch_id', $branchId)
            ->where('status', 'settled')
            ->whereBetween('settled_at', [$start, $end])
            ->sum('amount_due_to_hq');

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'revenue' => [
                'booked' => (float) $revenue,
                'delivered' => (float) $deliveredRevenue,
            ],
            'collections' => [
                'cod_collected' => (float) $codCollected,
            ],
            'receivables' => [
                'pending_invoices' => (float) $pendingInvoices,
            ],
            'settlements' => [
                'pending' => (float) $pendingSettlements,
                'settled_this_period' => (float) $settledThisPeriod,
            ],
        ];
    }

    /**
     * Get admin consolidated summary across all branches
     */
    public function getConsolidatedSummary(?Carbon $start = null, ?Carbon $end = null): array
    {
        $start = $start ?? now()->startOfMonth();
        $end = $end ?? now()->endOfMonth();

        $branches = Branch::where('status', 1)->get();
        $branchSummaries = [];

        foreach ($branches as $branch) {
            $branchSummaries[$branch->id] = array_merge(
                ['branch_name' => $branch->name],
                $this->getBranchSummary($branch->id, $start, $end)
            );
        }

        // Totals
        $totalRevenue = collect($branchSummaries)->sum('revenue.delivered');
        $totalCod = collect($branchSummaries)->sum('collections.cod_collected');
        $totalPendingSettlements = collect($branchSummaries)->sum('settlements.pending');

        // Pending approvals
        $pendingApprovals = BranchSettlement::where('status', 'submitted')->count();

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'totals' => [
                'revenue' => $totalRevenue,
                'cod_collected' => $totalCod,
                'pending_settlements' => $totalPendingSettlements,
                'pending_approvals' => $pendingApprovals,
            ],
            'by_branch' => $branchSummaries,
        ];
    }

    /**
     * Get settlements pending admin action
     */
    public function getPendingSettlements(): \Illuminate\Database\Eloquent\Collection
    {
        return BranchSettlement::with('branch')
            ->whereIn('status', ['submitted', 'approved'])
            ->orderBy('submitted_at')
            ->get();
    }
}
