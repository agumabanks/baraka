<?php

namespace App\Http\Controllers\Shared;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Services\Shared\InvoiceQueryService;
use Illuminate\Http\RedirectResponse;

/**
 * Base Invoice Controller
 * 
 * Shared functionality for both Admin and Branch invoice controllers.
 * Promotes code reuse and consistent behavior across modules.
 */
abstract class BaseInvoiceController extends Controller
{
    protected InvoiceQueryService $queryService;

    public function __construct(InvoiceQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * Create invoice using shared FormRequest
     */
    protected function performInvoiceCreation(StoreInvoiceRequest $request): Invoice
    {
        $data = $request->prepareForInvoice();
        
        $invoice = Invoice::create($data);

        // Generate invoice number if not set
        if (!$invoice->invoice_number) {
            $invoice->invoice_number = $this->generateInvoiceNumber($invoice);
            $invoice->save();
        }

        return $invoice;
    }

    /**
     * Mark invoice as paid
     */
    protected function markInvoiceAsPaid(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $invoice->markAsPaid();

        return redirect()
            ->back()
            ->with('success', "Invoice {$invoice->invoice_number} marked as paid");
    }

    /**
     * Get invoice statistics for a branch
     */
    protected function getInvoiceStats(int $branchId, ?string $period = 'month'): array
    {
        return $this->queryService->getStats($branchId, $period);
    }

    /**
     * Build invoice query with common filters
     */
    protected function buildInvoiceQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->queryService->baseQuery();

        if (isset($filters['branch_id'])) {
            $query = $this->queryService->forBranch($query, $filters['branch_id']);
        }

        if (isset($filters['status'])) {
            $query = $this->queryService->withStatus($query, $filters['status']);
        }

        if (!empty($filters['payable_only'])) {
            $query = $this->queryService->payable($query);
        }

        if (!empty($filters['overdue_only'])) {
            $query = $this->queryService->overdue($query);
        }

        if (!empty($filters['paid_only'])) {
            $query = $this->queryService->paid($query);
        }

        if (isset($filters['from']) || isset($filters['to'])) {
            $query = $this->queryService->dateRange(
                $query,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
                $filters['date_field'] ?? 'created_at'
            );
        }

        return $query;
    }

    /**
     * Validate invoice belongs to branch
     */
    protected function assertInvoiceBelongsToBranch(Invoice $invoice, int $branchId): void
    {
        if ($invoice->branch_id !== $branchId) {
            abort(403, 'Invoice does not belong to this branch');
        }
    }

    /**
     * Get aging buckets for receivables
     */
    protected function getAgingBuckets(int $branchId): array
    {
        return $this->queryService->getAgingBuckets($branchId);
    }

    /**
     * Generate invoice number
     */
    protected function generateInvoiceNumber(Invoice $invoice): string
    {
        $branch = $invoice->branch;
        $branchCode = $branch ? strtoupper(substr($branch->code ?? 'BR', 0, 3)) : 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        // Get the count of invoices in this branch for this month
        $count = Invoice::where('branch_id', $invoice->branch_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        return sprintf('%s-%s%s-%05d', $branchCode, $year, $month, $count + 1);
    }

    /**
     * Calculate invoice totals with tax
     */
    protected function calculateInvoiceTotals(float $subtotal, ?float $taxRate = null): array
    {
        $taxRate = $taxRate ?? 0.10; // Default 10% tax
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }
}
