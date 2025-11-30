<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Finance\CodManagementService;
use App\Services\Finance\MerchantSettlementService;
use App\Services\Finance\CurrencyService;
use App\Models\CodCollection;
use App\Models\MerchantSettlement;
use App\Models\Customer;
use App\Models\DriverCashAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EnhancedFinanceController extends Controller
{
    protected CodManagementService $codService;
    protected MerchantSettlementService $settlementService;
    protected CurrencyService $currencyService;

    public function __construct(
        CodManagementService $codService,
        MerchantSettlementService $settlementService,
        CurrencyService $currencyService
    ) {
        $this->codService = $codService;
        $this->settlementService = $settlementService;
        $this->currencyService = $currencyService;
    }

    /**
     * Finance dashboard
     */
    public function dashboard(Request $request)
    {
        return view('admin.finance.dashboard');
    }

    /**
     * Get finance dashboard data
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $dateRange = $this->getDateRange($request);

        $codSummary = $this->codService->getCodSummary([
            'branch_id' => $branchId,
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end'],
        ]);

        $settlementStats = $this->settlementService->getSettlementStats([
            'branch_id' => $branchId,
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'cod' => $codSummary,
                'settlements' => $settlementStats,
                'currencies' => $this->currencyService->getAllRates(),
            ],
        ]);
    }

    // ==================== COD Management ====================

    /**
     * COD collections list
     */
    public function codCollections(Request $request)
    {
        $status = $request->input('status');
        $branchId = $request->input('branch_id');

        $query = CodCollection::with(['shipment', 'collector', 'branch'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at');

        $collections = $query->paginate(50);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $collections,
            ]);
        }

        return view('admin.finance.cod-collections', compact('collections'));
    }

    /**
     * Record COD collection
     */
    public function recordCollection(Request $request): JsonResponse
    {
        $request->validate([
            'collection_id' => 'required|exists:cod_collections,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string',
            'reference' => 'nullable|string',
        ]);

        $collection = CodCollection::findOrFail($request->collection_id);

        try {
            $result = $this->codService->recordCollection(
                $collection,
                $request->amount,
                auth()->id(),
                $request->method,
                $request->reference
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify COD collection
     */
    public function verifyCollection(Request $request, CodCollection $collection): JsonResponse
    {
        try {
            $result = $this->codService->verifyCollection($collection, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get collections needing verification
     */
    public function collectionsNeedingVerification(Request $request): JsonResponse
    {
        $collections = $this->codService->getCollectionsNeedingVerification(
            $request->input('branch_id')
        );

        return response()->json([
            'success' => true,
            'data' => $collections,
        ]);
    }

    /**
     * Get COD discrepancies
     */
    public function codDiscrepancies(Request $request): JsonResponse
    {
        $discrepancies = $this->codService->getDiscrepancies(
            $request->input('branch_id')
        );

        return response()->json([
            'success' => true,
            'data' => $discrepancies,
        ]);
    }

    /**
     * Record driver remittance
     */
    public function recordRemittance(Request $request): JsonResponse
    {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
            'collection_ids' => 'required|array',
            'collection_ids.*' => 'exists:cod_collections,id',
            'total_amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string',
        ]);

        try {
            $result = $this->codService->recordRemittance(
                $request->collection_ids,
                $request->driver_id,
                $request->total_amount,
                $request->reference
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get driver pending collections
     */
    public function driverPendingCollections(Request $request, int $driverId): JsonResponse
    {
        $collections = $this->codService->getDriverPendingCollections($driverId);
        $performance = $this->codService->getDriverCodPerformance($driverId);

        return response()->json([
            'success' => true,
            'data' => [
                'collections' => $collections,
                'performance' => $performance,
            ],
        ]);
    }

    /**
     * Get driver cash accounts
     */
    public function driverCashAccounts(Request $request): JsonResponse
    {
        $accounts = DriverCashAccount::with('driver')
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    // ==================== Merchant Settlements ====================

    /**
     * Settlements list
     */
    public function settlements(Request $request)
    {
        $status = $request->input('status');
        $merchantId = $request->input('merchant_id');

        $query = MerchantSettlement::with('merchant')
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($merchantId, fn($q) => $q->where('merchant_id', $merchantId))
            ->orderByDesc('created_at');

        $settlements = $query->paginate(50);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $settlements,
            ]);
        }

        return view('admin.finance.settlements', compact('settlements'));
    }

    /**
     * Generate settlement for merchant
     */
    public function generateSettlement(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|exists:customers,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        try {
            $settlement = $this->settlementService->generateSettlement(
                $request->merchant_id,
                Carbon::parse($request->period_start),
                Carbon::parse($request->period_end),
                $request->input('branch_id')
            );

            return response()->json([
                'success' => true,
                'data' => $settlement,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Submit settlement for approval
     */
    public function submitSettlement(MerchantSettlement $settlement): JsonResponse
    {
        try {
            $result = $this->settlementService->submitForApproval($settlement);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approve settlement
     */
    public function approveSettlement(MerchantSettlement $settlement): JsonResponse
    {
        try {
            $result = $this->settlementService->approveSettlement($settlement, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Process settlement payment
     */
    public function paySettlement(Request $request, MerchantSettlement $settlement): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
            'payment_reference' => 'required|string',
        ]);

        try {
            $result = $this->settlementService->processPayment(
                $settlement,
                $request->payment_method,
                $request->payment_reference,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get settlement statement
     */
    public function settlementStatement(MerchantSettlement $settlement): JsonResponse
    {
        $statement = $this->settlementService->generateStatement($settlement);

        return response()->json([
            'success' => true,
            'data' => $statement,
        ]);
    }

    /**
     * Get merchant balance
     */
    public function merchantBalance(Customer $customer): JsonResponse
    {
        $balance = $this->settlementService->getMerchantBalance($customer->id);
        $settlements = $this->settlementService->getMerchantSettlements($customer->id, ['limit' => 10]);

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'recent_settlements' => $settlements,
            ],
        ]);
    }

    /**
     * Get pending settlements
     */
    public function pendingSettlements(Request $request): JsonResponse
    {
        $settlements = $this->settlementService->getPendingSettlements(
            $request->input('branch_id')
        );

        return response()->json([
            'success' => true,
            'data' => $settlements,
        ]);
    }

    // ==================== Currency Management ====================

    /**
     * Get exchange rates
     */
    public function exchangeRates(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->currencyService->getAllRates(),
        ]);
    }

    /**
     * Set exchange rate
     */
    public function setExchangeRate(Request $request): JsonResponse
    {
        $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0',
        ]);

        $rate = $this->currencyService->setRate(
            $request->from_currency,
            $request->to_currency,
            $request->rate
        );

        return response()->json([
            'success' => true,
            'data' => $rate,
        ]);
    }

    /**
     * Convert currency
     */
    public function convertCurrency(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        try {
            $result = $this->currencyService->convert(
                $request->amount,
                $request->from,
                $request->to
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update rates from API
     */
    public function updateRates(): JsonResponse
    {
        $result = $this->currencyService->updateRatesFromApi();

        return response()->json($result);
    }

    /**
     * Get date range from request
     */
    protected function getDateRange(Request $request): array
    {
        $preset = $request->input('preset', 'last_30_days');

        if ($request->has('start_date') && $request->has('end_date')) {
            return [
                'start' => Carbon::parse($request->start_date)->startOfDay(),
                'end' => Carbon::parse($request->end_date)->endOfDay(),
            ];
        }

        return match ($preset) {
            'today' => ['start' => now()->startOfDay(), 'end' => now()->endOfDay()],
            'last_7_days' => ['start' => now()->subDays(7)->startOfDay(), 'end' => now()->endOfDay()],
            'last_30_days' => ['start' => now()->subDays(30)->startOfDay(), 'end' => now()->endOfDay()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            default => ['start' => now()->subDays(30)->startOfDay(), 'end' => now()->endOfDay()],
        };
    }
}
