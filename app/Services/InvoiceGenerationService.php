<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceGenerationService
{
    /**
     * Generate invoice for a delivered shipment
     */
    public function generateForShipment(Shipment $shipment): Invoice
    {
        return DB::transaction(function () use ($shipment) {
            // Check if invoice already exists
            if ($shipment->invoice_id) {
                $existing = Invoice::find($shipment->invoice_id);
                if ($existing) {
                    return $existing;
                }
            }

            $customer = $shipment->customer;
            if (!$customer) {
                throw new \Exception('Shipment has no associated customer');
            }

            // Calculate charges
            $charges = $this->calculateCharges($shipment);

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($shipment->dest_branch_id ?? $shipment->origin_branch_id);

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_id' => Str::uuid()->toString(),
                'branch_id' => $shipment->dest_branch_id ?? $shipment->origin_branch_id,
                'merchant_id' => $customer->id,
                'invoice_date' => now()->format('Y-m-d'),
                'total_charge' => $charges['total'],
                'cash_collection' => $charges['cod_amount'] ?? 0,
                'current_payable' => $charges['total'] - ($charges['cod_amount'] ?? 0),
                'parcels_id' => json_encode([$shipment->id]),
                'status' => 1, // Draft status
            ]);

            // Link shipment to invoice
            $shipment->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });
    }

    /**
     * Generate invoice for multiple shipments (batch)
     */
    public function generateBatchInvoice(array $shipmentIds, int $customerId): Invoice
    {
        return DB::transaction(function () use ($shipmentIds, $customerId) {
            $shipments = Shipment::whereIn('id', $shipmentIds)
                ->where('customer_id', $customerId)
                ->whereNull('invoice_id')
                ->get();

            if ($shipments->isEmpty()) {
                throw new \Exception('No eligible shipments found for invoicing');
            }

            $customer = Customer::findOrFail($customerId);
            $totalCharges = 0;
            $totalCod = 0;

            foreach ($shipments as $shipment) {
                $charges = $this->calculateCharges($shipment);
                $totalCharges += $charges['total'];
                $totalCod += $charges['cod_amount'] ?? 0;
            }

            $branchId = $shipments->first()->dest_branch_id ?? $shipments->first()->origin_branch_id;
            $invoiceNumber = $this->generateInvoiceNumber($branchId);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_id' => Str::uuid()->toString(),
                'branch_id' => $branchId,
                'merchant_id' => $customer->id,
                'invoice_date' => now()->format('Y-m-d'),
                'total_charge' => $totalCharges,
                'cash_collection' => $totalCod,
                'current_payable' => $totalCharges - $totalCod,
                'parcels_id' => json_encode($shipmentIds),
                'status' => 1, // Draft
            ]);

            // Link all shipments to this invoice
            Shipment::whereIn('id', $shipmentIds)->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });
    }

    /**
     * Calculate charges for a shipment
     */
    protected function calculateCharges(Shipment $shipment): array
    {
        // Base charge (from shipment or calculated)
        $baseCharge = $shipment->total_delivery_cost ?? 0;
        
        // Weight-based calculation if no fixed cost
        if ($baseCharge == 0 && $shipment->parcels) {
            $totalWeight = $shipment->parcels->sum('weight');
            $baseCharge = $this->calculateWeightCharge($totalWeight, $shipment);
        }

        // Surcharges
        $surcharges = [
            'fuel_surcharge' => $baseCharge * 0.05, // 5% fuel surcharge
            'handling_fee' => $shipment->is_fragile ? 10 : 0,
            'insurance' => $shipment->declared_value ? ($shipment->declared_value * 0.01) : 0,
        ];

        // Tax calculation
        $subtotal = $baseCharge + array_sum($surcharges);
        $taxRate = $this->getTaxRate($shipment->dest_branch_id);
        $tax = $subtotal * $taxRate;

        $total = $subtotal + $tax;

        return [
            'base_charge' => $baseCharge,
            'surcharges' => $surcharges,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'total' => $total,
            'cod_amount' => $shipment->cod_amount ?? 0,
        ];
    }

    /**
     * Calculate weight-based charge
     */
    protected function calculateWeightCharge(float $weight, Shipment $shipment): float
    {
        // Simple tiered pricing
        $ratePerKg = 5.0; // Base rate
        
        // Distance-based multiplier
        $distance = $this->calculateDistance($shipment);
        if ($distance > 500) {
            $ratePerKg *= 1.5;
        } elseif ($distance > 200) {
            $ratePerKg *= 1.25;
        }

        return $weight * $ratePerKg;
    }

    /**
     * Calculate distance between origin and destination (placeholder)
     */
    protected function calculateDistance(Shipment $shipment): float
    {
        // Placeholder - in real implementation, calculate actual distance
        // using branch locations or geocoding
        return 100; // km
    }

    /**
     * Get tax rate for a branch (database-backed with safe fallback)
     */
    protected function getTaxRate(?int $branchId): float
    {
        // First check if branch has custom tax rate
        if ($branchId) {
            $branch = \App\Models\Backend\Branch::find($branchId);
            if ($branch && isset($branch->settings['tax_rate'])) {
                return (float) $branch->settings['tax_rate'] / 100;
            }
        }
        
        // Use system-wide tax rate from settings (stored as %, convert to decimal)
        $vatPercent = SystemSettings::vatRate(); // Returns 18 for 18%
        return $vatPercent / 100; // Convert to 0.18
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(int $branchId): string
    {
        $branch = \App\Models\Backend\Branch::find($branchId);
        // Use branch code, or fall back to system invoice prefix from settings
        $defaultPrefix = SystemSettings::invoicePrefix() ?: 'INV';
        $branchCode = $branch ? strtoupper(substr($branch->code ?? $defaultPrefix, 0, 3)) : $defaultPrefix;
        
        $year = now()->format('Y');
        $month = now()->format('m');
        
        // Get last invoice number for this branch and month
        $lastInvoice = Invoice::where('branch_id', $branchId)
            ->where('invoice_number', 'like', "{$branchCode}-{$year}{$month}%")
            ->orderByDesc('id')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s-%04d', $branchCode, $year, $month, $newNumber);
    }

    /**
     * Finalize a draft invoice
     */
    public function finalizeInvoice(Invoice $invoice): void
    {
        if ($invoice->status != 1) {
            throw new \Exception('Only draft invoices can be finalized');
        }

        $invoice->update([
            'status' => 2, // Finalized
        ]);
    }

    /**
     * Record a payment against an invoice
     */
    public function recordPayment(Invoice $invoice, float $amount, string $method = 'cash', ?array $metadata = null): void
    {
        $invoice->update([
            'current_payable' => max(0, $invoice->current_payable - $amount),
            'status' => ($invoice->current_payable - $amount) <= 0 ? 3 : 2, // 3 = Paid
        ]);

        // Log payment in payment history (if payment model exists)
        if (class_exists(\App\Models\Payment::class)) {
            \App\Models\Payment::create([
                'invoice_id' => $invoice->id,
                'merchant_id' => $invoice->merchant_id,
                'amount' => $amount,
                'payment_method' => $method,
                'metadata' => $metadata,
                'paid_at' => now(),
            ]);
        }
    }

    /**
     * Check if customer has exceeded credit limit
     */
    public function checkCreditLimit(Customer $customer): bool
    {
        $creditLimit = $customer->credit_limit ?? 0;
        if ($creditLimit <= 0) {
            return true; // No limit set
        }

        $outstandingBalance = Invoice::where('merchant_id', $customer->id)
            ->whereIn('status', [1, 2]) // Draft or Finalized (unpaid)
            ->sum('current_payable');

        return $outstandingBalance < $creditLimit;
    }

    /**
     * Get outstanding balance for a customer
     */
    public function getOutstandingBalance(int $customerId): float
    {
        return Invoice::where('merchant_id', $customerId)
            ->whereIn('status', [1, 2])
            ->sum('current_payable');
    }
}
