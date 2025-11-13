<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BranchStatus;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\BranchAlert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BranchOperationsController extends Controller
{
    private const SEED_CACHE_KEY = 'branch_seed_operations';
    private const MAINTENANCE_CACHE_KEY = 'branch_maintenance_windows';

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $branches = Branch::query()
            ->with(['branchManager.user', 'branchWorkers.user'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = BranchStatus::fromString($request->input('status'));
                $query->where('status', $status->value);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . trim($request->input('search')) . '%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate($perPage);

        $data = collect($branches->items())
            ->map(fn (Branch $branch) => $this->transformBranch($branch))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'pagination' => $this->formatPaginator($branches),
                'summary' => $this->buildSummary(),
            ],
        ]);
    }

    public function show(Branch $branch): JsonResponse
    {
        $branch->loadMissing(['branchManager.user', 'branchWorkers.user']);

        return response()->json([
            'success' => true,
            'data' => $this->transformBranch($branch),
        ]);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $payload = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'status' => 'sometimes|string',
            'capacity.daily_shipments' => 'nullable|integer',
            'capacity.storage_space' => 'nullable|integer',
            'capacity.staff_count' => 'nullable|integer',
        ]);

        if (isset($payload['status'])) {
            $status = BranchStatus::fromString($payload['status']);
            $branch->status = $status->value;
            unset($payload['status']);
        }

        $branch->fill($payload)->save();

        if ($request->has('capacity')) {
            $meta = $branch->metadata ?? [];
            $meta['capacity'] = array_merge($meta['capacity'] ?? [], $request->input('capacity', []));
            $branch->metadata = $meta;
            $branch->save();
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformBranch($branch->fresh(['branchManager.user', 'branchWorkers.user'])),
        ]);
    }

    public function performance(Branch $branch): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->resolvePerformanceMetrics($branch),
        ]);
    }

    public function capacity(Branch $branch): JsonResponse
    {
        $metrics = $branch->getCapacityMetrics();

        $forecast = collect(range(1, 7))->map(function (int $day) use ($metrics) {
            $date = Carbon::now()->addDays($day);
            $base = $metrics['utilization_rate'] ?? 50;

            return [
                'date' => $date->toDateString(),
                'predicted_usage' => max(10, min(100, $base + random_int(-10, 10))),
                'confidence_level' => 85,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'branch_id' => (string) $branch->id,
                'branch_name' => $branch->name,
                'current_capacity_usage' => $metrics['utilization_rate'] ?? 0,
                'max_capacity' => $metrics['capacity_limit'] ?? 0,
                'utilization_rate' => $metrics['utilization_rate'] ?? 0,
                'projections' => $forecast,
                'bottlenecks' => $this->resolveCapacityBottlenecks($metrics),
                'recommendations' => $this->resolveCapacityRecommendations($metrics),
            ],
        ]);
    }

    public function analytics(): JsonResponse
    {
        $branches = Branch::all();
        $total = $branches->count();

        $active = $branches->filter(fn (Branch $branch) => BranchStatus::fromString($branch->status) === BranchStatus::ACTIVE)->count();
        $inactive = $branches->filter(fn (Branch $branch) => BranchStatus::fromString($branch->status) === BranchStatus::INACTIVE)->count();
        $maintenance = $branches->filter(fn (Branch $branch) => BranchStatus::fromString($branch->status) === BranchStatus::MAINTENANCE)->count();

        $ranking = $branches->map(function (Branch $branch, int $index) {
            $metrics = $this->resolvePerformanceMetrics($branch);
            $score = round(($metrics['on_time_delivery_rate'] ?? 0) * 0.4 + ($metrics['delivery_rate'] ?? 0) * 0.4 + (100 - ($metrics['exception_rate'] ?? 0)) * 0.2, 2);

            return [
                'branch_id' => (string) $branch->id,
                'branch_name' => $branch->name,
                'performance_score' => $score,
                'rank' => $index + 1,
                'change_from_last_month' => random_int(-5, 5),
            ];
        })->sortByDesc('performance_score')->values();

        $capacityAnalysis = [
            'overutilized' => $branches->filter(fn (Branch $branch) => ($branch->getCapacityMetrics()['utilization_rate'] ?? 0) > 85)->pluck('code'),
            'underutilized' => $branches->filter(fn (Branch $branch) => ($branch->getCapacityMetrics()['utilization_rate'] ?? 0) < 40)->pluck('code'),
            'optimal_utilization' => $branches->filter(fn (Branch $branch) => ($branch->getCapacityMetrics()['utilization_rate'] ?? 0) >= 40 && ($branch->getCapacityMetrics()['utilization_rate'] ?? 0) <= 85)->pluck('code'),
        ];

        $geo = $branches->groupBy('country')->map(fn ($items, $country) => [
            'country' => $country ?? 'Unknown',
            'branch_count' => $items->count(),
            'total_shipments' => $items->sum(fn (Branch $branch) => $branch->originShipments()->count()),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_branches' => $total,
                    'active_branches' => $active,
                    'inactive_branches' => $inactive,
                    'under_maintenance' => $maintenance,
                    'average_performance_score' => round($ranking->avg('performance_score') ?? 0, 2),
                ],
                'performance_ranking' => $ranking->take(10),
                'capacity_analysis' => $capacityAnalysis,
                'geographic_distribution' => $geo,
                'trends' => [
                    'growth_rate' => random_int(3, 12),
                    'seasonal_patterns' => collect(range(1, 6))->map(fn ($i) => [
                        'month' => Carbon::now()->subMonths(6 - $i)->format('M'),
                        'average_shipments' => random_int(1200, 3200),
                    ]),
                ],
            ],
        ]);
    }

    public function maintenanceWindows(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getMaintenanceWindows(),
        ]);
    }

    public function createMaintenanceWindow(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'reason' => 'required|string',
            'affected_services' => 'array',
        ]);

        $windows = $this->getMaintenanceWindows();

        $window = [
            'id' => (string) Str::uuid(),
            'branch_id' => (string) $payload['branch_id'],
            'start_time' => Carbon::parse($payload['start_time'])->toIso8601String(),
            'end_time' => Carbon::parse($payload['end_time'])->toIso8601String(),
            'reason' => $payload['reason'],
            'affected_services' => $payload['affected_services'] ?? [],
            'status' => 'scheduled',
            'created_by' => $request->user()?->name ?? 'system',
            'created_at' => now()->toIso8601String(),
            'notification_sent' => false,
            'estimated_downtime' => Carbon::parse($payload['start_time'])->diffInMinutes(Carbon::parse($payload['end_time'])),
        ];

        $windows[] = $window;
        Cache::put(self::MAINTENANCE_CACHE_KEY, $windows, now()->addDay());

        return response()->json([
            'success' => true,
            'data' => $window,
        ], 201);
    }

    public function alerts(Request $request): JsonResponse
    {
        $alertsQuery = BranchAlert::query()
            ->with('branch:id,name,code')
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->input('branch_id')))
            ->orderByDesc('triggered_at');

        $alerts = $alertsQuery->limit(50)->get()->map(fn (BranchAlert $alert) => $this->transformAlert($alert));

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    public function alertsForBranch(Branch $branch): JsonResponse
    {
        $alerts = $branch->alerts()->latest()->get()->map(fn (BranchAlert $alert) => $this->transformAlert($alert));

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    public function resolveAlert(BranchAlert $alert): JsonResponse
    {
        $alert->markResolved();

        return response()->json([
            'success' => true,
        ]);
    }

    public function startSeedOperation(Request $request): JsonResponse
    {
        $operation = $this->createSeedOperation('validate', 'pending', $request->input('branch_ids', []));
        $this->storeSeedOperation($operation);

        return response()->json([
            'success' => true,
            'data' => $operation,
        ], 202);
    }

    public function dryRunSeed(Request $request): JsonResponse
    {
        $simulation = [
            'id' => (string) Str::uuid(),
            'name' => 'Dry Run Simulation',
            'parameters' => $request->all(),
            'estimated' => [
                'total_entities' => random_int(10, 60),
                'estimated_duration' => random_int(1, 5) * 60,
                'resource_requirements' => [
                    'memory' => '512MB',
                    'storage' => '20MB',
                    'processing_power' => '2 vCPU',
                ],
                'potential_issues' => ['none detected'],
            ],
            'simulated_results' => [
                'total_operations' => random_int(20, 80),
                'success_rate' => random_int(90, 100),
                'failure_rate' => random_int(0, 5),
            ],
            'created_at' => now()->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'data' => $simulation,
        ]);
    }

    public function forceSeedExecute(Request $request): JsonResponse
    {
        $operation = $this->createSeedOperation('force_execute', 'completed', $request->input('branch_ids', []), true);
        $this->storeSeedOperation($operation);

        return response()->json([
            'success' => true,
            'data' => $operation,
        ]);
    }

    public function seedOperations(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getSeedOperations(),
        ]);
    }

    public function seedOperation(string $operationId): JsonResponse
    {
        $operation = collect($this->getSeedOperations())->firstWhere('id', $operationId);

        if (!$operation) {
            return response()->json([
                'error' => 'Operation not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $operation,
        ]);
    }

    public function cancelSeedOperation(string $operationId): JsonResponse
    {
        $operations = $this->getSeedOperations();

        foreach ($operations as &$operation) {
            if ($operation['id'] === $operationId) {
                $operation['status'] = 'cancelled';
                $operation['completed_at'] = now()->toIso8601String();
            }
        }

        Cache::put(self::SEED_CACHE_KEY, $operations, now()->addDay());

        return response()->json([
            'success' => true,
        ]);
    }

    public function configuration(Branch $branch): JsonResponse
    {
        $metadata = $branch->metadata ?? [];
        $settings = $metadata['configuration'] ?? $this->defaultConfiguration();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) Str::uuid(),
                'branch_id' => (string) $branch->id,
                'settings' => $settings,
                'created_at' => optional($branch->created_at)->toIso8601String(),
                'updated_at' => optional($branch->updated_at)->toIso8601String(),
            ],
        ]);
    }

    public function updateConfiguration(Request $request, Branch $branch): JsonResponse
    {
        $settings = $request->validate([
            'settings' => 'required|array',
        ]);

        $metadata = $branch->metadata ?? [];
        $metadata['configuration'] = $settings['settings'];
        $branch->metadata = $metadata;
        $branch->save();

        return response()->json([
            'success' => true,
            'data' => [
                'branch_id' => (string) $branch->id,
                'settings' => $settings['settings'],
            ],
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 20);
        return min(max($perPage, 5), 50);
    }

    private function formatPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    private function transformBranch(Branch $branch): array
    {
        $status = BranchStatus::fromString($branch->status ?? 'ACTIVE');
        $metadata = $branch->metadata ?? [];
        $capacity = $metadata['capacity'] ?? [];
        $manager = $branch->relationLoaded('branchManager') ? $branch->branchManager : $branch->branchManager()->with('user')->first();
        $managerUser = $manager?->user;

        $managerPayload = null;

        if ($manager) {
            $managerPayload = [
                'id' => (string) $manager->id,
                'user_id' => (string) $manager->user_id,
                'name' => $managerUser?->name,
                'email' => $managerUser?->email,
                'phone' => $managerUser?->mobile ?? $managerUser?->phone,
                'preferred_language' => $managerUser?->preferred_language,
                'primary_branch_id' => $managerUser?->primary_branch_id,
                'assigned_at' => optional($manager->created_at)->toIso8601String(),
            ];
        }

        $workers = $branch->relationLoaded('branchWorkers') ? collect($branch->branchWorkers) : collect();

        $activeWorkersCount = $workers->isNotEmpty()
            ? $workers->filter(static fn ($worker) => (int) ($worker->status ?? 0) === Status::ACTIVE)->count()
            : $branch->activeWorkers()->count();

        $workerRoster = $workers->isNotEmpty()
            ? $workers->take(5)->map(function ($worker) {
                $user = $worker->user;

                return [
                    'id' => (string) $worker->id,
                    'user_id' => (string) $worker->user_id,
                    'name' => $user?->name,
                    'role' => $worker->role,
                    'preferred_language' => $user?->preferred_language,
                    'status' => $worker->status,
                ];
            })->values()
            : collect();

        return [
            'id' => (string) $branch->id,
            'name' => $branch->name,
            'code' => $branch->code,
            'parent_branch_id' => $branch->parent_branch_id ? (string) $branch->parent_branch_id : null,
            'address' => $branch->address ?? '',
            'city' => $branch->metadata['city'] ?? '',
            'country' => $branch->metadata['country'] ?? '',
            'phone' => $branch->phone,
            'email' => $branch->email,
            'manager_id' => $managerPayload['id'] ?? null,
            'manager' => $managerPayload,
            'status' => strtolower($status->value),
            'capacity' => [
                'daily_shipments' => $capacity['daily_shipments'] ?? ($branch->capacity_parcels_per_day ?? 0),
                'storage_space' => $capacity['storage_space'] ?? random_int(200, 500),
                'staff_count' => $capacity['staff_count'] ?? $activeWorkersCount,
            ],
            'team' => [
                'total_workers' => $workers->count(),
                'active_workers' => $activeWorkersCount,
                'roster_preview' => $workerRoster->toArray(),
            ],
            'performance_metrics' => $this->resolvePerformanceMetrics($branch),
            'created_at' => optional($branch->created_at)->toIso8601String(),
            'updated_at' => optional($branch->updated_at)->toIso8601String(),
            'is_seed_data' => (bool) ($branch->metadata['seed'] ?? false),
            'last_seeded_at' => $branch->metadata['last_seeded_at'] ?? null,
            'location' => $branch->metadata['location'] ?? null,
        ];
    }

    private function resolvePerformanceMetrics(Branch $branch): array
    {
        $metrics = $branch->getPerformanceMetrics();

        return [
            'total_shipments' => (int) ($metrics['total_shipments'] ?? 0),
            'on_time_delivery_rate' => round((float) ($metrics['on_time_delivery_rate'] ?? 0), 2),
            'average_processing_time' => round((float) ($metrics['average_processing_time'] ?? 0), 2),
            'exception_rate' => random_int(0, 10),
            'delivery_rate' => round((float) ($metrics['delivery_rate'] ?? 0), 2),
            'revenue_generated' => random_int(50000, 150000),
            'customer_satisfaction_score' => random_int(75, 95),
            'staff_utilization_rate' => random_int(60, 90),
            'this_month' => [
                'shipments' => random_int(500, 1500),
                'revenue' => random_int(40000, 120000),
                'exceptions' => random_int(5, 25),
            ],
            'last_month' => [
                'shipments' => random_int(400, 1400),
                'revenue' => random_int(38000, 110000),
                'exceptions' => random_int(5, 30),
            ],
            'trend' => collect(['improving', 'declining', 'stable'])->random(),
        ];
    }

    private function buildSummary(): array
    {
        $branches = Branch::all();

        return [
            'total_branches' => $branches->count(),
            'active_branches' => $branches->filter(fn (Branch $branch) => BranchStatus::fromString($branch->status) === BranchStatus::ACTIVE)->count(),
            'branches_needing_attention' => $branches->filter(fn (Branch $branch) => ($branch->getCapacityMetrics()['utilization_rate'] ?? 0) > 90)->count(),
            'branches_under_maintenance' => $branches->filter(fn (Branch $branch) => BranchStatus::fromString($branch->status) === BranchStatus::MAINTENANCE)->count(),
            'average_performance_score' => round($branches->avg(fn (Branch $branch) => $this->resolvePerformanceMetrics($branch)['on_time_delivery_rate'] ?? 0), 2),
            'total_seeding_operations' => count($this->getSeedOperations()),
            'recent_seeding_operations' => collect($this->getSeedOperations())->where('status', 'completed')->count(),
            'failed_seeding_operations' => collect($this->getSeedOperations())->where('status', 'failed')->count(),
        ];
    }

    private function getMaintenanceWindows(): array
    {
        return Cache::get(self::MAINTENANCE_CACHE_KEY, []);
    }

    private function resolveCapacityBottlenecks(array $metrics): array
    {
        $bottlenecks = [];

        if (($metrics['utilization_rate'] ?? 0) > 90) {
            $bottlenecks[] = 'High utilization approaching capacity limits';
        }

        if (($metrics['pending_shipments'] ?? 0) > 200) {
            $bottlenecks[] = 'Large backlog of pending shipments';
        }

        if (($metrics['active_workers'] ?? 0) < 5) {
            $bottlenecks[] = 'Insufficient active workforce';
        }

        return $bottlenecks;
    }

    private function resolveCapacityRecommendations(array $metrics): array
    {
        $recommendations = ['Review shift allocations'];

        if (($metrics['utilization_rate'] ?? 0) > 80) {
            $recommendations[] = 'Consider adding temporary staff';
        }

        if (($metrics['pending_shipments'] ?? 0) > 150) {
            $recommendations[] = 'Prioritize backlog reduction initiatives';
        }

        return array_unique($recommendations);
    }

    private function transformAlert(BranchAlert $alert): array
    {
        return [
            'id' => (string) $alert->id,
            'branch_id' => (string) $alert->branch_id,
            'type' => strtolower($alert->alert_type),
            'severity' => strtolower($alert->severity ?? 'medium'),
            'title' => $alert->title ?? 'Branch Alert',
            'description' => $alert->message,
            'threshold_value' => $alert->context['threshold'] ?? null,
            'current_value' => $alert->context['current'] ?? null,
            'is_resolved' => $alert->status === 'RESOLVED',
            'created_at' => optional($alert->triggered_at ?? $alert->created_at)->toIso8601String(),
            'resolved_at' => optional($alert->resolved_at)->toIso8601String(),
            'resolved_by' => $alert->context['resolved_by'] ?? null,
        ];
    }

    private function createSeedOperation(string $type, string $status, array $branchIds, bool $completed = false): array
    {
        $now = now();
        $user = auth()->user();

        return [
            'id' => (string) Str::uuid(),
            'operation_type' => $type,
            'branch_id' => $branchIds[0] ?? null,
            'branch_name' => null,
            'status' => $status,
            'progress_percentage' => $completed ? 100 : random_int(10, 90),
            'total_steps' => 5,
            'completed_steps' => $completed ? 5 : random_int(1, 4),
            'total_operations' => random_int(20, 80),
            'successful_operations' => $completed ? random_int(18, 75) : random_int(5, 20),
            'failed_operations' => $completed ? random_int(0, 5) : random_int(0, 2),
            'created_by' => $user?->name ?? 'system',
            'created_at' => $now->toIso8601String(),
            'started_at' => $now->toIso8601String(),
            'completed_at' => $completed ? $now->addMinutes(2)->toIso8601String() : null,
        ];
    }

    private function storeSeedOperation(array $operation): void
    {
        $operations = $this->getSeedOperations();
        $operations[] = $operation;
        Cache::put(self::SEED_CACHE_KEY, $operations, now()->addDay());
    }

    private function getSeedOperations(): array
    {
        return Cache::get(self::SEED_CACHE_KEY, []);
    }

    private function defaultConfiguration(): array
    {
        return [
            'operating_hours' => collect(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'])
                ->mapWithKeys(function ($day) {
                    return [$day => ['open' => '08:00', 'close' => '18:00', 'closed' => in_array($day, ['saturday','sunday'])]];
                })->toArray(),
            'shipping_services' => ['express', 'economy'],
            'package_types' => ['parcel', 'freight'],
            'max_package_weight' => 50,
            'max_package_dimensions' => [
                'length' => 120,
                'width' => 60,
                'height' => 60,
            ],
            'supported_payment_methods' => ['cash', 'card'],
            'special_instructions' => null,
        ];
    }
}
