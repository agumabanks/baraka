<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BranchWorkerController extends Controller
{
    /**
     * Available worker roles
     */
    private const WORKER_ROLES = [
        'dispatcher',
        'driver',
        'supervisor',
        'warehouse_worker',
        'customer_service'
    ];

    /**
     * Display a listing of branch workers with filtering
     */
    public function index(Request $request)
    {
        $query = BranchWorker::with(['branch', 'user', 'assignedShipments' => function ($q) {
            $q->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery']);
        }]);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('status', Status::INACTIVE);
            }
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('branch', function ($branchQuery) use ($search) {
                    $branchQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        $workers = $query->orderBy('assigned_at', 'desc')->paginate(15);

        // Get filter data
        $branches = Branch::active()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'workers' => $workers,
                'filters' => [
                    'branches' => $branches,
                    'roles' => self::WORKER_ROLES,
                    'statuses' => ['active', 'inactive'],
                ]
            ]);
        }

        return view('backend.branch-workers.index', compact('workers', 'branches'));
    }

    /**
     * Show the form for creating a new branch worker
     */
    public function create()
    {
        $branches = Branch::active()->get();
        $availableUsers = User::whereDoesntHave('branchWorkers', function ($q) {
            $q->whereNull('unassigned_at');
        })
        ->where('user_type', '!=', 'admin')
        ->select('id', 'name', 'email')
        ->get();

        return view('backend.branch-workers.create', compact('branches', 'availableUsers'));
    }

    /**
     * Store a newly created branch worker
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:unified_branches,id',
            'user_id' => 'nullable|exists:users,id',
            'create_user' => 'boolean',
            'name' => 'required_if:create_user,true|string|max:255',
            'email' => 'required_if:create_user,true|email|unique:users,email',
            'password' => 'required_if:create_user,true|string|min:8',
            'role' => ['required', Rule::in(self::WORKER_ROLES)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'work_schedule' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'assigned_at' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = null;

            if ($request->boolean('create_user')) {
                // Create new user
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'user_type' => 'branch_worker',
                ]);

                // Assign role
                $user->assignRole('branch_worker');
            } elseif ($request->filled('user_id')) {
                $user = User::find($request->user_id);

                // Check if user already has active assignment
                $activeAssignment = BranchWorker::where('user_id', $user->id)
                    ->whereNull('unassigned_at')
                    ->first();

                if ($activeAssignment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This user already has an active branch assignment.'
                    ], 422);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either select an existing user or create a new one.'
                ], 422);
            }

            $worker = BranchWorker::create([
                'branch_id' => $request->branch_id,
                'user_id' => $user->id,
                'role' => $request->role,
                'permissions' => $request->permissions,
                'work_schedule' => $request->work_schedule,
                'hourly_rate' => $request->hourly_rate,
                'assigned_at' => $request->assigned_at,
                'notes' => $request->notes,
                'status' => Status::ACTIVE,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch worker assigned successfully.',
                'worker' => $worker->load(['branch', 'user'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign branch worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified branch worker with full details
     */
    public function show(BranchWorker $worker)
    {
        $worker->load([
            'branch',
            'user',
            'assignedShipments' => function ($query) {
                $query->with(['originBranch', 'destBranch'])->latest()->take(10);
            },
            'assignedTasks' => function ($query) {
                $query->latest()->take(10);
            },
            'workLogs' => function ($query) {
                $query->latest()->take(20);
            }
        ]);

        // Get worker analytics
        $analytics = [
            'current_workload' => $worker->getCurrentWorkload(),
            'performance_metrics' => $worker->getPerformanceMetrics(),
            'assignment_info' => [
                'assignment_duration' => $worker->assignment_duration,
                'is_currently_active' => $worker->is_currently_active,
                'weekly_schedule' => $worker->getWeeklySchedule(),
            ],
            'recent_activity' => [
                'completed_shipments' => $worker->assignedShipments()
                    ->where('current_status', 'delivered')
                    ->where('updated_at', '>=', now()->subDays(30))
                    ->count(),
                'active_shipments' => $worker->assignedShipments()
                    ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                    ->count(),
            ]
        ];

        if (request()->wantsJson()) {
            return response()->json([
                'worker' => $worker,
                'analytics' => $analytics
            ]);
        }

        return view('backend.branch-workers.show', compact('worker', 'analytics'));
    }

    /**
     * Show the form for editing the branch worker
     */
    public function edit(BranchWorker $worker)
    {
        $branches = Branch::active()->get();

        return view('backend.branch-workers.edit', compact('worker', 'branches'));
    }

    /**
     * Update the specified branch worker
     */
    public function update(Request $request, BranchWorker $worker): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:unified_branches,id',
            'role' => ['required', Rule::in(self::WORKER_ROLES)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'work_schedule' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
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
            $worker->update([
                'branch_id' => $request->branch_id,
                'role' => $request->role,
                'permissions' => $request->permissions,
                'work_schedule' => $request->work_schedule,
                'hourly_rate' => $request->hourly_rate,
                'notes' => $request->notes,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch worker updated successfully.',
                'worker' => $worker->load(['branch', 'user'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unassign the branch worker
     */
    public function unassign(BranchWorker $worker): JsonResponse
    {
        if ($worker->unassigned_at) {
            return response()->json([
                'success' => false,
                'message' => 'Worker is already unassigned.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $worker->unassign();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch worker unassigned successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to unassign branch worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified branch worker
     */
    public function destroy(BranchWorker $worker): JsonResponse
    {
        // Check if worker can be deleted
        if (!$this->canDeleteWorker($worker)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete worker with active shipments or tasks.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $worker->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch worker deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete branch worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign shipment to worker
     */
    public function assignShipment(Request $request, BranchWorker $worker): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shipment = \App\Models\Shipment::find($request->shipment_id);

        if (!$worker->assignShipment($shipment)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign shipment to worker.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipment assigned to worker successfully.'
        ]);
    }

    /**
     * Get worker performance analytics
     */
    public function analytics(BranchWorker $worker): JsonResponse
    {
        $performance = $worker->getPerformanceMetrics();
        $workload = $worker->getCurrentWorkload();

        // Additional analytics
        $monthlyPerformance = $this->getMonthlyPerformance($worker);

        $analytics = [
            'performance' => $performance,
            'workload' => $workload,
            'monthly_performance' => $monthlyPerformance,
            'efficiency_score' => $this->calculateEfficiencyScore($performance, $workload),
        ];

        return response()->json(['analytics' => $analytics]);
    }

    /**
     * Get available users for worker assignment
     */
    public function availableUsers(): JsonResponse
    {
        $users = User::whereDoesntHave('branchWorkers', function ($q) {
            $q->whereNull('unassigned_at');
        })
        ->where('user_type', '!=', 'admin')
        ->select('id', 'name', 'email')
        ->get();

        return response()->json(['users' => $users]);
    }

    /**
     * Bulk update worker statuses
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'worker_ids' => 'required|array',
            'worker_ids.*' => 'exists:branch_workers,id',
            'status' => ['required', Rule::in([Status::ACTIVE, Status::INACTIVE])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            BranchWorker::whereIn('id', $request->worker_ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Branch workers updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch workers: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper Methods

    /**
     * Check if worker can be safely deleted
     */
    private function canDeleteWorker(BranchWorker $worker): bool
    {
        return $worker->assignedShipments()
                ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
                ->count() === 0 &&
               $worker->assignedTasks()
                ->where('status', 'pending')
                ->count() === 0;
    }

    /**
     * Get monthly performance data for worker
     */
    private function getMonthlyPerformance(BranchWorker $worker): array
    {
        $monthlyData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $completedShipments = $worker->assignedShipments()
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->where('current_status', 'delivered')
                ->count();

            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'completed_shipments' => $completedShipments,
                'efficiency_rate' => $completedShipments > 0 ?
                    ($worker->assignedShipments()
                        ->whereBetween('updated_at', [$monthStart, $monthEnd])
                        ->where('current_status', 'delivered')
                        ->whereRaw('delivered_at <= expected_delivery_date')
                        ->count() / $completedShipments) * 100 : 0,
            ];
        }

        return $monthlyData;
    }

    /**
     * Calculate overall efficiency score
     */
    private function calculateEfficiencyScore(array $performance, array $workload): float
    {
        $onTimeRate = ($performance['on_time_delivery_rate'] ?? 0) / 100;
        $capacityUtilization = ($workload['capacity_utilization'] ?? 0) / 100;
        $completedShipments = $performance['completed_shipments_30_days'] ?? 0;

        // Weighted efficiency score
        $score = ($onTimeRate * 0.4) + ($capacityUtilization * 0.4) + (min($completedShipments / 50, 1) * 0.2);

        return round($score * 100, 2);
    }
}
