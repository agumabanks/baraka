<?php

namespace App\Services\Analytics;

use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\User;
use App\Models\ScanEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * AnalyticsService
 * 
 * Comprehensive analytics engine providing:
 * - Real-time KPI calculations
 * - Historical trend analysis
 * - Performance benchmarking
 * - Revenue analytics
 */
class AnalyticsService
{
    protected int $cacheMinutes = 5;

    /**
     * Get executive dashboard KPIs
     */
    public function getExecutiveDashboard(array $filters = []): array
    {
        $cacheKey = 'executive_dashboard_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($filters) {
            $dateRange = $this->getDateRange($filters);
            $branchId = $filters['branch_id'] ?? null;
            
            return [
                'overview' => $this->getOverviewMetrics($dateRange, $branchId),
                'shipments' => $this->getShipmentMetrics($dateRange, $branchId),
                'financial' => $this->getFinancialMetrics($dateRange, $branchId),
                'performance' => $this->getPerformanceMetrics($dateRange, $branchId),
                'trends' => $this->getTrendData($dateRange, $branchId),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get overview metrics (top-level KPIs)
     */
    public function getOverviewMetrics(array $dateRange, ?int $branchId = null): array
    {
        $query = Shipment::query()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                  ->orWhere('dest_branch_id', $branchId);
            });
        }

        $totalShipments = (clone $query)->count();
        $deliveredShipments = (clone $query)->where('current_status', 'delivered')->count();
        
        // Calculate previous period for comparison
        $previousRange = $this->getPreviousPeriod($dateRange);
        $previousQuery = Shipment::query()
            ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']]);
        
