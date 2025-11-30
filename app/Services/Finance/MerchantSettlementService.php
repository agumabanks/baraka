<?php

namespace App\Services\Finance;

use App\Models\MerchantSettlement;
use App\Models\SettlementItem;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * MerchantSettlementService
 * 
 * Merchant payment settlement management:
 * - Settlement generation
 * - Approval workflow
 * - Payment processing
 * - Statement generation
 */
class MerchantSettlementService
{
    /**
     * Generate settlement for merchant
     */
    public function generateSettlement(
        int $merchantId,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $branchId = null
    ): MerchantSettlement {
        return DB::transaction(function () use ($merchantId, $periodStart, $periodEnd, $branchId) {
            // Get eligible shipments (delivered COD shipments in period)
            $shipments = Shipment::where('customer_id', $merchantId)
                ->where('payment_type', 'cod')
                ->where('status', 'delivered')
                ->whereBetween('delivered_at', [$periodStart, $periodEnd])
                ->whereDoesntHave('settlementItems') // Not already settled
                ->when($branchId, fn($q) => $q->where('origin_branch_id', $branchId))
                ->get();

            if ($shipments->isEmpty()) {
                throw new \Exception('No eligible shipments found for settlement');
            }

            // Create settlement
            $settlement = MerchantSettlement::create([
                'merchant_id' => $merchantId,
                'branch_id' => $branchId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'currency' => 'USD',
                'status' => 'draft',
            ]);

            // Create settlement items
            $totalShipping = 0;
            $totalCod = 0;
            $totalDeductions = 0;

            foreach ($shipments as $shipment) {
                $shippingFee = $shipment->shipping_cost ?? 0;
                $codAmount = $shipment->cod_amount ?? 0;
                $deductions = 0; // Could add return fees, insurance claims, etc.
                $netAmount = $codAmount - $shippingFee - $deductions;

                SettlementItem::create([
                    'settlement_id' => $settlement->id,
                    'shipment_id' => $shipment->id,
                    'shipping_fee' => $shippingFee,
                    'cod_amount' => $codAmount,
                    'insurance_fee' => $shipment->insurance_amount ?? 0,
                    'other_charges' => 0,
                    'deductions' => $deductions,
                    'net_amount' => $netAmount,
                ]);

                $totalShipping += $shippingFee;
                $totalCod += $codAmount;
                $totalDeductions += $deductions;
            }

            // Update settlement totals
            $settlement->update([
                'shipment_count' => $shipments->count(),
                'total_shipping_fees' => $totalShipping,
                'total_cod_collected' => $totalCod,
                'total_deductions' => $totalDeductions,
                'net_payable' => $totalCod - $totalShipping - $totalDeductions,
                'breakdown' => [
                    'shipping_fees' => $totalShipping,
                    'cod_collected' => $totalCod,
                    'deductions' => $totalDeductions,
                    'shipment_ids' => $shipments->pluck('id')->toArray(),
                ],
            ]);

            return $settlement->fresh(['items', 'merchant']);
        });
    }

    /**
     * Submit settlement for approval
     */
    public function submitForApproval(MerchantSettlement $settlement): MerchantSettlement
    {
        if ($settlement->status !== 'draft') {
            throw new \Exception('Only draft settlements can be submitted for approval');
        }

        $settlement->submitForApproval();

        return $settlement->fresh();
    }

    /**
     * Approve settlement
     */
    public function approveSettlement(MerchantSettlement $settlement, int $approverId): MerchantSettlement
    {
        if ($settlement->status !== 'pending_approval') {
            throw new \Exception('Settlement is not pending approval');
        }

        $settlement->approve($approverId);

        return $settlement->fresh();
    }

    /**
     * Process settlement payment
     */
    public function processPayment(
        MerchantSettlement $settlement,
        string $paymentMethod,
        string $paymentReference,
        ?int $processedBy = null
    ): MerchantSettlement {
        if ($settlement->status !== 'approved') {
            throw new \Exception('Only approved settlements can be paid');
        }

        return DB::transaction(function () use ($settlement, $paymentMethod, $paymentReference, $processedBy) {
            // Update settlement status
            $settlement->markPaid($paymentMethod, $paymentReference);

            // Record financial transaction
            FinancialTransaction::recordSettlementPayment(
                $settlement,
                $processedBy,
                $paymentMethod,
                $paymentReference
            );

            return $settlement->fresh();
        });
    }

