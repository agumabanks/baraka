<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PaymentTransaction;
use App\Models\RouteCapability;
use App\Models\RouteTemplate;
use App\Models\Shipment;
use App\Models\ShipmentAudit;
use App\Models\ShipmentDraft;
use App\Models\SupervisorOverride;
use App\Services\Finance\PostingService;
use App\Services\RatingService;
use App\Services\ShipmentAuditService;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosApiController extends Controller
{
    public function __construct(
        protected RatingService $ratingService,
        protected ShipmentService $shipmentService,
        protected ShipmentAuditService $auditService,
        protected PostingService $postingService
    ) {}

    /**
     * POS-RATE-02: Get a quote for a shipment
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_branch_id' => 'required|integer|exists:branches,id',
            'destination_branch_id' => 'required|integer|exists:branches,id',
            'service_level' => 'required|string|in:economy,standard,express,priority',
            'weight' => 'required|numeric|min:0.01',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'insurance_type' => 'nullable|string|in:none,basic,full,premium',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'zone' => 'nullable|string',
        ]);

        $quote = $this->ratingService->quote($validated);

        return response()->json([
            'success' => true,
            'data' => $quote,
        ]);
    }

    /**
     * POS-BR-03: Get route capabilities
     */
    public function routeCapabilities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_branch_id' => 'required|integer|exists:branches,id',
            'destination_branch_id' => 'required|integer|exists:branches,id',
        ]);

        $capabilities = RouteCapability::forRoute($validated['origin_branch_id'], $validated['destination_branch_id'])
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $capabilities,
        ]);
    }

    /**
     * POS-UX-06: Get route templates for a branch
     */
    public function routeTemplates(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id', auth()->user()->branch_id);

        $templates = RouteTemplate::active()
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->with(['originBranch:id,name,code', 'destinationBranch:id,name,code'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * POS-REL-01: Save a shipment draft
     */
    public function saveDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_id' => 'nullable|uuid',
            'payload' => 'required|array',
        ]);

        $draftId = $validated['draft_id'] ?? (string) Str::uuid();

        $draft = ShipmentDraft::updateOrCreate(
            ['id' => $draftId],
            [
                'payload' => $validated['payload'],
                'created_by' => auth()->id(),
                'branch_id' => auth()->user()->branch_id,
                'status' => 'draft',
                'expires_at' => now()->addHours(24),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'draft_id' => $draft->id,
                'expires_at' => $draft->expires_at,
            ],
        ]);
    }

    /**
     * POS-REL-02: Create shipment with idempotency
     */
    public function createShipment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_id' => 'nullable|uuid',
            'idempotency_key' => 'required|string|max:64',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'origin_branch_id' => 'required|integer|exists:branches,id',
            'destination_branch_id' => 'required|integer|exists:branches,id',
            'service_level' => 'required|string',
            'weight' => 'required|numeric|min:0.01',
            'dimensions' => 'nullable|array',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:50',
            'delivery_address' => 'required|string|max:500',
            'description' => 'nullable|string|max:500',
            'declared_value' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'insurance_type' => 'nullable|string',
            'payer_type' => 'nullable|string|in:sender,receiver,third_party,account',
            'content_type' => 'nullable|string|in:document,parcel,battery,liquid,hazmat,other',
            'special_instructions' => 'nullable|string|max:1000',
        ]);

        // Check idempotency - return existing shipment if already created
        $existingShipment = Shipment::where('metadata->idempotency_key', $validated['idempotency_key'])->first();
        if ($existingShipment) {
            return response()->json([
                'success' => true,
                'data' => $existingShipment->load('originBranch', 'destBranch'),
                'message' => 'Shipment already exists (idempotent)',
            ]);
        }

        DB::beginTransaction();
        try {
            // Get quote for pricing
            $quote = $this->ratingService->quote($validated);

            // Create shipment via service
            $shipmentData = array_merge($validated, [
                'price_amount' => $quote['total'],
                'currency' => $quote['currency'],
                'base_rate' => $quote['base_freight'],
                'weight_charge' => $quote['weight_charge'],
                'surcharges_total' => $quote['surcharges_total'] + $quote['fuel_surcharge'],
                'insurance_fee' => $quote['insurance_fee'],
                'cod_fee' => $quote['cod_fee'],
                'tax_amount' => $quote['tax'],
                'rate_table_version' => $quote['rate_table_version'],
                'metadata' => [
                    'idempotency_key' => $validated['idempotency_key'],
                    'quote' => $quote,
                ],
            ]);

            $shipment = $this->shipmentService->createShipment($shipmentData);

            // Mark draft as completed if provided
            if (!empty($validated['draft_id'])) {
                ShipmentDraft::where('id', $validated['draft_id'])
                    ->update([
                        'status' => 'completed',
                        'shipment_id' => $shipment->id,
                    ]);
                $shipment->draft_id = $validated['draft_id'];
                $shipment->save();
            }

            // Log audit
            $this->auditService->logCreated($shipment);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $shipment->fresh()->load('originBranch', 'destBranch'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POS-PAY-02: Process payment with idempotency
     */
    public function processPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'idempotency_key' => 'required|string|max:64',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string|in:cash,card,mobile_money,bank_transfer,on_account,cheque',
            'payer_type' => 'nullable|string|in:sender,receiver,third_party,account',
            'external_reference' => 'nullable|string|max:100',
        ]);

        // Check idempotency
        $existingPayment = PaymentTransaction::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existingPayment) {
            return response()->json([
                'success' => true,
                'data' => $existingPayment,
                'message' => 'Payment already processed (idempotent)',
            ]);
        }

        DB::beginTransaction();
        try {
            $shipment = Shipment::findOrFail($validated['shipment_id']);

            $payment = PaymentTransaction::create([
                'shipment_id' => $shipment->id,
                'customer_id' => $shipment->customer_id,
                'idempotency_key' => $validated['idempotency_key'],
                'amount' => $validated['amount'],
                'currency' => $shipment->currency ?? 'UGX',
                'status' => 'completed',
                'method' => $validated['method'],
                'payer_type' => $validated['payer_type'] ?? $shipment->payer_type ?? 'sender',
                'external_reference' => $validated['external_reference'],
                'created_by' => auth()->id(),
                'branch_id' => auth()->user()->branch_id,
                'completed_at' => now(),
            ]);

            // Update shipment payment status
            $shipment->update([
                'payment_status' => $validated['amount'] >= $shipment->price_amount ? 'paid' : 'partial',
            ]);

            // Post accounting entries
            $this->postingService->postPayment($payment);

            // Log audit
            $this->auditService->logPaymentReceived($shipment, $validated['amount'], $validated['method']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $payment,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POS-REL-03: Print label (track prints)
     */
    public function printLabel(Request $request, Shipment $shipment): JsonResponse
    {
        $isReprint = $shipment->label_print_count > 0;

        // If reprint by non-admin, may require approval
        if ($isReprint && !auth()->user()->hasRole(['admin', 'branch_admin'])) {
            return response()->json([
                'success' => false,
                'error' => 'Reprint requires supervisor approval',
                'requires_approval' => true,
            ], 403);
        }

        $shipment->increment('label_print_count');
        $shipment->update(['last_label_printed_at' => now()]);

        $this->auditService->logLabelPrinted($shipment, $isReprint);

        return response()->json([
            'success' => true,
            'data' => [
                'print_count' => $shipment->label_print_count,
                'is_reprint' => $isReprint,
                'label_url' => route('admin.pos.label', $shipment),
            ],
        ]);
    }

    /**
     * POS-SEC-03: Request supervisor override
     */
    public function requestOverride(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_id' => 'nullable|integer|exists:shipments,id',
            'action_type' => 'required|string|in:discount,cancel,backdate,reprint,price_override,refund',
            'reason' => 'required|string|max:500',
            'request_data' => 'nullable|array',
        ]);

        $override = SupervisorOverride::requestOverride(
            $validated['action_type'],
            auth()->id(),
            $validated['reason'],
            $validated['shipment_id'] ?? null,
            $validated['request_data'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $override,
            'message' => 'Override request submitted. Awaiting supervisor approval.',
        ], 201);
    }

    /**
     * POS-SEC-03: Approve supervisor override
     */
    public function approveOverride(Request $request, SupervisorOverride $override): JsonResponse
    {
        $validated = $request->validate([
            'supervisor_password' => 'required|string',
            'approved_data' => 'nullable|array',
        ]);

        // Verify supervisor has appropriate role
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'branch_admin', 'supervisor'])) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient permissions to approve overrides',
            ], 403);
        }

        // Verify password
        if (!\Hash::check($validated['supervisor_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid supervisor password',
            ], 401);
        }

        // Check if already processed or expired
        if ($override->status !== SupervisorOverride::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Override request has already been processed',
            ], 400);
        }

        if ($override->isExpired()) {
            $override->update(['status' => SupervisorOverride::STATUS_EXPIRED]);
            return response()->json([
                'success' => false,
                'error' => 'Override request has expired',
            ], 400);
        }

        $override->approve($user->id, $validated['approved_data']);

        return response()->json([
            'success' => true,
            'data' => $override->fresh(),
            'message' => 'Override approved successfully',
        ]);
    }

    /**
     * Get shipment audit history
     */
    public function getAuditHistory(Shipment $shipment): JsonResponse
    {
        $audits = $this->auditService->getHistory($shipment->id);

        return response()->json([
            'success' => true,
            'data' => $audits,
        ]);
    }

    /**
     * Search customers for POS
     */
    public function searchClients(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['success' => true, 'results' => []]);
        }

        $results = Customer::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('company', 'like', "%{$query}%");
        })
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email', 'company', 'address']);

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
}
