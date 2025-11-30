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
            ->with(['customer', 'originBranch', 'destBranch', 'parcels'])
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->where('origin_branch_id', $branchId)
                        ->orWhere('dest_branch_id', $branchId);
                });
            })
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('waybill_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $statusValue = ShipmentStatus::fromString($request->status)?->value ?? $request->status;
            $query->where('current_status', $statusValue);
        }

        $shipments = $query->paginate(15);

        return view('branch.shipments.index', compact('shipments'));
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

    public function show(Shipment $shipment)
    {
        $this->authorize('view', $shipment);
        $shipment->load(['parcels', 'shipmentEvents.user', 'originBranch', 'destBranch']);
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
        if ($currentStatus && in_array($currentStatus->value, ['DELIVERED', 'CANCELLED', 'RETURNED'])) {
            return redirect()->route('branch.shipments.show', $shipment)
                ->with('error', 'Cannot edit a ' . strtolower($currentStatus->value) . ' shipment');
        }

        $branches = Branch::all();
        $shipment->load('parcels');

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
        if ($currentStatus && in_array($currentStatus->value, ['DELIVERED', 'CANCELLED', 'RETURNED'])) {
            return redirect()->route('branch.shipments.show', $shipment)
                ->with('error', 'Cannot edit a ' . strtolower($currentStatus->value) . ' shipment');
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
            'parcels' => 'sometimes|array',
            'parcels.*.id' => 'nullable|exists:parcels,id',
            'parcels.*.weight_kg' => 'required|numeric|min:0.01|max:10000',
            'parcels.*.length_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.width_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.height_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Update shipment
            $shipment->update([
                'dest_branch_id' => $validated['dest_branch_id'],
                'service_level' => $validated['service_level'],
                'incoterms' => $validated['incoterms'] ?? $shipment->incoterms,
                'payer_type' => $validated['payer_type'],
                'special_instructions' => $validated['special_instructions'],
                'declared_value' => $validated['declared_value'] ?? 0,
                'insurance_amount' => $validated['insurance_amount'] ?? 0,
                'expected_delivery_date' => $validated['expected_delivery_date'],
            ]);

            // Update parcels if provided
            if (!empty($validated['parcels'])) {
                $existingParcelIds = [];
                foreach ($validated['parcels'] as $parcelData) {
                    if (!empty($parcelData['id'])) {
                        // Update existing parcel
                        $parcel = $shipment->parcels()->find($parcelData['id']);
                        if ($parcel) {
                            $parcel->update([
                                'weight_kg' => $parcelData['weight_kg'],
                                'length_cm' => $parcelData['length_cm'] ?? $parcel->length_cm,
                                'width_cm' => $parcelData['width_cm'] ?? $parcel->width_cm,
                                'height_cm' => $parcelData['height_cm'] ?? $parcel->height_cm,
                                'description' => $parcelData['description'] ?? $parcel->description,
                            ]);
                            $existingParcelIds[] = $parcel->id;
                        }
                    } else {
                        // Create new parcel
                        $newParcel = $this->shipmentService->addParcel($shipment, $parcelData);
                        $existingParcelIds[] = $newParcel->id;
                    }
                }

                // Delete removed parcels
                $shipment->parcels()->whereNotIn('id', $existingParcelIds)->delete();
            }

            // Recalculate totals
            $shipment->calculateTotals();

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
}
