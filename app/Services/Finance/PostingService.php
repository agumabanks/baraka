<?php

namespace App\Services\Finance;

use App\Models\AccountingEntry;
use App\Models\PaymentTransaction;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class PostingService
{
    public function postPayment(PaymentTransaction $transaction): array
    {
        $entries = [];

        DB::transaction(function () use ($transaction, &$entries) {
            $shipment = $transaction->shipment;
            $postingDate = $transaction->completed_at?->toDateString() ?? now()->toDateString();
            $reference = "PAY-{$transaction->uuid}";

            // Debit Cash/Bank (asset increases)
            $debitAccount = $this->getDebitAccountForMethod($transaction->method);
            $entries[] = AccountingEntry::create([
                'payment_transaction_id' => $transaction->id,
                'shipment_id' => $shipment?->id,
                'account_code' => $debitAccount['code'],
                'account_name' => $debitAccount['name'],
                'entry_type' => AccountingEntry::TYPE_DEBIT,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'reference' => $reference,
                'description' => "Payment received for shipment {$shipment?->tracking_number}",
                'posting_date' => $postingDate,
                'status' => AccountingEntry::STATUS_PENDING,
                'created_by' => $transaction->created_by,
            ]);

            // Credit Revenue breakdown
            if ($shipment) {
                $breakdown = $this->getRevenueBreakdown($shipment, $transaction->amount);

                foreach ($breakdown as $item) {
                    if ($item['amount'] > 0) {
                        $entries[] = AccountingEntry::create([
                            'payment_transaction_id' => $transaction->id,
                            'shipment_id' => $shipment->id,
                            'account_code' => $item['code'],
                            'account_name' => $item['name'],
                            'entry_type' => AccountingEntry::TYPE_CREDIT,
                            'amount' => $item['amount'],
                            'currency' => $transaction->currency,
                            'reference' => $reference,
                            'description' => $item['description'],
                            'posting_date' => $postingDate,
                            'status' => AccountingEntry::STATUS_PENDING,
                            'created_by' => $transaction->created_by,
                        ]);
                    }
                }
            } else {
                // Simple revenue credit
                $entries[] = AccountingEntry::create([
                    'payment_transaction_id' => $transaction->id,
                    'account_code' => AccountingEntry::ACCOUNT_REVENUE_FREIGHT,
                    'account_name' => 'Freight Revenue',
                    'entry_type' => AccountingEntry::TYPE_CREDIT,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'reference' => $reference,
                    'description' => 'Payment received',
                    'posting_date' => $postingDate,
                    'status' => AccountingEntry::STATUS_PENDING,
                    'created_by' => $transaction->created_by,
                ]);
            }
        });

        return $entries;
    }

    protected function getDebitAccountForMethod(string $method): array
    {
        return match ($method) {
            'cash' => ['code' => AccountingEntry::ACCOUNT_CASH, 'name' => 'Cash'],
            'card', 'mobile_money', 'bank_transfer' => ['code' => '1110', 'name' => 'Bank'],
            'on_account', 'cheque' => ['code' => AccountingEntry::ACCOUNT_RECEIVABLES, 'name' => 'Accounts Receivable'],
            default => ['code' => AccountingEntry::ACCOUNT_CASH, 'name' => 'Cash'],
        };
    }

    protected function getRevenueBreakdown(Shipment $shipment, float $totalAmount): array
    {
        $breakdown = [];

        // Calculate proportions if we have the breakdown fields
        $baseRate = $shipment->base_rate ?? 0;
        $weightCharge = $shipment->weight_charge ?? 0;
        $surcharges = $shipment->surcharges_total ?? 0;
        $insurance = $shipment->insurance_fee ?? 0;
        $tax = $shipment->tax_amount ?? 0;

        $freightRevenue = $baseRate + $weightCharge;

        if ($freightRevenue > 0) {
            $breakdown[] = [
                'code' => AccountingEntry::ACCOUNT_REVENUE_FREIGHT,
                'name' => 'Freight Revenue',
                'amount' => $freightRevenue,
                'description' => 'Base freight and weight charges',
            ];
        }

        if ($surcharges > 0) {
            $breakdown[] = [
                'code' => AccountingEntry::ACCOUNT_REVENUE_SURCHARGES,
                'name' => 'Surcharge Revenue',
                'amount' => $surcharges,
                'description' => 'Fuel and other surcharges',
            ];
        }

        if ($insurance > 0) {
            $breakdown[] = [
                'code' => AccountingEntry::ACCOUNT_REVENUE_INSURANCE,
                'name' => 'Insurance Revenue',
                'amount' => $insurance,
                'description' => 'Shipment insurance',
            ];
        }

        if ($tax > 0) {
            $breakdown[] = [
                'code' => AccountingEntry::ACCOUNT_TAX_PAYABLE,
                'name' => 'VAT Payable',
                'amount' => $tax,
                'description' => 'VAT collected',
            ];
        }

        // If no breakdown available, post full amount to freight revenue
        if (empty($breakdown)) {
            $breakdown[] = [
                'code' => AccountingEntry::ACCOUNT_REVENUE_FREIGHT,
                'name' => 'Freight Revenue',
                'amount' => $totalAmount,
                'description' => 'Shipment revenue',
            ];
        }

        return $breakdown;
    }

    public function postRefund(PaymentTransaction $originalTransaction, float $refundAmount): array
    {
        $entries = [];
        $postingDate = now()->toDateString();
        $reference = "REF-{$originalTransaction->uuid}";

        DB::transaction(function () use ($originalTransaction, $refundAmount, $postingDate, $reference, &$entries) {
            // Credit Cash (reduce asset)
            $debitAccount = $this->getDebitAccountForMethod($originalTransaction->method);
            $entries[] = AccountingEntry::create([
                'payment_transaction_id' => $originalTransaction->id,
                'shipment_id' => $originalTransaction->shipment_id,
                'account_code' => $debitAccount['code'],
                'account_name' => $debitAccount['name'],
                'entry_type' => AccountingEntry::TYPE_CREDIT,
                'amount' => $refundAmount,
                'currency' => $originalTransaction->currency,
                'reference' => $reference,
                'description' => "Refund for transaction {$originalTransaction->uuid}",
                'posting_date' => $postingDate,
                'status' => AccountingEntry::STATUS_PENDING,
                'created_by' => auth()->id(),
            ]);

            // Debit Revenue (reduce revenue)
            $entries[] = AccountingEntry::create([
                'payment_transaction_id' => $originalTransaction->id,
                'shipment_id' => $originalTransaction->shipment_id,
                'account_code' => AccountingEntry::ACCOUNT_REVENUE_FREIGHT,
                'account_name' => 'Freight Revenue',
                'entry_type' => AccountingEntry::TYPE_DEBIT,
                'amount' => $refundAmount,
                'currency' => $originalTransaction->currency,
                'reference' => $reference,
                'description' => "Refund reversal for transaction {$originalTransaction->uuid}",
                'posting_date' => $postingDate,
                'status' => AccountingEntry::STATUS_PENDING,
                'created_by' => auth()->id(),
            ]);
        });

        return $entries;
    }

    public function syncToExternalSystem(): int
    {
        $pending = AccountingEntry::pending()->get();
        $synced = 0;

        foreach ($pending as $entry) {
            // Placeholder for external ERP/accounting system integration
            // In real implementation, this would call an API or queue a job
            $entry->markPosted();
            $synced++;
        }

        return $synced;
    }
}
