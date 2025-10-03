<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Backend\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicingSystemService
{
    /**
     * Generate invoice for a shipment
     */
    public function generateShipmentInvoice(Shipment $shipment): array
    {
        // Check if invoice already exists
        if ($shipment->invoice) {
            return [
                'success' => false,
                'message' => 'Invoice already exists for this shipment',
                'invoice' => $shipment->invoice,
            ];
        }

        // Calculate charges
        $charges = $this->calculateShipmentCharges($shipment);

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'customer_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(30), // 30 days payment terms
                'subtotal' => $charges['subtotal'],
                'tax_amount' => $charges['tax_amount'],
                'discount_amount' => $charges['discount_amount'],
                'total_amount' => $charges['total_amount'],
                'currency' => 'USD',
                'status' => 'pending',
                'billing_address' => $this->getCustomerBillingAddress($shipment->customer),
                'line_items' => $charges['line_items'],
                'metadata' => [
                    'generated_by' => 'system',
                    'generation_date' => now()->toISOString(),
                    'shipment_details' => [
                        'tracking_number' => $shipment->tracking_number,
                        'origin_branch' => $shipment->originBranch->name,
                        'destination_branch' => $shipment->destBranch->name,
                        'service_level' => $shipment->service_level,
                    ],
                ],
            ]);

            // Update shipment with invoice reference
            $shipment->update(['invoice_id' => $invoice->id]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Invoice generated successfully',
                'invoice' => $invoice->load(['customer', 'shipment']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice generation failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate invoice: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate consolidated invoice for multiple shipments
     */
    public function generateConsolidatedInvoice(Collection $shipments, Customer $customer): array
    {
        if ($shipments->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No shipments provided for invoicing',
            ];
        }

        // Validate all shipments belong to the same customer
        $invalidShipments = $shipments->filter(function ($shipment) use ($customer) {
            return $shipment->customer_id !== $customer->id;
        });

        if ($invalidShipments->count() > 0) {
            return [
                'success' => false,
                'message' => 'All shipments must belong to the same customer',
            ];
        }

        // Check for existing invoices
        $invoicedShipments = $shipments->filter(function ($shipment) {
            return $shipment->invoice_id !== null;
        });

        if ($invoicedShipments->count() > 0) {
            return [
                'success' => false,
                'message' => 'Some shipments already have invoices',
                'invoiced_shipments' => $invoicedShipments->pluck('tracking_number'),
            ];
        }

        $allCharges = [];
        $totalSubtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($shipments as $shipment) {
            $charges = $this->calculateShipmentCharges($shipment);
            $allCharges[] = [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'charges' => $charges,
            ];
            $totalSubtotal += $charges['subtotal'];
            $totalTax += $charges['tax_amount'];
            $totalDiscount += $charges['discount_amount'];
        }

        $totalAmount = $totalSubtotal + $totalTax - $totalDiscount;

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $totalSubtotal,
                'tax_amount' => $totalTax,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount,
                'currency' => 'USD',
                'status' => 'pending',
                'billing_address' => $this->getCustomerBillingAddress($customer),
                'line_items' => $allCharges,
                'metadata' => [
                    'type' => 'consolidated',
                    'shipment_count' => $shipments->count(),
                    'generated_by' => 'system',
                    'generation_date' => now()->toISOString(),
                    'shipment_ids' => $shipments->pluck('id')->toArray(),
                ],
            ]);

            // Update all shipments with invoice reference
            foreach ($shipments as $shipment) {
                $shipment->update(['invoice_id' => $invoice->id]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Consolidated invoice generated successfully',
                'invoice' => $invoice->load('customer'),
                'shipments_count' => $shipments->count(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Consolidated invoice generation failed', [
                'customer_id' => $customer->id,
                'shipment_count' => $shipments->count(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate consolidated invoice: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate charges for a shipment
     */
    private function calculateShipmentCharges(Shipment $shipment): array
    {
        $rateService = app(RateCardManagementService::class);
        $rateCalculation = $rateService->calculateShippingRate($shipment);

        $lineItems = [
            [
                'description' => "Shipping Service - {$shipment->service_level}",
                'quantity' => 1,
                'unit_price' => $rateCalculation['base_rate'],
                'amount' => $rateCalculation['base_rate'],
                'type' => 'shipping',
            ],
        ];

        // Add surcharges
        if ($rateCalculation['surcharges']['weight'] > 0) {
            $lineItems[] = [
                'description' => 'Weight Surcharge',
                'quantity' => 1,
                'unit_price' => $rateCalculation['surcharges']['weight'],
                'amount' => $rateCalculation['surcharges']['weight'],
                'type' => 'surcharge',
            ];
        }

        if ($rateCalculation['surcharges']['dimension'] > 0) {
            $lineItems[] = [
                'description' => 'Dimension Surcharge',
                'quantity' => 1,
                'unit_price' => $rateCalculation['surcharges']['dimension'],
                'amount' => $rateCalculation['surcharges']['dimension'],
                'type' => 'surcharge',
            ];
        }

        if ($rateCalculation['surcharges']['special_handling'] > 0) {
            $lineItems[] = [
                'description' => 'Special Handling',
                'quantity' => 1,
                'unit_price' => $rateCalculation['surcharges']['special_handling'],
                'amount' => $rateCalculation['surcharges']['special_handling'],
                'type' => 'surcharge',
            ];
        }

        // Add fuel surcharge
        if ($rateCalculation['fuel_surcharge'] > 0) {
            $lineItems[] = [
                'description' => 'Fuel Surcharge',
                'quantity' => 1,
                'unit_price' => $rateCalculation['fuel_surcharge'],
                'amount' => $rateCalculation['fuel_surcharge'],
                'type' => 'fuel_surcharge',
            ];
        }

        return [
            'subtotal' => $rateCalculation['subtotal'],
            'tax_amount' => $rateCalculation['taxes']['total'],
            'discount_amount' => $rateCalculation['breakdown']['customer_discount_amount'],
            'total_amount' => $rateCalculation['grand_total'],
            'line_items' => $lineItems,
            'rate_calculation' => $rateCalculation,
        ];
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = Invoice::whereDate('created_at', today())->count() + 1;

        return "INV-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get customer billing address
     */
    private function getCustomerBillingAddress(Customer $customer): array
    {
        // In a real implementation, this would fetch from customer billing address
        return [
            'name' => $customer->name,
            'address_line_1' => $customer->address ?? '',
            'city' => $customer->city ?? '',
            'state' => $customer->state ?? '',
            'postal_code' => $customer->postal_code ?? '',
            'country' => $customer->country ?? 'USA',
        ];
    }

    /**
     * Send invoice to customer
     */
    public function sendInvoice(Invoice $invoice): array
    {
        try {
            // Generate PDF
            $pdf = $this->generateInvoicePDF($invoice);

            // Send email with PDF attachment
            $customer = $invoice->customer;

            // In a real implementation, you would use Laravel's Mail system
            // Mail::to($customer->email)->send(new InvoiceMail($invoice, $pdf));

            // Update invoice status
            $invoice->update([
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'sent_at' => now()->toISOString(),
                    'sent_by' => auth()->user()->name ?? 'system',
                ]),
            ]);

            return [
                'success' => true,
                'message' => 'Invoice sent successfully',
                'invoice' => $invoice,
            ];

        } catch (\Exception $e) {
            Log::error('Invoice sending failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send invoice: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate invoice PDF
     */
    public function generateInvoicePDF(Invoice $invoice): string
    {
        $data = [
            'invoice' => $invoice->load(['customer', 'shipment']),
            'company' => [
                'name' => 'Baraka Courier Management System',
                'address' => '123 Logistics Street, Business City, BC 12345',
                'phone' => '+1 (555) 123-4567',
                'email' => 'billing@baraka-courier.com',
            ],
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);

        return $pdf->output();
    }

    /**
     * Process payment for invoice
     */
    public function processPayment(Invoice $invoice, array $paymentData): array
    {
        if ($invoice->status === 'paid') {
            return [
                'success' => false,
                'message' => 'Invoice is already paid',
            ];
        }

        $paymentAmount = $paymentData['amount'] ?? $invoice->total_amount;
        $paymentMethod = $paymentData['method'] ?? 'credit_card';

        DB::beginTransaction();
        try {
            // Create payment record
            $payment = $invoice->payments()->create([
                'amount' => $paymentAmount,
                'payment_method' => $paymentMethod,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'payment_date' => now(),
                'status' => 'completed',
                'processed_by' => auth()->user()->id ?? null,
                'metadata' => $paymentData,
            ]);

            // Update invoice
            $totalPaid = $invoice->payments()->sum('amount');
            $remainingAmount = $invoice->total_amount - $totalPaid;

            $newStatus = $remainingAmount <= 0 ? 'paid' : 'partially_paid';

            $invoice->update([
                'status' => $newStatus,
                'paid_at' => $newStatus === 'paid' ? now() : null,
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'last_payment_date' => now()->toISOString(),
                    'total_paid' => $totalPaid,
                    'remaining_balance' => max(0, $remainingAmount),
                ]),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment' => $payment,
                'invoice' => $invoice,
                'remaining_balance' => max(0, $remainingAmount),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing failed', [
                'invoice_id' => $invoice->id,
                'amount' => $paymentAmount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate credit note for invoice
     */
    public function generateCreditNote(Invoice $invoice, array $creditData): array
    {
        $creditAmount = $creditData['amount'];
        $reason = $creditData['reason'];

        if ($creditAmount > $invoice->total_amount) {
            return [
                'success' => false,
                'message' => 'Credit amount cannot exceed invoice total',
            ];
        }

        DB::beginTransaction();
        try {
            // Create credit note
            $creditNote = $invoice->creditNotes()->create([
                'credit_note_number' => $this->generateCreditNoteNumber(),
                'amount' => $creditAmount,
                'reason' => $reason,
                'issued_date' => now(),
                'issued_by' => auth()->user()->id ?? null,
                'status' => 'issued',
                'metadata' => $creditData,
            ]);

            // Update invoice
            $totalCredits = $invoice->creditNotes()->sum('amount');
            $invoice->update([
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'total_credits' => $totalCredits,
                    'last_credit_date' => now()->toISOString(),
                ]),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Credit note generated successfully',
                'credit_note' => $creditNote,
                'invoice' => $invoice,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Credit note generation failed', [
                'invoice_id' => $invoice->id,
                'amount' => $creditAmount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate credit note: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate credit note number
     */
    private function generateCreditNoteNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = DB::table('credit_notes')->whereDate('created_at', today())->count() + 1;

        return "CN-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices(int $daysOverdue = 30): Collection
    {
        return Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<', now()->subDays($daysOverdue))
            ->with(['customer', 'shipment'])
            ->orderBy('due_date')
            ->get()
            ->map(function ($invoice) {
                $daysOverdue = now()->diffInDays($invoice->due_date);
                $overdueAmount = $invoice->total_amount - ($invoice->payments()->sum('amount') ?? 0);

                return [
                    'invoice' => $invoice,
                    'days_overdue' => $daysOverdue,
                    'overdue_amount' => $overdueAmount,
                    'customer_name' => $invoice->customer->name,
                    'severity' => $this->getOverdueSeverity($daysOverdue),
                ];
            });
    }

    /**
     * Get overdue severity
     */
    private function getOverdueSeverity(int $daysOverdue): string
    {
        if ($daysOverdue <= 30) {
            return 'low';
        } elseif ($daysOverdue <= 60) {
            return 'medium';
        } elseif ($daysOverdue <= 90) {
            return 'high';
        } else {
            return 'critical';
        }
    }

    /**
     * Generate monthly billing summary
     */
    public function generateMonthlyBillingSummary(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $invoices = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['customer', 'shipment'])
            ->get();

        $summary = [
            'period' => [
                'year' => $year,
                'month' => $month,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'totals' => [
                'invoice_count' => $invoices->count(),
                'total_billed' => $invoices->sum('total_amount'),
                'total_paid' => $invoices->sum(function ($invoice) {
                    return $invoice->payments()->sum('amount');
                }),
                'total_outstanding' => $invoices->sum(function ($invoice) {
                    $paid = $invoice->payments()->sum('amount');
                    return max(0, $invoice->total_amount - $paid);
                }),
            ],
            'status_breakdown' => $invoices->groupBy('status')->map->count(),
            'top_customers' => $invoices->groupBy('customer_id')
                ->map(function ($customerInvoices) {
                    $customer = $customerInvoices->first()->customer;
                    return [
                        'customer_name' => $customer->name,
                        'invoice_count' => $customerInvoices->count(),
                        'total_amount' => $customerInvoices->sum('total_amount'),
                    ];
                })
                ->sortByDesc('total_amount')
                ->take(10)
                ->values(),
        ];

        $summary['totals']['collection_rate'] = $summary['totals']['total_billed'] > 0
            ? ($summary['totals']['total_paid'] / $summary['totals']['total_billed']) * 100
            : 0;

        return $summary;
    }

    /**
     * Send payment reminders
     */
    public function sendPaymentReminders(): array
    {
        $overdueInvoices = $this->getOverdueInvoices(7); // 7 days overdue

        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($overdueInvoices as $overdue) {
            $results['processed']++;

            try {
                $invoice = $overdue['invoice'];
                $customer = $invoice->customer;

                // In a real implementation, send reminder email
                // Mail::to($customer->email)->send(new PaymentReminderMail($invoice, $overdue));

                $results['sent']++;
                $results['details'][] = [
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $customer->name,
                    'amount' => $overdue['overdue_amount'],
                    'days_overdue' => $overdue['days_overdue'],
                    'status' => 'sent',
                ];

            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'invoice_number' => $overdue['invoice']->invoice_number,
                    'error' => $e->getMessage(),
                    'status' => 'failed',
                ];
            }
        }

        return $results;
    }

    /**
     * Void invoice
     */
    public function voidInvoice(Invoice $invoice, string $reason): array
    {
        if (in_array($invoice->status, ['paid', 'voided'])) {
            return [
                'success' => false,
                'message' => 'Cannot void a paid or already voided invoice',
            ];
        }

        DB::beginTransaction();
        try {
            $invoice->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => auth()->user()->id ?? null,
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'void_reason' => $reason,
                    'voided_at' => now()->toISOString(),
                    'voided_by' => auth()->user()->name ?? 'system',
                ]),
            ]);

            // Update shipment to remove invoice reference
            if ($invoice->shipment) {
                $invoice->shipment->update(['invoice_id' => null]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Invoice voided successfully',
                'invoice' => $invoice,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice voiding failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to void invoice: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Store payment receipt
     */
    public function storePaymentReceipt(Invoice $invoice, array $receiptData): array
    {
        $receipt = $invoice->receipts()->create([
            'receipt_number' => $receiptData['number'],
            'amount' => $receiptData['amount'],
            'receipt_date' => $receiptData['date'],
            'payment_method' => $receiptData['method'],
            'transaction_id' => $receiptData['transaction_id'] ?? null,
            'notes' => $receiptData['notes'] ?? null,
            'stored_by' => auth()->user()->id ?? null,
        ]);

        // Update invoice with receipt details
        $invoice->update([
            'paid_at' => ($invoice->status !== 'paid') ? now() : $invoice->paid_at,
            'status' => ($invoice->status !== 'paid') ? 'partially_paid' : $invoice->status,
        ]);

        return [
            'success' => true,
            'message' => 'Payment receipt stored successfully',
            'receipt' => $receipt,
            'invoice' => $invoice,
        ];
    }

    /**
     * Get invoice history
     */
    public function getInvoiceHistory(Invoice $invoice): ?array
    {
        $history = $this->getPaymentHistory($invoice);

        $modified = $invoice->modified_at;

        return [
            'history' => $history,
            'invoice_modified_at' => $modified ? $modified->toDateTimeString() : null,
        ];
    }

    /**
     * Get usage analytics for invoices
     */
    public function getUsageAnalytics(): array
    {
        $currentYear = now()->year;
        $invoiceCount = Invoice::whereYear('invoice_date', $currentYear)
            ->count();

        $totalAmount = Invoice::whereYear('invoice_date', $currentYear)
            ->sum('total_amount');

        $leftOverAmount = $totalAmount - Invoice::whereYear('invoice_date', $currentYear)
            ->sum(function ($invoice) {
                return $invoice->payments()->sum('amount');
            });

        return [
            'invoice_count' => $invoiceCount,
            'total_amount' => $totalAmount,
            'left_over_amount' => $leftOverAmount,
            'collection_rate' => ($totalAmount > 0 && $leftOverAmount >= 0)
                ? floor(($leftOverAmount / $totalAmount) * 100)
                : null,
        ];
    }

    /**
     * Get payment history for an invoice
     */
    private function getPaymentHistory(Invoice $invoice): array
    {
        $payments = $invoice->payments()
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'payment_date' => $payment->payment_date->toDateString(),
                    'amount' => $payment->amount,
                    'method' => $payment->payment_method,
                ];
            });

        $receipts = $invoice->receipts()
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->map(function ($receipt) {
                return [
                    'receipt_date' => $receipt->receipt_date->toDateString(),
                    'amount' => $receipt->amount,
                    'method' => $receipt->payment_method,
                ];
            });

        return [
            'payments' => $payments,
            'receipts' => $receipts,
        ];
    }
}