    /**
     * Get merchant settlement history
     */
    public function getMerchantSettlements(int $merchantId, array $filters = []): Collection
    {
        $query = MerchantSettlement::with('items')
            ->where('merchant_id', $merchantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('period_start', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('period_end', '<=', $filters['end_date']);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Get pending settlements
     */
    public function getPendingSettlements(?int $branchId = null): Collection
    {
        return MerchantSettlement::with('merchant')
            ->whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get merchant balance (unsettled COD)
     */
    public function getMerchantBalance(int $merchantId): array
    {
        // Get unsettled delivered COD shipments
        $unsettled = Shipment::where('customer_id', $merchantId)
            ->where('payment_type', 'cod')
            ->where('status', 'delivered')
            ->whereDoesntHave('settlementItems')
            ->get();

        $totalCod = $unsettled->sum('cod_amount');
        $totalShipping = $unsettled->sum('shipping_cost');
        $netPayable = $totalCod - $totalShipping;

        // Get pending settlement amounts
        $pendingSettlements = MerchantSettlement::where('merchant_id', $merchantId)
            ->whereIn('status', ['pending_approval', 'approved', 'processing'])
            ->sum('net_payable');

        return [
            'unsettled_shipments' => $unsettled->count(),
            'unsettled_cod' => round($totalCod, 2),
            'unsettled_shipping' => round($totalShipping, 2),
            'unsettled_net' => round($netPayable, 2),
            'pending_settlements' => round($pendingSettlements, 2),
            'total_receivable' => round($netPayable + $pendingSettlements, 2),
        ];
    }

    /**
     * Generate settlement statement
     */
    public function generateStatement(MerchantSettlement $settlement): array
    {
        $items = $settlement->items()->with('shipment')->get();

        return [
            'settlement_number' => $settlement->settlement_number,
            'merchant' => [
                'id' => $settlement->merchant_id,
                'name' => $settlement->merchant->name,
                'email' => $settlement->merchant->email,
            ],
            'period' => [
                'start' => $settlement->period_start->format('Y-m-d'),
                'end' => $settlement->period_end->format('Y-m-d'),
            ],
            'summary' => [
                'shipment_count' => $settlement->shipment_count,
                'total_cod_collected' => $settlement->total_cod_collected,
                'total_shipping_fees' => $settlement->total_shipping_fees,
                'total_deductions' => $settlement->total_deductions,
                'net_payable' => $settlement->net_payable,
                'currency' => $settlement->currency,
            ],
            'items' => $items->map(fn($item) => [
                'tracking_number' => $item->shipment->tracking_number,
                'delivered_at' => $item->shipment->delivered_at?->format('Y-m-d'),
                'cod_amount' => $item->cod_amount,
                'shipping_fee' => $item->shipping_fee,
                'deductions' => $item->deductions,
                'net_amount' => $item->net_amount,
            ])->toArray(),
            'status' => $settlement->status,
            'payment' => $settlement->status === 'paid' ? [
                'method' => $settlement->payment_method,
                'reference' => $settlement->payment_reference,
                'paid_at' => $settlement->paid_at?->format('Y-m-d H:i'),
            ] : null,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get settlement statistics
     */
    public function getSettlementStats(array $filters = []): array
    {
        $query = MerchantSettlement::query();

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_settlements' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'pending_approval' => (clone $query)->where('status', 'pending_approval')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'paid' => (clone $query)->where('status', 'paid')->count(),
            'total_amount_settled' => round((clone $query)->where('status', 'paid')->sum('net_payable'), 2),
            'total_amount_pending' => round((clone $query)->whereIn('status', ['pending_approval', 'approved'])->sum('net_payable'), 2),
        ];
    }
}
