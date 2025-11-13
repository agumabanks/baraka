<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChartDataResource;
use App\Http\Resources\DashboardResource;
use App\Http\Resources\KPICardResource;
use App\Http\Resources\WorkflowItemResource;
use App\Http\Resources\WorkflowTaskActivityResource;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use App\Models\Backend\CourierStatement;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Fraud;
use App\Models\Backend\Hub;
use App\Models\Backend\HubStatement;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use App\Models\WorkflowTask;
use App\Models\WorkflowTaskActivity;
use App\Models\Backend\VatStatement;
use App\Models\Customer;
use App\Models\MerchantShops;
use App\Models\Shipment;
use App\Enums\Status as StatusEnum;
use App\Models\User;
use App\Repositories\Dashboard\DashboardInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Backend\Support;
use App\Enums\SupportStatus;

class DashboardApiController extends Controller
{
    protected $repo;

    private const WORKFLOW_QUEUE_LIMIT = 25;

    public function __construct(DashboardInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Get complete dashboard data
     */
    public function index(Request $request)
    {
        $fromTo = $this->repo->FromTo($request);

        $data = Auth::user()->user_type == UserType::MERCHANT
            ? $this->getMerchantDashboardData($fromTo)
            : $this->getAdminDashboardData($fromTo);

        $resource = new DashboardResource($data);

        return response()->json([
            'success' => true,
            'data' => $resource->toArray($request),
        ]);
    }

    /**
     * Get KPI metrics only
     */
    public function kpis(Request $request)
    {
        $fromTo = $this->repo->FromTo($request);

        $data = Auth::user()->user_type == UserType::MERCHANT
            ? $this->getMerchantKPIs($fromTo)
            : $this->getAdminKPIs($fromTo);

        $kpis = array_values(array_filter(array_map(function ($item) use ($request) {
            return (new KPICardResource($item))->toArray($request);
        }, $data)));

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }

    /**
     * Get chart data only
     */
    public function charts(Request $request)
    {
        $fromTo = $this->repo->FromTo($request);
        $startDate = Carbon::parse($fromTo['from'])->startOfDay();
        $endDate = Carbon::parse($fromTo['to'])->endOfDay();
        $datePeriod = $this->buildDatePeriod($startDate, $endDate);
        $range = [
            'from' => $startDate->toDateTimeString(),
            'to' => $endDate->toDateTimeString(),
        ];

        if (Auth::user()->user_type == UserType::MERCHANT) {
            $merchantId = Auth::user()->merchant->id;
            $data = $this->getMerchantCharts($range, $merchantId, $datePeriod, $startDate, $endDate);
        } else {
            $data = $this->getAdminCharts($range, $datePeriod, $startDate, $endDate);
        }

        $charts = [];
        foreach ($data as $key => $chart) {
            $charts[$key] = (new ChartDataResource($chart))->toArray($request);
        }

        return response()->json([
            'success' => true,
            'data' => $charts,
        ]);
    }

    /**
     * Get workflow queue items
     */
    public function workflowQueue(Request $request)
    {
        $fromTo = $this->repo->FromTo($request);
        $tasks = $this->getWorkflowItems($fromTo);
        $summary = $this->buildWorkflowSummary();

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks,
                'summary' => $summary,
                'meta' => [
                    'count' => count($tasks),
                    'limit' => self::WORKFLOW_QUEUE_LIMIT,
                    'refreshed_at' => now()->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Get merchant dashboard data
     */
    private function getMerchantDashboardData($fromTo)
    {
        $merchant = Auth::user()->merchant;
        $merchantId = $merchant->id;

        $startDate = Carbon::parse($fromTo['from'])->startOfDay();
        $endDate = Carbon::parse($fromTo['to'])->endOfDay();
        $datePeriod = $this->buildDatePeriod($startDate, $endDate);
        $range = [
            'from' => $startDate->toDateTimeString(),
            'to' => $endDate->toDateTimeString(),
        ];

        // Core metrics scoped to the selected window
        $totalParcels = Parcel::where('merchant_id', $merchantId)->count();
        $deliveredParcels = Parcel::where('merchant_id', $merchantId)
            ->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->whereBetween('delivered_date', [$startDate, $endDate])
            ->count();
        $pendingParcels = Parcel::where('merchant_id', $merchantId)
            ->where('status', ParcelStatus::PENDING)
            ->count();

        $totalSales = Parcel::where('merchant_id', $merchantId)
            ->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->whereBetween(DB::raw('COALESCE(delivered_date, updated_at)'), [$startDate, $endDate])
            ->sum('cash_collection');

        $totalVat = Parcel::where('merchant_id', $merchantId)
            ->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->whereBetween(DB::raw('COALESCE(delivered_date, updated_at)'), [$startDate, $endDate])
            ->sum('vat_amount');

        $totalDeliveryFee = Parcel::where('merchant_id', $merchantId)
            ->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->whereBetween(DB::raw('COALESCE(delivered_date, updated_at)'), [$startDate, $endDate])
            ->sum('total_delivery_amount');

        $pendingPaymentRequests = Payment::where('merchant_id', $merchantId)
            ->whereNull('transaction_id')
            ->count();

        $currencySymbol = optional(settings())->currency_symbol ?? optional(settings())->currency ?? 'UGX';

        return [
            'dateFilter' => [
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
                'preset' => 'custom'
            ],
            'healthKPIs' => [
                'slaStatus' => $this->createKPICard('sla_status', 'SLA Performance', '98.5%', 'Last 30 days', 'fas fa-clock', ['value' => 2.1, 'direction' => 'up'], 'success'),
                'exceptions' => $this->createKPICard('exceptions', 'Exceptions', 3, 'This week', 'fas fa-exclamation-triangle', ['value' => -15, 'direction' => 'down'], 'warning'),
                'onTimeDelivery' => $this->createKPICard('on_time_delivery', 'On-Time Delivery', '96.2%', 'Last 30 days', 'fas fa-shipping-fast', ['value' => 1.8, 'direction' => 'up'], 'success'),
                'openTickets' => $this->createKPICard('open_tickets', 'Open Tickets', 7, 'Active', 'fas fa-ticket-alt', ['value' => -2, 'direction' => 'down'], 'neutral'),
            ],
            'coreKPIs' => [
                $this->createKPICard('total_parcels', 'Total Parcels', $totalParcels, 'All time', 'fas fa-box'),
                $this->createKPICard('delivered_parcels', 'Delivered (window)', $deliveredParcels, 'Current filter', 'fas fa-check-circle'),
                $this->createKPICard('pending_parcels', 'Pending', $pendingParcels, 'Awaiting action', 'fas fa-clock', null, 'warning'),
                $this->createKPICard('total_sales', 'Cash Collected', number_format($totalSales, 2), 'Current filter', 'fas fa-dollar-sign'),
                $this->createKPICard('total_vat', 'VAT Collected', number_format($totalVat, 2), 'Current filter', 'fas fa-calculator'),
                $this->createKPICard('delivery_fees', 'Delivery Fees', number_format($totalDeliveryFee, 2), 'Current filter', 'fas fa-truck'),
            ],
            'workflowQueue' => $this->getWorkflowItems($range),
            'statements' => [
                'merchant' => [
                    'income' => $totalSales,
                    'expense' => $totalDeliveryFee + $totalVat,
                    'balance' => $totalSales - ($totalDeliveryFee + $totalVat),
                    'currency' => $currencySymbol,
                ]
            ],
            'charts' => $this->getMerchantCharts($range, $merchantId, $datePeriod, $startDate, $endDate),
            'quickActions' => [
                [
                    'id' => 'create_parcel',
                    'title' => 'Create Parcel',
                    'icon' => 'fas fa-plus',
                    'url' => '/parcels/create',
                    'badge' => null,
                ],
                [
                    'id' => 'view_reports',
                    'title' => 'View Reports',
                    'icon' => 'fas fa-chart-bar',
                    'url' => '/reports',
                    'badge' => null,
                ],
                [
                    'id' => 'payment_request',
                    'title' => 'Payment Request',
                    'icon' => 'fas fa-money-bill',
                    'url' => '/payments/request',
                    'badge' => $pendingPaymentRequests > 0 ? [
                        'count' => $pendingPaymentRequests,
                        'variant' => 'warning',
                    ] : null,
                ],
            ]
        ];
    }

    /**
     * Get admin dashboard data
     */

private function getAdminDashboardData($fromTo)
{
    $startDate = Carbon::parse($fromTo['from'])->startOfDay();
    $endDate = Carbon::parse($fromTo['to'])->endOfDay();
    $datePeriod = $this->buildDatePeriod($startDate, $endDate);
    $range = [
        'from' => $startDate->toDateTimeString(),
        'to' => $endDate->toDateTimeString(),
    ];

    $totalParcels = Parcel::whereBetween('created_at', [$startDate, $endDate])->count();
    $totalUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
    $totalMerchants = Merchant::whereBetween('created_at', [$startDate, $endDate])->count();
    $totalDeliveryMen = DeliveryMan::whereBetween('created_at', [$startDate, $endDate])->count();

    $deliveredParcels = Parcel::whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
        ->whereNotNull('delivered_date')
        ->whereBetween('delivered_date', [$startDate, $endDate])
        ->count();

    $pendingParcels = Parcel::where('status', ParcelStatus::PENDING)->count();

    $slaPercent = $totalParcels > 0
        ? round(($deliveredParcels / max($totalParcels, 1)) * 100, 1)
        : 0.0;

    $exceptionStatuses = [
        ParcelStatus::RETURN_WAREHOUSE,
        ParcelStatus::RETURNED_MERCHANT,
        ParcelStatus::RETURN_TO_COURIER,
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE,
        ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL,
        ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL,
        ParcelStatus::DELIVERED_CANCEL,
        ParcelStatus::PICKUP_ASSIGN_CANCEL,
        ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL,
        ParcelStatus::PARTIAL_DELIVERED_CANCEL,
    ];

    $exceptionCount = Parcel::whereIn('status', $exceptionStatuses)
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->count();

    $avgDeliveryMinutes = Parcel::whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
        ->whereNotNull('delivered_date')
        ->whereBetween('delivered_date', [$startDate, $endDate])
        ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered_date)) as avg_minutes')
        ->value('avg_minutes');

    $avgDeliveryHours = $avgDeliveryMinutes ? round($avgDeliveryMinutes / 60, 1) : 0;

    $openTickets = Support::whereIn('status', [
        SupportStatus::PENDING,
        SupportStatus::PROCESSING,
    ])->count();

    $deliveryManIncome = DeliverymanStatement::where('type', StatementType::INCOME)
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->sum('amount');
    $deliveryManExpense = DeliverymanStatement::where('type', StatementType::EXPENSE)
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->sum('amount');

    $merchantIncome = MerchantStatement::where('type', StatementType::INCOME)
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->sum('amount');
    $merchantExpense = MerchantStatement::where('type', StatementType::EXPENSE)
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->sum('amount');

    $hubIncome = HubStatement::where('type', StatementType::INCOME)
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->sum('amount');
    $hubExpense = HubStatement::where('type', StatementType::EXPENSE)
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->sum('amount');

    $currency = optional(settings())->currency_symbol ?? optional(settings())->currency ?? 'UGX';

    $pendingPayments = Payment::whereNull('transaction_id')->count();
    $recentBankEntries = BankTransaction::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->count();

    return [
        'dateFilter' => [
            'from' => $startDate->format('Y-m-d'),
            'to' => $endDate->format('Y-m-d'),
            'preset' => 'custom',
        ],
        'healthKPIs' => [
            'slaStatus' => $this->createKPICard('sla_status', 'On-time Delivery', sprintf('%.1f%%', $slaPercent), 'Current period', 'fas fa-server'),
            'exceptions' => $this->createKPICard('exceptions', 'Exceptions', $exceptionCount, 'Parcels flagged in period', 'fas fa-exclamation-triangle'),
            'onTimeDelivery' => $this->createKPICard('on_time_delivery', 'Avg Delivery Time', $avgDeliveryHours ? sprintf('%.1fh', $avgDeliveryHours) : 'N/A', 'Delivered parcels', 'fas fa-clock'),
            'openTickets' => $this->createKPICard('open_tickets', 'Open Support Tickets', $openTickets, 'Pending support cases', 'fas fa-headset'),
        ],
        'coreKPIs' => [
            $this->createKPICard('total_parcels', 'Total Parcels', $totalParcels, 'Created in period', 'fas fa-box'),
            $this->createKPICard('total_users', 'New Users', $totalUsers, 'Registered in period', 'fas fa-users'),
            $this->createKPICard('total_merchants', 'Merchants', $totalMerchants, 'Onboarded in period', 'fas fa-store'),
            $this->createKPICard('total_delivery_men', 'Delivery Staff', $totalDeliveryMen, 'Added in period', 'fas fa-truck'),
            $this->createKPICard('delivered_parcels', 'Delivered', $deliveredParcels, 'Delivered in period', 'fas fa-check-circle'),
            $this->createKPICard('pending_parcels', 'Pending', $pendingParcels, 'Awaiting processing', 'fas fa-clock'),
        ],
        'workflowQueue' => $this->getWorkflowItems($range),
        'statements' => [
            'deliveryMan' => [
                'income' => (float) $deliveryManIncome,
                'expense' => (float) $deliveryManExpense,
                'balance' => (float) ($deliveryManIncome - $deliveryManExpense),
                'currency' => $currency,
            ],
            'merchant' => [
                'income' => (float) $merchantIncome,
                'expense' => (float) $merchantExpense,
                'balance' => (float) ($merchantIncome - $merchantExpense),
                'currency' => $currency,
            ],
            'hub' => [
                'income' => (float) $hubIncome,
                'expense' => (float) $hubExpense,
                'balance' => (float) ($hubIncome - $hubExpense),
                'currency' => $currency,
            ],
        ],
        'charts' => $this->getAdminCharts($range, $datePeriod, $startDate, $endDate),
        'quickActions' => [
            [
                'id' => 'manage_users',
                'title' => 'Manage Users',
                'icon' => 'fas fa-users-cog',
                'url' => '/admin/users',
                'badge' => $totalUsers > 0 ? [
                    'count' => $totalUsers,
                    'variant' => 'info',
                ] : null,
            ],
            [
                'id' => 'finance_overview',
                'title' => 'Finance Overview',
                'icon' => 'fas fa-wallet',
                'url' => '/admin/reports',
                'badge' => $pendingPayments > 0 ? [
                    'count' => $pendingPayments,
                    'variant' => 'attention',
                ] : null,
            ],
            [
                'id' => 'support_center',
                'title' => 'Support Center',
                'icon' => 'fas fa-headset',
                'url' => '/support',
                'badge' => $openTickets > 0 ? [
                    'count' => $openTickets,
                    'variant' => 'warning',
                ] : null,
            ],
            [
                'id' => 'bank_activity',
                'title' => 'Bank Activity',
                'icon' => 'fas fa-university',
                'url' => '/admin/bank-transactions',
                'badge' => $recentBankEntries > 0 ? [
                    'count' => $recentBankEntries,
                    'variant' => 'default',
                ] : null,
            ],
        ],
        'teamOverview' => $this->buildTeamOverview(),
        'activityTimeline' => $this->getRecentWorkflowActivities(),
    ];
}

    private function buildTeamOverview(): array
    {
        $admins = User::query()
            ->where('user_type', UserType::ADMIN)
            ->select(['id', 'name', 'status', 'role_id', 'hub_id', 'department_id', 'joining_date'])
            ->with([
                'role:id,name,slug',
                'department:id,title',
                'hub:id,name',
            ])
            ->get();

        if ($admins->isEmpty()) {
            return [];
        }

        $recentWindow = Carbon::now()->subDays(30);

        return $admins
            ->groupBy(fn (User $user) => ($user->department_id ?? 'null').'|'.($user->hub_id ?? 'null'))
            ->map(function ($group) use ($recentWindow) {
                /** @var \Illuminate\Support\Collection<int, User> $group */
                $first = $group->first();

                $department = $first?->department ? [
                    'id' => $first->department->id,
                    'title' => $first->department->title,
                ] : null;

                $hub = $first?->hub ? [
                    'id' => $first->hub->id,
                    'name' => $first->hub->name,
                ] : null;

                $labelParts = array_filter([
                    $department['title'] ?? null,
                    $hub['name'] ?? null,
                ]);
                $label = $labelParts ? implode(' · ', $labelParts) : 'Unassigned';

                $total = $group->count();
                $active = $group->filter(fn (User $user) => (int) $user->status === StatusEnum::ACTIVE)->count();
                $recent = $group->filter(function (User $user) use ($recentWindow) {
                    if (! $user->joining_date) {
                        return false;
                    }

                    try {
                        return Carbon::parse($user->joining_date)->greaterThanOrEqualTo($recentWindow);
                    } catch (\Throwable $e) {
                        return false;
                    }
                })->count();

                $sampleUsers = $group
                    ->sortByDesc(fn (User $user) => (int) $user->status)
                    ->take(3)
                    ->map(fn (User $user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'status' => (int) $user->status,
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => ($department['id'] ?? 'null').'|'.($hub['id'] ?? 'null'),
                    'label' => $label,
                    'department' => $department,
                    'hub' => $hub,
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $total - $active,
                    'active_ratio' => $total > 0 ? round(($active / $total) * 100, 1) : 0.0,
                    'recent_hires' => $recent,
                    'sample_users' => $sampleUsers,
                ];
            })
            ->sortByDesc(fn (array $team) => $team['total'])
            ->values()
            ->take(6)
            ->all();
    }

    private function getRecentWorkflowActivities(int $limit = 10): array
    {
        $activities = WorkflowTaskActivity::query()
            ->with([
                'actor:id,name,email',
                'task:id,title,status',
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return WorkflowTaskActivityResource::collection($activities)->resolve();
    }
    /**
     * Get merchant KPIs
     */
    private function getMerchantKPIs($fromTo)
    {
        $merchantId = Auth::user()->merchant->id;

        $totalParcels = Parcel::where('merchant_id', $merchantId)->count();
        $deliveredParcels = Parcel::where('status', ParcelStatus::DELIVERED)->where('merchant_id', $merchantId)->count();
        $pendingParcels = Parcel::where('status', ParcelStatus::PENDING)->where('merchant_id', $merchantId)->count();

        return [
            $this->createKPICard('total_parcels', 'Total Parcels', $totalParcels, 'All time', 'fas fa-box'),
            $this->createKPICard('delivered_parcels', 'Delivered', $deliveredParcels, 'This period', 'fas fa-check-circle'),
            $this->createKPICard('pending_parcels', 'Pending', $pendingParcels, 'Current', 'fas fa-clock'),
        ];
    }

    /**
     * Get admin KPIs
     */
    private function getAdminKPIs($fromTo)
    {
        $totalParcels = Parcel::whereBetween('created_at', $fromTo)->count();
        $totalUsers = User::whereBetween('created_at', $fromTo)->count();
        $deliveredParcels = $this->repo->parcelPosition(new Request(), ParcelStatus::DELIVERED, $fromTo)->count();

        return [
            $this->createKPICard('total_parcels', 'Total Parcels', $totalParcels, 'This period', 'fas fa-box'),
            $this->createKPICard('total_users', 'Total Users', $totalUsers, 'All time', 'fas fa-users'),
            $this->createKPICard('delivered_parcels', 'Delivered', $deliveredParcels, 'This period', 'fas fa-check-circle'),
        ];
    }

    /**
     * Get merchant charts
     */

    private function getMerchantCharts(array $range, int $merchantId, array $datePeriod, Carbon $startDate, Carbon $endDate)
    {
        $cashRows = Parcel::where('merchant_id', $merchantId)
            ->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->whereRaw('DATE(COALESCE(delivered_date, updated_at)) BETWEEN ? AND ?', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->selectRaw('DATE(COALESCE(delivered_date, updated_at)) as day, SUM(cash_collection) as total_cash, SUM(total_delivery_amount) as total_fee, SUM(vat_amount) as total_vat')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $cashCollectionData = [];
        $netRevenueData = [];

        foreach ($datePeriod as $date) {
            $dayKey = $date->toDateString();
            $row = $cashRows[$dayKey] ?? null;
            $cash = $row ? (float) $row->total_cash : 0.0;
            $fees = $row ? (float) $row->total_fee : 0.0;
            $vat = $row ? (float) $row->total_vat : 0.0;

            $label = $date->format('M d');

            $cashCollectionData[] = [
                'label' => $label,
                'value' => round($cash, 2),
            ];

            $netRevenueData[] = [
                'label' => $label,
                'value' => round($cash - $fees - $vat, 2),
            ];
        }

        return [
            'cashCollection' => [
                'title' => 'Daily Cash Collection',
                'type' => 'area',
                'data' => $cashCollectionData,
                'height' => 320,
            ],
            'incomeExpense' => [
                'title' => 'Net Revenue Trend',
                'type' => 'line',
                'data' => $netRevenueData,
                'height' => 300,
            ],
        ];
    }



    /**
     * Get admin charts
     */

    private function getAdminCharts(array $range, array $datePeriod, Carbon $startDate, Carbon $endDate)
    {
        $cashRows = Parcel::whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->whereRaw('DATE(COALESCE(delivered_date, updated_at)) BETWEEN ? AND ?', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->selectRaw('DATE(COALESCE(delivered_date, updated_at)) as day, SUM(cash_collection) as total_cash, SUM(total_delivery_amount) as total_fee, SUM(vat_amount) as total_vat')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $cashCollectionData = [];
        $netRevenueData = [];

        foreach ($datePeriod as $date) {
            $dayKey = $date->toDateString();
            $row = $cashRows[$dayKey] ?? null;
            $cash = $row ? (float) $row->total_cash : 0.0;
            $fees = $row ? (float) $row->total_fee : 0.0;
            $vat = $row ? (float) $row->total_vat : 0.0;

            $label = $date->format('M d');

            $cashCollectionData[] = [
                'label' => $label,
                'value' => round($cash, 2),
            ];

            $netRevenueData[] = [
                'label' => $label,
                'value' => round($cash - $fees - $vat, 2),
            ];
        }

        $incomeExpression = 'SUM(CASE WHEN type = ' . StatementType::INCOME . ' THEN amount ELSE 0 END)';
        $expenseExpression = 'SUM(CASE WHEN type = ' . StatementType::EXPENSE . ' THEN amount ELSE 0 END)';

        $courierSummary = DeliverymanStatement::selectRaw('delivery_man_id, ' . $incomeExpression . ' AS income_total, ' . $expenseExpression . ' AS expense_total')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->groupBy('delivery_man_id')
            ->orderByDesc(DB::raw($incomeExpression . ' - ' . $expenseExpression))
            ->limit(5)
            ->get();

        $courierData = [];
        foreach ($courierSummary as $summary) {
            $deliveryMan = DeliveryMan::with('user')->find($summary->delivery_man_id);
            $name = optional(optional($deliveryMan)->user)->name ?? 'Courier #' . $summary->delivery_man_id;
            $net = (float) $summary->income_total - (float) $summary->expense_total;
            $courierData[] = [
                'label' => $name,
                'value' => round($net, 2),
            ];
        }

        if (empty($courierData)) {
            $courierData[] = [
                'label' => 'No data',
                'value' => 0,
            ];
        }

        return [
            'cashCollection' => [
                'title' => 'Daily Cash Collection',
                'type' => 'area',
                'data' => $cashCollectionData,
                'height' => 320,
            ],
            'incomeExpense' => [
                'title' => 'Net Revenue Trend',
                'type' => 'line',
                'data' => $netRevenueData,
                'height' => 300,
            ],
            'courierRevenue' => [
                'title' => 'Top Courier Net Earnings',
                'type' => 'bar',
                'data' => $courierData,
                'height' => 280,
            ],
        ];
    }



    private function buildDatePeriod(Carbon $startDate, Carbon $endDate): array
    {
        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->copy();
        }

        if (empty($dates)) {
            $dates[] = $startDate->copy();
        }

        return $dates;
    }

    private function getWorkflowItems(array $fromTo): array
    {
        $startDate = Carbon::parse($fromTo['from'])->startOfDay();
        $endDate = Carbon::parse($fromTo['to'])->endOfDay();

        $orderExpression = "CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END";

        $tasks = WorkflowTask::query()
            ->withSummary()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->orWhereBetween('updated_at', [$startDate, $endDate])
                    ->orWhereNull('completed_at');
            })
            ->orderByRaw($orderExpression)
            ->orderBy('due_at')
            ->orderByDesc('updated_at')
            ->limit(self::WORKFLOW_QUEUE_LIMIT)
            ->get();

        $request = app('request');
        if (! $request instanceof HttpRequest) {
            $request = HttpRequest::create('/', 'GET');
        }

        return WorkflowItemResource::collection($tasks)->toArray($request);
    }

    private function buildWorkflowSummary(): array
    {
        $statusCounts = WorkflowTask::select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = [
            'total' => WorkflowTask::count(),
        ];

        foreach (WorkflowTask::STATUSES as $status) {
            $summary[$status] = (int) ($statusCounts[$status] ?? 0);
        }

        return $summary;
    }

    /**
     * Create KPI card data structure
     */
    private function createKPICard($id, $title, $value, $subtitle, $icon = null, $trend = null, $state = 'neutral')
    {
        return [
            'id' => $id,
            'title' => $title,
            'value' => $value,
            'subtitle' => $subtitle,
            'icon' => $icon,
            'trend' => $trend,
            'state' => $state,
            'drilldownRoute' => "/analytics/{$id}",
            'tooltip' => "Click to view detailed {$title} analytics",
            'loading' => false
        ];
    }
}
