<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\BranchSettlement;
use App\Services\Finance\BranchSettlementService;
use App\Support\SystemSettings;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettlementController extends Controller
{
    use ResolvesBranch;

    public function __construct(
        protected BranchSettlementService $settlementService
    ) {}

    /**
     * Display branch P&L dashboard
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $start = $request->filled('start') 
            ? Carbon::parse($request->start) 
            : now()->startOfMonth();
        $end = $request->filled('end') 
            ? Carbon::parse($request->end) 
            : now()->endOfMonth();

        $summary = $this->settlementService->getBranchSummary($branch->id, $start, $end);

        // Recent settlements
        $settlements = BranchSettlement::where('branch_id', $branch->id)
            ->latest()
            ->take(10)
            ->get();

        // Pending settlement (draft)
        $draftSettlement = BranchSettlement::where('branch_id', $branch->id)
            ->where('status', 'draft')
            ->first();

        return view('branch.settlement_dashboard', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'summary' => $summary,
            'settlements' => $settlements,
            'draftSettlement' => $draftSettlement,
            'start' => $start,
            'end' => $end,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
        ]);
    }

    /**
     * List all settlements for branch
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $status = $request->get('status');

        $settlements = BranchSettlement::where('branch_id', $branch->id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return view('branch.settlements', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'settlements' => $settlements,
            'statusFilter' => $status,
        ]);
    }

    /**
     * Generate new settlement
     */
    public function create(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'currency' => 'nullable|string|size:3',
        ]);

        try {
            $settlement = $this->settlementService->generateSettlement(
                $branch->id,
                Carbon::parse($data['period_start']),
                Carbon::parse($data['period_end']),
                $data['currency'] ?? 'USD'
            );

            return redirect()
                ->route('branch.settlements.show', $settlement)
                ->with('success', 'Settlement generated successfully. Review and submit for approval.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show settlement details
     */
    public function show(Request $request, BranchSettlement $settlement): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Ensure settlement belongs to this branch
        abort_unless($settlement->branch_id === $branch->id, 403);

        return view('branch.settlement_show', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'settlement' => $settlement,
        ]);
    }

    /**
     * Submit settlement for approval
     */
    public function submit(Request $request, BranchSettlement $settlement): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($settlement->branch_id === $branch->id, 403);
        abort_unless($settlement->canSubmit(), 400, 'Settlement cannot be submitted');

        $settlement->submit($user->id);

        return back()->with('success', 'Settlement submitted for HQ approval.');
    }

    /**
     * Add notes to settlement
     */
    public function addNotes(Request $request, BranchSettlement $settlement): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($settlement->branch_id === $branch->id, 403);

        $data = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $settlement->update(['notes' => $data['notes']]);

        return back()->with('success', 'Notes updated.');
    }

    /**
     * P&L Report with detailed breakdown
     */
    public function plReport(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $start = $request->filled('start') 
            ? Carbon::parse($request->start) 
            : now()->startOfMonth();
        $end = $request->filled('end') 
            ? Carbon::parse($request->end) 
            : now()->endOfMonth();

        // Revenue breakdown
        $revenue = [
            'delivery_charges' => \App\Models\Shipment::where('origin_branch_id', $branch->id)
                ->whereBetween('created_at', [$start, $end])
                ->sum('base_charge'),
            'fuel_surcharge' => \App\Models\Shipment::where('origin_branch_id', $branch->id)
                ->whereBetween('created_at', [$start, $end])
                ->sum('fuel_surcharge'),
            'insurance_fees' => \App\Models\Shipment::where('origin_branch_id', $branch->id)
                ->whereBetween('created_at', [$start, $end])
                ->sum('insurance_charge'),
            'special_handling' => \App\Models\Shipment::where('origin_branch_id', $branch->id)
                ->whereBetween('created_at', [$start, $end])
                ->sum('special_handling_charge'),
            'cod_fees' => \App\Models\Shipment::where('origin_branch_id', $branch->id)
                ->whereBetween('created_at', [$start, $end])
                ->where('payment_type', 'COD')
                ->sum('cod_fee'),
        ];
        $revenue['total'] = array_sum($revenue);

        // Expenses breakdown
        $expenses = \Illuminate\Support\Facades\DB::table('branch_expenses')
            ->where('branch_id', $branch->id)
            ->whereBetween('expense_date', [$start, $end])
            ->select('category', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();
        $expensesTotal = array_sum($expenses);

        // COD position
        $codCollected = \App\Models\Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->whereBetween('cod_collected_at', [$start, $end])
            ->sum('cod_collected_amount');

        $codRemitted = \Illuminate\Support\Facades\DB::table('cod_remittances')
            ->where('branch_id', $branch->id)
            ->whereBetween('remitted_at', [$start, $end])
            ->sum('amount');

        // Net position
        $netProfit = $revenue['total'] - $expensesTotal;
        $codPosition = $codCollected - $codRemitted;

        return view('branch.settlements.pl_report', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'start' => $start,
            'end' => $end,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'expensesTotal' => $expensesTotal,
            'codCollected' => $codCollected,
            'codRemitted' => $codRemitted,
            'codPosition' => $codPosition,
            'netProfit' => $netProfit,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
        ]);
    }

    /**
     * Expense breakdown report
     */
    public function expenseBreakdown(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $start = $request->filled('start') 
            ? Carbon::parse($request->start) 
            : now()->startOfMonth();
        $end = $request->filled('end') 
            ? Carbon::parse($request->end) 
            : now()->endOfMonth();

        // Expenses by category
        $byCategory = \Illuminate\Support\Facades\DB::table('branch_expenses')
            ->where('branch_id', $branch->id)
            ->whereBetween('expense_date', [$start, $end])
            ->select(
                'category',
                \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count')
            )
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // Daily trend
        $dailyTrend = \Illuminate\Support\Facades\DB::table('branch_expenses')
            ->where('branch_id', $branch->id)
            ->whereBetween('expense_date', [$start, $end])
            ->select(
                \Illuminate\Support\Facades\DB::raw('DATE(expense_date) as date'),
                \Illuminate\Support\Facades\DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Recent expenses
        $recentExpenses = \Illuminate\Support\Facades\DB::table('branch_expenses')
            ->where('branch_id', $branch->id)
            ->whereBetween('expense_date', [$start, $end])
            ->orderByDesc('expense_date')
            ->limit(20)
            ->get();

        $totalExpenses = $byCategory->sum('total');

        return view('branch.settlements.expense_breakdown', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'start' => $start,
            'end' => $end,
            'byCategory' => $byCategory,
            'dailyTrend' => $dailyTrend,
            'recentExpenses' => $recentExpenses,
            'totalExpenses' => $totalExpenses,
            'defaultCurrency' => SystemSettings::defaultCurrency(),
        ]);
    }

    /**
     * Download settlement as PDF
     */
    public function downloadPdf(Request $request, BranchSettlement $settlement)
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        abort_unless($settlement->branch_id === $branch->id, 403);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('branch.settlements.pdf', [
            'settlement' => $settlement,
            'branch' => $branch,
        ]);

        return $pdf->download("settlement-{$settlement->settlement_number}.pdf");
    }
}
