<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Backend\Branch;
use App\Models\Shipment;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\Merchant;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Invoice;
use App\Models\CodCollection;
use App\Models\MerchantSettlement;
use App\Models\ScanEvent;
use App\Models\Customer;
use App\Models\Client;
use App\Enums\StatementType;
use App\Support\SystemSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with comprehensive DHL-grade analytics.
     */
    public function index(Request $request): View
    {
        $dateRange = $this->parseDateRange($request);
        
        // Get shipment counts by status
        $statusCounts = Shipment::whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalShipments = array_sum($statusCounts);
        $delivered = $statusCounts['delivered'] ?? 0;
        $inTransit = ($statusCounts['in_transit'] ?? 0) + ($statusCounts['out_for_delivery'] ?? 0);
        
        // Revenue calculation
        $revenue = Shipment::whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->where('status', 'delivered')
            ->sum('price_amount') ?? 0;

        // Previous period for trend calculation
        $previousPeriod = $this->getPreviousPeriod($dateRange);
        $previousRevenue = Shipment::whereBetween('created_at', [$previousPeriod['from'], $previousPeriod['to']])
            ->where('status', 'delivered')
            ->sum('price_amount') ?? 0;
        $revenueTrend = $previousRevenue > 0 ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0;

        $previousShipments = Shipment::whereBetween('created_at', [$previousPeriod['from'], $previousPeriod['to']])->count();
        $shipmentTrend = $previousShipments > 0 ? round((($totalShipments - $previousShipments) / $previousShipments) * 100, 1) : 0;

        // Core Statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 1)->count(),
            'total_branches' => Branch::count(),
            'active_branches' => Branch::where('status', 'active')->count(),
            'active_shipments' => Shipment::whereIn('status', ['created', 'picked_up', 'processing', 'in_transit', 'out_for_delivery'])->count(),
            'total_shipments' => $totalShipments,
            'delivered' => $delivered,
            'delivery_rate' => $totalShipments > 0 ? round(($delivered / $totalShipments) * 100, 1) : 0,
            'in_transit' => $inTransit,
            'revenue' => $revenue,
            'revenue_trend' => $revenueTrend,
            'shipment_trend' => $shipmentTrend,
            'status_breakdown' => $statusCounts,
            'active_drivers' => $this->getActiveDriversCount(),
            'total_merchants' => $this->getMerchantsCount(),
            'total_customers' => $this->getCustomersCount(),
        ];

        // SLA & Performance Metrics
        $slaMetrics = $this->getSlaMetrics($dateRange);
        
        // COD & Finance Overview
        $financeOverview = $this->getFinanceOverviewData($dateRange);
        
        // Fleet & Operations Metrics
        $operationsMetrics = $this->getOperationsMetrics($dateRange);
        
        // Top Performing Data
        $topPerformers = $this->getTopPerformers($dateRange);
        
        // Alerts & Critical Issues
        $alerts = $this->getSystemAlerts();

        // Recent Shipments
        $recentShipments = Shipment::with(['originBranch', 'destBranch'])
            ->latest()
            ->take(10)
            ->get();

        // Recent Activity (from audit logs if available)
        $recentActivity = $this->getRecentActivity();

        // Chart Data
        $chartData = $this->getShipmentChartData($dateRange);
        
        // Revenue Chart Data
        $revenueChartData = $this->getRevenueChartData($dateRange);
        
        // Geographic Data
        $geographicData = $this->getGeographicData($dateRange);

        $defaultCurrency = SystemSettings::defaultCurrency();
        $formatCurrency = fn($amount, ?string $currency = null) => SystemSettings::formatCurrency((float) $amount, $currency);
        $branding = ['company_name' => SystemSettings::companyName()];

        return view('admin.dashboard', compact(
            'stats',
            'slaMetrics',
            'financeOverview',
            'operationsMetrics',
            'topPerformers',
            'alerts',
            'recentShipments',
            'recentActivity',
            'chartData',
            'revenueChartData',
            'geographicData',
            'dateRange',
            'defaultCurrency',
            'formatCurrency',
            'branding'
        ));
    }
    
    /**
     * Get SLA and performance metrics
     */
    private function getSlaMetrics(array $dateRange): array
    {
        $totalDelivered = Shipment::where('status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
            ->count();

        $onTimeDelivered = 0;
        if (Schema::hasColumn('shipments', 'expected_delivery_date')) {
            $onTimeDelivered = Shipment::where('status', 'delivered')
                ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
                ->whereNotNull('expected_delivery_date')
                ->whereColumn('delivered_at', '<=', 'expected_delivery_date')
                ->count();
        }

        // Average delivery time (in hours)
        $avgDeliveryTime = 0;
        if (Schema::hasColumn('shipments', 'delivered_at') && Schema::hasColumn('shipments', 'picked_up_at')) {
            $avgDeliveryTime = Shipment::where('status', 'delivered')
                ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
                ->whereNotNull('picked_up_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_hours')
                ->value('avg_hours') ?? 0;
        }

        $exceptions = Shipment::where('has_exception', true)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->count();

        $returns = Shipment::whereIn('status', ['returned', 'return_requested'])
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->count();
            
        $cancelled = Shipment::where('status', 'cancelled')
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->count();

        return [
            'on_time_rate' => $totalDelivered > 0 ? round(($onTimeDelivered / $totalDelivered) * 100, 1) : 0,
            'on_time_delivered' => $onTimeDelivered,
            'total_delivered' => $totalDelivered,
            'avg_delivery_time' => round($avgDeliveryTime, 1),
            'exceptions' => $exceptions,
            'exception_rate' => $totalDelivered > 0 ? round(($exceptions / $totalDelivered) * 100, 2) : 0,
            'returns' => $returns,
            'cancelled' => $cancelled,
            'first_attempt_rate' => $this->getFirstAttemptDeliveryRate($dateRange),
        ];
    }
    
    /**
     * Get first attempt delivery rate
     */
    private function getFirstAttemptDeliveryRate(array $dateRange): float
    {
        if (!Schema::hasTable('scan_events')) {
            return 0;
        }
        
        $deliveredShipments = Shipment::where('status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
            ->pluck('id');
            
        if ($deliveredShipments->isEmpty()) {
            return 0;
        }
        
        // Count shipments with only one delivery attempt
        $singleAttempt = DB::table('scan_events')
            ->whereIn('shipment_id', $deliveredShipments)
            ->whereIn('scan_type', ['delivery_attempt', 'out_for_delivery'])
            ->groupBy('shipment_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();
            
        return round(($singleAttempt / $deliveredShipments->count()) * 100, 1);
    }
    
    /**
     * Get comprehensive finance overview
     */
    private function getFinanceOverviewData(array $dateRange): array
    {
        $codCollected = 0;
        $codPending = 0;
        $settlementsPending = 0;
        $settlementsCompleted = 0;
        $invoicesPending = 0;
        $invoicesOverdue = 0;
        
        // COD Collections
        if (Schema::hasTable('cod_collections')) {
            $amountColumn = Schema::hasColumn('cod_collections', 'collected_amount') ? 'collected_amount' : 'amount';
            
            $codCollected = DB::table('cod_collections')
                ->where('status', 'collected')
                ->whereBetween('collected_at', [$dateRange['from'], $dateRange['to']])
                ->sum($amountColumn) ?? 0;
                
            $codPending = DB::table('cod_collections')
                ->where('status', 'pending')
                ->sum(Schema::hasColumn('cod_collections', 'expected_amount') ? 'expected_amount' : $amountColumn) ?? 0;
        } else {
            // Fallback to shipments COD (use cod_amount > 0 to identify COD shipments)
            $codCollected = Shipment::where('cod_amount', '>', 0)
                ->where('status', 'delivered')
                ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
                ->sum('cod_amount') ?? 0;
                
            $codPending = Shipment::where('cod_amount', '>', 0)
                ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
                ->sum('cod_amount') ?? 0;
        }
        
        // Settlements
        if (Schema::hasTable('merchant_settlements')) {
            $settlementAmountCol = Schema::hasColumn('merchant_settlements', 'net_payable') ? 'net_payable' : 'total_amount';
            $settlementCompletedCol = Schema::hasColumn('merchant_settlements', 'paid_at') ? 'paid_at' : 'completed_at';
            
            $settlementsPending = DB::table('merchant_settlements')
                ->whereIn('status', ['pending', 'processing', 'approved'])
                ->sum($settlementAmountCol) ?? 0;
                
            $settlementsCompleted = DB::table('merchant_settlements')
                ->whereIn('status', ['paid', 'completed'])
                ->whereBetween($settlementCompletedCol, [$dateRange['from'], $dateRange['to']])
                ->sum($settlementAmountCol) ?? 0;
        }
        
        // Invoices
        if (Schema::hasTable('invoices')) {
            $invoicesPending = DB::table('invoices')
                ->whereIn('status', ['pending', 'sent'])
                ->sum('total_amount') ?? 0;
                
            $invoicesOverdue = DB::table('invoices')
                ->where('status', '!=', 'paid')
                ->where('due_date', '<', now())
                ->sum('total_amount') ?? 0;
        }
        
        // Bank transactions summary
        $bankSummary = $this->getBankTransactionsSummary($dateRange);
        
        return [
            'cod_collected' => round($codCollected, 2),
            'cod_pending' => round($codPending, 2),
            'settlements_pending' => round($settlementsPending, 2),
            'settlements_completed' => round($settlementsCompleted, 2),
            'invoices_pending' => round($invoicesPending, 2),
            'invoices_overdue' => round($invoicesOverdue, 2),
            'total_income' => $bankSummary['income'],
            'total_expense' => $bankSummary['expense'],
            'net_profit' => $bankSummary['income'] - $bankSummary['expense'],
        ];
    }
    
    /**
     * Get bank transactions summary
     */
    private function getBankTransactionsSummary(array $dateRange): array
    {
        if (!Schema::hasTable('bank_transactions')) {
            return ['income' => 0, 'expense' => 0];
        }
        
        $income = DB::table('bank_transactions')
            ->where('type', 'income')
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->sum('amount') ?? 0;
            
        $expense = DB::table('bank_transactions')
            ->where('type', 'expense')
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->sum('amount') ?? 0;
            
        return [
            'income' => round($income, 2),
            'expense' => round($expense, 2),
        ];
    }
    
    /**
     * Get operations and fleet metrics
     */
    private function getOperationsMetrics(array $dateRange): array
    {
        // Active pickups today
        $pendingPickups = Shipment::whereIn('status', ['created', 'pickup_scheduled'])
            ->whereDate('created_at', today())
            ->count();
            
        // Out for delivery today
        $outForDelivery = Shipment::where('status', 'out_for_delivery')
            ->whereDate('updated_at', today())
            ->count();
            
        // Hub throughput
        $hubThroughput = $this->getHubThroughput($dateRange);
        
        // Fleet status
        $fleetStatus = $this->getFleetStatus();
        
        // Driver performance
        $driverMetrics = $this->getDriverPerformanceMetrics($dateRange);
        
        return [
            'pending_pickups' => $pendingPickups,
            'out_for_delivery' => $outForDelivery,
            'hub_throughput' => $hubThroughput,
            'fleet_status' => $fleetStatus,
            'driver_metrics' => $driverMetrics,
            'scans_today' => $this->getScansToday(),
        ];
    }
    
    /**
     * Get hub throughput data
     */
    private function getHubThroughput(array $dateRange): array
    {
        if (!Schema::hasTable('hubs')) {
            return [];
        }
        
        // Check for transfer_hub_id column in shipments
        if (!Schema::hasColumn('shipments', 'transfer_hub_id')) {
            return [];
        }
        
        return DB::table('hubs')
            ->select('hubs.id', 'hubs.name')
            ->selectRaw('COUNT(shipments.id) as processed')
            ->leftJoin('shipments', 'shipments.transfer_hub_id', '=', 'hubs.id')
            ->whereBetween('shipments.created_at', [$dateRange['from'], $dateRange['to']])
            ->groupBy('hubs.id', 'hubs.name')
            ->orderByDesc('processed')
            ->limit(5)
            ->get()
            ->map(fn($hub) => [
                'name' => $hub->name,
                'processed' => $hub->processed,
            ])
            ->toArray();
    }
    
    /**
     * Get fleet status overview
     */
    private function getFleetStatus(): array
    {
        if (!Schema::hasTable('vehicles')) {
            return ['total' => 0, 'active' => 0, 'maintenance' => 0, 'inactive' => 0];
        }
        
        $statusColumn = Schema::hasColumn('vehicles', 'status') ? 'status' : 'is_active';
        
        if ($statusColumn === 'is_active') {
            return [
                'total' => DB::table('vehicles')->count(),
                'active' => DB::table('vehicles')->where('is_active', true)->count(),
                'maintenance' => 0,
                'inactive' => DB::table('vehicles')->where('is_active', false)->count(),
            ];
        }
        
        return [
            'total' => DB::table('vehicles')->count(),
            'active' => DB::table('vehicles')->where('status', 'active')->count(),
            'maintenance' => DB::table('vehicles')->where('status', 'maintenance')->count(),
            'inactive' => DB::table('vehicles')->whereIn('status', ['inactive', 'retired'])->count(),
        ];
    }
    
    /**
     * Get driver performance metrics
     */
    private function getDriverPerformanceMetrics(array $dateRange): array
    {
        $activeDrivers = $this->getActiveDriversCount();
        
        // Deliveries per driver
        $totalDeliveries = Shipment::where('status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
            ->count();
            
        $avgDeliveriesPerDriver = $activeDrivers > 0 ? round($totalDeliveries / $activeDrivers, 1) : 0;
        
        return [
            'active_drivers' => $activeDrivers,
            'avg_deliveries_per_driver' => $avgDeliveriesPerDriver,
            'total_deliveries' => $totalDeliveries,
        ];
    }
    
    /**
     * Get scans processed today
     */
    private function getScansToday(): int
    {
        if (!Schema::hasTable('scan_events')) {
            return 0;
        }
        
        return DB::table('scan_events')
            ->whereDate('created_at', today())
            ->count();
    }
    
    /**
     * Get top performers data
     */
    private function getTopPerformers(array $dateRange): array
    {
        return [
            'top_branches' => $this->getTopBranches($dateRange),
            'top_customers' => $this->getTopCustomers($dateRange),
            'top_routes' => $this->getTopRoutes($dateRange),
        ];
    }
    
    /**
     * Get top performing branches
     */
    private function getTopBranches(array $dateRange): array
    {
        return Branch::select('branches.id', 'branches.name', 'branches.code')
            ->withCount(['originShipments as shipments_count' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', [$dateRange['from'], $dateRange['to']]);
            }])
            ->orderByDesc('shipments_count')
            ->limit(5)
            ->get()
            ->map(fn($branch) => [
                'name' => $branch->name,
                'code' => $branch->code,
                'shipments' => $branch->shipments_count,
            ])
            ->toArray();
    }
    
    /**
     * Get top customers by shipment volume
     */
    private function getTopCustomers(array $dateRange): array
    {
        $table = Schema::hasTable('customers') ? 'customers' : (Schema::hasTable('clients') ? 'clients' : null);
        
        if (!$table) {
            return [];
        }
        
        $nameColumn = Schema::hasColumn($table, 'business_name') ? 'business_name' : 'name';
        $foreignKey = $table === 'customers' ? 'customer_id' : 'client_id';
        
        if (!Schema::hasColumn('shipments', $foreignKey)) {
            return [];
        }
        
        return DB::table($table)
            ->select("$table.id", "$table.$nameColumn as name")
            ->selectRaw('COUNT(shipments.id) as shipments_count')
            ->selectRaw('SUM(shipments.price_amount) as revenue')
            ->join('shipments', "shipments.$foreignKey", '=', "$table.id")
            ->whereBetween('shipments.created_at', [$dateRange['from'], $dateRange['to']])
            ->groupBy("$table.id", "$table.$nameColumn")
            ->orderByDesc('shipments_count')
            ->limit(5)
            ->get()
            ->map(fn($customer) => [
                'name' => $customer->name ?? 'Unknown',
                'shipments' => $customer->shipments_count,
                'revenue' => round($customer->revenue ?? 0, 2),
            ])
            ->toArray();
    }
    
    /**
     * Get top routes by volume
     */
    private function getTopRoutes(array $dateRange): array
    {
        return DB::table('shipments')
            ->select('origin_branch_id', 'dest_branch_id')
            ->selectRaw('COUNT(*) as volume')
            ->join('branches as origin', 'shipments.origin_branch_id', '=', 'origin.id')
            ->join('branches as dest', 'shipments.dest_branch_id', '=', 'dest.id')
            ->selectRaw('origin.name as origin_name, dest.name as dest_name')
            ->whereBetween('shipments.created_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotNull('origin_branch_id')
            ->whereNotNull('dest_branch_id')
            ->groupBy('origin_branch_id', 'dest_branch_id', 'origin.name', 'dest.name')
            ->orderByDesc('volume')
            ->limit(5)
            ->get()
            ->map(fn($route) => [
                'route' => ($route->origin_name ?? 'Unknown') . ' → ' . ($route->dest_name ?? 'Unknown'),
                'volume' => $route->volume,
            ])
            ->toArray();
    }
    
    /**
     * Get system alerts and critical issues
     */
    private function getSystemAlerts(): array
    {
        $alerts = [];
        
        // SLA breach alerts
        $slaBreaches = Shipment::where('has_exception', true)
            ->whereIn('status', ['in_transit', 'out_for_delivery'])
            ->count();
        if ($slaBreaches > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'SLA Breaches',
                'message' => "$slaBreaches shipments have exceptions requiring attention",
                'count' => $slaBreaches,
            ];
        }
        
        // Overdue invoices
        if (Schema::hasTable('invoices')) {
            $overdueCount = DB::table('invoices')
                ->where('status', '!=', 'paid')
                ->where('due_date', '<', now())
                ->count();
            if ($overdueCount > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'title' => 'Overdue Invoices',
                    'message' => "$overdueCount invoices are overdue for payment",
                    'count' => $overdueCount,
                ];
            }
        }
        
        // Stuck shipments (no status change in 48+ hours)
        $stuckShipments = Shipment::whereIn('status', ['in_transit', 'processing'])
            ->where('updated_at', '<', now()->subHours(48))
            ->count();
        if ($stuckShipments > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Stuck Shipments',
                'message' => "$stuckShipments shipments haven't moved in 48+ hours",
                'count' => $stuckShipments,
            ];
        }
        
        // Low COD collection rate (COD shipments where cod_amount > 0)
        $pendingCOD = Shipment::where('cod_amount', '>', 0)
            ->where('status', 'delivered')
            ->when(Schema::hasColumn('shipments', 'cod_collected'), fn($q) => $q->where('cod_collected', false))
            ->count();
        if ($pendingCOD > 10) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Pending COD Collection',
                'message' => "$pendingCOD delivered shipments have uncollected COD",
                'count' => $pendingCOD,
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Get revenue chart data
     */
    private function getRevenueChartData(array $dateRange): array
    {
        $days = min($dateRange['from']->diffInDays($dateRange['to']) + 1, 30);
        $labels = [];
        $revenue = [];
        $cod = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $dateRange['from']->copy()->addDays($i);
            $labels[] = $date->format('M d');

            $dayRevenue = Shipment::whereDate('delivered_at', $date)
                ->where('status', 'delivered')
                ->sum('price_amount') ?? 0;
                
            $dayCod = Shipment::whereDate('delivered_at', $date)
                ->where('status', 'delivered')
                ->where('cod_amount', '>', 0)
                ->sum('cod_amount') ?? 0;

            $revenue[] = round($dayRevenue, 2);
            $cod[] = round($dayCod, 2);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'cod' => $cod,
        ];
    }
    
    /**
     * Get geographic distribution data
     */
    private function getGeographicData(array $dateRange): array
    {
        // Shipments by destination branch (since receiver_city doesn't exist)
        $byCity = DB::table('shipments')
            ->join('branches', 'shipments.dest_branch_id', '=', 'branches.id')
            ->select('branches.name as city')
            ->selectRaw('COUNT(shipments.id) as count')
            ->whereBetween('shipments.created_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotNull('shipments.dest_branch_id')
            ->whereNull('shipments.deleted_at')
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'city' => $item->city,
                'count' => $item->count,
            ])
            ->toArray();
            
        return [
            'by_city' => $byCity,
        ];
    }
    
    /**
     * Get total customers count
     */
    private function getCustomersCount(): int
    {
        if (Schema::hasTable('customers')) {
            return DB::table('customers')->count();
        }
        if (Schema::hasTable('clients')) {
            return DB::table('clients')->count();
        }
        return 0;
    }

    /**
     * Get active drivers count
     */
    private function getActiveDriversCount(): int
    {
        if (Schema::hasTable('delivery_men') && Schema::hasColumn('delivery_men', 'is_active')) {
            return DB::table('delivery_men')->where('is_active', true)->count();
        }
        if (Schema::hasTable('branch_workers')) {
            // branch_workers uses 'status' column (1 = active) not 'is_active'
            return DB::table('branch_workers')
                ->where('status', 1)
                ->whereNull('unassigned_at')
                ->count();
        }
        return 0;
    }

    /**
     * Get merchants count
     */
    private function getMerchantsCount(): int
    {
        if (Schema::hasTable('merchants')) {
            return DB::table('merchants')->count();
        }
        if (Schema::hasTable('customers')) {
            if (Schema::hasColumn('customers', 'is_merchant')) {
                return DB::table('customers')->where('is_merchant', true)->count();
            }
            return DB::table('customers')->count();
        }
        if (Schema::hasTable('clients')) {
            return DB::table('clients')->count();
        }
        return 0;
    }

    /**
     * Get recent activity from audit logs
     */
    private function getRecentActivity(): array
    {
        if (!Schema::hasTable('account_audit_logs')) {
            return [];
        }

        return DB::table('account_audit_logs')
            ->leftJoin('users', 'account_audit_logs.user_id', '=', 'users.id')
            ->select([
                'account_audit_logs.action',
                'account_audit_logs.created_at',
                'users.name as user',
            ])
            ->orderByDesc('account_audit_logs.created_at')
            ->limit(10)
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();
    }

    /**
     * Get shipment chart data for the dashboard
     */
    private function getShipmentChartData(array $dateRange): array
    {
        $days = min($dateRange['from']->diffInDays($dateRange['to']) + 1, 30);
        $labels = [];
        $created = [];
        $delivered = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $dateRange['from']->copy()->addDays($i);
            $labels[] = $date->format('M d');

            $created[] = Shipment::whereDate('created_at', $date)->count();
            $delivered[] = Shipment::whereDate('delivered_at', $date)->count();
        }

        return [
            'labels' => $labels,
            'created' => $created,
            'delivered' => $delivered,
        ];
    }

    /**
     * Parse date range from request or use defaults
     */
    private function parseDateRange(Request $request): array
    {
        $range = $request->get('range', '7d');
        
        switch ($range) {
            case 'today':
                return [
                    'from' => Carbon::today()->startOfDay(),
                    'to' => Carbon::today()->endOfDay(),
                    'label' => 'Today',
                ];
            case '7d':
                return [
                    'from' => Carbon::now()->subDays(6)->startOfDay(),
                    'to' => Carbon::now()->endOfDay(),
                    'label' => 'Last 7 Days',
                ];
            case '30d':
                return [
                    'from' => Carbon::now()->subDays(29)->startOfDay(),
                    'to' => Carbon::now()->endOfDay(),
                    'label' => 'Last 30 Days',
                ];
            case 'this_month':
                return [
                    'from' => Carbon::now()->startOfMonth(),
                    'to' => Carbon::now()->endOfDay(),
                    'label' => Carbon::now()->format('F Y'),
                ];
            case 'custom':
                $from = $request->get('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : Carbon::now()->subDays(6)->startOfDay();
                $to = $request->get('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : Carbon::now()->endOfDay();
                return [
                    'from' => $from,
                    'to' => $to,
                    'label' => $from->format('M d') . ' - ' . $to->format('M d, Y'),
                ];
            default:
                return [
                    'from' => Carbon::now()->subDays(6)->startOfDay(),
                    'to' => Carbon::now()->endOfDay(),
                    'label' => 'Last 7 Days',
                ];
        }
    }

    /**
     * Get financial overview data
     */
    private function getFinancialOverview(array $dateRange): array
    {
        if (!Schema::hasTable('bank_transactions')) {
            return [
                'total_income' => 0,
                'total_expense' => 0,
                'net_income' => 0,
                'trend' => 0,
            ];
        }

        $totalIncome = BankTransaction::where('type', StatementType::INCOME)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->sum('amount') ?? 0;

        $totalExpense = BankTransaction::where('type', StatementType::EXPENSE)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->sum('amount') ?? 0;

        $netIncome = $totalIncome - $totalExpense;

        // Calculate trend (compare with previous period)
        $previousPeriod = $this->getPreviousPeriod($dateRange);
        $previousIncome = BankTransaction::where('type', StatementType::INCOME)
            ->whereBetween('created_at', [$previousPeriod['from'], $previousPeriod['to']])
            ->sum('amount') ?? 0;
        
        $trend = ($previousIncome > 0) ? (($totalIncome - $previousIncome) / $previousIncome) * 100 : 0;

        return [
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'net_income' => round($netIncome, 2),
            'trend' => round($trend, 1),
        ];
    }

    /**
     * Get parcel analytics data
     */
    private function getParcelAnalytics(array $dateRange): array
    {
        $statusCounts = [];
        $statuses = ['created', 'picked_up', 'processing', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled', 'returned'];
        
        foreach ($statuses as $status) {
            $statusCounts[$status] = Shipment::where('status', $status)
                ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
                ->count();
        }

        $totalShipments = array_sum($statusCounts);
        $deliveredRate = $totalShipments > 0 ? round(($statusCounts['delivered'] / $totalShipments) * 100, 1) : 0;

        return [
            'status_counts' => $statusCounts,
            'total_shipments' => $totalShipments,
            'delivered_rate' => $deliveredRate,
        ];
    }

    /**
     * Get entity financial statements
     */
    private function getEntityStatements(array $dateRange): array
    {
        $statements = [];

        // Helper to safely get statement data
        $getStatement = function($table, $dateRange) {
            if (!Schema::hasTable($table)) {
                return ['income' => 0, 'expense' => 0, 'balance' => 0];
            }

            $income = DB::table($table)
                ->where('type', StatementType::INCOME)
                ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
                ->sum('amount') ?? 0;

            $expense = DB::table($table)
                ->where('type', StatementType::EXPENSE)
                ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
                ->sum('amount') ?? 0;

            return [
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'balance' => round($income - $expense, 2),
            ];
        };

        $statements['bank'] = $getStatement('bank_transactions', $dateRange);
        $statements['merchant'] = $getStatement('merchant_statements', $dateRange);
        $statements['deliveryman'] = $getStatement('deliveryman_statements', $dateRange);
        $statements['hub'] = $getStatement('hub_statements', $dateRange);
        $statements['courier'] = $getStatement('courier_statements', $dateRange);
        $statements['vat'] = $getStatement('vat_statements', $dateRange);

        return $statements;
    }

    /**
     * Get performance KPIs
     */
    private function getKPIs(array $dateRange): array
    {
        $totalDelivered = Shipment::where('status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
            ->count();

        $onTimeDelivered = Shipment::where('status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
            ->whereRaw('delivered_at <= expected_delivery_date')
            ->count();

        $onTimeRate = $totalDelivered > 0 ? round(($onTimeDelivered / $totalDelivered) * 100, 1) : 0;

        $exceptions = Shipment::where('has_exception', true)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->count();

        $revenue = Shipment::where('status', 'delivered')
            ->whereBetween('delivered_at', [$dateRange['from'], $dateRange['to']])
            ->sum('price_amount') ?? 0;

        return [
            'on_time_rate' => $onTimeRate,
            'exceptions' => $exceptions,
            'revenue' => round($revenue, 2),
            'total_delivered' => $totalDelivered,
        ];
    }

    /**
     * Get hub analytics
     */
    private function getHubAnalytics(array $dateRange): array
    {
        if (!Schema::hasTable('hubs')) {
            return [];
        }

        // Get all hubs and manually count shipments
        $hubs = Hub::all();
        $hubData = [];

        foreach ($hubs as $hub) {
            $count = Shipment::where('transfer_hub_id', $hub->id)
                ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
                ->count();
            
            if ($count > 0) {
                $hubData[] = [
                    'name' => $hub->name,
                    'shipments_count' => $count,
                ];
            }
        }

        // Sort by count and take top 5
        usort($hubData, function($a, $b) {
            return $b['shipments_count'] - $a['shipments_count'];
        });

        return array_slice($hubData, 0, 5);
    }

    /**
     * Get chart data for visualizations
     */
    private function getChartData(array $dateRange): array
    {
        $days = $dateRange['from']->diffInDays($dateRange['to']) + 1;
        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $shipmentsData = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $dateRange['from']->copy()->addDays($i);
            $labels[] = $date->format('M d');

            if (Schema::hasTable('bank_transactions')) {
                $dayIncome = BankTransaction::where('type', StatementType::INCOME)
                    ->whereDate('created_at', $date)
                    ->sum('amount') ?? 0;
                $dayExpense = BankTransaction::where('type', StatementType::EXPENSE)
                    ->whereDate('created_at', $date)
                    ->sum('amount') ?? 0;
            } else {
                $dayIncome = 0;
                $dayExpense = 0;
            }

            $dayShipments = Shipment::whereDate('created_at', $date)->count();

            $incomeData[] = round($dayIncome, 2);
            $expenseData[] = round($dayExpense, 2);
            $shipmentsData[] = $dayShipments;
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
            'shipments' => $shipmentsData,
        ];
    }

    /**
     * Get previous period for trend calculation
     */
    private function getPreviousPeriod(array $dateRange): array
    {
        $duration = $dateRange['from']->diffInDays($dateRange['to']) + 1;
        
        return [
            'from' => $dateRange['from']->copy()->subDays($duration),
            'to' => $dateRange['from']->copy()->subDay()->endOfDay(),
        ];
    }
}
