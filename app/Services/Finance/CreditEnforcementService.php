<?php

namespace App\Services\Finance;

use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CreditEnforcementService
{
    /**
     * Credit hold thresholds
     */
    protected array $thresholds = [
        'warning' => 0.80,      // 80% - Warning alert
        'soft_block' => 0.95,   // 95% - Soft block (require approval)
        'hard_block' => 1.00,   // 100% - Hard block (no new shipments)
    ];

    /**
     * Check if customer can create shipment
     */
    public function canCreateShipment(Customer $customer, float $shipmentValue = 0): array
    {
        // COD customers bypass credit check
        if ($customer->payment_terms === 'cod') {
            return [
                'allowed' => true,
                'reason' => 'COD payment terms',
            ];
        }

        // Inactive/suspended customers cannot create shipments
        if (!in_array($customer->status, ['active'])) {
            return [
                'allowed' => false,
                'reason' => "Customer status is {$customer->status}",
                'block_type' => 'status_block',
            ];
        }

        // No credit limit set - allow
        if ($customer->credit_limit <= 0) {
            return [
                'allowed' => true,
                'reason' => 'No credit limit configured',
            ];
        }

        $projectedBalance = $customer->current_balance + $shipmentValue;
        $utilization = $projectedBalance / $customer->credit_limit;

        // Hard block - over limit
        if ($utilization >= $this->thresholds['hard_block']) {
            return [
                'allowed' => false,
                'reason' => 'Credit limit exceeded',
                'block_type' => 'hard_block',
                'current_balance' => $customer->current_balance,
                'credit_limit' => $customer->credit_limit,
                'utilization_percent' => round($utilization * 100, 1),
                'available_credit' => max(0, $customer->credit_limit - $customer->current_balance),
            ];
        }

        // Soft block - near limit (requires approval)
        if ($utilization >= $this->thresholds['soft_block']) {
            return [
                'allowed' => false,
                'reason' => 'Credit limit almost reached - approval required',
                'block_type' => 'soft_block',
                'requires_approval' => true,
                'current_balance' => $customer->current_balance,
                'credit_limit' => $customer->credit_limit,
                'utilization_percent' => round($utilization * 100, 1),
                'available_credit' => max(0, $customer->credit_limit - $customer->current_balance),
            ];
        }

        // Warning level
        if ($utilization >= $this->thresholds['warning']) {
            return [
                'allowed' => true,
                'warning' => true,
                'reason' => 'Approaching credit limit',
                'utilization_percent' => round($utilization * 100, 1),
                'available_credit' => max(0, $customer->credit_limit - $customer->current_balance),
            ];
        }

        return [
            'allowed' => true,
            'utilization_percent' => round($utilization * 100, 1),
            'available_credit' => max(0, $customer->credit_limit - $customer->current_balance),
        ];
    }

    /**
     * Place shipment on credit hold
     */
    public function placeCreditHold(Shipment $shipment, string $reason): void
    {
        $shipment->update([
            'credit_hold' => true,
            'credit_hold_reason' => $reason,
            'credit_hold_at' => now(),
        ]);

        // Log the hold
        Log::info('Shipment placed on credit hold', [
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'customer_id' => $shipment->customer_id,
            'reason' => $reason,
        ]);

        // TODO: Send notification to credit manager
    }

    /**
     * Release shipment from credit hold
     */
    public function releaseCreditHold(Shipment $shipment, User $approvedBy, string $notes = null): void
    {
        $shipment->update([
            'credit_hold' => false,
            'credit_hold_reason' => null,
            'credit_hold_released_at' => now(),
            'credit_hold_released_by' => $approvedBy->id,
            'credit_hold_release_notes' => $notes,
        ]);

        Log::info('Shipment released from credit hold', [
            'shipment_id' => $shipment->id,
            'approved_by' => $approvedBy->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Get customers approaching credit limit
     */
    public function getCustomersApproachingLimit(float $threshold = 0.80): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::active()
            ->where('credit_limit', '>', 0)
            ->whereRaw('current_balance >= credit_limit * ?', [$threshold])
            ->orderByRaw('current_balance / credit_limit DESC')
            ->get();
    }

    /**
     * Get shipments on credit hold
     */
    public function getShipmentsOnCreditHold(int $branchId = null): \Illuminate\Database\Eloquent\Collection
    {
        return Shipment::with(['customer:id,company_name,contact_person,credit_limit,current_balance'])
            ->where('credit_hold', true)
            ->when($branchId, fn($q) => $q->where('origin_branch_id', $branchId))
            ->orderBy('credit_hold_at')
            ->get();
    }

    /**
     * Update customer balance after shipment delivery
     */
    public function updateBalanceOnDelivery(Shipment $shipment): void
    {
        if (!$shipment->customer_id) {
            return;
        }

        $customer = $shipment->customer;
        if (!$customer) {
            return;
        }

        // Add shipment value to balance (for credit customers)
        if ($customer->payment_terms !== 'cod') {
            $shipmentValue = $shipment->total_amount ?? $shipment->price_amount ?? 0;
            
            $customer->increment('current_balance', $shipmentValue);

            Log::info('Customer balance updated on delivery', [
                'customer_id' => $customer->id,
                'shipment_id' => $shipment->id,
                'amount_added' => $shipmentValue,
                'new_balance' => $customer->fresh()->current_balance,
            ]);
        }
    }

    /**
     * Update customer balance after payment received
     */
    public function updateBalanceOnPayment(Customer $customer, float $amount): void
    {
        $customer->decrement('current_balance', $amount);

        Log::info('Customer balance updated on payment', [
            'customer_id' => $customer->id,
            'amount_paid' => $amount,
            'new_balance' => $customer->fresh()->current_balance,
        ]);
    }

    /**
     * Get credit summary for customer
     */
    public function getCreditSummary(Customer $customer): array
    {
        $utilization = $customer->credit_limit > 0 
            ? ($customer->current_balance / $customer->credit_limit) 
            : 0;

        $status = 'good';
        if ($utilization >= 1.0) {
            $status = 'over_limit';
        } elseif ($utilization >= 0.95) {
            $status = 'critical';
        } elseif ($utilization >= 0.80) {
            $status = 'warning';
        }

        return [
            'credit_limit' => $customer->credit_limit,
            'current_balance' => $customer->current_balance,
            'available_credit' => max(0, $customer->credit_limit - $customer->current_balance),
            'utilization_percent' => round($utilization * 100, 1),
            'status' => $status,
            'payment_terms' => $customer->payment_terms,
            'is_cod' => $customer->payment_terms === 'cod',
            'can_create_shipments' => $this->canCreateShipment($customer)['allowed'],
        ];
    }

    /**
     * Get credit report for all customers
     */
    public function getCreditReport(): array
    {
        $customers = Customer::active()
            ->where('credit_limit', '>', 0)
            ->get();

        $overLimit = $customers->filter(fn($c) => $c->current_balance > $c->credit_limit);
        $nearLimit = $customers->filter(fn($c) => 
            $c->current_balance <= $c->credit_limit && 
            $c->current_balance >= $c->credit_limit * 0.80
        );
        $healthy = $customers->filter(fn($c) => 
            $c->current_balance < $c->credit_limit * 0.80
        );

        return [
            'total_customers_with_credit' => $customers->count(),
            'total_credit_extended' => $customers->sum('credit_limit'),
            'total_outstanding' => $customers->sum('current_balance'),
            'overall_utilization' => $customers->sum('credit_limit') > 0 
                ? round(($customers->sum('current_balance') / $customers->sum('credit_limit')) * 100, 1) 
                : 0,
            'over_limit_count' => $overLimit->count(),
            'over_limit_amount' => $overLimit->sum(fn($c) => $c->current_balance - $c->credit_limit),
            'near_limit_count' => $nearLimit->count(),
            'healthy_count' => $healthy->count(),
        ];
    }
}
