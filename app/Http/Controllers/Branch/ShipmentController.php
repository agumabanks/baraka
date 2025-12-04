<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\ShipmentService;
use App\Services\LabelGeneratorService;
use App\Models\Backend\Branch;
use App\Models\Backend\Client;
use App\Models\User;
use App\Enums\ShipmentStatus;
use App\Services\BranchContext;
use App\Services\Logistics\ShipmentLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    protected $shipmentService;
    protected $labelGenerator;
    protected ShipmentLifecycleService $lifecycleService;

    public function __construct(ShipmentService $shipmentService, LabelGeneratorService $labelGenerator, ShipmentLifecycleService $lifecycleService)
    {
        $this->shipmentService = $shipmentService;
        $this->labelGenerator = $labelGenerator;
        $this->lifecycleService = $lifecycleService;
    }

    public function index(Request $request)
    {
        $branchId = BranchContext::currentId();

        $query = Shipment::query()
            ->with(['customer', 'customerProfile', 'originBranch', 'destBranch'])
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->where('origin_branch_id', $branchId)
                        ->orWhere('dest_branch_id', $branchId);
                });
            })
            ->latest();

        // Enhanced search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('waybill_number', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customerProfile', function($cq) use ($search) {
                      $cq->where('contact_person', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $statusValue = ShipmentStatus::fromString($request->status)?->value ?? $request->status;
            $query->where(function ($q) use ($statusValue, $request) {
                $q->where('current_status', $statusValue)
                  ->orWhere('status', $request->status);
            });
        }

        // Date range filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = in_array($perPage, [15, 25, 50, 100]) ? $perPage : 15;

        $shipments = $query->paginate($perPage)->withQueryString();

        // Calculate stats for this branch
        $statsQuery = Shipment::query()
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->where('origin_branch_id', $branchId)
                        ->orWhere('dest_branch_id', $branchId);
                });
            });

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'delivered' => (clone $statsQuery)->where(function ($q) {
                $q->where('current_status', 'DELIVERED')
                  ->orWhere('status', 'delivered');
            })->count(),
            'in_transit' => (clone $statsQuery)->where(function ($q) {
                $q->where('current_status', 'IN_TRANSIT')
                  ->orWhere('current_status', 'LINEHAUL_DEPARTED')
                  ->orWhere('status', 'in_transit');
            })->count(),
            'out_for_delivery' => (clone $statsQuery)->where(function ($q) {
                $q->where('current_status', 'OUT_FOR_DELIVERY')
                  ->orWhere('status', 'out_for_delivery');
            })->count(),
            'pending' => (clone $statsQuery)->where(function ($q) {
                $q->whereIn('current_status', ['CREATED', 'BOOKED', 'PICKED_UP', 'PROCESSING'])
                  ->orWhereIn('status', ['created', 'booked', 'picked_up', 'processing']);
            })->count(),
        ];

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'html' => view('branch.shipments._table', compact('shipments'))->render(),
                'pagination' => view('branch.shipments._pagination', compact('shipments', 'perPage'))->render(),
                'stats' => $stats,
            ]);
        }

        return view('branch.shipments.index', compact('shipments', 'stats', 'perPage'));
    }

    public function create()
    {
        $this->authorize('create', Shipment::class);
        $branchId = BranchContext::currentId();

        $branches = Branch::all();
        $clients = Client::all();
        // In a real app, we'd filter customers based on permissions or search them via AJAX
        $customers = User::where('role_id', '!=', 1)->limit(50)->get(); 

        return view('branch.shipments.create', compact('branches', 'clients', 'customers', 'branchId'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Shipment::class);

        $branchId = BranchContext::currentId() ?? $request->user()->primary_branch_id;

        if (! $branchId) {
            abort(403, 'No active branch context.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'dest_branch_id' => 'required|exists:branches,id',
            'service_level' => 'required|string',
            'incoterms' => 'nullable|string',
            'payer_type' => 'required|in:sender,receiver,third_party',
            'parcels' => 'required|array|min:1',
            'parcels.*.weight_kg' => 'required|numeric|min:0.1',
            'parcels.*.length_cm' => 'required|numeric|min:1',
            'parcels.*.width_cm' => 'required|numeric|min:1',
            'parcels.*.height_cm' => 'required|numeric|min:1',
        ]);

        $payload = array_merge($validated, [
            'origin_branch_id' => $branchId,
            'current_status' => ShipmentStatus::BOOKED->value,
            'status' => strtolower(ShipmentStatus::BOOKED->value), // Legacy field, auto-synced
        ]);

        $shipment = $this->shipmentService->createShipment($payload, Auth::user());

        // Ensure lifecycle timestamps are set via lifecycle service
        $this->lifecycleService->transition($shipment, ShipmentStatus::BOOKED, [
            'performed_by_user' => Auth::user(),
            'timestamp' => $shipment->booked_at ?? now(),
        ]);

        return redirect()->route('branch.shipments.show', $shipment)
            ->with('success', 'Shipment created successfully');
    }

    public function show(Request $request, Shipment $shipment)
    {
        $this->authorize('view', $shipment);
        
        // Load relationships (parcels may not exist in legacy schema)
        try {
            $shipment->load(['shipmentEvents.user', 'originBranch', 'destBranch', 'customer', 'customerProfile']);
        } catch (\Exception $e) {
            $shipment->load(['originBranch', 'destBranch', 'customer', 'customerProfile']);
        }

        // Return JSON for AJAX requests (modal view)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'shipment' => $shipment->toArray(),
            ]);
        }

        return view('branch.shipments.show', compact('shipment'));
    }

    public function edit(Shipment $shipment)
    {
        $this->authorize('update', $shipment);
        $branchId = BranchContext::currentId();

        // Verify shipment belongs to this branch
        if ($branchId && ! in_array($branchId, [$shipment->origin_branch_id, $shipment->dest_branch_id], true)) {
            abort(403, 'Shipment does not belong to your branch');
        }

        // Don't allow editing of delivered/cancelled shipments
        $currentStatus = $shipment->current_status;
        $statusValue = is_object($currentStatus) ? $currentStatus->value : $currentStatus;
        if ($statusValue && in_array(strtoupper($statusValue), ['DELIVERED', 'CANCELLED', 'RETURNED'])) {
            return redirect()->route('branch.shipments.show', $shipment)
                ->with('error', 'Cannot edit a ' . strtolower($statusValue) . ' shipment');
        }

        $branches = Branch::all();

        return view('branch.shipments.edit', compact('shipment', 'branches', 'branchId'));
    }

    public function update(Request $request, Shipment $shipment)
    {
        $this->authorize('update', $shipment);
        $branchId = BranchContext::currentId();

        // Verify shipment belongs to this branch
        if ($branchId && ! in_array($branchId, [$shipment->origin_branch_id, $shipment->dest_branch_id], true)) {
            abort(403, 'Shipment does not belong to your branch');
        }

        // Don't allow editing of delivered/cancelled shipments
        $currentStatus = $shipment->current_status;
        $statusValue = is_object($currentStatus) ? $currentStatus->value : $currentStatus;
        if ($statusValue && in_array(strtoupper($statusValue), ['DELIVERED', 'CANCELLED', 'RETURNED'])) {
            return redirect()->route('branch.shipments.show', $shipment)
                ->with('error', 'Cannot edit a ' . strtolower($statusValue) . ' shipment');
        }

        $validated = $request->validate([
            'dest_branch_id' => 'required|exists:branches,id',
            'service_level' => 'required|string',
            'incoterms' => 'nullable|string',
            'payer_type' => 'required|in:sender,receiver,third_party',
            'special_instructions' => 'nullable|string|max:2000',
            'declared_value' => 'nullable|numeric|min:0',
            'insurance_amount' => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date',
            'chargeable_weight_kg' => 'nullable|numeric|min:0.01|max:10000',
            'piece_count' => 'nullable|integer|min:1',
            'package_type' => 'nullable|string|max:50',
            'contents_description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Update shipment
            $shipment->update([
                'dest_branch_id' => $validated['dest_branch_id'],
                'service_level' => $validated['service_level'],
                'incoterms' => $validated['incoterms'] ?? $shipment->incoterms,
                'payer_type' => $validated['payer_type'],
                'special_instructions' => $validated['special_instructions'] ?? $shipment->special_instructions,
                'declared_value' => $validated['declared_value'] ?? 0,
                'insurance_amount' => $validated['insurance_amount'] ?? 0,
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'chargeable_weight_kg' => $validated['chargeable_weight_kg'] ?? $shipment->chargeable_weight_kg,
                'piece_count' => $validated['piece_count'] ?? $shipment->piece_count,
                'package_type' => $validated['package_type'] ?? $shipment->package_type,
                'contents_description' => $validated['contents_description'] ?? $shipment->contents_description,
            ]);

            // Log the update
            $this->lifecycleService->logEvent($shipment, 'UPDATED', 'Shipment updated via branch portal', Auth::user());

            DB::commit();

            return redirect()->route('branch.shipments.show', $shipment)
                ->with('success', 'Shipment updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Branch shipment update failed', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Failed to update shipment: ' . $e->getMessage()]);
        }
    }

    public function label(Request $request, Shipment $shipment)
    {
        $this->authorize('view', $shipment);
        $branchId = BranchContext::currentId();
        if ($branchId && ! in_array($branchId, [$shipment->origin_branch_id, $shipment->dest_branch_id], true)) {
            abort(403);
        }

        $format = $request->get('format', 'html');

        if ($format === 'pdf' && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($this->labelGenerator->generateLabel($shipment));
            return $pdf->download("label_{$shipment->tracking_number}.pdf");
        }

        if ($format === 'zpl') {
            $zpl = $this->labelGenerator->generateZpl($shipment);
            return response($zpl, 200, ['Content-Type' => 'text/plain']);
        }

        $html = $this->labelGenerator->generateLabel($shipment);
        return response($html);
    }

    public function labels(Request $request)
    {
        $branchId = BranchContext::currentId();
        $ids = collect(explode(',', (string) $request->get('ids', '')))->filter()->unique()->values();

        $query = Shipment::query()
            ->when($branchId, fn ($q) => $q->where(function ($inner) use ($branchId) {
                $inner->where('origin_branch_id', $branchId)->orWhere('dest_branch_id', $branchId);
            }))
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids))
            ->latest()
            ->limit(100);

        $shipments = $query->get();

        if ($shipments->isEmpty()) {
            abort(404, 'No shipments found');
        }

        $format = $request->get('format', 'html');

        if ($format === 'pdf' && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($this->labelGenerator->generateBulkLabels($shipments));
            return $pdf->download('shipment_labels.pdf');
        }

        if ($format === 'zpl') {
            $zpl = $shipments->map(fn ($s) => $this->labelGenerator->generateZpl($s))->implode("\n\n");
            return response($zpl, 200, ['Content-Type' => 'text/plain']);
        }

        $html = $this->labelGenerator->generateBulkLabels($shipments);
        return response($html);
    }

    public function tracking(Shipment $shipment)
    {
        $shipment->load('shipmentEvents');
        return view('branch.shipments.tracking', compact('shipment'));
    }

    /**
     * Bulk update status for multiple shipments
     */
    public function bulkUpdateStatus(Request $request)
    {
        $branchId = BranchContext::currentId();

        $validated = $request->validate([
            'shipment_ids' => 'required|array|min:1|max:100',
            'shipment_ids.*' => 'required|integer|exists:shipments,id',
            'status' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $status = ShipmentStatus::fromString($validated['status']);
        if (!$status) {
            return response()->json(['success' => false, 'error' => 'Invalid status'], 422);
        }

        // Verify all shipments belong to this branch
        $shipments = Shipment::whereIn('id', $validated['shipment_ids'])
            ->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                    ->orWhere('dest_branch_id', $branchId);
            })
            ->get();

        if ($shipments->count() !== count($validated['shipment_ids'])) {
            return response()->json([
                'success' => false,
                'error' => 'Some shipments do not belong to your branch',
            ], 403);
        }

        $updated = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($shipments as $shipment) {
                try {
                    $this->lifecycleService->transition($shipment, $status, [
                        'performed_by_user' => Auth::user(),
                        'notes' => $validated['notes'] ?? null,
                        'bulk_operation' => true,
                    ]);
                    $updated++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "#{$shipment->tracking_number}: {$e->getMessage()}";
                }
            }

            DB::commit();

            // Clear branch cache
            \App\Support\BranchCache::flushForBranch($branchId);

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} shipment(s)" . ($failed > 0 ? ", {$failed} failed" : ''),
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk status update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Bulk update failed'], 500);
        }
    }

    /**
     * Bulk assign shipments to a worker
     */
    public function bulkAssign(Request $request)
    {
        $branchId = BranchContext::currentId();

        $validated = $request->validate([
            'shipment_ids' => 'required|array|min:1|max:50',
            'shipment_ids.*' => 'required|integer|exists:shipments,id',
            'worker_id' => 'required|integer|exists:branch_workers,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Verify worker belongs to this branch
        $worker = \App\Models\Backend\BranchWorker::where('branch_id', $branchId)
            ->find($validated['worker_id']);

        if (!$worker) {
            return response()->json(['success' => false, 'error' => 'Worker not found in this branch'], 404);
        }

        // Verify all shipments belong to this branch
        $shipments = Shipment::whereIn('id', $validated['shipment_ids'])
            ->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                    ->orWhere('dest_branch_id', $branchId);
            })
            ->whereNull('assigned_worker_id')
            ->get();

        $assigned = 0;

        DB::beginTransaction();
        try {
            foreach ($shipments as $shipment) {
                $shipment->update([
                    'assigned_worker_id' => $worker->id,
                    'assigned_at' => now(),
                ]);

                // Update status if currently BOOKED
                if ($shipment->current_status === ShipmentStatus::BOOKED->value) {
                    $this->lifecycleService->transition($shipment, ShipmentStatus::PICKUP_SCHEDULED, [
                        'performed_by_user' => Auth::user(),
                    ]);
                }

                $assigned++;
            }

            DB::commit();
            \App\Support\BranchCache::flushForBranch($branchId);

            return response()->json([
                'success' => true,
                'message' => "Assigned {$assigned} shipment(s) to {$worker->user->name}",
                'assigned' => $assigned,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk assignment failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Bulk assignment failed'], 500);
        }
    }

    /**
     * Export shipments to CSV
     */
    public function export(Request $request)
    {
        $branchId = BranchContext::currentId();

        $query = Shipment::query()
            ->with(['originBranch:id,name,code', 'destBranch:id,name,code', 'customer:id,name'])
            ->where(function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId)
                    ->orWhere('dest_branch_id', $branchId);
            });

        // Apply filters
        if ($request->filled('status')) {
            $query->where('current_status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $shipments = $query->latest()->limit(1000)->get();

        $filename = 'shipments_export_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($shipments) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Tracking Number',
                'Status',
                'Origin',
                'Destination',
                'Customer',
                'Service Level',
                'Weight (kg)',
                'Price',
                'Created At',
                'Expected Delivery',
                'Delivered At',
            ]);

            foreach ($shipments as $shipment) {
                fputcsv($handle, [
                    $shipment->tracking_number,
                    $shipment->current_status,
                    $shipment->originBranch?->name,
                    $shipment->destBranch?->name,
                    $shipment->customer?->name,
                    $shipment->service_level,
                    $shipment->chargeable_weight_kg,
                    $shipment->price_amount,
                    $shipment->created_at?->format('Y-m-d H:i'),
                    $shipment->expected_delivery_date?->format('Y-m-d H:i'),
                    $shipment->delivered_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
