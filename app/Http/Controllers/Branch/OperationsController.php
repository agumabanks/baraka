<?php

namespace App\Http\Controllers\Branch;

use App\Enums\ShipmentStatus;
use App\Enums\ScanType;
use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\BranchAlert;
use App\Models\Shipment;
use App\Models\ScanEvent;
use App\Models\Backend\Hub;
use App\Models\Backend\BranchWorker;
use App\Models\BranchHandoff;
use App\Support\BranchCache;
use App\Services\Dispatch\AssignmentEngine;
use App\Services\Logistics\ShipmentLifecycleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OperationsController extends Controller
{
    use ResolvesBranch;

    public function shipments(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $direction = $request->get('direction', 'all');
        $status = $request->get('status');
        $dateFrom = $request->get('from');
        $dateTo = $request->get('to');

        // Build shipments query
        $shipmentsQuery = Shipment::query()
            ->with(['customer:id,name', 'assignedWorker.user:id,name', 'originBranch:id,name,code', 'destBranch:id,name,code'])
            ->when($direction === 'inbound', fn($q) => $q->where('dest_branch_id', $branch->id))
            ->when($direction === 'outbound', fn($q) => $q->where('origin_branch_id', $branch->id))
            ->when($direction === 'all', fn($q) => $q->where(function($inner) use ($branch) {
                $inner->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            }))
            ->when($status, fn($q) => $q->where('current_status', strtoupper($status)))
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest();

        $shipments = $shipmentsQuery->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => Shipment::where(function($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })->count(),
            
            'in_transit' => Shipment::where(function($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })->whereIn('current_status', ['IN_TRANSIT', 'AT_ORIGIN_HUB', 'AT_DESTINATION_HUB'])->count(),
            
            'delivered_today' => Shipment::where(function($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })->where('current_status', 'DELIVERED')
              ->whereDate('delivered_at', today())
              ->count(),
            
            'at_risk' => Shipment::where(function($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })->whereNotNull('expected_delivery_date')
              ->where('expected_delivery_date', '<=', now()->addHours(24))
              ->whereNull('delivered_at')
              ->count(),
            
            'inbound' => Shipment::where('dest_branch_id', $branch->id)
                ->whereNotIn('current_status', ['DELIVERED', 'CANCELLED'])
                ->count(),
            
            'outbound' => Shipment::where('origin_branch_id', $branch->id)
                ->whereNotIn('current_status', ['DELIVERED', 'CANCELLED'])
                ->count(),
        ];

        return view('branch.shipments', compact('branch', 'shipments', 'stats', 'direction', 'status'));
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $direction = $request->get('direction', 'outbound');
        $status = $request->get('status');
        $slaRisk = $request->boolean('sla_risk');
        $dateFrom = $request->date('from');
        $dateTo = $request->date('to');
        $perPage = $request->integer('per_page', 15);

        $shipments = Shipment::query()
            ->with(['assignedWorker.user:id,name', 'destBranch:id,name,code', 'originBranch:id,name,code'])
            ->when($direction === 'inbound', fn ($q) => $q->where('dest_branch_id', $branch->id))
            ->when($direction === 'outbound', fn ($q) => $q->where('origin_branch_id', $branch->id))
            ->when($direction === 'all', fn ($q) => $q->where(function ($inner) use ($branch) {
                $inner->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            }))
            ->when($status, fn ($q) => $q->where('current_status', ShipmentStatus::fromString($status)?->value ?? $status))
            ->when($slaRisk && Schema::hasColumn('shipments', 'expected_delivery_date'), function ($q) {
                $threshold = now()->addHours(24);
                $q->where(function ($inner) use ($threshold) {
                    $inner->whereNotNull('expected_delivery_date')
                        ->where(function ($deadline) use ($threshold) {
                            $deadline->where(function ($deliveredLate) {
                                $deliveredLate->whereNotNull('delivered_at')
                                    ->whereColumn('delivered_at', '>', 'expected_delivery_date');
                            })->orWhere(function ($atRisk) use ($threshold) {
                                $atRisk->whereNull('delivered_at')
                                    ->where('expected_delivery_date', '<=', $threshold);
                            });
                        });
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate($perPage);

        $backlog = Shipment::query()
            ->whereNull('assigned_worker_id')
            ->where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)
                    ->orWhere('dest_branch_id', $branch->id);
            })
            ->count();

        $alerts = BranchAlert::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'OPEN')
            ->orderByDesc('triggered_at')
            ->limit(6)
            ->get();

        $maintenance = BranchAlert::query()
            ->where('branch_id', $branch->id)
            ->where('alert_type', 'MAINTENANCE')
            ->orderByDesc('triggered_at')
            ->limit(4)
            ->get();

        $activeMaintenance = $this->activeMaintenanceAlerts($branch);
        $maintenanceCapacity = $activeMaintenance
            ->map(fn ($alert) => (int) ($alert->context['capacity_factor'] ?? 100))
            ->min() ?? 100;

        $workers = BranchWorker::query()
            ->with('user:id,name')
            ->where('branch_id', $branch->id)
            ->active()
            ->orderBy('role')
            ->get();

        return view('branch.operations', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'shipments' => $shipments,
            'backlog' => $backlog,
            'alerts' => $alerts,
            'maintenance' => $maintenance,
            'workers' => $workers,
            'activeMaintenance' => $activeMaintenance,
            'maintenanceCapacity' => $maintenanceCapacity,
            'perPage' => $perPage,
            'filters' => [
                'direction' => $direction,
                'status' => $status,
                'sla_risk' => $slaRisk,
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }

    /**
     * View exceptions/problem shipments
     */
    public function exceptions(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $exceptionStatuses = [
            ShipmentStatus::EXCEPTION->value ?? 'EXCEPTION',
            ShipmentStatus::FAILED_DELIVERY->value ?? 'FAILED_DELIVERY',
            ShipmentStatus::ON_HOLD->value ?? 'ON_HOLD',
            ShipmentStatus::RETURNED_TO_SENDER->value ?? 'RETURNED_TO_SENDER',
        ];

        $exceptions = Shipment::query()
            ->with(['customer:id,company_name,contact_person', 'assignedWorker.user:id,name', 'originBranch:id,name,code', 'destBranch:id,name,code'])
            ->where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->whereIn('current_status', $exceptionStatuses)
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => $exceptions->total(),
            'failed_delivery' => Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })->where('current_status', ShipmentStatus::FAILED_DELIVERY->value ?? 'FAILED_DELIVERY')->count(),
            'on_hold' => Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })->where('current_status', ShipmentStatus::ON_HOLD->value ?? 'ON_HOLD')->count(),
            'returned' => Shipment::where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })->where('current_status', ShipmentStatus::RETURNED_TO_SENDER->value ?? 'RETURNED_TO_SENDER')->count(),
        ];

        return view('branch.exceptions.index', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'exceptions' => $exceptions,
            'stats' => $stats,
        ]);
    }

    public function assign(Request $request, AssignmentEngine $assignmentEngine, ShipmentLifecycleService $lifecycleService): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_id' => 'required|integer',
            'worker_id' => 'nullable|integer',
            'auto' => 'sometimes|boolean',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        $activeMaintenance = $this->activeMaintenanceAlerts($branch);
        $blockedMaintenance = $activeMaintenance->first(function ($alert) {
            return (int) ($alert->context['capacity_factor'] ?? 100) <= 0;
        });

        if ($blockedMaintenance) {
            $until = data_get($blockedMaintenance->context, 'ends_at');

            return back()->with('error', 'Assignments paused during maintenance window'.($until ? ' until '.$until : ''));
        }

        if (! empty($data['auto'])) {
            $worker = $assignmentEngine->autoAssign($shipment, $branch);
            if (! $worker) {
                return back()->with('error', 'No available worker for auto-assignment.');
            }
        } else {
            $worker = BranchWorker::where('branch_id', $branch->id)->findOrFail($data['worker_id']);
            $shipment->assigned_worker_id = $worker->id;
            $shipment->assigned_at = now();
            $shipment->save();
        }

        $current = ShipmentStatus::fromString((string) ($shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status));
        if ($current === ShipmentStatus::BOOKED) {
            $lifecycleService->transition($shipment, ShipmentStatus::PICKUP_SCHEDULED, [
                'performed_by_user' => $user,
            ]);
        }

        BranchCache::flushForBranches([$branch->id, $shipment->origin_branch_id, $shipment->dest_branch_id]);

        return back()->with('success', 'Shipment assigned to '.$worker->user?->name ?? 'worker');
    }

    public function updateStatus(Request $request, ShipmentLifecycleService $lifecycleService): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_id' => 'required|integer',
            'status' => 'required|string',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        $status = ShipmentStatus::fromString($data['status']);
        if (! $status) {
            abort(422, 'Unknown status');
        }

        if ($user->cannot('updateStatus', $shipment)) {
            abort(403);
        }

        try {
            $lifecycleService->transition($shipment, $status, [
                'performed_by_user' => $user,
            ]);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'status' => [$e->getMessage()],
            ]);
        }

        BranchCache::flushForBranches([$branch->id, $shipment->origin_branch_id, $shipment->dest_branch_id]);

        return back()->with('success', 'Shipment '.$shipment->id.' updated to '.$status->label());
    }

    public function raiseAlert(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'severity' => 'required|in:info,warning,critical',
            'message' => 'required|string|max:255',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        BranchAlert::create([
            'branch_id' => $branch->id,
            'alert_type' => 'SLA_RISK',
            'severity' => strtoupper($data['severity']),
            'status' => 'OPEN',
            'title' => 'SLA risk on '.$shipment->tracking_number,
            'message' => $data['message'],
            'context' => [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'expected_delivery_date' => $shipment->expected_delivery_date,
                'current_status' => $shipment->current_status,
            ],
            'triggered_at' => now(),
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Alert raised for SLA risk.');
    }

    public function resolveAlert(Request $request, BranchAlert $alert): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if ($alert->branch_id !== $branch->id) {
            abort(403);
        }

        $alert->markResolved('Resolved by '.$user->name);
        BranchCache::flushForBranch($alert->branch_id);

        return back()->with('success', 'Alert resolved.');
    }

    public function scan(Request $request, ShipmentLifecycleService $lifecycleService): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'tracking_number' => 'required|string',
            'mode' => 'nullable|in:bag,load,unload,route,delivery,returns',
        ]);

        $shipment = Shipment::where('tracking_number', $data['tracking_number'])->first();

        if (! $shipment) {
            return Redirect::back()->with('error', 'Tracking not found');
        }

        $belongsToBranch = $shipment->origin_branch_id === $branch->id || $shipment->dest_branch_id === $branch->id;
        if (! $belongsToBranch) {
            return Redirect::back()->with('error', 'MISRouted: belongs to another branch');
        }

        $mode = $data['mode'] ?? 'route';
        $modeMap = [
            'bag' => ['scan' => ScanType::BAGGED, 'status' => ShipmentStatus::BAGGED],
            'load' => ['scan' => ScanType::LINEHAUL_DEPARTED, 'status' => ShipmentStatus::LINEHAUL_DEPARTED],
            'unload' => ['scan' => ScanType::DESTINATION_ARRIVAL, 'status' => ShipmentStatus::AT_DESTINATION_HUB],
            'route' => ['scan' => ScanType::OUT_FOR_DELIVERY, 'status' => ShipmentStatus::OUT_FOR_DELIVERY],
            'delivery' => ['scan' => ScanType::DELIVERY_CONFIRMED, 'status' => ShipmentStatus::DELIVERED],
            'returns' => ['scan' => ScanType::RETURN_INITIATED, 'status' => ShipmentStatus::RETURN_INITIATED],
        ];

        if ($mode === 'unload' && $shipment->dest_branch_id !== $branch->id) {
            return Redirect::back()->with('error', 'Unload denied: destination branch mismatch');
        }

        if ($shipment->current_status === ShipmentStatus::DELIVERED->value && $mode !== 'returns') {
            return Redirect::back()->with('error', 'Duplicate: already delivered');
        }

        $scanType = $modeMap[$mode]['scan'] ?? null;
        $targetStatus = $modeMap[$mode]['status'] ?? null;

        $hub = Hub::query()->where('branch_code', $branch->code)->first();
        if (! $hub) {
            $hub = Hub::create([
                'name' => $branch->name ?? 'Branch Hub '.$branch->id,
                'branch_code' => $branch->code ?? 'BR-'.$branch->id,
                'branch_type' => $branch->is_hub ? 'hub' : 'regional',
                'address' => $branch->address ?? ($branch->code ?? 'BR-'.$branch->id),
            ]);
        }
        $hubId = $hub->id;

        $scanEvent = ScanEvent::create([
            'sscc' => $shipment->barcode ?? $shipment->tracking_number,
            'shipment_id' => $shipment->id,
            'branch_id' => $hubId,
            'type' => $scanType,
            'status_after' => $targetStatus,
            'user_id' => $user->id,
            'occurred_at' => now(),
            'location_type' => 'branch',
            'location_id' => $branch->id,
            'payload' => [
                'mode' => $mode,
            ],
        ]);

        if ($targetStatus instanceof ShipmentStatus) {
            try {
                $force = $mode === 'returns' && ShipmentStatus::fromString((string) $shipment->current_status)?->isTerminal();
                $lifecycleService->transition($shipment, $targetStatus, [
                    'performed_by_user' => $user,
                    'scan_event' => $scanEvent,
                    'location_type' => 'branch',
                    'location_id' => $branch->id,
                    'trigger' => 'scan.'.$mode,
                    'timestamp' => now(),
                    'force' => $force,
                ]);
            } catch (\Throwable $e) {
                if ($mode === 'returns') {
                    $shipment->update([
                        'current_status' => $targetStatus->value,
                        'status' => strtolower($targetStatus->value),
                        'return_initiated_at' => $shipment->return_initiated_at ?? now(),
                    ]);
                } else {
                    return Redirect::back()->with('error', $e->getMessage());
                }
            }
        }

        return Redirect::back()->with('success', ucfirst($mode).' scan recorded for '.$shipment->tracking_number);
    }

    public function requestHandoff(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'dest_branch_id' => 'required|integer|exists:branches,id',
            'notes' => 'nullable|string|max:255',
            'expected_hand_off_at' => 'nullable|date',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        $handoff = BranchHandoff::create([
            'shipment_id' => $shipment->id,
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $data['dest_branch_id'],
            'requested_by' => $user->id,
            'status' => 'PENDING',
            'notes' => $data['notes'] ?? null,
            'expected_hand_off_at' => $request->date('expected_hand_off_at') ?: null,
        ]);

        activity()->performedOn($shipment)->causedBy($user)->withProperties([
            'handoff_id' => $handoff->id,
            'to_branch' => $data['dest_branch_id'],
        ])->log('Branch handoff requested');

        return back()->with('success', 'Handoff requested to branch.');
    }

    public function approveHandoff(Request $request, BranchHandoff $handoff): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if ($handoff->dest_branch_id !== $branch->id) {
            abort(403);
        }

        $handoff->update([
            'status' => 'APPROVED',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        activity()->performedOn($handoff->shipment)->causedBy($user)->withProperties([
            'handoff_id' => $handoff->id,
        ])->log('Branch handoff approved');

        return back()->with('success', 'Handoff approved.');
    }

    public function completeHandoff(Request $request, BranchHandoff $handoff): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if ($handoff->dest_branch_id !== $branch->id && $handoff->origin_branch_id !== $branch->id) {
            abort(403);
        }

        $handoff->update([
            'handoff_completed_at' => now(),
        ]);

        activity()->performedOn($handoff->shipment)->causedBy($user)->withProperties([
            'handoff_id' => $handoff->id,
        ])->log('Branch handoff completed');

        BranchCache::flushForBranches([$handoff->origin_branch_id, $handoff->dest_branch_id]);

        return back()->with('success', 'Handoff completed and recorded.');
    }

    public function handoffManifest(BranchHandoff $handoff, Request $request)
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if ($handoff->dest_branch_id !== $branch->id && $handoff->origin_branch_id !== $branch->id) {
            abort(403);
        }

        $format = $request->get('format', 'csv');
        $handoff->load(['shipment.originBranch', 'shipment.destBranch', 'originBranch', 'destBranch']);
        $shipment = $handoff->shipment;

        if ($format === 'pdf') {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('branch.operations.manifest', compact('handoff', 'shipment'));

                return $pdf->download("handoff_manifest_{$handoff->id}.pdf");
            }

            return response(view('branch.operations.manifest', compact('handoff', 'shipment'))->render(), 200, [
                'Content-Type' => 'text/html',
            ]);
        }

        $csv = "handoff_id,tracking_number,origin,destination,status,expected,notes\n";
        $statusValue = $shipment?->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment?->current_status;
        $csv .= "{$handoff->id},{$shipment->tracking_number},{$shipment->originBranch?->code},{$shipment->destBranch?->code},{$statusValue},{$shipment->expected_delivery_date},\"{$handoff->notes}\"\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=handoff_manifest_{$handoff->id}.csv",
        ]);
    }

    public function batchHandoffManifest(Request $request)
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'status' => 'nullable|in:PENDING,APPROVED,REJECTED',
            'direction' => 'nullable|in:outbound,inbound,all',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'format' => 'nullable|in:csv,pdf',
        ]);

        $format = $data['format'] ?? 'csv';

        $handoffs = BranchHandoff::query()
            ->with(['shipment.originBranch', 'shipment.destBranch', 'originBranch', 'destBranch'])
            ->where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['from'] ?? null, fn ($q, $from) => $q->whereDate('expected_hand_off_at', '>=', $from))
            ->when($data['to'] ?? null, fn ($q, $to) => $q->whereDate('expected_hand_off_at', '<=', $to))
            ->when(($data['direction'] ?? null) === 'outbound', fn ($q) => $q->where('origin_branch_id', $branch->id))
            ->when(($data['direction'] ?? null) === 'inbound', fn ($q) => $q->where('dest_branch_id', $branch->id))
            ->latest('expected_hand_off_at')
            ->limit(250)
            ->get();

        if ($format === 'pdf') {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('branch.operations.manifest_batch', [
                    'branch' => $branch,
                    'handoffs' => $handoffs,
                ]);

                return $pdf->download('handoff_manifest_batch.pdf');
            }

            return response(view('branch.operations.manifest_batch', [
                'branch' => $branch,
                'handoffs' => $handoffs,
            ])->render(), 200, ['Content-Type' => 'text/html']);
        }

        $lines = ["handoff_id,tracking_number,origin,destination,status,expected,notes"];
        foreach ($handoffs as $item) {
            $shipment = $item->shipment;
            $lines[] = implode(',', [
                $item->id,
                $shipment?->tracking_number,
                $shipment?->originBranch?->code,
                $shipment?->destBranch?->code,
                $shipment?->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment?->current_status,
                optional($item->expected_hand_off_at)->toDateTimeString(),
                '"'.str_replace('"', '\"', $item->notes ?? '').'"',
            ]);
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=handoff_manifest_batch.csv',
        ]);
    }

    public function reprioritize(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_id' => 'required|integer',
            'priority' => 'required|integer|min:0|max:100',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        $shipment->priority = $data['priority'];
        $shipment->save();

        BranchCache::flushForBranches([$branch->id]);

        return back()->with('success', 'Shipment priority updated.');
    }

    public function hold(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_id' => 'required|integer',
            'reason' => 'nullable|string|max:255',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        $shipment->update([
            'held_at' => now(),
            'held_by' => $user->id,
            'hold_reason' => $data['reason'] ?? null,
        ]);

        BranchCache::flushForBranches([$branch->id]);

        return back()->with('success', 'Shipment placed on hold.');
    }

    public function reroute(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'shipment_id' => 'required|integer',
            'dest_branch_id' => 'required|integer|exists:branches,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($data['shipment_id']);

        $shipment->update([
            'rerouted_from_branch_id' => $shipment->dest_branch_id,
            'dest_branch_id' => $data['dest_branch_id'],
            'rerouted_at' => now(),
            'rerouted_by' => $user->id,
            'metadata' => array_merge($shipment->metadata ?? [], [
                'reroute_reason' => $data['reason'] ?? null,
            ]),
        ]);

        BranchCache::flushForBranches([$branch->id, $shipment->origin_branch_id, $shipment->dest_branch_id]);

        return back()->with('success', 'Shipment rerouted.');
    }

    public function shipmentManifest(Request $request)
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'ids' => 'nullable|string',
            'direction' => 'nullable|in:outbound,inbound,all',
            'status' => 'nullable|string',
            'format' => 'nullable|in:csv,pdf',
        ]);

        $ids = collect(explode(',', (string) ($data['ids'] ?? '')))->filter()->map(fn ($id) => (int) $id);

        $shipments = Shipment::query()
            ->where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids))
            ->when(($data['direction'] ?? null) === 'outbound', fn ($q) => $q->where('origin_branch_id', $branch->id))
            ->when(($data['direction'] ?? null) === 'inbound', fn ($q) => $q->where('dest_branch_id', $branch->id))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('current_status', $status))
            ->with(['originBranch', 'destBranch'])
            ->limit(200)
            ->get();

        $format = $data['format'] ?? 'csv';

        if ($format === 'pdf' && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = Pdf::loadView('branch.operations.shipment_manifest', [
                'branch' => $branch,
                'shipments' => $shipments,
            ]);

            return $pdf->download('shipment_manifest.pdf');
        }

        $lines = ["tracking_number,origin,destination,status,expected_delivery"];
        foreach ($shipments as $shipment) {
            $statusValue = $shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status;
            $lines[] = implode(',', [
                $shipment->tracking_number,
                $shipment->originBranch?->code,
                $shipment->destBranch?->code,
                $statusValue,
                optional($shipment->expected_delivery_date)->toDateTimeString(),
            ]);
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=shipment_manifest.csv',
        ]);
    }

    public function routeManifest(Request $request)
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'status' => 'nullable|string',
            'format' => 'nullable|in:csv,pdf',
        ]);

        $statuses = $data['status'] ? [$data['status']] : [
            ShipmentStatus::OUT_FOR_DELIVERY->value,
            ShipmentStatus::LINEHAUL_DEPARTED->value,
            ShipmentStatus::LINEHAUL_ARRIVED->value,
        ];

        $shipments = Shipment::query()
            ->where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
            })
            ->whereIn('current_status', $statuses)
            ->with(['originBranch', 'destBranch'])
            ->limit(200)
            ->get();

        $format = $data['format'] ?? 'csv';

        if ($format === 'pdf' && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = Pdf::loadView('branch.operations.route_manifest', [
                'branch' => $branch,
                'shipments' => $shipments,
                'statuses' => $statuses,
            ]);

            return $pdf->download('route_manifest.pdf');
        }

        $lines = ["tracking_number,origin,destination,status,expected_delivery"];
        foreach ($shipments as $shipment) {
            $lines[] = implode(',', [
                $shipment->tracking_number,
                $shipment->originBranch?->code,
                $shipment->destBranch?->code,
                $shipment->current_status,
                optional($shipment->expected_delivery_date)->toDateTimeString(),
            ]);
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=route_manifest.csv',
        ]);
    }

    public function scheduleMaintenance(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'title' => 'required|string|max:160',
            'window_starts_at' => 'required|date',
            'window_ends_at' => 'required|date|after_or_equal:window_starts_at',
            'message' => 'nullable|string',
            'capacity_factor' => 'nullable|integer|min:0|max:100',
        ]);

        $capacity = $data['capacity_factor'] ?? 100;

        BranchAlert::create([
            'branch_id' => $branch->id,
            'alert_type' => 'MAINTENANCE',
            'severity' => $capacity <= 50 ? 'high' : 'medium',
            'status' => 'OPEN',
            'title' => $data['title'],
            'message' => $data['message'] ?? 'Scheduled maintenance window',
            'context' => [
                'starts_at' => $data['window_starts_at'],
                'ends_at' => $data['window_ends_at'],
                'capacity_factor' => $capacity,
            ],
            'triggered_at' => now(),
        ]);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Maintenance window scheduled.');
    }

    private function activeMaintenanceAlerts($branch)
    {
        return BranchAlert::query()
            ->where('branch_id', $branch->id)
            ->where('alert_type', 'MAINTENANCE')
            ->where('status', 'OPEN')
            ->get()
            ->filter(function (BranchAlert $alert) {
                $ctx = $alert->context ?? [];
                $starts = isset($ctx['starts_at']) ? Carbon::parse($ctx['starts_at']) : null;
                $ends = isset($ctx['ends_at']) ? Carbon::parse($ctx['ends_at']) : null;

                if ($starts && $starts->isFuture()) {
                    return false;
                }

                if ($ends && $ends->isPast()) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /**
     * Maintenance window management
     */
    public function maintenance(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $query = \App\Models\MaintenanceWindow::query()
            ->where('branch_id', $branch->id)
            ->with('creator:id,name');

        // Apply filters
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('scheduled_start_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('scheduled_end_at', '<=', $request->to);
        }

        $maintenanceWindows = $query->orderByDesc('scheduled_start_at')->paginate(15);
        $activeMaintenanceWindows = \App\Models\MaintenanceWindow::active()
            ->where('branch_id', $branch->id)
            ->get();

        return view('branch.operations.maintenance', compact('maintenanceWindows', 'activeMaintenanceWindows', 'branch'));
    }

    public function storeMaintenance(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'entity_type' => 'required|in:branch,vehicle,warehouse_location',
            'entity_id' => 'required|integer',
            'maintenance_type' => 'required|in:scheduled,emergency,repair,inspection',
            'capacity_impact_percent' => 'required|integer|min:0|max:100',
            'scheduled_start_at' => 'required|date',
            'scheduled_end_at' => 'required|date|after:scheduled_start_at',
            'description' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data['branch_id'] = $branch->id;
        $data['created_by'] = $user->id;
        $data['status'] = 'scheduled';

        \App\Models\MaintenanceWindow::create($data);

        // Create alert for upcoming maintenance
        BranchAlert::create([
            'branch_id' => $branch->id,
            'alert_type' => 'MAINTENANCE',
            'severity' => 'INFO',
            'status' => 'OPEN',
            'title' => 'Maintenance Scheduled',
            'message' => "Maintenance scheduled for {$data['entity_type']} #{$data['entity_id']}",
            'context' => [
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'scheduled_start' => $data['scheduled_start_at'],
                'capacity_impact' => $data['capacity_impact_percent'],
            ],
            'triggered_at' => now(),
        ]);

        BranchCache::flushForBranch($branch->id);

        return redirect()->route('branch.operations.maintenance')->with('success', 'Maintenance window scheduled successfully.');
    }

    public function startMaintenance(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $window = \App\Models\MaintenanceWindow::where('branch_id', $branch->id)->findOrFail($id);
        $window->markStarted();

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Maintenance window started.');
    }

    public function completeMaintenance(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $data = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $window = \App\Models\MaintenanceWindow::where('branch_id', $branch->id)->findOrFail($id);
        $window->markCompleted($data['notes'] ?? null);

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Maintenance window completed.');
    }

    public function cancelMaintenance(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $window = \App\Models\MaintenanceWindow::where('branch_id', $branch->id)->findOrFail($id);
        $window->cancel();

        BranchCache::flushForBranch($branch->id);

        return back()->with('success', 'Maintenance window cancelled.');
    }

    public function getMaintenanceEntities(Request $request)
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $type = $request->get('type');
        $entities = [];

        switch ($type) {
            case 'branch':
                $entities = [['id' => $branch->id, 'name' => $branch->name]];
                break;
            case 'vehicle':
                $entities = \App\Models\Backend\Vehicle::where('branch_id', $branch->id)
                    ->get(['id', 'plate_no as name'])
                    ->toArray();
                break;
            case 'warehouse_location':
                $entities = \App\Models\WhLocation::where('branch_id', $branch->id)
                    ->get(['id', 'code as name'])
                    ->toArray();
                break;
        }

        return response()->json($entities);
    }
}
