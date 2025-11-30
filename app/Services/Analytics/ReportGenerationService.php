<?php

namespace App\Services\Analytics;

use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ReportGenerationService
 * 
 * Comprehensive report generation:
 * - Multiple report types (shipment, financial, performance)
 * - Export to PDF, Excel, CSV
 * - Scheduled report execution
 */
class ReportGenerationService
{
    /**
     * Generate shipment report
     */
    public function generateShipmentReport(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $query = Shipment::query()
            ->with(['customer', 'originBranch', 'destBranch', 'assignedDriver'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('origin_branch_id', $filters['branch_id'])
                  ->orWhere('dest_branch_id', $filters['branch_id']);
            });
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        $shipments = $query->orderBy('created_at', 'desc')->get();

        // Calculate summary
        $summary = [
            'total_shipments' => $shipments->count(),
            'total_revenue' => $shipments->sum('shipping_cost'),
            'total_cod' => $shipments->where('payment_type', 'cod')->sum('cod_amount'),
            'by_status' => $shipments->groupBy('status')->map->count()->toArray(),
            'by_payment_type' => $shipments->groupBy('payment_type')->map->count()->toArray(),
        ];

        return [
            'report_type' => 'shipment',
            'date_range' => $dateRange,
            'filters' => $filters,
            'summary' => $summary,
            'data' => $shipments->map(function ($s) {
                return [
                    'id' => $s->id,
                    'tracking_number' => $s->tracking_number,
                    'created_at' => $s->created_at->format('Y-m-d H:i'),
                    'status' => $s->status,
                    'customer' => $s->customer?->name,
                    'origin' => $s->originBranch?->name,
                    'destination' => $s->destBranch?->name,
                    'payment_type' => $s->payment_type,
                    'shipping_cost' => $s->shipping_cost,
                    'cod_amount' => $s->cod_amount,
                    'driver' => $s->assignedDriver?->name,
                    'delivered_at' => $s->delivered_at?->format('Y-m-d H:i'),
                ];
            })->toArray(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate financial report
     */
    public function generateFinancialReport(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);

        // Revenue by day
        $dailyRevenue = Shipment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(shipping_cost) as revenue'),
                DB::raw('SUM(CASE WHEN payment_type = "cod" THEN cod_amount ELSE 0 END) as cod_total'),
                DB::raw('COUNT(*) as shipment_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Invoice summary
        $invoiceSummary = Invoice::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as amount')
            )
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // COD collection status
        $codStatus = Shipment::where('payment_type', 'cod')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                DB::raw('SUM(cod_amount) as total_cod'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN cod_amount ELSE 0 END) as collected'),
                DB::raw('SUM(CASE WHEN status != "delivered" THEN cod_amount ELSE 0 END) as pending')
            )
            ->first();

        // Revenue by branch
        $branchRevenue = Shipment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->join('branches', 'shipments.origin_branch_id', '=', 'branches.id')
            ->select(
                'branches.name',
                DB::raw('SUM(shipping_cost) as revenue'),
                DB::raw('COUNT(*) as shipment_count')
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('revenue')
            ->get();

        return [
            'report_type' => 'financial',
            'date_range' => $dateRange,
            'summary' => [
                'total_revenue' => $dailyRevenue->sum('revenue'),
                'total_cod' => $codStatus->total_cod ?? 0,
                'cod_collected' => $codStatus->collected ?? 0,
                'cod_pending' => $codStatus->pending ?? 0,
                'total_invoiced' => $invoiceSummary->sum('amount'),
                'invoices_paid' => $invoiceSummary->get('paid')?->amount ?? 0,
                'invoices_pending' => $invoiceSummary->get('unpaid')?->amount ?? 0,
            ],
            'daily_revenue' => $dailyRevenue->toArray(),
            'invoice_summary' => $invoiceSummary->toArray(),
            'branch_revenue' => $branchRevenue->toArray(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);

        // Delivery performance
        $deliveryStats = DB::table('shipments')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('SUM(CASE WHEN status = "returned" THEN 1 ELSE 0 END) as returned'),
                DB::raw('AVG(CASE WHEN status = "delivered" AND picked_up_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at) END) as avg_delivery_hours')
            )
            ->first();

        // On-time delivery
        $onTimeStats = DB::table('shipments')
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('expected_delivery_date')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN delivered_at <= expected_delivery_date THEN 1 ELSE 0 END) as on_time')
            )
            ->first();

        // Driver performance
        $driverPerformance = DB::table('shipments')
            ->join('users', 'shipments.assigned_driver_id', '=', 'users.id')
            ->whereBetween('shipments.created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('shipments.assigned_driver_id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as assigned'),
                DB::raw('SUM(CASE WHEN shipments.status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('AVG(CASE WHEN shipments.status = "delivered" THEN 
                    TIMESTAMPDIFF(HOUR, shipments.picked_up_at, shipments.delivered_at) END) as avg_hours')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('delivered')
            ->limit(20)
            ->get();

        // Branch performance
        $branchPerformance = DB::table('shipments')
            ->join('branches', 'shipments.dest_branch_id', '=', 'branches.id')
            ->whereBetween('shipments.created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                'branches.id',
                'branches.name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN shipments.status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('AVG(CASE WHEN shipments.status = "delivered" THEN 
                    TIMESTAMPDIFF(HOUR, shipments.picked_up_at, shipments.delivered_at) END) as avg_hours')
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total')
            ->get();

        return [
            'report_type' => 'performance',
            'date_range' => $dateRange,
            'summary' => [
                'total_shipments' => $deliveryStats->total ?? 0,
                'delivered' => $deliveryStats->delivered ?? 0,
                'delivery_rate' => ($deliveryStats->total ?? 0) > 0 
                    ? round(($deliveryStats->delivered / $deliveryStats->total) * 100, 1) 
                    : 0,
                'on_time_rate' => ($onTimeStats->total ?? 0) > 0 
                    ? round(($onTimeStats->on_time / $onTimeStats->total) * 100, 1) 
                    : 0,
                'avg_delivery_hours' => round($deliveryStats->avg_delivery_hours ?? 0, 1),
                'cancelled' => $deliveryStats->cancelled ?? 0,
                'returned' => $deliveryStats->returned ?? 0,
            ],
            'driver_performance' => $driverPerformance->toArray(),
            'branch_performance' => $branchPerformance->toArray(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Export report to Excel
     */
    public function exportToExcel(array $reportData, string $filename): string
    {
        $path = "reports/{$filename}.xlsx";
        
        // Create export class dynamically based on report type
        $exportData = collect($reportData['data'] ?? []);
        
        // Use Laravel Excel if available
        if (class_exists('Maatwebsite\Excel\Facades\Excel')) {
            $export = new class($exportData) implements \Maatwebsite\Excel\Concerns\FromCollection {
                protected $data;
                
                public function __construct($data)
                {
                    $this->data = $data;
                }
                
                public function collection()
                {
                    return $this->data;
                }
            };
            
            Excel::store($export, $path, 'local');
        } else {
            // Fallback to CSV
            $path = str_replace('.xlsx', '.csv', $path);
            $this->exportToCsv($reportData, str_replace('.csv', '', $filename));
        }

        return Storage::path($path);
    }

    /**
     * Export report to CSV
     */
    public function exportToCsv(array $reportData, string $filename): string
    {
        $path = "reports/{$filename}.csv";
        $data = $reportData['data'] ?? [];

        if (empty($data)) {
            Storage::put($path, '');
            return Storage::path($path);
        }

        // Get headers from first row
        $headers = array_keys($data[0]);
        
        // Build CSV content
        $csv = implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $values = array_map(function ($value) {
                if (is_string($value) && (str_contains($value, ',') || str_contains($value, '"'))) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }
                return $value ?? '';
            }, array_values($row));
            
            $csv .= implode(',', $values) . "\n";
        }

        Storage::put($path, $csv);

        return Storage::path($path);
    }

    /**
     * Generate report execution record
     */
    public function recordExecution(
        string $reportType,
        array $parameters,
        ?int $userId = null,
        ?int $savedReportId = null
    ): int {
        return DB::table('report_executions')->insertGetId([
            'saved_report_id' => $savedReportId,
            'report_type' => $reportType,
            'parameters' => json_encode($parameters),
            'executed_by' => $userId,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update execution status
     */
    public function updateExecutionStatus(
        int $executionId,
        string $status,
        ?string $filePath = null,
        ?string $format = null,
        ?int $rowCount = null,
        ?int $executionTimeMs = null,
        ?string $error = null
    ): void {
        DB::table('report_executions')
            ->where('id', $executionId)
            ->update([
                'status' => $status,
                'file_path' => $filePath,
                'file_format' => $format,
                'row_count' => $rowCount,
                'execution_time_ms' => $executionTimeMs,
                'error_message' => $error,
                'updated_at' => now(),
            ]);
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            return [
                'start' => Carbon::parse($filters['start_date'])->startOfDay(),
                'end' => Carbon::parse($filters['end_date'])->endOfDay(),
            ];
        }

        $preset = $filters['preset'] ?? 'last_30_days';

        return match ($preset) {
            'today' => ['start' => now()->startOfDay(), 'end' => now()->endOfDay()],
            'yesterday' => ['start' => now()->subDay()->startOfDay(), 'end' => now()->subDay()->endOfDay()],
            'last_7_days' => ['start' => now()->subDays(7)->startOfDay(), 'end' => now()->endOfDay()],
            'last_30_days' => ['start' => now()->subDays(30)->startOfDay(), 'end' => now()->endOfDay()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'last_month' => ['start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()],
            default => ['start' => now()->subDays(30)->startOfDay(), 'end' => now()->endOfDay()],
        };
    }
}
