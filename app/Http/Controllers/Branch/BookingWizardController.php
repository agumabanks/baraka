<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Backend\Branch;
use App\Models\Backend\Parcel;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Customer;
use App\Services\ShipmentService;
use App\Services\Pricing\RateCalculationService;
use App\Services\LabelGeneratorService;
use App\Services\BranchContext;
use App\Services\Logistics\ShipmentLifecycleService;
use App\Enums\ShipmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingWizardController extends Controller
{
    protected ShipmentService $shipmentService;
    protected RateCalculationService $rateService;
    protected LabelGeneratorService $labelGenerator;
    protected ShipmentLifecycleService $lifecycleService;

    public function __construct(
        ShipmentService $shipmentService,
        RateCalculationService $rateService,
        LabelGeneratorService $labelGenerator,
        ShipmentLifecycleService $lifecycleService
    ) {
        $this->shipmentService = $shipmentService;
        $this->rateService = $rateService;
        $this->labelGenerator = $labelGenerator;
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Show the booking wizard
     */
    public function index()
    {
        $this->authorize('create', Shipment::class);
        $branchId = BranchContext::currentId();
        $branches = Branch::where('id', '!=', $branchId)->get();
        $currentBranch = Branch::find($branchId);

        return view('branch.booking-wizard.index', compact('branches', 'currentBranch', 'branchId'));
    }

    /**
     * Step 1: Customer selection/creation
     */
    public function step1(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'nullable|exists:users,id',
            'name' => 'required_if:customer_id,null|string|max:255',
            'email' => 'required_if:customer_id,null|email|max:255',
            'phone' => 'required_if:customer_id,null|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            if ($request->customer_id) {
                $customer = User::find($request->customer_id);
            } else {
                // Create new customer
                $customer = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'company_name' => $request->company,
                    'address' => $request->address,
                    'user_type' => 'customer',
                    'password' => bcrypt(Str::random(16)),
                ]);

                Log::info('Branch booking wizard created customer', [
                    'customer_id' => $customer->id,
                    'branch_id' => BranchContext::currentId(),
                ]);
            }

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found',
                ], 404);
            }

            session(['branch_booking_customer_id' => $customer->id]);

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ],
                'next_step' => 2,
            ]);

        } catch (\Exception $e) {
            Log::error('Branch booking step 1 failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to process customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 2: Shipment details
     */
    public function step2(Request $request): JsonResponse
    {
        $request->validate([
            'dest_branch_id' => 'required|exists:branches,id',
            'service_level' => 'required|in:economy,standard,express,priority',
            'payer_type' => 'required|in:sender,receiver,third_party',
            'incoterm' => 'nullable|string',
            'declared_value' => 'nullable|numeric|min:0',
            'insurance_type' => 'nullable|in:none,basic,full,premium',
            'cod_amount' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string|max:2000',
        ]);

        $branchId = BranchContext::currentId();

        if ($request->dest_branch_id == $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Destination must be different from origin',
            ], 422);
        }

        session([
            'branch_booking_dest_branch_id' => $request->dest_branch_id,
            'branch_booking_service_level' => $request->service_level,
            'branch_booking_payer_type' => $request->payer_type,
            'branch_booking_incoterm' => $request->incoterm,
            'branch_booking_declared_value' => $request->declared_value ?? 0,
            'branch_booking_insurance_type' => $request->insurance_type ?? 'none',
            'branch_booking_cod_amount' => $request->cod_amount ?? 0,
            'branch_booking_special_instructions' => $request->special_instructions,
        ]);

        return response()->json([
            'success' => true,
            'next_step' => 3,
        ]);
    }

    /**
     * Step 3: Parcel details and pricing
     */
    public function step3(Request $request): JsonResponse
    {
        $request->validate([
            'parcels' => 'required|array|min:1|max:99',
            'parcels.*.weight_kg' => 'required|numeric|min:0.01|max:10000',
            'parcels.*.length_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.width_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.height_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.description' => 'nullable|string|max:500',
            'parcels.*.declared_value' => 'nullable|numeric|min:0',
        ]);

        session(['branch_booking_parcels' => $request->parcels]);

        // Calculate pricing
        $branchId = BranchContext::currentId();
        $pricing = $this->rateService->calculateRate([
            'origin_branch_id' => $branchId,
            'dest_branch_id' => session('branch_booking_dest_branch_id'),
            'service_level' => session('branch_booking_service_level'),
            'declared_value' => session('branch_booking_declared_value'),
            'insurance_type' => session('branch_booking_insurance_type'),
            'cod_amount' => session('branch_booking_cod_amount'),
            'parcels' => $request->parcels,
        ]);

        if (!$pricing['success']) {
            return response()->json([
                'success' => false,
                'message' => $pricing['error'] ?? 'Failed to calculate pricing',
            ], 422);
        }

        session(['branch_booking_pricing' => $pricing]);

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
            'next_step' => 4,
        ]);
    }

    /**
     * Step 4: Confirm and create shipment
     */
    public function step4(Request $request): JsonResponse
    {
        $customerId = session('branch_booking_customer_id');
        $destBranchId = session('branch_booking_dest_branch_id');
        $parcels = session('branch_booking_parcels');
        $pricing = session('branch_booking_pricing');
        $branchId = BranchContext::currentId();

        if (!$customerId || !$destBranchId || empty($parcels)) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please restart the booking.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create shipment
            $shipment = Shipment::create([
                'tracking_number' => 'TRK-' . strtoupper(Str::random(10)),
                'customer_id' => $customerId,
                'origin_branch_id' => $branchId,
                'dest_branch_id' => $destBranchId,
                'service_level' => session('branch_booking_service_level'),
                'incoterms' => session('branch_booking_incoterm'),
                'payer_type' => session('branch_booking_payer_type'),
                'declared_value' => session('branch_booking_declared_value') ?? 0,
                'insurance_amount' => $pricing['insurance']['amount'] ?? 0,
                'price_amount' => $pricing['total'] ?? 0,
                'currency' => $pricing['currency'] ?? 'USD',
                'special_instructions' => session('branch_booking_special_instructions'),
                'current_status' => ShipmentStatus::BOOKED,
                'status' => 'booked',
                'created_by' => Auth::id(),
                'booked_at' => now(),
            ]);

            // Create parcels
            foreach ($parcels as $parcelData) {
                $this->shipmentService->addParcel($shipment, $parcelData);
            }

            // Calculate totals
            $shipment->calculateTotals();

            // Transition to booked status
            $this->lifecycleService->transition($shipment, ShipmentStatus::BOOKED, [
                'trigger' => 'branch_booking_wizard',
                'performed_by' => Auth::id(),
                'timestamp' => now(),
                'force' => true,
                'location_type' => 'branch',
                'location_id' => $branchId,
            ]);

            DB::commit();

            // Clear session
            session()->forget([
                'branch_booking_customer_id',
                'branch_booking_dest_branch_id',
                'branch_booking_service_level',
                'branch_booking_payer_type',
                'branch_booking_incoterm',
                'branch_booking_declared_value',
                'branch_booking_insurance_type',
                'branch_booking_cod_amount',
                'branch_booking_special_instructions',
                'branch_booking_parcels',
                'branch_booking_pricing',
            ]);

            Log::info('Branch booking wizard created shipment', [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'branch_id' => $branchId,
            ]);

            return response()->json([
                'success' => true,
                'shipment' => [
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'status' => $shipment->current_status,
                ],
                'label_url' => route('branch.shipments.label', $shipment),
                'view_url' => route('branch.shipments.show', $shipment),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Branch booking wizard failed', [
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
     * Search customers
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->input('q', '');

        if (strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $customers = User::where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        })
        ->whereIn('user_type', ['customer', 'merchant'])
        ->limit(10)
        ->get(['id', 'name', 'email', 'phone']);

        return response()->json([
            'results' => $customers->map(fn($c) => [
                'id' => $c->id,
                'text' => "{$c->name} ({$c->email})",
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
            ]),
        ]);
    }

    /**
     * Get rate quote
     */
    public function getQuote(Request $request): JsonResponse
    {
        $request->validate([
            'dest_branch_id' => 'required|exists:branches,id',
            'service_level' => 'required|string',
            'parcels' => 'required|array|min:1',
            'parcels.*.weight_kg' => 'required|numeric|min:0.01',
            'declared_value' => 'nullable|numeric|min:0',
            'insurance_type' => 'nullable|string',
        ]);

        $branchId = BranchContext::currentId();

        $pricing = $this->rateService->calculateRate([
            'origin_branch_id' => $branchId,
            'dest_branch_id' => $request->dest_branch_id,
            'service_level' => $request->service_level,
            'declared_value' => $request->declared_value ?? 0,
            'insurance_type' => $request->insurance_type ?? 'none',
            'parcels' => $request->parcels,
        ]);

        return response()->json($pricing);
    }

    /**
     * Compare service levels
     */
    public function compareServices(Request $request): JsonResponse
    {
        $request->validate([
            'dest_branch_id' => 'required|exists:branches,id',
            'parcels' => 'required|array|min:1',
            'parcels.*.weight_kg' => 'required|numeric|min:0.01',
        ]);

        $branchId = BranchContext::currentId();

        $comparisons = $this->rateService->compareServiceLevels([
            'origin_branch_id' => $branchId,
            'dest_branch_id' => $request->dest_branch_id,
            'parcels' => $request->parcels,
        ]);

        return response()->json([
            'success' => true,
            'comparisons' => $comparisons,
        ]);
    }
}
