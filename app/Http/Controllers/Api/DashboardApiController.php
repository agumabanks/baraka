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
use App\Models\Backend\VatStatement;
use App\Models\Customer;
use App\Models\MerchantShops;
use App\Models\Shipment;
use App\Models\User;
use App\Repositories\Dashboard\DashboardInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardApiController extends Controller
{
    protected $repo;

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

        if (Auth::user()->user_type == UserType::MERCHANT) {
            return new DashboardResource($this->getMerchantDashboardData($fromTo));
        } else {
            return new DashboardResource($this->getAdminDashboardData($fromTo));
        }
    }

    /**
     * Get KPI metrics only
     */
    public function kpis(Request $request)
    {
        $fromTo = $this->repo->FromTo($request);

        if (Auth::user()->user_type == UserType::MERCHANT) {
            $data = $this->getMerchantKPIs($fromTo);
        } else {
            $data = $this->getAdminKPIs($fromTo);
        }

        return KPICardResource::collection($data);
    }

    /**
     * Get chart data only
     */
    public function charts(Request $request)
    {
        $fromTo = $this->repo->FromTo($request);

        if (Auth::user()->user_type == UserType::MERCHANT) {
            $data = $this->getMerchantCharts($fromTo);
        } else {
            $data = $this->getAdminCharts($fromTo);
        }

        return ChartDataResource::collection($data);
    }

    /**
     * Get workflow queue items
     */
    public function workflowQueue(Request $request)
    {
        $items = $this->getWorkflowItems();
        return WorkflowItemResource::collection($items);
    }

    /**
     * Get merchant dashboard data
     */
    private function getMerchantDashboardData($fromTo)
    {
        $merchantId = Auth::user()->merchant->id;

        // Basic metrics
        $totalParcels = Parcel::where('merchant_id', $merchantId)->count();
        $deliveredParcels = Parcel::where('status', ParcelStatus::DELIVERED)
            ->where('merchant_id', $merchantId)->count();
        $pendingParcels = Parcel::where('status', ParcelStatus::PENDING)
            ->where('merchant_id', $merchantId)->count();

        // Financial metrics
        $totalSales = Parcel::where('merchant_id', $merchantId)
            ->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->sum('cash_collection');

        $totalVat = Parcel::where('merchant_id', $merchantId)
            ->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->sum('vat_amount');

        $totalDeliveryFee = Parcel::where('merchant_id', $merchantId)
            ->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])
            ->sum('total_delivery_amount');

        return [
            'dateFilter' => [
                'from' => $fromTo['from']->format('Y-m-d'),
                'to' => $fromTo['to']->format('Y-m-d'),
                'preset' => 'custom'
            ],
            'healthKPIs' => [
                'slaStatus' => $this->createKPICard('sla_status', 'SLA Status', '98.5%', 'Last 30 days', 'fas fa-clock', ['value' => 2.1, 'direction' => 'up'], 'success'),
                'exceptions' => $this->createKPICard('exceptions', 'Exceptions', 3, 'This week', 'fas fa-exclamation-triangle', ['value' => -15, 'direction' => 'down'], 'warning'),
                'onTimeDelivery' => $this->createKPICard('on_time_delivery', 'On-Time Delivery', '96.2%', 'Last 30 days', 'fas fa-shipping-fast', ['value' => 1.8, 'direction' => 'up'], 'success'),
                'openTickets' => $this->createKPICard('open_tickets', 'Open Tickets', 7, 'Active', 'fas fa-ticket-alt', ['value' => -2, 'direction' => 'down'], 'neutral'),
            ],
            'coreKPIs' => [
                $this->createKPICard('total_parcels', 'Total Parcels', $totalParcels, 'All time', 'fas fa-box'),
                $this->createKPICard('delivered_parcels', 'Delivered', $deliveredParcels, 'This period', 'fas fa-check-circle', ['value' => 12.5, 'direction' => 'up'], 'success'),
                $this->createKPICard('pending_parcels', 'Pending', $pendingParcels, 'Current', 'fas fa-clock', null, 'warning'),
                $this->createKPICard('total_sales', 'Total Sales', number_format($totalSales, 2), 'This period', 'fas fa-dollar-sign', ['value' => 8.3, 'direction' => 'up'], 'success'),
                $this->createKPICard('total_vat', 'VAT Collected', number_format($totalVat, 2), 'This period', 'fas fa-calculator'),
                $this->createKPICard('delivery_fees', 'Delivery Fees', number_format($totalDeliveryFee, 2), 'This period', 'fas fa-truck'),
            ],
            'workflowQueue' => $this->getWorkflowItems(),
            'statements' => [
                'merchant' => [
                    'income' => $totalSales,
                    'expense' => $totalDeliveryFee + $totalVat,
                    'balance' => $totalSales - ($totalDeliveryFee + $totalVat),
                    'currency' => '$'
                ]
            ],
            'charts' => $this->getMerchantCharts($fromTo),
            'quickActions' => [
                ['id' => 'create_parcel', 'title' => 'Create Parcel', 'icon' => 'fas fa-plus', 'url' => '/parcels/create', 'badge' => null],
                ['id' => 'view_reports', 'title' => 'View Reports', 'icon' => 'fas fa-chart-bar', 'url' => '/reports', 'badge' => null],
                ['id' => 'payment_request', 'title' => 'Payment Request', 'icon' => 'fas fa-money-bill', 'url' => '/payments/request', 'badge' => 2],
            ]
        ];
    }

    /**
     * Get admin dashboard data
     */
    private function getAdminDashboardData($fromTo)
    {
        $totalParcels = Parcel::whereBetween('created_at', $fromTo)->count();
        $totalUsers = User::whereBetween('created_at', $fromTo)->count();
        $totalMerchants = Merchant::whereBetween('created_at', $fromTo)->count();
        $totalDeliveryMen = DeliveryMan::whereBetween('created_at', $fromTo)->count();

        $deliveredParcels = $this->repo->parcelPosition(new Request(), ParcelStatus::DELIVERED, $fromTo)->count();
        $pendingParcels = $this->repo->parcelPosition(new Request(), ParcelStatus::PENDING, $fromTo)->count();

        return [
            'dateFilter' => [
                'from' => Carbon::parse($fromTo['from'])->format('Y-m-d'),
                'to' => Carbon::parse($fromTo['to'])->format('Y-m-d'),
                'preset' => 'custom'
            ],
            'healthKPIs' => [
                'slaStatus' => $this->createKPICard('sla_status', 'System SLA', '97.8%', 'Last 24h', 'fas fa-server', ['value' => 0.5, 'direction' => 'up'], 'success'),
                'exceptions' => $this->createKPICard('exceptions', 'System Exceptions', 12, 'Last 24h', 'fas fa-exclamation-triangle', ['value' => -8, 'direction' => 'down'], 'warning'),
                'onTimeDelivery' => $this->createKPICard('on_time_delivery', 'Avg Delivery Time', '2.3h', 'Last 7 days', 'fas fa-clock', ['value' => -0.2, 'direction' => 'down'], 'success'),
                'openTickets' => $this->createKPICard('open_tickets', 'Support Tickets', 23, 'Open', 'fas fa-headset', ['value' => 5, 'direction' => 'up'], 'neutral'),
            ],
            'coreKPIs' => [
                $this->createKPICard('total_parcels', 'Total Parcels', $totalParcels, 'This period', 'fas fa-box'),
                $this->createKPICard('total_users', 'Total Users', $totalUsers, 'All time', 'fas fa-users'),
                $this->createKPICard('total_merchants', 'Merchants', $totalMerchants, 'Active', 'fas fa-store'),
                $this->createKPICard('total_delivery_men', 'Delivery Staff', $totalDeliveryMen, 'Active', 'fas fa-truck'),
                $this->createKPICard('delivered_parcels', 'Delivered', $deliveredParcels, 'This period', 'fas fa-check-circle', ['value' => 15.2, 'direction' => 'up'], 'success'),
                $this->createKPICard('pending_parcels', 'Pending', $pendingParcels, 'Current', 'fas fa-clock', null, 'warning'),
            ],
            'workflowQueue' => $this->getWorkflowItems(),
            'statements' => [
                'deliveryMan' => [
                    'income' => DeliverymanStatement::where('type', StatementType::INCOME)->whereBetween('updated_at', $fromTo)->sum('amount'),
                    'expense' => DeliverymanStatement::where('type', StatementType::EXPENSE)->whereBetween('updated_at', $fromTo)->sum('amount'),
                    'balance' => 0,
                    'currency' => '$'
                ],
                'merchant' => [
                    'income' => MerchantStatement::where('type', StatementType::INCOME)->whereBetween('updated_at', $fromTo)->sum('amount'),
                    'expense' => MerchantStatement::where('type', StatementType::EXPENSE)->whereBetween('updated_at', $fromTo)->sum('amount'),
                    'balance' => 0,
                    'currency' => '$'
                ],
                'hub' => [
                    'income' => HubStatement::where('type', StatementType::INCOME)->whereBetween('updated_at', $fromTo)->sum('amount'),
                    'expense' => HubStatement::where('type', StatementType::EXPENSE)->whereBetween('updated_at', $fromTo)->sum('amount'),
                    'balance' => 0,
                    'currency' => '$'
                ]
            ],
            'charts' => $this->getAdminCharts($fromTo),
            'quickActions' => [
                ['id' => 'manage_users', 'title' => 'Manage Users', 'icon' => 'fas fa-users-cog', 'url' => '/admin/users', 'badge' => null],
                ['id' => 'system_reports', 'title' => 'System Reports', 'icon' => 'fas fa-chart-line', 'url' => '/admin/reports', 'badge' => null],
                ['id' => 'support_center', 'title' => 'Support Center', 'icon' => 'fas fa-headset', 'url' => '/admin/support', 'badge' => 5],
            ]
        ];
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
    private function getMerchantCharts($fromTo)
    {
        $merchantId = Auth::user()->merchant->id;
        $dates = [];
        $parcelData = [];

        // Last 7 days data
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dates[] = $date;

            $delivered = Parcel::where('merchant_id', $merchantId)
                ->where('status', ParcelStatus::DELIVERED)
                ->whereDate('updated_at', $date)
                ->count();

            $pending = Parcel::where('merchant_id', $merchantId)
                ->where('status', ParcelStatus::PENDING)
                ->whereDate('updated_at', $date)
                ->count();

            $parcelData[] = [
                'label' => $date,
                'value' => $delivered,
                'category' => 'delivered'
            ];
            $parcelData[] = [
                'label' => $date,
                'value' => $pending,
                'category' => 'pending'
            ];
        }

        return [
            'incomeExpense' => [
                'title' => 'Parcel Status Trend',
                'type' => 'line',
                'data' => $parcelData,
                'loading' => false
            ]
        ];
    }

    /**
     * Get admin charts
     */
    private function getAdminCharts($fromTo)
    {
        $dates = $this->repo->Dates(new Request());
        $incomeData = $this->repo->income($fromTo);
        $expenseData = $this->repo->expense($fromTo);

        $chartData = [];
        foreach ($dates as $index => $date) {
            $chartData[] = [
                'label' => $date,
                'value' => $incomeData[$index] ?? 0,
                'category' => 'income'
            ];
            $chartData[] = [
                'label' => $date,
                'value' => $expenseData[$index] ?? 0,
                'category' => 'expense'
            ];
        }

        return [
            'incomeExpense' => [
                'title' => 'Income vs Expense',
                'type' => 'bar',
                'data' => $chartData,
                'loading' => false
            ]
        ];
    }

    /**
     * Get workflow items
     */
    private function getWorkflowItems()
    {
        // Mock workflow items - in real implementation, this would come from a workflow system
        return [
            [
                'id' => 'pending_payments',
                'title' => 'Process Pending Payments',
                'description' => '12 payment requests awaiting approval',
                'status' => 'pending',
                'priority' => 3,
                'assignedTo' => 'Finance Team',
                'dueDate' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'actionUrl' => '/payments/pending'
            ],
            [
                'id' => 'delivery_assignments',
                'title' => 'Assign Delivery Personnel',
                'description' => '28 parcels need delivery assignment',
                'status' => 'in_progress',
                'priority' => 4,
                'assignedTo' => 'Operations',
                'dueDate' => Carbon::now()->addHours(4)->format('Y-m-d'),
                'actionUrl' => '/deliveries/assign'
            ],
            [
                'id' => 'customer_support',
                'title' => 'Resolve Customer Tickets',
                'description' => '15 open support tickets',
                'status' => 'delayed',
                'priority' => 5,
                'assignedTo' => 'Support Team',
                'dueDate' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'actionUrl' => '/support/tickets'
            ]
        ];
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