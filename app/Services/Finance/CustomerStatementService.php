<?php

namespace App\Services\Finance;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * CustomerStatementService
 * 
 * Generates customer account statements including:
 * - Shipment history
 * - Invoice summary
 * - Payment history
 * - Outstanding balance
 */
class CustomerStatementService
{
    /**
     * Generate customer statement data
     */
    public function generateStatement(
        Customer $customer,
        Carbon $startDate,
        Carbon $endDate,
        ?int $branchId = null
    ): array {
        // Get shipments in period
        $shipments = Shipment::where('customer_id', $customer->id)
            ->when($branchId, fn($q) => $q->where('origin_branch_id', $branchId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['originBranch:id,name', 'destBranch:id,name'])
            ->orderBy('created_at')
            ->get();

        // Get invoices in period
        $invoices = Invoice::where('customer_id', $customer->id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        // Get payments in period
        $payments = Payment::where(function ($query) use ($customer, $branchId) {
                $query->whereHas('invoice', function ($q) use ($customer, $branchId) {
                        $q->where('customer_id', $customer->id)
                            ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId));
                    })
                    ->orWhere(function ($q) use ($customer, $branchId) {
                        $q->where('client_id', $customer->id)
                            ->when($branchId, fn($q2) => $q2->whereHas('shipment', fn($qs) => $qs->where('origin_branch_id', $branchId)))
                            ->orWhereHas('shipment', fn($qs) => $qs->where('customer_id', $customer->id));
                    });
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        // Calculate totals
        $totalShipments = $shipments->count();
        $totalShipmentValue = $shipments->sum('price_amount');
        $totalInvoiced = $invoices->sum('total_amount');
        $totalPaid = $payments->sum('amount');
        $outstandingBalance = $customer->current_balance ?? ($totalInvoiced - $totalPaid);

        // Opening balance (balance at start of period)
        $openingInvoices = Invoice::where('customer_id', $customer->id)
            ->where('created_at', '<', $startDate)
            ->sum('total_amount');
        $openingPayments = Payment::where(function ($query) use ($customer) {
                $query->whereHas('invoice', function ($q) use ($customer) {
                        $q->where('customer_id', $customer->id);
                    })
                    ->orWhere(function ($q) use ($customer) {
                        $q->where('client_id', $customer->id)
                            ->orWhereHas('shipment', fn($qs) => $qs->where('customer_id', $customer->id));
                    });
            })
            ->where('created_at', '<', $startDate)
            ->sum('amount');
        $openingBalance = $openingInvoices - $openingPayments;

        // Build transaction ledger
        $transactions = $this->buildTransactionLedger($invoices, $payments, $openingBalance);

        return [
            'customer' => $customer,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'opening_balance' => $openingBalance,
                'total_shipments' => $totalShipments,
                'total_shipment_value' => $totalShipmentValue,
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'closing_balance' => $openingBalance + $totalInvoiced - $totalPaid,
            ],
            'shipments' => $shipments,
            'invoices' => $invoices,
            'payments' => $payments,
            'transactions' => $transactions,
            'generated_at' => now(),
        ];
    }

    /**
     * Build transaction ledger with running balance
     */
    protected function buildTransactionLedger(Collection $invoices, Collection $payments, float $openingBalance): Collection
    {
        $transactions = collect();

        // Add invoices
        foreach ($invoices as $invoice) {
            $transactions->push([
                'date' => $invoice->created_at,
                'type' => 'invoice',
                'reference' => $invoice->invoice_number ?? "INV-{$invoice->id}",
                'description' => "Invoice for shipment",
                'debit' => $invoice->total_amount,
                'credit' => 0,
            ]);
        }

        // Add payments
        foreach ($payments as $payment) {
            $transactions->push([
                'date' => $payment->created_at,
                'type' => 'payment',
                'reference' => $payment->reference ?? "PAY-{$payment->id}",
                'description' => "Payment received - {$payment->payment_method}",
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }

        // Sort by date
        $sorted = $transactions->sortBy('date');

        // Add running balance
        $balance = $openingBalance;
        return $sorted->map(function ($tx) use (&$balance) {
            $balance = $balance + $tx['debit'] - $tx['credit'];
            $tx['balance'] = $balance;
            return $tx;
        })->values();
    }

    /**
     * Generate PDF statement
     */
    public function generatePdf(
        Customer $customer,
        Carbon $startDate,
        Carbon $endDate,
        ?int $branchId = null
    ): \Barryvdh\DomPDF\PDF {
        $data = $this->generateStatement($customer, $startDate, $endDate, $branchId);

        return Pdf::loadView('pdf.customer_statement', $data)
            ->setPaper('a4', 'portrait');
    }

    /**
     * Get aging summary for customer
     */
    public function getAgingSummary(Customer $customer): array
    {
        $now = now();

        $invoices = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['PENDING', 'SENT', 'OVERDUE'])
            ->get();

        $aging = [
            'current' => 0,      // 0-30 days
            'days_31_60' => 0,   // 31-60 days
            'days_61_90' => 0,   // 61-90 days
            'over_90' => 0,      // 90+ days
            'total' => 0,
        ];

        foreach ($invoices as $invoice) {
            $dueDate = $invoice->due_date ?? $invoice->created_at;
            $daysOverdue = $now->diffInDays($dueDate, false);
            $amount = $invoice->total_amount - ($invoice->payments?->sum('amount') ?? 0);

            if ($daysOverdue <= 30) {
                $aging['current'] += $amount;
            } elseif ($daysOverdue <= 60) {
                $aging['days_31_60'] += $amount;
            } elseif ($daysOverdue <= 90) {
                $aging['days_61_90'] += $amount;
            } else {
                $aging['over_90'] += $amount;
            }
            $aging['total'] += $amount;
        }

        return $aging;
    }
}
