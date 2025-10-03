<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ShipmentStatus;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\BranchWorker;
use App\Models\Shipment;
use App\Services\BranchAnalyticsService;
use App\Services\BranchCapacityService;
use App\Services\BranchHierarchyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchNetworkController extends Controller
{
    public function __construct(
        protected BranchHierarchyService $hierarchyService,
        protected BranchAnalyticsService $analyticsService,
        protected BranchCapacityService $capacityService
    ) {}

    /**
     * Return paginated branch directory with operational snapshots.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $since24Hours = Carbon::now()->subDay();

        $inboundStatuses = [
            ShipmentStatus::ARRIVE->value,
            ShipmentStatus::ARRIVE_DEST->value,
            ShipmentStatus::IN_TRANSIT->value,
            ShipmentStatus::CUSTOMS_HOLD->value,
        ];

        $outboundStatuses = [
            ShipmentStatus::CREATED->value,
            ShipmentStatus::HANDED_OVER->value,
            ShipmentStatus::SORT->value,
            ShipmentStatus::LOAD->value,
            ShipmentStatus::DEPART->value,
            ShipmentStatus::IN_TRANSIT->value,
            ShipmentStatus::OUT_FOR_DELIVERY->value,
        ];

        $query = Branch::query()
            ->with([
                'parent:id,name,code',
                'branchManager:id,branch_id,user_id',
                'branchManager.user:id,name,email,phone',
            ])
            ->withCount([
                'activeWorkers as active_workers_count',
                'branchWorkers as total_workers_count',
                'primaryClients as active_clients_count' => function ($query) {
                    $query->where('status', Status::ACTIVE);
                },
                'destinationShipments as inbound_queue_count' => function ($query) use ($inboundStatuses) {
                    $query->whereIn('current_status', $inboundStatuses);
                },
                'originShipments as outbound_queue_count' => function ($query) use ($outboundStatuses) {
                    $query->whereIn('current_status', $outboundStatuses);
                },
                'originShipments as exception_queue_count' => function ($query) {
                    $query->where('has_exception', true);
                },
                'originShipments as throughput_24h' => function ($query) use ($since24Hours) {
                    $query->where('created_at', '>=', $since24Hours);
                },
            ]);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('is_hub')) {
            $query->where('is_hub', $request->boolean('is_hub'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_branch_id', $request->integer('parent_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $branches = $query
            ->orderBy('parent_branch_id')
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn (Branch $branch) => $this->toListPayload($branch));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $branches->items(),
                'meta' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'per_page' => $branches->perPage(),
                    'total' => $branches->total(),
                ],
                'filters' => [
                    'types' => ['HUB', 'REGIONAL', 'LOCAL'],
                ],
            ],
        ]);
    }

    /**
     * Return a detailed branch profile including analytics and hierarchy context.
     */
    public function show(Branch $branch): JsonResponse
    {
        $branch->load([
            'parent:id,name,code',
            'children:id,name,code,parent_branch_id',
            'branchManager:id,branch_id,user_id,business_name,current_balance,status',
            'branchManager.user:id,name,email,phone',
            'activeWorkers:id,branch_id,user_id,role,status,assigned_at',
            'activeWorkers.user:id,name,email,phone',
        ]);

        $recentShipments = $branch->originShipments()
            ->with([
                'destBranch:id,name,code',
                'assignedWorker.user:id,name',
            ])
            ->latest('created_at')
            ->take(6)
            ->get();

        $analytics = $this->analyticsService->getBranchPerformanceAnalytics($branch);
        $capacity = $this->capacityService->getCapacityAnalysis($branch);

        return response()->json([
            'success' => true,
            'data' => [
                'branch' => $this->toDetailPayload($branch, $recentShipments),
                'analytics' => $analytics,
                'capacity' => $capacity,
                'hierarchy' => [
                    'ancestors' => $this->hierarchyService->getAncestors($branch)->map(function (Branch $ancestor) {
                        return [
                            'id' => $ancestor->id,
                            'name' => $ancestor->name,
                            'code' => $ancestor->code,
                            'type' => $ancestor->type,
                            'is_hub' => (bool) $ancestor->is_hub,
                        ];
                    })->values(),
                    'descendants' => $this->hierarchyService->getAllDescendants($branch)->map(function (Branch $descendant) {
                        return [
                            'id' => $descendant->id,
                            'name' => $descendant->name,
                            'code' => $descendant->code,
                            'type' => $descendant->type,
                            'parent_id' => $descendant->parent_branch_id,
                        ];
                    })->values(),
                ],
            ],
        ]);
    }

    /**
     * Provide a full hierarchy tree for network visualisations.
     */
    public function hierarchy(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'tree' => $this->hierarchyService->getHierarchyTree(),
            ],
        ]);
    }

    /**
     * Format list payload consumed by the React dashboard.
     */
    protected function toListPayload(Branch $branch): array
    {
        $capacityMetrics = $branch->getCapacityMetrics();

        $operationalState = $this->resolveOperationalState($branch, $capacityMetrics);

        return [
            'id' => $branch->id,
            'code' => $branch->code,
            'name' => $branch->name,
            'type' => $branch->type,
            'status' => $branch->status,
            'status_label' => $operationalState['label'],
            'status_state' => $operationalState['state'],
            'is_hub' => (bool) $branch->is_hub,
            'address' => $branch->address,
            'coordinates' => [
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
            ],
            'parent' => $branch->parent ? [
                'id' => $branch->parent->id,
                'name' => $branch->parent->name,
                'code' => $branch->parent->code,
            ] : null,
            'manager' => $branch->branchManager ? [
                'id' => $branch->branchManager->id,
                'name' => $branch->branchManager->user?->name,
                'email' => $branch->branchManager->user?->email,
                'phone' => $branch->branchManager->user?->phone,
            ] : null,
            'workforce' => [
                'active' => (int) ($branch->active_workers_count ?? 0),
                'total' => (int) ($branch->total_workers_count ?? 0),
            ],
            'metrics' => [
                'capacity_utilization' => $capacityMetrics['utilization_rate'] ?? 0,
                'active_clients' => (int) ($branch->active_clients_count ?? 0),
                'throughput_24h' => (int) ($branch->throughput_24h ?? 0),
            ],
            'queues' => $this->formatQueues($branch),
            'operating' => [
                'opening_time' => $this->resolveOpeningTime($branch),
            ],
            'hierarchy_path' => $branch->hierarchy_path,
        ];
    }

    /**
     * Extend list payload with children and metadata for detail view.
     */
    protected function toDetailPayload(Branch $branch, $recentShipments = null): array
    {
        $recentShipments ??= collect();

        return array_merge(
            $this->toListPayload($branch),
            [
                'children' => $branch->children->map(function (Branch $child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'code' => $child->code,
                        'type' => $child->type,
                        'status' => $child->status,
                    ];
                })->values(),
                'team' => [
                    'manager' => $this->transformManager($branch->branchManager),
                    'active_workers' => $branch->activeWorkers->map(function (BranchWorker $worker) {
                        return $this->transformWorker($worker);
                    })->values(),
                ],
                'recent_shipments' => $recentShipments->map(function (Shipment $shipment) {
                    return [
                        'id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number ?? $shipment->public_token,
                        'status' => $shipment->current_status,
                        'price_amount' => $shipment->price_amount,
                        'service_level' => $shipment->service_level,
                        'destination_branch' => $shipment->destBranch ? [
                            'id' => $shipment->destBranch->id,
                            'name' => $shipment->destBranch->name,
                            'code' => $shipment->destBranch->code,
                        ] : null,
                        'assigned_worker' => $shipment->assignedWorker?->user?->name,
                        'created_at' => $shipment->created_at,
                        'expected_delivery_date' => $shipment->expected_delivery_date,
                    ];
                })->values(),
                'insights' => [
                    'open_queues' => $branch->getCapacityMetrics()['pending_shipments'] ?? 0,
                    'active_workers' => $branch->activeWorkers->count(),
                    'manager_status' => $branch->branchManager?->status ?? null,
                ],
            ]
        );
    }

    protected function transformManager(?BranchManager $manager): ?array
    {
        if (!$manager) {
            return null;
        }

        $manager->loadMissing(['user', 'branch']);

        $settlementSummary = $manager->getSettlementSummary();
        $performance = $manager->getPerformanceMetrics();

        return [
            'id' => $manager->id,
            'name' => $manager->user?->name,
            'email' => $manager->user?->email,
            'phone' => $manager->user?->phone,
            'business_name' => $manager->business_name,
            'status' => $manager->status,
            'current_balance' => $manager->current_balance,
            'settlement_summary' => $settlementSummary,
            'performance_metrics' => [
                'shipments_last_30_days' => $performance['shipments_last_30_days'] ?? 0,
                'delivery_success_rate' => $performance['delivery_success_rate'] ?? 0,
                'on_time_delivery_rate' => $performance['on_time_delivery_rate'] ?? 0,
                'revenue_last_30_days' => $performance['revenue_last_30_days'] ?? 0,
                'average_shipment_value' => $performance['average_shipment_value'] ?? 0,
            ],
            'pending_requests' => $manager->paymentRequests()->where('status', 'pending')->count(),
        ];
    }

    protected function transformWorker(BranchWorker $worker): array
    {
        $assignedCount = $worker->assignedShipments()
            ->whereIn('current_status', [
                ShipmentStatus::CREATED->value,
                ShipmentStatus::HANDED_OVER->value,
                ShipmentStatus::SORT->value,
                ShipmentStatus::LOAD->value,
                ShipmentStatus::DEPART->value,
                ShipmentStatus::IN_TRANSIT->value,
                ShipmentStatus::OUT_FOR_DELIVERY->value,
            ])
            ->count();

        return [
            'id' => $worker->id,
            'name' => $worker->user?->name,
            'email' => $worker->user?->email,
            'phone' => $worker->user?->phone,
            'role' => $worker->role,
            'status' => $worker->status,
            'assigned_at' => $worker->assigned_at,
            'active_assignments' => $assignedCount,
        ];
    }

    /**
     * Convert queue counts into a consumable structure for progress bars.
     */
    protected function formatQueues(Branch $branch): array
    {
        $inbound = (int) ($branch->inbound_queue_count ?? 0);
        $outbound = (int) ($branch->outbound_queue_count ?? 0);
        $exceptions = (int) ($branch->exception_queue_count ?? 0);

        return [
            [
                'id' => 'inbound',
                'label' => 'Inbound',
                'value' => $inbound,
                'max' => $this->deriveQueueMax($inbound),
            ],
            [
                'id' => 'outbound',
                'label' => 'Outbound',
                'value' => $outbound,
                'max' => $this->deriveQueueMax($outbound),
            ],
            [
                'id' => 'exceptions',
                'label' => 'Exceptions',
                'value' => $exceptions,
                'max' => $this->deriveQueueMax($exceptions, 30),
            ],
        ];
    }

    protected function deriveQueueMax(int $value, int $floor = 50): int
    {
        $base = max($floor, $value);

        return (int) ceil($base * 1.2);
    }

    protected function resolveOpeningTime(Branch $branch): ?string
    {
        $hours = $branch->operating_hours;

        if (is_array($hours) && isset($hours['start'])) {
            return $hours['start'];
        }

        if (is_array($hours) && isset($hours['monday']['start'])) {
            return $hours['monday']['start'];
        }

        return null;
    }

    protected function resolveOperationalState(Branch $branch, array $capacityMetrics): array
    {
        $utilization = (float) ($capacityMetrics['utilization_rate'] ?? 0);

        if ((int) $branch->status !== Status::ACTIVE) {
            return [
                'label' => 'Maintenance',
                'state' => 'maintenance',
            ];
        }

        if ($utilization >= 95) {
            return [
                'label' => 'Delayed',
                'state' => 'delayed',
            ];
        }

        return [
            'label' => 'Operational',
            'state' => 'operational',
        ];
    }
}