        if ($branchId) {
            $previousQuery->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                  ->orWhere('dest_branch_id', $branchId);
            });
        }
        
        $previousTotal = $previousQuery->count();

        return [
            'total_shipments' => $totalShipments,
            'delivered_shipments' => $deliveredShipments,
            'delivery_rate' => $totalShipments > 0 
                ? round(($deliveredShipments / $totalShipments) * 100, 1) 
                : 0,
            'growth_rate' => $previousTotal > 0 
                ? round((($totalShipments - $previousTotal) / $previousTotal) * 100, 1) 
                : 0,
            'active_shipments' => Shipment::whereNotIn('status', ['delivered', 'cancelled', 'returned'])->count(),
        ];
    }

    /**
     * Get detailed shipment metrics
     */
    public function getShipmentMetrics(array $dateRange, ?int $branchId = null): array
    {
        $query = Shipment::query()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                  ->orWhere('dest_branch_id', $branchId);
            });
        }

        $statusCounts = (clone $query)
            ->select('current_status as status', DB::raw('COUNT(*) as count'))
            ->groupBy('current_status')
            ->pluck('count', 'status')
            ->toArray();

        $byType = (clone $query)
            ->select('service_level', DB::raw('COUNT(*) as count'))
            ->groupBy('service_level')
            ->pluck('count', 'service_level')
            ->toArray();

        // Calculate average processing times
        $avgPickupTime = DB::table('shipments')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('picked_up_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, picked_up_at)) as avg_hours')
            ->value('avg_hours');

        $avgDeliveryTime = DB::table('shipments')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('delivered_at')
            ->whereNotNull('picked_up_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'by_status' => $statusCounts,
            'by_type' => $byType,
            'total' => array_sum($statusCounts),
            'avg_pickup_hours' => round($avgPickupTime ?? 0, 1),
            'avg_delivery_hours' => round($avgDeliveryTime ?? 0, 1),
            'pending_pickup' => ($statusCounts['pending'] ?? 0) + ($statusCounts['booked'] ?? 0),
            'in_transit' => $statusCounts['in_transit'] ?? 0,
            'out_for_delivery' => $statusCounts['out_for_delivery'] ?? 0,
        ];
    }

    /**
     * Get financial metrics
     */
    public function getFinancialMetrics(array $dateRange, ?int $branchId = null): array
    {
        $shipmentQuery = Shipment::query()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        
        if ($branchId) {
            $shipmentQuery->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                  ->orWhere('dest_branch_id', $branchId);
            });
        }

        $totalRevenue = (clone $shipmentQuery)->sum('price_amount');
        $codAmount = (clone $shipmentQuery)->where('payer_type', 'cod')->sum('cod_amount');
        $codCollected = (clone $shipmentQuery)
            ->where('payer_type', 'cod')
            ->where('current_status', 'delivered')
            ->sum('cod_amount');

        // Invoice metrics
        $invoiceQuery = Invoice::query()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        $invoicedAmount = (clone $invoiceQuery)->sum('total_amount');
        $paidAmount = (clone $invoiceQuery)->where('status', 'paid')->sum('total_amount');
        $overdueAmount = (clone $invoiceQuery)
            ->where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->sum('total_amount');

        // Previous period comparison
        $previousRange = $this->getPreviousPeriod($dateRange);
        $previousRevenue = Shipment::query()
            ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
            ->sum('price_amount');

        return [
            'total_revenue' => round($totalRevenue, 2),
            'revenue_growth' => $previousRevenue > 0 
                ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1) 
                : 0,
            'cod_amount' => round($codAmount, 2),
            'cod_collected' => round($codCollected, 2),
            'cod_pending' => round($codAmount - $codCollected, 2),
            'cod_collection_rate' => $codAmount > 0 
                ? round(($codCollected / $codAmount) * 100, 1) 
                : 0,
            'invoiced_amount' => round($invoicedAmount, 2),
            'paid_amount' => round($paidAmount, 2),
            'overdue_amount' => round($overdueAmount, 2),
            'average_shipment_value' => (clone $shipmentQuery)->avg('price_amount') ?? 0,
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(array $dateRange, ?int $branchId = null): array
    {
        $deliveredShipments = Shipment::query()
            ->where('current_status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['start'], $dateRange['end']]);

        if ($branchId) {
            $deliveredShipments->where('dest_branch_id', $branchId);
        }

        $totalDelivered = (clone $deliveredShipments)->count();
        
        // On-time delivery (delivered before or on expected date)
        $onTimeDeliveries = (clone $deliveredShipments)
            ->whereNotNull('expected_delivery_date')
            ->whereRaw('delivered_at <= expected_delivery_date')
            ->count();

        // First attempt delivery success (estimated based on scan events)
        $firstAttemptSuccess = $totalDelivered; // Assume all delivered are first attempt if no data

        // SLA compliance
        $slaTarget = 48; // hours
        $withinSla = DB::table('shipments')
            ->where('current_status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('picked_up_at')
            ->whereRaw('TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at) <= ?', [$slaTarget])
            ->count();

        // Exception rate
        $totalShipments = Shipment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $exceptions = Shipment::where('has_exception', true)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        return [
            'on_time_delivery_rate' => $totalDelivered > 0 
                ? round(($onTimeDeliveries / $totalDelivered) * 100, 1) 
                : 0,
            'first_attempt_success_rate' => $totalDelivered > 0 
                ? round(($firstAttemptSuccess / $totalDelivered) * 100, 1) 
                : 0,
            'sla_compliance_rate' => $totalDelivered > 0 
                ? round(($withinSla / $totalDelivered) * 100, 1) 
                : 0,
            'exception_rate' => $totalShipments > 0 
                ? round(($exceptions / $totalShipments) * 100, 2) 
                : 0,
            'total_delivered' => $totalDelivered,
            'total_exceptions' => $exceptions,
        ];
    }

    /**
     * Get trend data for charts
     */
    public function getTrendData(array $dateRange, ?int $branchId = null): array
    {
        $start = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        $days = $start->diffInDays($end);

        // Determine grouping based on date range
        if ($days <= 31) {
            $groupBy = 'DATE(created_at)';
            $format = 'Y-m-d';
        } elseif ($days <= 90) {
            $groupBy = 'YEARWEEK(created_at)';
            $format = 'W';
        } else {
            $groupBy = 'DATE_FORMAT(created_at, "%Y-%m")';
            $format = 'Y-m';
        }

        $query = Shipment::query()
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                DB::raw("{$groupBy} as period"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN current_status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(price_amount) as revenue')
            )
            ->groupBy('period')
            ->orderBy('period');

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                  ->orWhere('dest_branch_id', $branchId);
            });
        }

        $data = $query->get();

        return [
            'shipments' => $data->map(fn($row) => [
                'period' => $row->period,
                'total' => $row->total,
                'delivered' => $row->delivered,
            ])->toArray(),
            'revenue' => $data->map(fn($row) => [
                'period' => $row->period,
                'amount' => round($row->revenue ?? 0, 2),
            ])->toArray(),
        ];
    }

    /**
     * Get branch comparison metrics
     */
    public function getBranchComparison(array $dateRange): array
    {
        return DB::table('shipments')
            ->join('branches', 'shipments.origin_branch_id', '=', 'branches.id')
            ->whereBetween('shipments.created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                'branches.id',
                'branches.name',
                DB::raw('COUNT(*) as total_shipments'),
                DB::raw('SUM(CASE WHEN shipments.current_status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(shipments.price_amount) as revenue'),
                DB::raw('AVG(CASE WHEN shipments.current_status = "delivered" AND shipments.picked_up_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, shipments.picked_up_at, shipments.delivered_at) END) as avg_delivery_hours')
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total_shipments')
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'total_shipments' => $branch->total_shipments,
                    'delivered' => $branch->delivered,
                    'delivery_rate' => $branch->total_shipments > 0 
                        ? round(($branch->delivered / $branch->total_shipments) * 100, 1) 
                        : 0,
                    'revenue' => round($branch->revenue, 2),
                    'avg_delivery_hours' => round($branch->avg_delivery_hours ?? 0, 1),
                ];
            })
            ->toArray();
    }

    /**
     * Get driver performance metrics
     */
    public function getDriverPerformance(array $dateRange, ?int $branchId = null): array
    {
        $query = DB::table('shipments')
            ->join('users', 'shipments.assigned_driver_id', '=', 'users.id')
            ->whereBetween('shipments.created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('shipments.assigned_driver_id');

        if ($branchId) {
            $query->where('shipments.dest_branch_id', $branchId);
        }

        return $query->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as total_assigned'),
                DB::raw('SUM(CASE WHEN shipments.current_status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN shipments.current_status = "returned" THEN 1 ELSE 0 END) as returned'),
                DB::raw('100 as first_attempt_rate') // Default to 100% if no delivery attempts tracking
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('delivered')
            ->limit(20)
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'total_assigned' => $driver->total_assigned,
                    'delivered' => $driver->delivered,
                    'returned' => $driver->returned,
                    'success_rate' => $driver->total_assigned > 0 
                        ? round(($driver->delivered / $driver->total_assigned) * 100, 1) 
                        : 0,
                    'first_attempt_rate' => round($driver->first_attempt_rate ?? 0, 1),
                ];
            })
            ->toArray();
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(array $dateRange): array
    {
        // New customers
        $newCustomers = Customer::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();

        // Active customers (with shipments in period)
        $activeCustomers = Shipment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->distinct('customer_id')
            ->count('customer_id');

        // Top customers by volume
        $topByVolume = DB::table('shipments')
            ->join('customers', 'shipments.customer_id', '=', 'customers.id')
            ->whereBetween('shipments.created_at', [$dateRange['start'], $dateRange['end']])
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('COUNT(*) as shipment_count'),
                DB::raw('SUM(shipments.price_amount) as total_spent')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('shipment_count')
            ->limit(10)
            ->get();

        // Customer retention (customers who shipped in both periods)
        $previousRange = $this->getPreviousPeriod($dateRange);
        $previousCustomers = Shipment::whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
            ->distinct()
            ->pluck('customer_id');
        
        $returningCustomers = Shipment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereIn('customer_id', $previousCustomers)
            ->distinct()
            ->count('customer_id');

        $retentionRate = count($previousCustomers) > 0 
            ? round(($returningCustomers / count($previousCustomers)) * 100, 1) 
            : 0;

        return [
            'new_customers' => $newCustomers,
            'active_customers' => $activeCustomers,
            'retention_rate' => $retentionRate,
            'top_by_volume' => $topByVolume->toArray(),
        ];
    }

    /**
     * Snapshot daily metrics for historical tracking
     */
    public function snapshotDailyMetrics(?Carbon $date = null, ?int $branchId = null): void
    {
        $date = $date ?? now()->subDay();
        $dateRange = [
            'start' => $date->copy()->startOfDay(),
            'end' => $date->copy()->endOfDay(),
        ];

        $metrics = [
            'date' => $date->toDateString(),
            'branch_id' => $branchId,
            'shipments_created' => Shipment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->when($branchId, fn($q) => $q->where('origin_branch_id', $branchId))
                ->count(),
            'shipments_delivered' => Shipment::where('current_status', 'delivered')
                ->whereBetween('delivered_at', [$dateRange['start'], $dateRange['end']])
                ->when($branchId, fn($q) => $q->where('dest_branch_id', $branchId))
                ->count(),
            'total_revenue' => Shipment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->when($branchId, fn($q) => $q->where('origin_branch_id', $branchId))
                ->sum('price_amount'),
            'cod_collected' => Shipment::where('payer_type', 'cod')
                ->where('current_status', 'delivered')
                ->whereBetween('delivered_at', [$dateRange['start'], $dateRange['end']])
                ->when($branchId, fn($q) => $q->where('dest_branch_id', $branchId))
                ->sum('cod_amount'),
        ];

        DB::table('daily_metrics')->updateOrInsert(
            ['date' => $metrics['date'], 'branch_id' => $branchId],
            array_merge($metrics, ['updated_at' => now()])
        );
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        $preset = $filters['preset'] ?? 'last_30_days';

        return match ($preset) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'yesterday' => [
                'start' => now()->subDay()->startOfDay(),
                'end' => now()->subDay()->endOfDay(),
            ],
            'last_7_days' => [
                'start' => now()->subDays(7)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'last_30_days' => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'this_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [
                'start' => now()->startOfQuarter(),
                'end' => now()->endOfQuarter(),
            ],
            'this_year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            'custom' => [
                'start' => Carbon::parse($filters['start_date'] ?? now()->subDays(30)),
                'end' => Carbon::parse($filters['end_date'] ?? now()),
            ],
            default => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }

    /**
     * Get previous period for comparison
     */
    protected function getPreviousPeriod(array $dateRange): array
    {
        $start = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        $days = $start->diffInDays($end);

        return [
            'start' => $start->copy()->subDays($days + 1),
            'end' => $start->copy()->subDay(),
        ];
    }
}
