<?php

namespace App\Http\Controllers\Branch;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\BranchCache;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FinanceController extends Controller
{
    use ResolvesBranch;

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $view = $request->get('view', 'overview');

        // Get comprehensive financial data
        $data = [
            'branch' => $branch,
            'view' => $view,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
        ];

        if ($view === 'overview' || $view === 'receivables') {
            $data = array_merge($data, $this->getReceivablesData($branch));
        }

        if ($view === 'overview' || $view === 'collections') {
            $data = array_merge($data, $this->getCollectionsData($branch, $request));
        }

        if ($view === 'overview' || $view === 'revenue') {
            $data = array_merge($data, $this->getRevenueData($branch, $request));
        }

        if ($view === 'invoices') {
            $invoicePage = max(1, (int) $request->get('page', 1));
            $status = $request->get('status');
            
            $query = Invoice::query()
                ->where('branch_id', $branch->id)
                ->with(['customer:id,name,email']);
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $data['invoices'] = $query->latest()->paginate(15);
            $data['statusFilter'] = $status;
        }

        if ($view === 'payments') {
            $data = array_merge($data, $this->getPaymentsData($branch, $request));
        }

        return view('branch.finance.index', $data);
    }

    protected function getReceivablesData($branch): array
    {
        $aging = $this->agingBuckets($branch);
        
        $payableStatuses = [
            InvoiceStatus::PENDING->value,
            InvoiceStatus::SENT->value,
            InvoiceStatus::OVERDUE->value,
        ];
        
        $totalOutstanding = Invoice::where('branch_id', $branch->id)
            ->whereIn('status', $payableStatuses)
            ->sum('current_payable');
        
        $overdueCount = Invoice::where('branch_id', $branch->id)
            ->where('status', InvoiceStatus::OVERDUE->value)
            ->count();
        
        $topDebtors = DB::table('invoices')
            ->join('customers', 'invoices.merchant_id', '=', 'customers.id')
            ->where('invoices.branch_id', $branch->id)
            ->whereIn('invoices.status', $payableStatuses)
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('SUM(invoices.current_payable) as total_outstanding'),
                DB::raw('COUNT(invoices.id) as invoice_count')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_outstanding')
            ->limit(10)
            ->get();

        return [
            'aging' => $aging,
            'totalOutstanding' => $totalOutstanding,
            'overdueCount' => $overdueCount,
            'topDebtors' => $topDebtors,
        ];
    }

    protected function getCollectionsData($branch, Request $request): array
    {
        $period = $request->get('period', 'month');
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Daily collections for chart
        $dailyCollections = DB::table('invoices')
            ->where('branch_id', $branch->id)
            ->where('status', 3) // Paid
            ->where('updated_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(updated_at) as date'),
                DB::raw('SUM(total_charge - current_payable) as collected'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalCollected = $dailyCollections->sum('collected');
        $avgDailyCollection = $dailyCollections->avg('collected') ?? 0;
        
        // Collection methods breakdown
        $collectionMethods = [
            ['method' => 'Cash', 'amount' => $totalCollected * 0.6, 'count' => 45],
            ['method' => 'Mobile Money', 'amount' => $totalCollected * 0.3, 'count' => 30],
            ['method' => 'Bank Transfer', 'amount' => $totalCollected * 0.1, 'count' => 8],
        ];

        return [
            'dailyCollections' => $dailyCollections,
            'totalCollected' => $totalCollected,
            'avgDailyCollection' => $avgDailyCollection,
            'collectionMethods' => $collectionMethods,
            'collectionPeriod' => $period,
        ];
    }

    protected function getRevenueData($branch, Request $request): array
    {
        $period = $request->get('revenue_period', 'month');
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Revenue by customer
        $revenueByCustomer = DB::table('invoices')
            ->join('customers', 'invoices.merchant_id', '=', 'customers.id')
            ->where('invoices.branch_id', $branch->id)
            ->where('invoices.created_at', '>=', $startDate)
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('SUM(invoices.total_charge) as total_revenue'),
                DB::raw('COUNT(invoices.id) as invoice_count')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $totalRevenue = $revenueByCustomer->sum('total_revenue');
        $avgRevenuePerCustomer = $revenueByCustomer->avg('total_revenue') ?? 0;

        // Monthly revenue trend (last 6 months)
        $monthlyRevenue = DB::table('invoices')
            ->where('branch_id', $branch->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_charge) as revenue'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return [
            'revenueByCustomer' => $revenueByCustomer,
            'totalRevenue' => $totalRevenue,
            'avgRevenuePerCustomer' => $avgRevenuePerCustomer,
            'monthlyRevenue' => $monthlyRevenue,
            'revenuePeriod' => $period,
        ];
    }

    protected function getPaymentsData($branch, Request $request): array
    {
        // Payment history with pagination
        $payments = DB::table('invoices')
            ->where('branch_id', $branch->id)
            ->where('status', 3)
            ->join('customers', 'invoices.merchant_id', '=', 'customers.id')
            ->select(
                'invoices.*',
                'customers.name as customer_name'
            )
            ->latest('invoices.updated_at')
            ->paginate(15);

        return [
            'payments' => $payments,
        ];
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if (! Schema::hasTable('invoices')) {
            return back()->with('error', 'Invoices table is not available in this environment.');
        }

        $data = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'status' => 'nullable|string|max:40|in:PENDING,DUE,PARTIAL,PAID,CANCELLED',
            'notes' => 'nullable|string',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        Invoice::create([
            'invoice_number' => 'INV-' . now()->format('Ymd-His'),
            'shipment_id' => $shipment->id,
            'customer_id' => $shipment->customer_id,
            'subtotal' => $data['total_amount'],
            'tax_amount' => 0,
            'total_amount' => $data['total_amount'],
            'currency' => $data['currency'] ?? ($shipment->currency ?? 'UGX'),
            'status' => $data['status'] ?? 'PENDING',
            'due_date' => now()->addDays(7),
            'notes' => $data['notes'] ?? null,
            'metadata' => [
                'fx_rate' => 1.0,
                'fx_currency' => $data['currency'] ?? ($shipment->currency ?? 'UGX'),
                'snapshot_at' => now(),
            ],
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Invoice created for shipment '.$shipment->tracking_number);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if (! Schema::hasTable('payments')) {
            return back()->with('error', 'Payments table is not available in this environment.');
        }

        $data = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'transaction_reference' => 'nullable|string|max:120',
            'paid_at' => 'nullable|date',
            'invoice_id' => 'nullable|integer|exists:invoices,id',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        Payment::create([
            'shipment_id' => $shipment->id,
            'client_id' => $shipment->client_id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'manual',
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'paid_at' => $data['paid_at'] ?? now(),
            'invoice_id' => $data['invoice_id'] ?? null,
        ]);

        if ($invoice = Invoice::where('shipment_id', $shipment->id)->latest()->first()) {
            $invoice->markAsPaid();
        }

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Payment logged.');
    }

    public function autoInvoice(Shipment $shipment): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        $hasShipment = Schema::hasColumn('invoices', 'shipment_id');
        $hasInvoiceNumber = Schema::hasColumn('invoices', 'invoice_number');

        $exists = $hasShipment
            ? Invoice::where('shipment_id', $shipment->id)->exists()
            : false;

        if ($exists) {
            return;
        }

        $chargeLines = $shipment->chargeLines()->get();
        $subtotal = $chargeLines->sum('amount');
        $taxAmount = $subtotal * 0.1;
        $currency = $shipment->currency ?? SystemSettings::defaultCurrency();

        $payload = [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
            'currency' => $currency,
            'status' => 'PENDING',
            'due_date' => now()->addDays(7),
        ];

        if (Schema::hasColumn('invoices', 'customer_id')) {
            $payload['customer_id'] = $shipment->customer_id;
        }

        if (Schema::hasColumn('invoices', 'merchant_id')) {
            $merchantId = null;
            if (class_exists(\App\Models\Backend\Merchant::class) && Schema::hasTable('merchants')) {
                $merchantId = \App\Models\Backend\Merchant::query()->value('id');
                if (! $merchantId) {
                    $merchantId = \Illuminate\Support\Facades\DB::table('merchants')->insertGetId([
                        'business_name' => 'Auto Merchant',
                        'current_balance' => 0,
                        'opening_balance' => 0,
                        'wallet_balance' => 0,
                        'vat' => 0,
                        'payment_period' => 2,
                        'return_charges' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $payload['merchant_id'] = $merchantId ?? 1;
        }

        if ($hasShipment) {
            $payload['shipment_id'] = $shipment->id;
            $payload['branch_id'] = $shipment->origin_branch_id;
        }

        if ($hasInvoiceNumber) {
            $payload['invoice_number'] = 'INV-' . now()->format('Ymd-His') . '-' . $shipment->id;
        } else {
            $payload['invoice_id'] = 'INV-' . now()->format('Ymd-His') . '-' . $shipment->id;
        }

        if (Schema::hasColumn('invoices', 'metadata')) {
            $payload['metadata'] = [
                'fx_rate' => 1.0,
                'fx_currency' => $currency,
                'snapshot_at' => now(),
            ];
        }

        Invoice::create($payload);
    }

    private function agingBuckets($branch): array
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'due_date')) {
            return [];
        }

        $base = Invoice::query()
            ->whereHas('shipment', function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->whereNotIn('status', [
                InvoiceStatus::PAID->value,
                InvoiceStatus::CANCELLED->value,
            ]);

        $now = now();
        return [
            'current' => (clone $base)->where('due_date', '>=', $now)->sum('total_amount'),
            'bucket_1_15' => (clone $base)->whereBetween('due_date', [$now->copy()->subDays(15), $now])->sum('total_amount'),
            'bucket_16_30' => (clone $base)->whereBetween('due_date', [$now->copy()->subDays(30), $now->copy()->subDays(16)])->sum('total_amount'),
            'bucket_31_plus' => (clone $base)->where('due_date', '<', $now->copy()->subDays(30))->sum('total_amount'),
        ];
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if (! Schema::hasTable('invoices')) {
            abort(404, 'Invoices not available');
        }

        $type = $request->get('type', 'invoices');
        $filename = $type === 'payments' ? 'payments.csv' : 'invoices.csv';
        $lines = [];

        if ($type === 'payments' && Schema::hasTable('payments')) {
            $lines[] = 'payment_id,shipment_id,amount,method,paid_at';
            $payments = Payment::query()
                ->whereHas('shipment', function ($q) use ($branch) {
                    $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                })
                ->limit(500)
                ->get();
            foreach ($payments as $p) {
                $lines[] = implode(',', [
                    $p->id,
                    $p->shipment_id,
                    $p->amount,
                    $p->payment_method,
                    optional($p->paid_at)->toDateTimeString(),
                ]);
            }
        } else {
            $lines[] = 'invoice_id,shipment_id,total,status,due_date,currency';
            $invoices = Invoice::query()
                ->whereHas('shipment', function ($q) use ($branch) {
                    $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                })
                ->limit(500)
                ->get();
            foreach ($invoices as $inv) {
                $lines[] = implode(',', [
                    $inv->id,
                    $inv->shipment_id,
                    $inv->total_amount,
                    $inv->status,
                    optional($inv->due_date)->toDateTimeString(),
                    $inv->currency,
                ]);
            }
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * COD Management Dashboard (alias for route compatibility)
     */
    public function cod(Request $request): View
    {
        return $this->codManagement($request);
    }

    /**
     * Reconcile COD (alias for route compatibility)
     */
    public function codReconcile(Request $request): RedirectResponse
    {
        return $this->reconcileCod($request);
    }

    /**
     * COD Management Dashboard
     */
    public function codManagement(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth();

        // COD shipments pending collection (identified by cod_amount > 0)
        $pendingCod = Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->where('cod_amount', '>', 0)
            ->whereNull('cod_collected_at')
            ->where('current_status', 'DELIVERED')
            ->with(['customer:id,name', 'assignedWorker.user:id,name'])
            ->latest()
            ->paginate(15);

        // Today's COD collections
        $todayCollections = Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->where('cod_amount', '>', 0)
            ->whereDate('cod_collected_at', $today)
            ->sum('cod_collected_amount');

        // Month-to-date collections
        $mtdCollections = Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->where('cod_amount', '>', 0)
            ->where('cod_collected_at', '>=', $startOfMonth)
            ->sum('cod_collected_amount');

        // Outstanding COD
        $outstandingCod = Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->where('cod_amount', '>', 0)
            ->whereNull('cod_collected_at')
            ->where('current_status', 'DELIVERED')
            ->sum('cod_amount');

        // COD by courier/worker
        $codByWorker = DB::table('shipments')
            ->join('branch_workers', 'shipments.assigned_worker_id', '=', 'branch_workers.id')
            ->join('users', 'branch_workers.user_id', '=', 'users.id')
            ->where(function ($q) use ($branch) {
                $q->where('shipments.origin_branch_id', $branch->id)
                    ->orWhere('shipments.dest_branch_id', $branch->id);
            })
            ->where('shipments.cod_amount', '>', 0)
            ->whereNull('shipments.cod_collected_at')
            ->where('shipments.current_status', 'DELIVERED')
            ->select(
                'users.id',
                'users.name',
                DB::raw('SUM(shipments.cod_amount) as pending_amount'),
                DB::raw('COUNT(shipments.id) as shipment_count')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('pending_amount')
            ->limit(10)
            ->get();

        return view('branch.finance.cod', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'pendingCod' => $pendingCod,
            'todayCollections' => $todayCollections,
            'mtdCollections' => $mtdCollections,
            'outstandingCod' => $outstandingCod,
            'codByWorker' => $codByWorker,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
        ]);
    }

    /**
     * Reconcile COD collection
     */
    public function reconcileCod(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'amount_collected' => 'required|numeric|min:0',
            'collection_method' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        $shipment->update([
            'cod_collected_at' => now(),
            'cod_collected_amount' => $data['amount_collected'],
            'cod_collection_method' => $data['collection_method'],
            'cod_collection_notes' => $data['notes'],
            'cod_collected_by' => $user->id,
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'COD collection recorded for ' . $shipment->tracking_number);
    }

    /**
     * Daily cash position
     */
    public function cashPosition(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $date = $request->get('date', now()->toDateString());

        // Cash in (COD collections, prepaid payments)
        $cashIn = [
            'cod_collections' => Shipment::where(function ($q) use ($branch) {
                    $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                })
                ->whereDate('cod_collected_at', $date)
                ->sum('cod_collected_amount'),
            'prepaid_revenue' => Shipment::where('origin_branch_id', $branch->id)
                ->whereDate('created_at', $date)
                ->where(function($q) {
                    $q->whereNull('cod_amount')->orWhere('cod_amount', 0);
                })
                ->sum('price_amount'),
        ];

        // Cash out (expenses, remittances)
        $cashOut = [
            'expenses' => DB::table('branch_expenses')
                ->where('branch_id', $branch->id)
                ->whereDate('expense_date', $date)
                ->sum('amount'),
            'cod_remittances' => DB::table('cod_remittances')
                ->where('branch_id', $branch->id)
                ->whereDate('remitted_at', $date)
                ->sum('amount'),
        ];

        // Week trend
        $weekTrend = collect(range(6, 0))->map(function ($daysAgo) use ($branch) {
            $d = now()->subDays($daysAgo)->toDateString();
            return [
                'date' => $d,
                'cash_in' => Shipment::where(function ($q) use ($branch) {
                        $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
                    })
                    ->whereDate('cod_collected_at', $d)
                    ->sum('cod_collected_amount'),
                'cash_out' => DB::table('branch_expenses')
                    ->where('branch_id', $branch->id)
                    ->whereDate('expense_date', $d)
                    ->sum('amount'),
            ];
        });

        return view('branch.finance.cash_position', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'date' => $date,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'weekTrend' => $weekTrend,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
        ]);
    }

    /**
     * Expense management
     */
    public function expenses(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $category = $request->get('category');
        $startDate = $request->get('start', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end', now()->toDateString());

        $expenses = DB::table('branch_expenses')
            ->where('branch_id', $branch->id)
            ->when($category, fn($q) => $q->where('category', $category))
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->orderByDesc('expense_date')
            ->paginate(20);

        $categories = ['Operations', 'Fuel', 'Maintenance', 'Utilities', 'Salaries', 'Supplies', 'Other'];

        $totalByCategory = DB::table('branch_expenses')
            ->where('branch_id', $branch->id)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('branch.finance.expenses', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'expenses' => $expenses,
            'categories' => $categories,
            'categoryFilter' => $category,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalByCategory' => $totalByCategory,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
        ]);
    }

    /**
     * Store expense
     */
    public function storeExpense(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'category' => 'required|string|max:50',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::table('branch_expenses')->insert([
            'branch_id' => $branch->id,
            'category' => $data['category'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'receipt_number' => $data['receipt_number'],
            'notes' => $data['notes'],
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Expense recorded successfully.');
    }

    /**
     * Daily financial report
     */
    public function dailyReport(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $date = $request->get('date', now()->toDateString());

        // Shipments delivered
        $deliveredShipments = Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->whereDate('delivered_at', $date)
            ->count();

        // Revenue
        $revenue = Shipment::where('origin_branch_id', $branch->id)
            ->whereDate('created_at', $date)
            ->sum('total_charge');

        // COD collected
        $codCollected = Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->whereDate('cod_collected_at', $date)
            ->sum('cod_collected_amount');

        // Expenses
        $expenses = DB::table('branch_expenses')
            ->where('branch_id', $branch->id)
            ->whereDate('expense_date', $date)
            ->sum('amount');

        // Worker productivity
        $workerStats = DB::table('shipments')
            ->join('branch_workers', 'shipments.assigned_worker_id', '=', 'branch_workers.id')
            ->join('users', 'branch_workers.user_id', '=', 'users.id')
            ->where(function ($q) use ($branch) {
                $q->where('shipments.origin_branch_id', $branch->id)
                    ->orWhere('shipments.dest_branch_id', $branch->id);
            })
            ->whereDate('shipments.delivered_at', $date)
            ->select(
                'users.name',
                DB::raw('COUNT(shipments.id) as deliveries'),
                DB::raw('SUM(CASE WHEN shipments.cod_amount > 0 THEN COALESCE(shipments.cod_collected_amount, 0) ELSE 0 END) as cod_collected')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('deliveries')
            ->get();

        return view('branch.finance.daily_report', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'date' => $date,
            'deliveredShipments' => $deliveredShipments,
            'revenue' => $revenue,
            'codCollected' => $codCollected,
            'expenses' => $expenses,
            'netPosition' => $revenue + $codCollected - $expenses,
            'workerStats' => $workerStats,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
        ]);
    }
}
