<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchSettlement;
use App\Models\Backend\Branch;
use App\Services\Finance\BranchSettlementService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchSettlementController extends Controller
{
    public function __construct(
        protected BranchSettlementService $settlementService
    ) {}

    /**
     * Admin consolidated finance dashboard
     */
    public function dashboard(Request $request): View
    {
        $start = $request->filled('start') 
            ? Carbon::parse($request->start) 
            : now()->startOfMonth();
        $end = $request->filled('end') 
            ? Carbon::parse($request->end) 
            : now()->endOfMonth();

        $summary = $this->settlementService->getConsolidatedSummary($start, $end);

        // Pending settlements for approval
        $pendingSettlements = BranchSettlement::with('branch')
            ->where('status', 'submitted')
            ->orderBy('submitted_at')
            ->get();

        // Recent settled
        $recentSettled = BranchSettlement::with('branch')
            ->where('status', 'settled')
            ->latest('settled_at')
            ->take(10)
            ->get();

        return view('admin.finance.consolidated_dashboard', [
            'summary' => $summary,
            'pendingSettlements' => $pendingSettlements,
            'recentSettled' => $recentSettled,
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * List all branch settlements
     */
    public function index(Request $request): View
    {
        $branchId = $request->get('branch_id');
        $status = $request->get('status');

        $settlements = BranchSettlement::with('branch')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        $branches = Branch::where('status', 1)->orderBy('name')->get();

        return view('admin.finance.branch_settlements', [
            'settlements' => $settlements,
            'branches' => $branches,
            'branchFilter' => $branchId,
            'statusFilter' => $status,
        ]);
    }

    /**
     * Show settlement details
     */
    public function show(BranchSettlement $settlement): View
    {
        $settlement->load(['branch', 'submittedByUser', 'approvedByUser', 'settledByUser']);

        return view('admin.finance.settlement_show', [
            'settlement' => $settlement,
        ]);
    }

    /**
     * Approve settlement
     */
    public function approve(Request $request, BranchSettlement $settlement): RedirectResponse
    {
        abort_unless($settlement->canApprove(), 400, 'Settlement cannot be approved');

        $settlement->approve($request->user()->id);

        return back()->with('success', 'Settlement approved. Ready for payment processing.');
    }

    /**
     * Reject settlement
     */
    public function reject(Request $request, BranchSettlement $settlement): RedirectResponse
    {
        abort_unless($settlement->canApprove(), 400, 'Settlement cannot be rejected');

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $settlement->reject($request->user()->id, $data['rejection_reason']);

        return back()->with('success', 'Settlement rejected and returned to branch.');
    }

    /**
     * Mark settlement as settled (payment completed)
     */
    public function settle(Request $request, BranchSettlement $settlement): RedirectResponse
    {
        abort_unless($settlement->canSettle(), 400, 'Settlement cannot be processed');

        $data = $request->validate([
            'payment_method' => 'required|string|max:50',
            'payment_reference' => 'required|string|max:100',
        ]);

        $settlement->markSettled(
            $request->user()->id,
            $data['payment_method'],
            $data['payment_reference']
        );

        return back()->with('success', 'Settlement marked as complete.');
    }
}
