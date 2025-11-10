<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\BranchStatus;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\BranchWorker;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchPortalController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        [$branch, $roleContext] = $this->resolveBranchContext($request->user());

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch access is not configured for this account.',
            ], 403);
        }

        $branch->loadMissing(['branchManager.user:id,name,email', 'activeWorkers.user:id,name,email']);

        $baseShipmentScope = Shipment::query()
            ->where(function ($query) use ($branch, $roleContext) {
                $query->where('origin_branch_id', $branch->id)
                      ->orWhere('dest_branch_id', $branch->id);

                if ($roleContext['type'] === 'worker') {
                    $query->orWhere('assigned_worker_id', $roleContext['worker_id']);
                }
            });

        $shipmentsQuery = (clone $baseShipmentScope)->with([
            'client:id,business_name',
            'destBranch:id,name,code',
        ]);

        $recentShipments = (clone $shipmentsQuery)
            ->latest('created_at')
            ->take(10)
            ->get([
                'id',
                'tracking_number',
                'current_status',
                'client_id',
                'dest_branch_id',
                'expected_delivery_date',
                'price_amount',
                'currency',
                'created_at',
            ]);

        $closedStatuses = [
            ShipmentStatus::DELIVERED->value,
            ShipmentStatus::RETURNED->value,
            ShipmentStatus::CANCELLED->value,
        ];

        $metrics = [
            'active_shipments' => (clone $baseShipmentScope)->whereNotIn('current_status', $closedStatuses)->count(),
            'delivered_today' => (clone $baseShipmentScope)->whereDate('delivered_at', now()->toDateString())->count(),
            'pending_pickups' => Shipment::where('origin_branch_id', $branch->id)
                ->whereIn('current_status', [
                    ShipmentStatus::BOOKED->value,
                    ShipmentStatus::PICKUP_SCHEDULED->value,
                    ShipmentStatus::PICKED_UP->value,
                ])->count(),
        ];

        $modeTotals = (clone $baseShipmentScope)
            ->selectRaw("COALESCE(mode, 'individual') as mode_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('mode_key')
            ->pluck('total', 'mode_key')
            ->mapWithKeys(fn ($count, $modeKey) => [ShipmentMode::fromString($modeKey)->value => $count]);

        $modeActiveTotals = (clone $baseShipmentScope)
            ->whereNotIn('current_status', $closedStatuses)
            ->selectRaw("COALESCE(mode, 'individual') as mode_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('mode_key')
            ->pluck('total', 'mode_key')
            ->mapWithKeys(fn ($count, $modeKey) => [ShipmentMode::fromString($modeKey)->value => $count]);

        $overallModeTotal = $modeTotals->sum() ?: 1;

        $modeDistribution = collect(ShipmentMode::cases())->map(function (ShipmentMode $mode) use ($modeTotals, $modeActiveTotals, $overallModeTotal) {
            $count = $modeTotals[$mode->value] ?? 0;
            $active = $modeActiveTotals[$mode->value] ?? 0;

            return [
                'mode' => $mode->value,
                'label' => $mode->label(),
                'count' => $count,
                'active' => $active,
                'percentage' => round(($count / $overallModeTotal) * 100, 1),
            ];
        })->values();

        $branchStatus = $this->resolveBranchStatus($branch);

        $response = [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'type' => $branch->type,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'email' => $branch->email,
                'status' => $branchStatus->value,
                'status_label' => $branchStatus->label(),
                'manager' => optional($branch->branchManager?->user)->only(['id', 'name', 'email']),
            ],
            'role' => $roleContext,
            'metrics' => $metrics,
            'mode_distribution' => $modeDistribution,
            'shipments' => $recentShipments->map(function (Shipment $shipment) {
                return [
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $shipment->current_status,
                    'client' => optional($shipment->client)->only(['id', 'business_name']),
                    'destination' => [
                        'id' => $shipment->destBranch?->id,
                        'name' => $shipment->destBranch?->name,
                    ],
                    'expected_delivery_date' => optional($shipment->expected_delivery_date)->toDateString(),
                    'amount' => $shipment->price_amount,
                    'currency' => $shipment->currency,
                    'created_at' => optional($shipment->created_at)->toIso8601String(),
                ];
            }),
            'links' => [
                'booking_wizard' => url('/admin/booking'),
                'shipments' => url('/admin/branches/shipments?branch_id=' . $branch->id),
                'branch_profile' => url('/admin/branches/' . $branch->id),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
    }

    private function resolveBranchStatus(Branch $branch): BranchStatus
    {
        $rawStatus = $branch->status;

        if (is_numeric($rawStatus)) {
            return BranchStatus::fromLegacy((int) $rawStatus);
        }

        if (is_string($rawStatus) && $rawStatus !== '') {
            return BranchStatus::fromString($rawStatus);
        }

        return BranchStatus::ACTIVE;
    }

    private function resolveBranchContext(?User $user): array
    {
        if (! $user) {
            return [null, []];
        }

        /** @var BranchManager|null $manager */
        $manager = $user->branchManager()->with('branch')->first();
        if ($manager && $manager->branch) {
            return [
                $manager->branch,
                [
                    'type' => 'manager',
                    'role' => 'branch_ops_manager',
                ],
            ];
        }

        /** @var BranchWorker|null $worker */
        $worker = $user->branchWorker()->with('branch')->first();
        if ($worker && $worker->branch) {
            return [
                $worker->branch,
                [
                    'type' => 'worker',
                    'role' => $worker->role instanceof \BackedEnum ? $worker->role->value : $worker->role,
                    'worker_id' => $worker->id,
                ],
            ];
        }

        return [null, []];
    }
}
