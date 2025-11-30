<?php

namespace App\Http\Controllers;

use App\Enums\BranchStatus;
use App\Enums\ShipmentStatus;
use App\Enums\Status;
use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Models\Backend\Branch;
use App\Support\BranchCache;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class BranchDashboardController extends Controller
{
    use ResolvesBranch;

    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user || (! $user->hasPermission('branch_read') && ! $user->hasPermission('branch_manage'))) {
            abort(403);
        }

        $branch = $this->resolveBranch($request);

        $branch->load([
            'branchManager.user:id,name,email,mobile,phone_e164',
            'activeWorkers.user:id,name,email,mobile,phone_e164',
            'parent:id,name,code',
        ]);

        $inboundStatuses = array_map(fn (ShipmentStatus $status) => $status->value, [
            ShipmentStatus::LINEHAUL_ARRIVED,
            ShipmentStatus::AT_DESTINATION_HUB,
            ShipmentStatus::CUSTOMS_HOLD,
            ShipmentStatus::CUSTOMS_CLEARED,
            ShipmentStatus::OUT_FOR_DELIVERY,
            ShipmentStatus::RETURN_IN_TRANSIT,
        ]);

        $outboundStatuses = array_map(fn (ShipmentStatus $status) => $status->value, [
            ShipmentStatus::BOOKED,
            ShipmentStatus::PICKUP_SCHEDULED,
            ShipmentStatus::PICKED_UP,
            ShipmentStatus::AT_ORIGIN_HUB,
            ShipmentStatus::BAGGED,
            ShipmentStatus::LINEHAUL_DEPARTED,
        ]);

        $now = now();
        $since24h = $now->copy()->subDay();

        $stats = BranchCache::rememberStats($branch->id, function () use ($branch, $inboundStatuses, $outboundStatuses, $since24h) {
            return [
                'active_workers' => $branch->activeWorkers()->count(),
                'active_clients' => $branch->primaryClients()->where('status', Status::ACTIVE)->count(),
                'inbound_queue' => $branch->destinationShipments()->whereIn('current_status', $inboundStatuses)->count(),
                'outbound_queue' => $branch->originShipments()->whereIn('current_status', $outboundStatuses)->count(),
                'exceptions' => $branch->originShipments()->where('has_exception', true)->count(),
                'throughput_24h' => $branch->originShipments()->where('created_at', '>=', $since24h)->count(),
                'capacity_utilization' => $branch->getCapacityMetrics()['utilization_rate'] ?? 0,
            ];
        });

        $recentShipments = $branch->originShipments()
            ->with(['destBranch:id,name,code', 'assignedWorker.user:id,name'])
            ->latest('created_at')
            ->take(6)
            ->get();

        $inboundList = $branch->destinationShipments()
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'tracking_number', 'current_status', 'origin_branch_id', 'created_at']);

        $outboundList = $branch->originShipments()
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'tracking_number', 'current_status', 'dest_branch_id', 'created_at']);

        $exceptionsList = $branch->originShipments()
            ->where('has_exception', true)
            ->latest('updated_at')
            ->take(5)
            ->get(['id', 'tracking_number', 'current_status', 'dest_branch_id', 'updated_at']);

        $workforce = $branch->activeWorkers()
            ->with('user:id,name,email,mobile,phone_e164')
            ->latest('assigned_at')
            ->take(6)
            ->get();

        $clients = $branch->primaryClients()
            ->latest('created_at')
            ->take(6)
            ->get(['id', 'business_name', 'status', 'created_at']);

        // SLA / on-time metrics where data exists.
        $deliveredTotal = $branch->originShipments()
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->count();

        $onTimeDelivered = $branch->originShipments()
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->when(Schema::hasColumn('shipments', 'expected_delivery_date'), function ($q) {
                $q->whereNotNull('expected_delivery_date')
                    ->whereColumn('delivered_at', '<=', 'expected_delivery_date');
            })
            ->count();

        $slaBreach = max(0, $deliveredTotal - $onTimeDelivered);

        $financeToday = $branch->originShipments()
            ->whereDate('created_at', now()->toDateString())
            ->sum('price_amount');

        // Optional lighter lists for warehouse / fleet if tables exist.
        $warehouseAlerts = [];
        if (Schema::hasTable('warehouses')) {
            $warehouseAlerts = \DB::table('warehouses')
                ->where('branch_id', $branch->id)
                ->select('id', 'name', 'status', 'capacity')
                ->orderBy('name')
                ->limit(4)
                ->get();
        }

        $fleetItems = [];
        if (Schema::hasTable('vehicles')) {
            $selects = ['id', 'status'];
            if (Schema::hasColumn('vehicles', 'registration')) {
                $selects[] = 'registration';
            }
            if (Schema::hasColumn('vehicles', 'type')) {
                $selects[] = 'type';
            }
            if (Schema::hasColumn('vehicles', 'capacity')) {
                $selects[] = 'capacity';
            }

            $fleetItems = \DB::table('vehicles')
                ->where('branch_id', $branch->id)
                ->select($selects)
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
        }

        // Branch selector options for admins/regional roles.
        $branchOptions = $this->branchOptions($user);
        
        // Additional DHL-grade metrics
        $today = now()->toDateString();
        $last7Days = now()->subDays(6)->startOfDay();
        
        // COD Collection metrics
        $codMetrics = $this->getBranchCodMetrics($branch, $today);
        
        // Driver/Worker performance metrics
        $driverPerformance = $this->getBranchDriverPerformance($branch, $last7Days);
        
        // Daily trends for charts
        $dailyTrends = $this->getBranchDailyTrends($branch);
        
        // Scan activity
        $scanActivity = $this->getBranchScanActivity($branch, $today);
        
        // Average delivery time
        $avgDeliveryTime = $this->getBranchAvgDeliveryTime($branch, $last7Days);
        
        // First attempt delivery rate
        $firstAttemptRate = $this->getBranchFirstAttemptRate($branch, $last7Days);

        return view('branch.dashboard', [
            'branch' => $branch,
            'stats' => $stats,
            'recentShipments' => $recentShipments,
            'inboundList' => $inboundList,
            'outboundList' => $outboundList,
            'exceptionsList' => $exceptionsList,
            'workforce' => $workforce,
            'clients' => $clients,
            'onTimeRate' => $deliveredTotal > 0 ? round(($onTimeDelivered / $deliveredTotal) * 100, 1) : 0,
            'slaBreaches' => $slaBreach,
            'financeToday' => $financeToday,
            'warehouseAlerts' => $warehouseAlerts,
            'fleetItems' => $fleetItems,
            'branchOptions' => $branchOptions,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
            'codMetrics' => $codMetrics,
            'driverPerformance' => $driverPerformance,
            'dailyTrends' => $dailyTrends,
            'scanActivity' => $scanActivity,
            'avgDeliveryTime' => $avgDeliveryTime,
            'firstAttemptRate' => $firstAttemptRate,
        ]);
    }
    
    /**
     * Get COD collection metrics for the branch
     */
    private function getBranchCodMetrics(Branch $branch, string $today): array
    {
        $codCollected = $branch->originShipments()
            ->where('cod_amount', '>', 0)
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->whereDate('delivered_at', $today)
            ->sum('cod_amount') ?? 0;
            
        $codPending = $branch->originShipments()
            ->where('cod_amount', '>', 0)
            ->whereNotIn('current_status', [
                ShipmentStatus::DELIVERED->value,
                ShipmentStatus::CANCELLED->value,
                ShipmentStatus::RETURNED->value,
            ])
            ->sum('cod_amount') ?? 0;
            
        return [
            'collected_today' => round($codCollected, 2),
            'pending' => round($codPending, 2),
        ];
    }
    
    /**
     * Get driver/worker performance metrics
     */
    private function getBranchDriverPerformance(Branch $branch, Carbon $since): array
    {
        $activeWorkers = $branch->activeWorkers()->count();
        
        $totalDeliveries = $branch->originShipments()
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->where('delivered_at', '>=', $since)
            ->count();
            
        $avgPerWorker = $activeWorkers > 0 ? round($totalDeliveries / $activeWorkers, 1) : 0;
        
        // Top performers
        $topWorkers = [];
        if (Schema::hasColumn('shipments', 'delivered_by')) {
            $topWorkers = DB::table('shipments')
                ->select('delivered_by', DB::raw('COUNT(*) as delivery_count'))
                ->where('origin_branch_id', $branch->id)
                ->where('current_status', ShipmentStatus::DELIVERED->value)
                ->where('delivered_at', '>=', $since)
                ->whereNotNull('delivered_by')
                ->groupBy('delivered_by')
                ->orderByDesc('delivery_count')
                ->limit(5)
                ->get()
                ->toArray();
        }
        
        return [
            'active_workers' => $activeWorkers,
            'total_deliveries' => $totalDeliveries,
            'avg_per_worker' => $avgPerWorker,
            'top_workers' => $topWorkers,
        ];
    }
    
    /**
     * Get daily trends for the last 7 days
     */
    private function getBranchDailyTrends(Branch $branch): array
    {
        $labels = [];
        $created = [];
        $delivered = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');
            
            $created[] = $branch->originShipments()
                ->whereDate('created_at', $date->toDateString())
                ->count();
                
            $delivered[] = $branch->originShipments()
                ->whereDate('delivered_at', $date->toDateString())
                ->where('current_status', ShipmentStatus::DELIVERED->value)
                ->count();
        }
        
        return [
            'labels' => $labels,
            'created' => $created,
            'delivered' => $delivered,
        ];
    }
    
    /**
     * Get scan activity for today
     */
    private function getBranchScanActivity(Branch $branch, string $today): array
    {
        if (!Schema::hasTable('scan_events')) {
            return ['total' => 0, 'by_type' => []];
        }
        
        $total = DB::table('scan_events')
            ->where('branch_id', $branch->id)
            ->whereDate('created_at', $today)
            ->count();
            
        $byType = DB::table('scan_events')
            ->select('scan_type', DB::raw('COUNT(*) as count'))
            ->where('branch_id', $branch->id)
            ->whereDate('created_at', $today)
            ->groupBy('scan_type')
            ->pluck('count', 'scan_type')
            ->toArray();
            
        return [
            'total' => $total,
            'by_type' => $byType,
        ];
    }
    
    /**
     * Get average delivery time in hours
     */
    private function getBranchAvgDeliveryTime(Branch $branch, Carbon $since): float
    {
        if (!Schema::hasColumn('shipments', 'picked_up_at') || !Schema::hasColumn('shipments', 'delivered_at')) {
            return 0;
        }
        
        $avgHours = $branch->originShipments()
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->where('delivered_at', '>=', $since)
            ->whereNotNull('picked_up_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, picked_up_at, delivered_at)) as avg_hours')
            ->value('avg_hours');
            
        return round($avgHours ?? 0, 1);
    }
    
    /**
     * Get first attempt delivery rate
     */
    private function getBranchFirstAttemptRate(Branch $branch, Carbon $since): float
    {
        if (!Schema::hasTable('scan_events')) {
            return 0;
        }
        
        $deliveredIds = $branch->originShipments()
            ->where('current_status', ShipmentStatus::DELIVERED->value)
            ->where('delivered_at', '>=', $since)
            ->pluck('id');
            
        if ($deliveredIds->isEmpty()) {
            return 0;
        }
        
        // Count shipments with single delivery attempt
        $singleAttempt = DB::table('scan_events')
            ->whereIn('shipment_id', $deliveredIds)
            ->whereIn('scan_type', ['delivery_attempt', 'out_for_delivery'])
            ->groupBy('shipment_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();
            
        return round(($singleAttempt / $deliveredIds->count()) * 100, 1);
    }
}
