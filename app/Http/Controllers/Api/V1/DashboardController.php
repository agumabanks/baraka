<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\Task;
use App\Models\Support;
use App\Models\Payment;
use App\Models\DeliveryMan;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard KPIs and analytics endpoints"
 * )
 */
class DashboardController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/kpis",
     *     summary="Get dashboard KPIs",
     *     description="Retrieve comprehensive KPIs for dashboard display",
     *     operationId="getDashboardKPIs",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="date_range",
     *         in="query",
     *         description="Date range: today, 7d, 30d, custom",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "7d", "30d", "custom"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Start date for custom range (Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="End date for custom range (Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         description="Filter by branch ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="courier_id",
     *         in="query",
     *         description="Filter by courier ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="KPIs retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="sla_status", type="object"),
     *                 @OA\Property(property="exceptions", type="object"),
     *                 @OA\Property(property="on_time_delivery_7d", type="object"),
     *                 @OA\Property(property="open_tickets", type="object"),
     *                 @OA\Property(property="cash_collected_7d", type="object"),
     *                 @OA\Property(property="today_queue", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function kpis(Request $request)
    {
        $cacheKey = $this->buildCacheKey('kpis', $request->all());
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($request) {
            $dateRange = $this->parseDateRange($request);
            $filters = $this->buildFilters($request);

            return $this->responseWithSuccess('KPIs retrieved', [
                'sla_status' => $this->getSLAStatus($dateRange, $filters),
                'exceptions' => $this->getExceptions($filters),
                'on_time_delivery_7d' => $this->getOnTimeDelivery7d($filters),
                'open_tickets' => $this->getOpenTickets(),
                'cash_collected_7d' => $this->getCashCollected7d($filters),
                'today_queue' => $this->getTodayQueue($filters),
            ], 200);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/statements",
     *     summary="Get financial statements",
     *     description="Retrieve income/expense/balance for delivery man, merchant, or hub",
     *     operationId="getDashboardStatements",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="entity_type",
     *         in="query",
     *         description="Entity type: delivery_man, merchant, hub",
     *         required=true,
     *         @OA\Schema(type="string", enum={"delivery_man", "merchant", "hub"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Start date (Y-m-d)",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="End date (Y-m-d)",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Statements retrieved successfully"
     *     )
     * )
     */
    public function statements(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|in:delivery_man,merchant,hub',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $cacheKey = $this->buildCacheKey('statements', $request->all());
        $cacheTtl = 600; // 10 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($request) {
            $entityType = $request->entity_type;
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $dateTo = Carbon::parse($request->date_to)->endOfDay();

            $data = $this->getEntityStatement($entityType, $dateFrom, $dateTo);

            return $this->responseWithSuccess('Statements retrieved', [
                'entity_type' => $entityType,
                'income' => $data['income'],
                'expense' => $data['expense'],
                'balance' => $data['income'] - $data['expense'],
                'transactions' => $data['transactions'],
            ], 200);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/charts/income-expense",
     *     summary="Get income/expense chart data",
     *     description="Retrieve chart data for income vs expense over time",
     *     operationId="getIncomeExpenseChart",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="date_range",
     *         in="query",
     *         description="Date range: 7d, 30d, 90d",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d"}, default="7d")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Chart data retrieved successfully"
     *     )
     * )
     */
    public function incomeExpenseChart(Request $request)
    {
        $dateRange = $request->get('date_range', '7d');
        $days = (int) str_replace('d', '', $dateRange);

        $cacheKey = "dashboard:charts:income_expense:{$days}";
        $cacheTtl = 600; // 10 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($days) {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays($days - 1);

            $income = [];
            $expense = [];
            $labels = [];

            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $labels[] = $date->format('M j');

                // Get income for this date (simplified - adjust based on your payment model)
                $dayIncome = Payment::whereDate('created_at', $date)
                    ->where('type', 'income')
                    ->sum('amount') ?? 0;

                // Get expense for this date
                $dayExpense = Payment::whereDate('created_at', $date)
                    ->where('type', 'expense')
                    ->sum('amount') ?? 0;

                $income[] = (float) $dayIncome;
                $expense[] = (float) $dayExpense;
            }

            $net = array_map(function ($inc, $exp) {
                return $inc - $exp;
            }, $income, $expense);

            return $this->responseWithSuccess('Chart data retrieved', [
                'income' => $income,
                'expense' => $expense,
                'net' => $net,
                'labels' => $labels,
            ], 200);
        });
    }

    // Private helper methods

    private function buildCacheKey($endpoint, $params)
    {
        ksort($params);
        $paramString = http_build_query($params);
        return "dashboard:{$endpoint}:" . md5($paramString);
    }

    private function parseDateRange(Request $request)
    {
        $range = $request->get('date_range', 'today');

        switch ($range) {
            case 'today':
                return [
                    'from' => Carbon::today(),
                    'to' => Carbon::today()->endOfDay(),
                ];
            case '7d':
                return [
                    'from' => Carbon::now()->subDays(6)->startOfDay(),
                    'to' => Carbon::now()->endOfDay(),
                ];
            case '30d':
                return [
                    'from' => Carbon::now()->subDays(29)->startOfDay(),
                    'to' => Carbon::now()->endOfDay(),
                ];
            case 'custom':
                return [
                    'from' => $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subDays(6)->startOfDay(),
                    'to' => $request->date_to ? Carbon::parse($request->date_to) : Carbon::now()->endOfDay(),
                ];
            default:
                return [
                    'from' => Carbon::today(),
                    'to' => Carbon::today()->endOfDay(),
                ];
        }
    }

    private function buildFilters(Request $request)
    {
        return [
            'branch_id' => $request->branch_id,
            'courier_id' => $request->courier_id,
        ];
    }

    private function getSLAStatus($dateRange, $filters)
    {
        $query = Parcel::whereBetween('created_at', [$dateRange['from'], $dateRange['to']]);

        if ($filters['branch_id']) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if ($filters['courier_id']) {
            $query->where('courier_id', $filters['courier_id']);
        }

        $totalParcels = $query->count();
        $onTimeParcels = $query->where('status', 'delivered')
            ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, delivered_at) <= promised_time')
            ->count();

        $percentage = $totalParcels > 0 ? round(($onTimeParcels / $totalParcels) * 100, 1) : 0;

        return [
            'percentage' => $percentage,
            'on_time' => $onTimeParcels,
            'total' => $totalParcels,
            'change_7d' => 0, // TODO: Calculate change from previous period
        ];
    }

    private function getExceptions($filters)
    {
        $query = Parcel::whereIn('status', ['exception', 'failed_delivery', 'damaged', 'lost']);

        if ($filters['branch_id']) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if ($filters['courier_id']) {
            $query->where('courier_id', $filters['courier_id']);
        }

        $count = $query->count();

        return [
            'count' => $count,
            'change_24h' => 0, // TODO: Calculate change from yesterday
            'by_type' => [
                'failed_delivery' => Parcel::where('status', 'failed_delivery')->count(),
                'damaged' => Parcel::where('status', 'damaged')->count(),
                'lost' => Parcel::where('status', 'lost')->count(),
                'address_issue' => Parcel::where('status', 'address_issue')->count(),
            ],
        ];
    }

    private function getOnTimeDelivery7d($filters)
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(6);

        $query = Parcel::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        if ($filters['branch_id']) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if ($filters['courier_id']) {
            $query->where('courier_id', $filters['courier_id']);
        }

        $totalDelivered = $query->count();
        $onTimeDelivered = $query->whereRaw('TIMESTAMPDIFF(HOUR, created_at, delivered_at) <= promised_time')
            ->count();

        $percentage = $totalDelivered > 0 ? round(($onTimeDelivered / $totalDelivered) * 100, 1) : 0;

        // Generate sparkline data (simplified)
        $sparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayPercentage = rand(85, 98); // TODO: Calculate actual daily percentages
            $sparkline[] = $dayPercentage;
        }

        return [
            'percentage' => $percentage,
            'sparkline' => $sparkline,
            'trend' => 'stable', // TODO: Calculate trend (up/down/stable)
        ];
    }

    private function getOpenTickets()
    {
        $urgent = Support::where('status', 'open')->where('priority', 'urgent')->count();
        $high = Support::where('status', 'open')->where('priority', 'high')->count();
        $normal = Support::where('status', 'open')->where('priority', 'normal')->count();

        return [
            'total' => $urgent + $high + $normal,
            'urgent' => $urgent,
            'high' => $high,
            'normal' => $normal,
        ];
    }

    private function getCashCollected7d($filters)
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(6);

        // Simplified - adjust based on your COD/payment model
        $expected = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'cod_expected')
            ->sum('amount') ?? 0;

        $collected = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'cod_collected')
            ->sum('amount') ?? 0;

        $variance = $expected > 0 ? round((($collected - $expected) / $expected) * 100, 1) : 0;

        // Generate daily breakdown
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayExpected = rand(15000, 25000); // TODO: Calculate actual daily amounts
            $dayCollected = rand(14000, 24000);
            $daily[] = [
                'date' => $date->format('Y-m-d'),
                'expected' => $dayExpected,
                'collected' => $dayCollected,
            ];
        }

        return [
            'expected' => $expected,
            'collected' => $collected,
            'variance' => $variance,
            'daily' => $daily,
        ];
    }

    private function getTodayQueue($filters)
    {
        $today = Carbon::today();

        $pendingPickup = Parcel::whereDate('created_at', $today)
            ->where('status', 'pending_pickup');

        $outForDelivery = Parcel::whereDate('created_at', $today)
            ->where('status', 'out_for_delivery');

        if ($filters['branch_id']) {
            $pendingPickup->where('branch_id', $filters['branch_id']);
            $outForDelivery->where('branch_id', $filters['branch_id']);
        }

        if ($filters['courier_id']) {
            $pendingPickup->where('courier_id', $filters['courier_id']);
            $outForDelivery->where('courier_id', $filters['courier_id']);
        }

        $pendingCount = $pendingPickup->count();
        $deliveryCount = $outForDelivery->count();

        // Get top 5 pending parcels
        $topPending = $pendingPickup->with(['customer', 'branch'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($parcel) {
                return [
                    'id' => $parcel->id,
                    'tracking_number' => $parcel->tracking_number,
                    'customer' => $parcel->customer?->name ?? 'Unknown',
                    'branch' => $parcel->branch?->name ?? 'Unknown',
                ];
            });

        return [
            'pending_pickup' => $pendingCount,
            'out_for_delivery' => $deliveryCount,
            'top_pending' => $topPending,
        ];
    }

    private function getEntityStatement($entityType, $dateFrom, $dateTo)
    {
        // Simplified - adjust based on your financial models
        $income = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('type', 'income')
            ->sum('amount') ?? 0;

        $expense = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('type', 'expense')
            ->sum('amount') ?? 0;

        $transactions = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'date' => $payment->created_at->format('Y-m-d'),
                    'type' => $payment->type,
                    'amount' => $payment->amount,
                    'description' => $payment->description ?? 'Payment',
                ];
            });

        return [
            'income' => (float) $income,
            'expense' => (float) $expense,
            'transactions' => $transactions,
        ];
    }
}