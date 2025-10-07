<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BranchManagerController extends Controller
{
    /**
     * Display a listing of branch managers with filtering
     */
    public function index(Request $request)
    {
        $query = BranchManager::with(['branch', 'user']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('branch', function ($branchQuery) use ($search) {
                      $branchQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $managers = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get available branches for filter dropdown
        $branches = Branch::active()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'managers' => $managers,
                'filters' => [
                    'branches' => $branches,
                    'statuses' => [Status::ACTIVE, Status::INACTIVE],
                ]
            ]);
        }

        return view('backend.branch-managers.index', compact('managers', 'branches'));
    }

    /**
     * Show the form for creating a new branch manager
     */
    public function create()
    {
        $availableBranches = Branch::active()
            ->whereDoesntHave('branchManager')
            ->get();

        return view('backend.branch-managers.create', compact('availableBranches'));
    }

    /**
     * Store a newly created branch manager
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'user_id' => 'nullable|exists:users,id',
            'create_user' => 'boolean',
            'name' => 'required_if:create_user,true|string|max:255',
            'email' => 'required_if:create_user,true|email|unique:users,email',
            'password' => 'required_if:create_user,true|string|min:8',
            'business_name' => 'nullable|string|max:255',
            'cod_charges' => 'nullable|array',
            'payment_info' => 'nullable|array',
            'settlement_config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Check if branch already has a manager
            $existingManager = BranchManager::where('branch_id', $request->branch_id)->first();
            if ($existingManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'This branch already has a manager assigned.'
                ], 422);
            }

            $user = null;

            if ($request->boolean('create_user')) {
                // Create new user
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'user_type' => 'branch_manager',
                ]);

                // Assign role
                $user->assignRole('branch_manager');
            } elseif ($request->filled('user_id')) {
                $user = User::find($request->user_id);

                // Check if user is already a branch manager
                if (BranchManager::where('user_id', $user->id)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This user is already assigned as a branch manager.'
                    ], 422);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either select an existing user or create a new one.'
                ], 422);
            }

            $manager = BranchManager::create([
                'branch_id' => $request->branch_id,
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'cod_charges' => $request->cod_charges,
                'payment_info' => $request->payment_info,
                'settlement_config' => $request->settlement_config,
                'status' => Status::ACTIVE,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch manager created successfully.',
                'manager' => $manager->load(['branch', 'user'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create branch manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified branch manager with full details
     */
    public function show(BranchManager $manager)
    {
        $manager->load([
            'branch',
            'user',
            'shipments' => function ($query) {
                $query->latest()->take(10);
            },
            'paymentRequests' => function ($query) {
                $query->latest()->take(5);
            },
            'settlements' => function ($query) {
                $query->latest()->take(5);
            }
        ]);

        // Get manager analytics
        $analytics = [
            'settlement_summary' => $manager->getSettlementSummary(),
            'performance_metrics' => $manager->getPerformanceMetrics(),
            'recent_activity' => [
                'shipments_count' => $manager->shipments()->count(),
                'pending_requests' => $manager->paymentRequests()->where('status', 'pending')->count(),
                'completed_settlements' => $manager->settlements()->where('status', 'completed')->count(),
            ]
        ];

        if (request()->wantsJson()) {
            return response()->json([
                'manager' => $manager,
                'analytics' => $analytics
            ]);
        }

        return view('backend.branch-managers.show', compact('manager', 'analytics'));
    }

    /**
     * Show the form for editing the branch manager
     */
    public function edit(BranchManager $manager)
    {
        $availableBranches = Branch::active()
            ->where(function ($query) use ($manager) {
                $query->whereDoesntHave('branchManager')
                      ->orWhere('id', $manager->branch_id);
            })
            ->get();

        return view('backend.branch-managers.edit', compact('manager', 'availableBranches'));
    }

    /**
     * Update the specified branch manager
     */
    public function update(Request $request, BranchManager $manager): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'business_name' => 'nullable|string|max:255',
            'cod_charges' => 'nullable|array',
            'payment_info' => 'nullable|array',
            'settlement_config' => 'nullable|array',
            'status' => ['required', Rule::in([Status::ACTIVE, Status::INACTIVE])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Check if branch change conflicts with existing manager
            if ($request->branch_id != $manager->branch_id) {
                $existingManager = BranchManager::where('branch_id', $request->branch_id)
                    ->where('id', '!=', $manager->id)
                    ->first();

                if ($existingManager) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This branch already has a manager assigned.'
                    ], 422);
                }
            }

            $manager->update([
                'branch_id' => $request->branch_id,
                'business_name' => $request->business_name,
                'cod_charges' => $request->cod_charges,
                'payment_info' => $request->payment_info,
                'settlement_config' => $request->settlement_config,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch manager updated successfully.',
                'manager' => $manager->load(['branch', 'user'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified branch manager
     */
    public function destroy(BranchManager $manager): JsonResponse
    {
        // Check if manager can be deleted
        if (!$this->canDeleteManager($manager)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete branch manager with active shipments or pending payments.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $manager->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch manager deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete branch manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branch manager dashboard data
     */
    public function dashboard(BranchManager $manager): JsonResponse
    {
        $dashboardData = $manager->getDashboardData();

        return response()->json(['dashboard' => $dashboardData]);
    }

    /**
     * Update branch manager balance
     */
    public function updateBalance(Request $request, BranchManager $manager): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'type' => ['required', Rule::in(['credit', 'debit'])],
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $manager->updateBalance(
                $request->amount,
                $request->type,
                $request->description
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update balance.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Balance updated successfully.',
                'new_balance' => $manager->current_balance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branch manager settlement summary
     */
    public function settlements(BranchManager $manager): JsonResponse
    {
        $settlements = $manager->settlements()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $summary = $manager->getSettlementSummary();

        return response()->json([
            'settlements' => $settlements,
            'summary' => $summary
        ]);
    }

    /**
     * Get branch manager performance analytics
     */
    public function analytics(BranchManager $manager): JsonResponse
    {
        $performance = $manager->getPerformanceMetrics();

        // Additional analytics
        $monthlyRevenue = $manager->shipments()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(price_amount) as revenue')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $analytics = [
            'performance' => $performance,
            'monthly_revenue' => $monthlyRevenue,
            'efficiency_metrics' => [
                'average_shipment_value' => $performance['average_shipment_value'] ?? 0,
                'shipments_per_month' => $performance['shipments_last_30_days'] ?? 0,
                'revenue_trend' => $this->calculateRevenueTrend($monthlyRevenue),
            ]
        ];

        return response()->json(['analytics' => $analytics]);
    }

    // Helper Methods

    /**
     * Check if branch manager can be safely deleted
     */
    private function canDeleteManager(BranchManager $manager): bool
    {
        return $manager->shipments()->where('status', '!=', 'delivered')->count() === 0 &&
               $manager->paymentRequests()->where('status', 'pending')->count() === 0;
    }

    /**
     * Calculate revenue trend from monthly data
     */
    private function calculateRevenueTrend($monthlyRevenue): array
    {
        if ($monthlyRevenue->count() < 2) {
            return ['trend' => 'insufficient_data', 'change_percent' => 0];
        }

        $currentMonth = $monthlyRevenue->last();
        $previousMonth = $monthlyRevenue->slice(-2, 1)->first();

        if (!$previousMonth || $previousMonth->revenue == 0) {
            return ['trend' => 'no_previous_data', 'change_percent' => 0];
        }

        $changePercent = (($currentMonth->revenue - $previousMonth->revenue) / $previousMonth->revenue) * 100;

        $trend = $changePercent > 5 ? 'increasing' :
                ($changePercent < -5 ? 'decreasing' : 'stable');

        return [
            'trend' => $trend,
            'change_percent' => round($changePercent, 2),
            'current_month' => $currentMonth->revenue,
            'previous_month' => $previousMonth->revenue,
        ];
    }

    /**
     * Get available users for branch manager assignment
     */
    public function availableUsers(): JsonResponse
    {
        $users = User::whereDoesntHave('branchManager')
            ->where('user_type', '!=', 'admin')
            ->select('id', 'name', 'email')
            ->get();

        return response()->json(['users' => $users]);
    }

    /**
     * Bulk update branch manager statuses
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'manager_ids' => 'required|array',
            'manager_ids.*' => 'exists:branch_managers,id',
            'status' => ['required', Rule::in([Status::ACTIVE, Status::INACTIVE])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            BranchManager::whereIn('id', $request->manager_ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Branch managers updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch managers: ' . $e->getMessage()
            ], 500);
        }
    }
}
