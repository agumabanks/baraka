<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\BranchWorker;
use App\Services\BranchHierarchyService;
use App\Services\BranchAnalyticsService;
use App\Services\BranchCapacityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    protected BranchHierarchyService $hierarchyService;
    protected BranchAnalyticsService $analyticsService;

    public function __construct(BranchHierarchyService $hierarchyService, BranchAnalyticsService $analyticsService)
    {
        $this->hierarchyService = $hierarchyService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display a listing of branches with filtering and hierarchy support
     */
    public function index(Request $request)
    {
        $query = Branch::with(['parent', 'children', 'branchManager.user', 'activeWorkers']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_hub')) {
            $query->where('is_hub', $request->boolean('is_hub'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_branch_id', $request->parent_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Sort by hierarchy level and name
        $branches = $query->orderBy('parent_branch_id')
                          ->orderBy('name')
                          ->paginate(15);

        // Get branch types and hierarchy data for filters
        $branchTypes = ['HUB', 'REGIONAL', 'LOCAL'];
        $rootBranches = Branch::root()->active()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'branches' => $branches,
                'filters' => [
                    'types' => $branchTypes,
                    'root_branches' => $rootBranches,
                ]
            ]);
        }

        return view('backend.branches.index', compact('branches', 'branchTypes', 'rootBranches'));
    }

    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        $branchTypes = ['HUB', 'REGIONAL', 'LOCAL'];
        $potentialParents = Branch::active()
            ->where(function ($query) {
                $query->where('type', '!=', 'LOCAL')
                      ->orWhere('is_hub', true);
            })
            ->get();

        return view('backend.branches.create', compact('branchTypes', 'potentialParents'));
    }

    /**
     * Store a newly created branch
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches,code',
            'type' => ['required', Rule::in(['HUB', 'REGIONAL', 'LOCAL'])],
            'is_hub' => 'boolean',
            'parent_branch_id' => 'nullable|exists:branches,id',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'operating_hours' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'capabilities.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Validate HUB uniqueness
            if ($request->boolean('is_hub')) {
                $existingHub = Branch::where('is_hub', true)->first();
                if ($existingHub) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A HUB branch already exists. Only one HUB is allowed.'
                    ], 422);
                }
            }

            // Validate hierarchy rules
            if ($request->filled('parent_branch_id')) {
                $parent = Branch::find($request->parent_branch_id);
                if (!$this->isValidParentChildRelationship($parent, $request->type)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid parent-child relationship for branch types.'
                    ], 422);
                }
            }

            $branch = Branch::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'type' => $request->type,
                'is_hub' => $request->boolean('is_hub'),
                'parent_branch_id' => $request->parent_branch_id,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'operating_hours' => $request->operating_hours,
                'capabilities' => $request->capabilities,
                'status' => Status::ACTIVE,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch created successfully.',
                'branch' => $branch->load(['parent', 'children'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified branch with full details
     */
    public function show(Branch $branch)
    {
        $branch->load([
            'parent',
            'children',
            'branchManager.user',
            'activeWorkers.user',
            'originShipments' => function ($query) {
                $query->latest()->take(10);
            },
            'destShipments' => function ($query) {
                $query->latest()->take(10);
            },
            'primaryClients' => function ($query) {
                $query->take(10);
            }
        ]);

        // Get branch analytics
        $analytics = [
            'capacity_metrics' => $branch->getCapacityMetrics(),
            'performance_metrics' => $branch->getPerformanceMetrics(),
            'hierarchy_info' => [
                'level' => $branch->hierarchy_level,
                'path' => $branch->hierarchy_path,
                'descendants_count' => $branch->getAllDescendants()->count(),
            ],
            'operational_status' => [
                'is_operational' => $branch->isOperational(),
                'next_operational_check' => $this->getNextOperationalCheck($branch),
            ]
        ];

        if (request()->wantsJson()) {
            return response()->json([
                'branch' => $branch,
                'analytics' => $analytics
            ]);
        }

        return view('backend.branches.show', compact('branch', 'analytics'));
    }

    /**
     * Show the form for editing the branch
     */
    public function edit(Branch $branch)
    {
        $branchTypes = ['HUB', 'REGIONAL', 'LOCAL'];
        $potentialParents = Branch::active()
            ->where('id', '!=', $branch->id)
            ->where(function ($query) {
                $query->where('type', '!=', 'LOCAL')
                      ->orWhere('is_hub', true);
            })
            ->get();

        return view('backend.branches.edit', compact('branch', 'branchTypes', 'potentialParents'));
    }

    /**
     * Update the specified branch
     */
    public function update(Request $request, Branch $branch): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:10', Rule::unique('branches')->ignore($branch->id)],
            'type' => ['required', Rule::in(['HUB', 'REGIONAL', 'LOCAL'])],
            'is_hub' => 'boolean',
            'parent_branch_id' => ['nullable', 'exists:branches,id', Rule::notIn([$branch->id])],
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'operating_hours' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'capabilities.*' => 'string',
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
            // Validate HUB uniqueness
            if ($request->boolean('is_hub') && !$branch->is_hub) {
                $existingHub = Branch::where('is_hub', true)->where('id', '!=', $branch->id)->first();
                if ($existingHub) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A HUB branch already exists. Only one HUB is allowed.'
                    ], 422);
                }
            }

            // Prevent circular references in hierarchy
            if ($request->filled('parent_branch_id')) {
                if ($this->wouldCreateCircularReference($branch, $request->parent_branch_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot set parent branch as it would create a circular reference.'
                    ], 422);
                }

                $parent = Branch::find($request->parent_branch_id);
                if (!$this->isValidParentChildRelationship($parent, $request->type)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid parent-child relationship for branch types.'
                    ], 422);
                }
            }

            $branch->update([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'type' => $request->type,
                'is_hub' => $request->boolean('is_hub'),
                'parent_branch_id' => $request->parent_branch_id,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'operating_hours' => $request->operating_hours,
                'capabilities' => $request->capabilities,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch updated successfully.',
                'branch' => $branch->load(['parent', 'children'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified branch
     */
    public function destroy(Branch $branch): JsonResponse
    {
        // Check if branch can be deleted
        if (!$this->canDeleteBranch($branch)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete branch with active relationships.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Soft delete or cascade as needed
            $branch->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branch hierarchy as tree structure
     */
    public function hierarchy(): JsonResponse
    {
        $tree = $this->hierarchyService->getHierarchyTree();

        return response()->json(['hierarchy' => $tree]);
    }

    /**
     * Get regional branch groupings
     */
    public function regionalGroupings(): JsonResponse
    {
        $groupings = $this->hierarchyService->getRegionalGroupings();

        return response()->json(['regional_groupings' => $groupings]);
    }

    /**
     * Get branches by hierarchy level
     */
    public function byLevel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $branches = $this->hierarchyService->getBranchesByLevel($request->level);

        return response()->json(['branches' => $branches]);
    }

    /**
     * Move branch to new parent
     */
    public function moveBranch(Request $request, Branch $branch): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_parent_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $success = $this->hierarchyService->moveBranch($branch, $request->new_parent_id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot move branch due to hierarchy constraints.'
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch moved successfully.',
                'branch' => $branch->load(['parent', 'children'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to move branch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suggest parent for new branch
     */
    public function suggestParent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(['HUB', 'REGIONAL', 'LOCAL'])],
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $suggestedParent = $this->hierarchyService->suggestParentForNewBranch(
            $request->type,
            $request->latitude,
            $request->longitude
        );

        return response()->json([
            'suggested_parent' => $suggestedParent,
            'available_parents' => $this->hierarchyService->getPotentialParents($request->type)
        ]);
    }

    /**
     * Get branch analytics dashboard
     */
    public function analytics(Request $request): JsonResponse
    {
        $branchId = $request->get('branch_id');
        $dateRange = $request->get('date_range', 30); // days

        if ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $analytics = $this->analyticsService->getBranchPerformanceAnalytics($branch, $dateRange);
        } else {
            // System-wide analytics - would need a system analytics service
            $analytics = $this->getSystemAnalytics($dateRange);
        }

        return response()->json(['analytics' => $analytics]);
    }

    /**
     * Get branch capacity planning data
     */
    public function capacity(Request $request): JsonResponse
    {
        $branchId = $request->get('branch_id');

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ID is required.'
            ], 422);
        }

        $branch = Branch::findOrFail($branchId);
        $capacity = $this->calculateBranchCapacity($branch);

        return response()->json(['capacity' => $capacity]);
    }

    // Helper Methods

    /**
     * Validate parent-child relationship rules
     */
    private function isValidParentChildRelationship(?Branch $parent, string $childType): bool
    {
        if (!$parent) {
            return true; // Root branches are allowed
        }

        return match($childType) {
            'HUB' => false, // HUB cannot have parent
            'REGIONAL' => $parent->is_hub || $parent->type === 'REGIONAL',
            'LOCAL' => $parent->type === 'REGIONAL' || $parent->is_hub,
            default => false
        };
    }

    /**
     * Check if setting parent would create circular reference
     */
    private function wouldCreateCircularReference(Branch $branch, int $potentialParentId): bool
    {
        $current = Branch::find($potentialParentId);

        while ($current) {
            if ($current->id === $branch->id) {
                return true; // Circular reference detected
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Check if branch can be safely deleted
     */
    private function canDeleteBranch(Branch $branch): bool
    {
        // Check for active relationships
        return $branch->children()->count() === 0 &&
               $branch->branchManager()->count() === 0 &&
               $branch->activeWorkers()->count() === 0 &&
               $branch->originShipments()->where('status', '!=', 'delivered')->count() === 0 &&
               $branch->destShipments()->where('status', '!=', 'delivered')->count() === 0;
    }

    /**
     * Build hierarchy tree structure
     */
    private function buildHierarchyTree($branches): array
    {
        return $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'type' => $branch->type,
                'is_hub' => $branch->is_hub,
                'status' => $branch->status,
                'children' => $this->buildHierarchyTree($branch->children)
            ];
        })->toArray();
    }

    /**
     * Get analytics for a specific branch
     */
    private function getBranchAnalytics(Branch $branch, int $days): array
    {
        $startDate = now()->subDays($days);

        return [
            'branch_info' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'type' => $branch->type,
                'is_hub' => $branch->is_hub,
            ],
            'capacity_metrics' => $branch->getCapacityMetrics(),
            'performance_metrics' => $branch->getPerformanceMetrics(),
            'shipment_stats' => [
                'total_origin' => $branch->originShipments()->count(),
                'total_dest' => $branch->destShipments()->count(),
                'recent_origin' => $branch->originShipments()->where('created_at', '>=', $startDate)->count(),
                'recent_dest' => $branch->destShipments()->where('created_at', '>=', $startDate)->count(),
            ],
            'worker_stats' => [
                'total_workers' => $branch->branchWorkers()->count(),
                'active_workers' => $branch->activeWorkers()->count(),
                'utilization_rate' => $branch->getCapacityMetrics()['utilization_rate'] ?? 0,
            ]
        ];
    }

    /**
     * Get system-wide analytics
     */
    private function getSystemAnalytics(int $days): array
    {
        $startDate = now()->subDays($days);

        return [
            'system_overview' => [
                'total_branches' => Branch::count(),
                'active_branches' => Branch::active()->count(),
                'hub_branches' => Branch::hub()->count(),
                'regional_branches' => Branch::type('REGIONAL')->count(),
                'local_branches' => Branch::type('LOCAL')->count(),
            ],
            'capacity_overview' => [
                'total_workers' => BranchWorker::count(),
                'active_workers' => BranchWorker::active()->count(),
                'total_managers' => BranchManager::count(),
                'active_managers' => BranchManager::active()->count(),
            ],
            'performance_overview' => [
                'total_shipments' => \App\Models\Shipment::where('created_at', '>=', $startDate)->count(),
                'delivered_shipments' => \App\Models\Shipment::where('created_at', '>=', $startDate)
                    ->where('current_status', \App\Enums\ShipmentStatus::DELIVERED)->count(),
            ]
        ];
    }

    /**
     * Calculate branch capacity planning data
     */
    private function calculateBranchCapacity(Branch $branch): array
    {
        $activeWorkers = $branch->activeWorkers()->count();
        $currentShipments = $branch->originShipments()
            ->whereIn('current_status', ['assigned', 'in_transit', 'out_for_delivery'])
            ->count();

        $maxCapacity = $activeWorkers * 10; // Assume 10 shipments per worker
        $utilizationRate = $maxCapacity > 0 ? ($currentShipments / $maxCapacity) * 100 : 0;

        return [
            'current_capacity' => [
                'active_workers' => $activeWorkers,
                'current_shipments' => $currentShipments,
                'max_capacity' => $maxCapacity,
                'utilization_rate' => round($utilizationRate, 2),
            ],
            'capacity_status' => $this->getCapacityStatus($utilizationRate),
            'recommendations' => $this->getCapacityRecommendations($utilizationRate, $activeWorkers),
        ];
    }

    /**
     * Get capacity status based on utilization rate
     */
    private function getCapacityStatus(float $utilizationRate): string
    {
        if ($utilizationRate < 50) return 'Underutilized';
        if ($utilizationRate < 80) return 'Optimal';
        if ($utilizationRate < 100) return 'High';
        return 'Overloaded';
    }

    /**
     * Get capacity recommendations
     */
    private function getCapacityRecommendations(float $utilizationRate, int $activeWorkers): array
    {
        $recommendations = [];

        if ($utilizationRate < 50) {
            $recommendations[] = 'Consider reducing workforce or expanding service area';
        } elseif ($utilizationRate > 90) {
            $recommendations[] = 'Consider hiring additional workers to handle load';
            $recommendations[] = 'Review workflow efficiency and bottlenecks';
        }

        if ($activeWorkers === 0) {
            $recommendations[] = 'No active workers assigned - branch cannot operate';
        }

        return $recommendations;
    }

    /**
     * Get next operational check time
     */
    private function getNextOperationalCheck(Branch $branch): ?string
    {
        if (!$branch->operating_hours) {
            return null;
        }

        $now = now();
        $dayOfWeek = (int) $now->format('w');

        for ($i = 0; $i < 7; $i++) {
            $checkDay = ($dayOfWeek + $i) % 7;
            if (isset($branch->operating_hours[$checkDay])) {
                $hours = $branch->operating_hours[$checkDay];
                if ($i === 0) {
                    // Today - check if currently operational
                    $currentTime = $now->format('H:i');
                    if ($currentTime < $hours['start']) {
                        return $now->format('Y-m-d') . ' ' . $hours['start'];
                    }
                } else {
                    // Future day
                    $checkDate = $now->addDays($i);
                    return $checkDate->format('Y-m-d') . ' ' . $hours['start'];
                }
            }
        }

        return null;
    }
}
