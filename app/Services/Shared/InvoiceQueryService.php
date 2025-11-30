<?php

namespace App\Services\Shared;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Shared Invoice Query Service
 * 
 * Provides consistent query building for invoices across Admin and Branch modules.
 * Ensures canonical status field usage and consistent business logic.
 */
class InvoiceQueryService
{
    /**
     * Get base invoice query with standard eager loading
     */
    public function baseQuery(): Builder
    {
        return Invoice::query()
            ->with([
                'customer:id,name,email',
                'shipment:id,tracking_number,current_status',
            ]);
    }

    /**
     * Filter invoices by branch
     */
    public function forBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Filter invoices by status
     */
    public function withStatus(Builder $query, InvoiceStatus|string|null $status): Builder
    {
        if (!$status) {
            return $query;
        }

        if ($status instanceof InvoiceStatus) {
            return $query->where('status', $status->value);
        }

        $statusEnum = InvoiceStatus::fromString($status);
        if ($statusEnum) {
            return $query->where('status', $statusEnum->value);
        }

        // Fallback: use as-is if not recognized
        return $query->where('status', $status);
    }

    /**
     * Filter payable invoices (pending, sent, overdue)
     */
    public function payable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InvoiceStatus::PENDING->value,
            InvoiceStatus::SENT->value,
            InvoiceStatus::OVERDUE->value,
        ]);
    }

    /**
     * Filter overdue invoices
     */
    public function overdue(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', InvoiceStatus::OVERDUE->value)
              ->orWhere(function ($dueSoon) {
                  $dueSoon->where('status', InvoiceStatus::PENDING->value)
                      ->whereNotNull('due_date')
                      ->where('due_date', '<', now());
              });
        });
    }

    /**
     * Filter paid invoices
     */
    public function paid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::PAID->value);
    }

    /**
     * Filter by date range
     */
    public function dateRange(Builder $query, ?string $from, ?string $to, string $field = 'created_at'): Builder
    {
        if ($from) {
            $query->whereDate($field, '>=', $from);
        }

        if ($to) {
            $query->whereDate($field, '<=', $to);
        }

        return $query;
    }

    /**
     * Get aging buckets for receivables
     */
    public function getAgingBuckets(int $branchId): array
    {
        $base = Invoice::where('branch_id', $branchId)->payable();

        return [
            'current' => (clone $base)
                ->where(function ($q) {
                    $q->whereNull('due_date')
                      ->orWhere('due_date', '>=', now());
                })
                ->sum('total_amount'),

            '1_30_days' => (clone $base)
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->subDays(30), now()])
                ->sum('total_amount'),

            '31_60_days' => (clone $base)
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->subDays(60), now()->subDays(31)])
                ->sum('total_amount'),

            '61_90_days' => (clone $base)
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->subDays(90), now()->subDays(61)])
                ->sum('total_amount'),

            'over_90_days' => (clone $base)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->subDays(90))
                ->sum('total_amount'),
        ];
    }

    /**
     * Get finance statistics for a branch
     */
    public function getStats(int $branchId, ?string $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $base = Invoice::where('branch_id', $branchId);

        return [
            'total_receivables' => (clone $base)
                ->payable()
                ->sum('total_amount'),

            'total_overdue' => (clone $base)
                ->where('status', InvoiceStatus::OVERDUE->value)
                ->sum('total_amount'),

            'collections_period' => (clone $base)
                ->where('status', InvoiceStatus::PAID->value)
                ->where('paid_at', '>=', $startDate)
                ->sum('total_amount'),

            'pending_count' => (clone $base)
                ->where('status', InvoiceStatus::PENDING->value)
                ->count(),

            'overdue_count' => (clone $base)
                ->where('status', InvoiceStatus::OVERDUE->value)
                ->count(),

            'paid_count' => (clone $base)
                ->where('status', InvoiceStatus::PAID->value)
                ->where('paid_at', '>=', $startDate)
                ->count(),

            'average_invoice_value' => (clone $base)
                ->where('created_at', '>=', $startDate)
                ->avg('total_amount') ?? 0,

            'collection_rate' => $this->calculateCollectionRate($branchId, $startDate),
        ];
    }

    /**
     * Calculate collection rate percentage
     */
    protected function calculateCollectionRate(int $branchId, $startDate): float
    {
        $base = Invoice::where('branch_id', $branchId)
            ->where('created_at', '>=', $startDate);

        $totalBilled = (clone $base)->sum('total_amount');
        $totalCollected = (clone $base)
            ->where('status', InvoiceStatus::PAID->value)
            ->sum('total_amount');

        return $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 2) : 0;
    }

    /**
     * Get top debtors
     */
    public function getTopDebtors(int $branchId, int $limit = 10): array
    {
        return DB::table('invoices')
            ->join('customers', 'invoices.merchant_id', '=', 'customers.id')
            ->where('invoices.branch_id', $branchId)
            ->whereIn('invoices.status', [
                InvoiceStatus::PENDING->value,
                InvoiceStatus::SENT->value,
                InvoiceStatus::OVERDUE->value,
            ])
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('SUM(invoices.total_amount) as total_outstanding'),
                DB::raw('COUNT(invoices.id) as invoice_count')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_outstanding')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get top paying customers
     */
    public function getTopPayingCustomers(int $branchId, int $limit = 10): array
    {
        return DB::table('invoices')
            ->join('customers', 'invoices.merchant_id', '=', 'customers.id')
            ->where('invoices.branch_id', $branchId)
            ->where('invoices.status', InvoiceStatus::PAID->value)
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('SUM(invoices.total_amount) as total_paid'),
                DB::raw('COUNT(invoices.id) as invoice_count')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_paid')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Mark overdue invoices
     * 
     * Should be called periodically (e.g., via scheduled job)
     */
    public function markOverdueInvoices(): int
    {
        return Invoice::where('status', InvoiceStatus::PENDING->value)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->update(['status' => InvoiceStatus::OVERDUE->value]);
    }
}
