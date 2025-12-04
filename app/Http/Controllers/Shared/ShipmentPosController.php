<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Backend\Branch;
use App\Models\User;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\RouteCapability;
use App\Models\ServiceConstraint;
use App\Models\ShipmentAudit;
use App\Services\ShipmentService;
use App\Services\RatingService;
use App\Services\Pricing\RateCalculationService;
use App\Services\LabelGeneratorService;
use App\Services\BranchContext;
use App\Services\Logistics\ShipmentLifecycleService;
use App\Services\ShipmentAuditService;
use App\Services\Finance\PostingService;
use App\Enums\ShipmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class ShipmentPosController extends Controller
{
    protected ShipmentService $shipmentService;
    protected RateCalculationService $rateService;
    protected RatingService $ratingService;
    protected LabelGeneratorService $labelGenerator;
    protected ShipmentLifecycleService $lifecycleService;
    protected ShipmentAuditService $auditService;
    protected PostingService $postingService;
    protected string $context; // 'admin' or 'branch'

    public function __construct(
        ShipmentService $shipmentService,
        RateCalculationService $rateService,
        RatingService $ratingService,
        LabelGeneratorService $labelGenerator,
        ShipmentLifecycleService $lifecycleService,
        ShipmentAuditService $auditService,
        PostingService $postingService
    ) {
        $this->shipmentService = $shipmentService;
        $this->rateService = $rateService;
        $this->ratingService = $ratingService;
        $this->labelGenerator = $labelGenerator;
        $this->lifecycleService = $lifecycleService;
        $this->auditService = $auditService;
        $this->postingService = $postingService;
    }

    /**
     * Show POS interface
     */
    public function index(Request $request)
    {
        $this->authorize('create', Shipment::class);

        $context = $this->getContext($request);
        $branchId = $context === 'branch' ? BranchContext::currentId() : null;

        $branches = Branch::orderBy('name')->get();
        $currentBranch = $branchId ? Branch::find($branchId) : null;

        // Get recent shipments for quick reference
        $recentShipments = Shipment::with(['customer', 'originBranch', 'destBranch'])
            ->when($branchId, fn($q) => $q->where('origin_branch_id', $branchId))
            ->latest()
            ->limit(10)
            ->get();

        // Get today's stats
        $todayStats = $this->getTodayStats($branchId);

        // Load system settings
        $settings = DB::table('general_settings')->first();
        $details = json_decode($settings->details ?? '{}', true);
        $financeSettings = $details['finance'] ?? [];
        $operationsSettings = $details['operations'] ?? [];
        
        $systemConfig = [
            // Company Info
            'currency' => $financeSettings['primary_currency'] ?? $settings->currency ?? 'USD',
            'currency_symbol' => $this->getCurrencySymbol($financeSettings['primary_currency'] ?? $settings->currency ?? 'USD'),
            'company_name' => $settings->name ?? config('app.name', 'Baraka Logistics'),
            'company_phone' => $settings->phone ?? '',
            'company_email' => $settings->email ?? '',
            'company_address' => $settings->address ?? '',
            'logo' => $settings->logo ?? null,
            
            // Pricing Settings
            'vat_rate' => (float)($financeSettings['vat_rate'] ?? 18),
            'fuel_surcharge' => (float)($financeSettings['fuel_surcharge'] ?? 8),
            'insurance_rate' => (float)($financeSettings['insurance_rate'] ?? 1.5),
            'min_charge' => (float)($financeSettings['min_charge'] ?? 5000),
            'decimal_places' => (int)($financeSettings['decimal_places'] ?? 0),
            'currency_position' => $financeSettings['currency_position'] ?? 'before',
            'thousand_separator' => $financeSettings['thousand_separator'] ?? ',',
            
            // Invoicing
            'invoice_prefix' => $settings->invoice_prefix ?? 'INV-',
            'tracking_prefix' => $settings->par_track_prefix ?? 'BRK',
            
            // Payment Methods
            'payment_methods' => [
                'cash' => (bool)($financeSettings['payment_cash'] ?? true),
                'card' => (bool)($financeSettings['payment_card'] ?? false),
                'mobile_money' => (bool)($financeSettings['payment_mobile_money'] ?? true),
                'bank_transfer' => (bool)($financeSettings['payment_bank_transfer'] ?? true),
                'credit' => (bool)($financeSettings['payment_credit'] ?? true),
            ],
            
            // Operations
            'auto_generate_tracking' => (bool)($operationsSettings['auto_generate_tracking_ids'] ?? true),
            'require_pod' => (bool)($operationsSettings['enforce_pod_otp'] ?? true),
            
            // Service Levels with default pricing multipliers
            'service_levels' => [
                'economy' => ['name' => 'Economy', 'days' => '5-7', 'multiplier' => 0.8, 'icon' => 'clock'],
                'standard' => ['name' => 'Standard', 'days' => '3-5', 'multiplier' => 1.0, 'icon' => 'package'],
                'express' => ['name' => 'Express', 'days' => '1-2', 'multiplier' => 1.5, 'icon' => 'zap'],
                'priority' => ['name' => 'Priority', 'days' => 'Same Day', 'multiplier' => 2.0, 'icon' => 'flame'],
            ],
            
            // Package Presets
            'package_presets' => [
                ['name' => 'Document', 'weight' => 0.5, 'l' => 35, 'w' => 25, 'h' => 2, 'icon' => 'file-text'],
                ['name' => 'Small Box', 'weight' => 2, 'l' => 30, 'w' => 20, 'h' => 15, 'icon' => 'box'],
                ['name' => 'Medium Box', 'weight' => 5, 'l' => 45, 'w' => 35, 'h' => 25, 'icon' => 'package'],
                ['name' => 'Large Box', 'weight' => 10, 'l' => 60, 'w' => 45, 'h' => 35, 'icon' => 'archive'],
                ['name' => 'Pallet', 'weight' => 50, 'l' => 120, 'w' => 100, 'h' => 80, 'icon' => 'layers'],
            ],
            
            // Default base rates per kg (fallback if no rate card)
            'base_rate_per_kg' => (float)($financeSettings['base_rate_per_kg'] ?? 5000),
        ];

        $viewPath = $context === 'admin' ? 'admin.pos.index' : 'branch.pos.index';

        // Check for pre-selected customer (from client management quick shipment)
        $preSelectedCustomer = null;
        if ($request->has('customer_id')) {
            $preSelectedCustomer = Customer::find($request->input('customer_id'));
        }

        return view($viewPath, compact('branches', 'currentBranch', 'branchId', 'recentShipments', 'todayStats', 'systemConfig', 'preSelectedCustomer'));
    }

    /**
     * Get currency symbol
     */
    protected function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'UGX' => 'UGX ',
            'KES' => 'KES ',
            'TZS' => 'TZS ',
            'RWF' => 'RWF ',
            'CDF' => 'CDF ',
        ];
        return $symbols[$currency] ?? $currency . ' ';
    }

    /**
     * Quick customer search - searches ONLY customers, not all users
     */
    public function searchCustomer(Request $request): JsonResponse
    {
        $search = $request->input('q', '');

        if (strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        // Search in Customer model (clients table)
        $customers = Customer::where(function ($query) use ($search) {
            $query->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
        })
        ->where('status', 'active')
        ->visibleToUser(Auth::user())
        ->limit(10)
        ->get(['id', 'customer_code', 'company_name', 'contact_person', 'email', 'phone', 'mobile', 'billing_address']);

        return response()->json([
            'results' => $customers->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->contact_person ?: $c->company_name,
                'email' => $c->email,
                'phone' => $c->mobile ?: $c->phone,
                'company' => $c->company_name,
                'code' => $c->customer_code,
                'address' => $c->billing_address,
                'display' => $c->company_name 
                    ? "{$c->contact_person} ({$c->company_name})" 
                    : ($c->contact_person ?: $c->company_name),
            ]),
        ]);
    }

    /**
     * Quick create customer - creates in Customer model
     */
    public function quickCreateCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $user = Auth::user();
            $branchId = $user->primary_branch_id ?? BranchContext::currentId();
            
            $customer = Customer::create([
                'contact_person' => $validated['name'],
                'company_name' => $validated['company'] ?: $validated['name'],
                'mobile' => $validated['phone'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'billing_address' => $validated['address'],
                'shipping_address' => $validated['address'],
                'primary_branch_id' => $branchId,
                'created_by_branch_id' => $branchId,
                'created_by_user_id' => $user->id,
                'customer_type' => 'regular',
                'status' => 'active',
                'payment_terms' => 'cod',
            ]);

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->contact_person,
                    'phone' => $customer->mobile,
                    'email' => $customer->email,
                    'company' => $customer->company_name,
                    'code' => $customer->customer_code,
                    'address' => $customer->billing_address,
                    'display' => $customer->company_name 
                        ? "{$customer->contact_person} ({$customer->company_name})" 
                        : $customer->contact_person,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create customer', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate rate (live pricing) - Uses enhanced RatingService with route validation
     */
    public function calculateRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_branch_id' => 'required|exists:branches,id',
            'dest_branch_id' => 'required|exists:branches,id',
            'service_level' => 'required|string',
            'weight' => 'required|numeric|min:0.01',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'insurance_type' => 'nullable|string',
            'cod_amount' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        try {
            // Use enhanced RatingService for quote with route/service validation
            $quote = $this->ratingService->quote([
                'origin_branch_id' => $validated['origin_branch_id'],
                'destination_branch_id' => $validated['dest_branch_id'],
                'service_level' => $validated['service_level'],
                'weight' => $validated['weight'],
                'length' => $validated['length'] ?? null,
                'width' => $validated['width'] ?? null,
                'height' => $validated['height'] ?? null,
                'declared_value' => $validated['declared_value'] ?? 0,
                'insurance_type' => $validated['insurance_type'] ?? 'none',
                'cod_amount' => $validated['cod_amount'] ?? 0,
                'customer_id' => $validated['customer_id'] ?? null,
            ]);

            // Map to expected frontend format
            return response()->json([
                'success' => true,
                'total' => $quote['total'],
                'currency' => $quote['currency'],
                'base_rate' => $quote['base_freight'],
                'weight_charge' => $quote['weight_charge'],
                'fuel_surcharge' => $quote['fuel_surcharge'],
                'surcharges' => $quote['surcharges_total'],
                'insurance' => ['amount' => $quote['insurance_fee'], 'type' => $validated['insurance_type'] ?? 'none'],
                'cod_fee' => $quote['cod_fee'],
                'tax' => $quote['tax'],
                'tax_rate' => $quote['tax_rate'],
                'discount' => $quote['discount'],
                'rate_table_version' => $quote['rate_table_version'],
                'weight_data' => $quote['weight_data'],
                'warnings' => $quote['warnings'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('POS rate calculation failed', ['error' => $e->getMessage()]);
            
            // Fallback to legacy rate service
            try {
                $pricing = $this->rateService->calculateRate([
                    'origin_branch_id' => $validated['origin_branch_id'],
                    'dest_branch_id' => $validated['dest_branch_id'],
                    'service_level' => $validated['service_level'],
                    'weight' => $validated['weight'],
                    'length' => $validated['length'] ?? 0,
                    'width' => $validated['width'] ?? 0,
                    'height' => $validated['height'] ?? 0,
                    'declared_value' => $validated['declared_value'] ?? 0,
                    'insurance_type' => $validated['insurance_type'] ?? 'none',
                    'cod_amount' => $validated['cod_amount'] ?? 0,
                ]);
                return response()->json($pricing);
            } catch (\Throwable $e2) {
                return response()->json([
                    'success' => false,
                    'error' => 'Rate calculation failed',
                    'total' => 0,
                ], 500);
            }
        }
    }

    /**
     * Create shipment (POS transaction) - Enhanced with audit trail and payment transactions
     */
    public function createShipment(Request $request): JsonResponse
    {
        $context = $this->getContext($request);
        $branchId = $context === 'branch' ? BranchContext::currentId() : $request->input('origin_branch_id');

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'origin_branch_id' => $context === 'admin' ? 'required|exists:branches,id' : 'nullable',
            'dest_branch_id' => 'required|exists:branches,id',
            'service_level' => 'required|string|in:economy,standard,express,priority',
            'payer_type' => 'required|in:sender,receiver,third_party',

            // Package details
            'weight' => 'required|numeric|min:0.01|max:10000',
            'length' => 'nullable|numeric|min:0|max:500',
            'width' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:500',
            'description' => 'nullable|string|max:500',
            'pieces' => 'nullable|integer|min:1|max:999',

            // Value & payment
            'declared_value' => 'nullable|numeric|min:0',
            'insurance_type' => 'nullable|string|in:none,basic,full,premium',
            'cod_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile_money,bank_transfer,credit,account,cod',
            'amount_received' => 'nullable|numeric|min:0',

            // Addresses
            'pickup_address' => 'nullable|string|max:500',
            'delivery_address' => 'nullable|string|max:500',
            'receiver_name' => 'nullable|string|max:255',
            'receiver_phone' => 'nullable|string|max:50',

            // Options
            'special_instructions' => 'nullable|string|max:2000',
            'is_fragile' => 'nullable|boolean',
            'requires_signature' => 'nullable|boolean',
            
            // Content classification (POS-BR-05)
            'content_type' => 'nullable|string|in:document,parcel,battery,liquid,hazmat,other',
        ]);

        DB::beginTransaction();

        try {
            // Use enhanced RatingService for pricing with validation
            $quote = $this->ratingService->quote([
                'origin_branch_id' => $branchId,
                'destination_branch_id' => $validated['dest_branch_id'],
                'service_level' => $validated['service_level'],
                'weight' => $validated['weight'],
                'length' => $validated['length'] ?? null,
                'width' => $validated['width'] ?? null,
                'height' => $validated['height'] ?? null,
                'declared_value' => $validated['declared_value'] ?? 0,
                'insurance_type' => $validated['insurance_type'] ?? 'none',
                'cod_amount' => $validated['cod_amount'] ?? 0,
                'customer_id' => $validated['customer_id'],
            ]);

            // Check for blocking warnings (hazmat on restricted route, overweight, etc.)
            $blockingWarnings = array_filter($quote['warnings'] ?? [], fn($w) => 
                str_contains($w, 'not available') || str_contains($w, 'exceeds maximum')
            );
            if (!empty($blockingWarnings)) {
                throw new \Exception(implode('; ', $blockingWarnings));
            }

            // Resolve customer profile and optional linked user (legacy FK)
            $customerProfile = Customer::find($validated['customer_id']);
            $linkedUserId = null;
            if ($customerProfile && $customerProfile->email) {
                $linkedUserId = User::where('email', $customerProfile->email)->value('id');
            }
            $customerIdForCreation = $linkedUserId;

            // Build parcel payload (single-piece POS entry)
            $parcels = [[
                'weight_kg' => $validated['weight'],
                'length_cm' => $validated['length'] ?? 0,
                'width_cm' => $validated['width'] ?? 0,
                'height_cm' => $validated['height'] ?? 0,
                'description' => $validated['description'] ?? 'POS Parcel',
            ]];

            // Create shipment via service pipeline with enhanced pricing breakdown
            $shipment = $this->shipmentService->createShipment([
                'customer_id' => $customerIdForCreation,
                'customer_profile_id' => $customerProfile?->id,
                'origin_branch_id' => $branchId,
                'dest_branch_id' => $validated['dest_branch_id'],
                'service_level' => $validated['service_level'],
                'payer_type' => $validated['payer_type'],
                'declared_value' => $validated['declared_value'] ?? 0,
                'insurance_amount' => $quote['insurance_fee'] ?? 0,
                'currency' => $quote['currency'] ?? 'UGX',
                'price_amount' => $quote['total'],
                // POS Hardening pricing breakdown fields
                'base_rate' => $quote['base_freight'] ?? 0,
                'weight_charge' => $quote['weight_charge'] ?? 0,
                'surcharges_total' => ($quote['surcharges_total'] ?? 0) + ($quote['fuel_surcharge'] ?? 0),
                'insurance_fee' => $quote['insurance_fee'] ?? 0,
                'cod_fee' => $quote['cod_fee'] ?? 0,
                'tax_amount' => $quote['tax'] ?? 0,
                'rate_table_version' => $quote['rate_table_version'] ?? 'default-1.0',
                'payment_status' => $validated['payment_method'] === 'cod' ? 'unpaid' : 
                    (($validated['amount_received'] ?? 0) >= $quote['total'] ? 'paid' : 'partial'),
                'content_type' => $validated['content_type'] ?? 'parcel',
                'priority' => 0,
                'metadata' => [
                    'pos_transaction' => true,
                    'payment_method' => $validated['payment_method'],
                    'amount_received' => $validated['amount_received'] ?? 0,
                    'receiver_name' => $validated['receiver_name'] ?? null,
                    'receiver_phone' => $validated['receiver_phone'] ?? null,
                    'pickup_address' => $validated['pickup_address'] ?? null,
                    'delivery_address' => $validated['delivery_address'] ?? null,
                    'is_fragile' => $validated['is_fragile'] ?? false,
                    'requires_signature' => $validated['requires_signature'] ?? false,
                    'pieces' => $validated['pieces'] ?? 1,
                    'description' => $validated['description'] ?? null,
                    'pricing_breakdown' => $quote,
                    'warnings' => $quote['warnings'] ?? [],
                ],
                'weight' => $validated['weight'],
                'length' => $validated['length'] ?? 0,
                'width' => $validated['width'] ?? 0,
                'height' => $validated['height'] ?? 0,
                'parcels' => $parcels,
                'created_via' => 'pos',
            ], Auth::user());

            // Record lifecycle transition (ensures unified tracking)
            $this->lifecycleService->transition($shipment, ShipmentStatus::BOOKED, [
                'trigger' => 'pos_transaction',
                'performed_by' => Auth::id(),
                'timestamp' => now(),
                'force' => true,
                'location_type' => 'branch',
                'location_id' => $branchId,
            ]);

            // POS-SEC-04: Log audit trail for shipment creation
            $this->auditService->logCreated($shipment);

            // Handle payment recording with PaymentTransaction (POS-PAY-02)
            if ($validated['payment_method'] !== 'cod' && ($validated['amount_received'] ?? 0) > 0) {
                $paymentTransaction = PaymentTransaction::create([
                    'shipment_id' => $shipment->id,
                    'customer_id' => $customerProfile?->id,
                    'idempotency_key' => 'pos-' . $shipment->tracking_number . '-' . now()->timestamp,
                    'amount' => $validated['amount_received'],
                    'currency' => $quote['currency'] ?? 'UGX',
                    'status' => 'completed',
                    'method' => $validated['payment_method'] === 'account' ? 'on_account' : $validated['payment_method'],
                    'payer_type' => $validated['payer_type'],
                    'created_by' => Auth::id(),
                    'branch_id' => $branchId,
                    'completed_at' => now(),
                    'metadata' => [
                        'pos_transaction' => true,
                        'cashier_id' => Auth::id(),
                    ],
                ]);

                // Post accounting entries (POS-PAY-03)
                $this->postingService->postPayment($paymentTransaction);

                // Log payment audit
                $this->auditService->logPaymentReceived($shipment, $validated['amount_received'], $validated['payment_method']);

                // Also create legacy Payment record for backward compatibility
                Payment::create([
                    'shipment_id' => $shipment->id,
                    'client_id' => $shipment->client_id,
                    'branch_id' => $branchId,
                    'amount' => $validated['amount_received'],
                    'payment_method' => $validated['payment_method'] === 'account' ? 'credit' : $validated['payment_method'],
                    'status' => 'completed',
                    'paid_at' => now(),
                    'metadata' => [
                        'pos_transaction' => true,
                        'cashier_id' => Auth::id(),
                        'payer_type' => $validated['payer_type'],
                        'payment_transaction_id' => $paymentTransaction->id,
                    ],
                ]);

                Log::info('POS payment recorded', [
                    'shipment_id' => $shipment->id,
                    'payment_transaction_id' => $paymentTransaction->id,
                    'amount' => $validated['amount_received'],
                    'method' => $validated['payment_method'],
                    'branch_id' => $branchId,
                ]);
            }

            DB::commit();

            Log::info('POS shipment created', [
                'shipment_id' => $shipment->id,
                'tracking' => $shipment->tracking_number,
                'branch_id' => $branchId,
                'amount' => $quote['total'],
            ]);

            return response()->json([
                'success' => true,
                'shipment' => [
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'waybill_number' => $shipment->waybill_number,
                    'status' => 'booked',
                    'total' => $quote['total'],
                    'currency' => $quote['currency'],
                    'warnings' => $quote['warnings'] ?? [],
                ],
                'urls' => [
                    'label' => route($context . '.pos.label', $shipment),
                    'receipt' => route($context . '.pos.receipt', $shipment),
                    'view' => route($context . '.shipments.show', $shipment),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS shipment creation failed', [
                'error' => $e->getMessage(),
                'branch_id' => $branchId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate label for POS shipment - Tracks label prints (POS-REL-03)
     */
    public function printLabel(Request $request, Shipment $shipment)
    {
        $this->authorize('view', $shipment);

        $format = $request->get('format', 'html');
        $isReprint = ($shipment->label_print_count ?? 0) > 0;

        // Track label print (POS-REL-03)
        $shipment->increment('label_print_count');
        $shipment->update(['last_label_printed_at' => now()]);

        // Log audit trail
        $this->auditService->logLabelPrinted($shipment, $isReprint);

        Log::info('POS label printed', [
            'shipment_id' => $shipment->id,
            'tracking' => $shipment->tracking_number,
            'is_reprint' => $isReprint,
            'print_count' => $shipment->label_print_count,
            'user_id' => Auth::id(),
        ]);

        if ($format === 'pdf' && class_exists(Pdf::class)) {
            $html = $this->labelGenerator->generateLabel($shipment);
            $pdf = Pdf::loadHTML($html);
            return $pdf->download("label_{$shipment->tracking_number}.pdf");
        }

        if ($format === 'zpl') {
            $zpl = $this->labelGenerator->generateZpl($shipment);
            return response($zpl, 200, ['Content-Type' => 'text/plain']);
        }

        $html = $this->labelGenerator->generateLabel($shipment);
        return response($html);
    }

    /**
     * Generate receipt for POS transaction
     */
    public function printReceipt(Request $request, Shipment $shipment)
    {
        $this->authorize('view', $shipment);
        $shipment->load(['customer', 'customerProfile', 'originBranch', 'destBranch']);

        $format = $request->get('format', 'html');
        $context = $this->getContext($request);

        $receiptData = [
            'shipment' => $shipment,
            'company' => [
                'name' => config('app.name', 'Baraka Logistics'),
                'address' => $shipment->originBranch->address ?? '',
                'phone' => $shipment->originBranch->phone ?? '',
            ],
            'transaction_date' => $shipment->created_at,
            'cashier' => Auth::user()->name,
        ];

        $html = view('shared.pos.receipt', $receiptData)->render();

        if ($format === 'pdf' && class_exists(Pdf::class)) {
            $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 226.77, 600], 'portrait'); // 80mm width
            return $pdf->download("receipt_{$shipment->tracking_number}.pdf");
        }

        return response($html);
    }

    /**
     * Get today's stats for dashboard
     */
    protected function getTodayStats(?int $branchId): array
    {
        $today = now()->startOfDay();

        $query = Shipment::where('created_at', '>=', $today);
        if ($branchId) {
            $query->where('origin_branch_id', $branchId);
        }

        return [
            'shipments_count' => (clone $query)->count(),
            'total_revenue' => (clone $query)->sum('price_amount'),
            'total_weight' => (clone $query)->sum('chargeable_weight_kg'),
            'pending_pickup' => (clone $query)->where('current_status', ShipmentStatus::BOOKED)->count(),
        ];
    }

    /**
     * Get context (admin or branch)
     */
    protected function getContext(Request $request): string
    {
        if (str_contains($request->path(), 'admin/')) {
            return 'admin';
        }
        return 'branch';
    }

    /**
     * Track shipment by tracking number (quick lookup)
     */
    public function quickTrack(Request $request): JsonResponse
    {
        $tracking = $request->input('tracking');
        $context = $this->getContext($request);
        $branchId = $context === 'branch' ? BranchContext::currentId() : null;

        $shipment = Shipment::with(['customer', 'customerProfile', 'originBranch', 'destBranch', 'scanEvents' => fn($q) => $q->latest()->limit(5)])
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->where('origin_branch_id', $branchId)
                        ->orWhere('dest_branch_id', $branchId);
                });
            })
            ->where(function ($q) use ($tracking) {
                $q->where('tracking_number', $tracking)
                  ->orWhere('waybill_number', $tracking);
            })
            ->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'shipment' => [
                'id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'waybill_number' => $shipment->waybill_number,
                'status' => $shipment->current_status,
                'customer' => $shipment->customerProfile?->contact_person ?: $shipment->customer?->name,
                'origin' => $shipment->originBranch?->name,
                'destination' => $shipment->destBranch?->name,
                'created_at' => $shipment->created_at->format('Y-m-d H:i'),
                'events' => $shipment->scanEvents->map(fn($e) => [
                    'type' => $e->type,
                    'location' => $e->location,
                    'occurred_at' => $e->occurred_at?->format('Y-m-d H:i'),
                ]),
            ],
        ]);
    }

    /**
     * Get service level options with pricing preview
     */
    public function getServiceLevels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_branch_id' => 'required|exists:branches,id',
            'dest_branch_id' => 'required|exists:branches,id',
            'weight' => 'required|numeric|min:0.01',
        ]);

        $comparisons = $this->rateService->compareServiceLevels([
            'origin_branch_id' => $validated['origin_branch_id'],
            'dest_branch_id' => $validated['dest_branch_id'],
            'parcels' => [['weight_kg' => $validated['weight']]],
        ]);

        return response()->json([
            'success' => true,
            'service_levels' => $comparisons,
        ]);
    }
}
