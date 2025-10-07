<?php

namespace App\Http\Controllers\Backend;

use App\Models\Customer;
use App\Models\Backend\Branch;
use App\Models\User;
use App\Services\CustomerAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomersExport;
use App\Imports\CustomersImport;

class CustomerController extends Controller
{
    protected CustomerAnalyticsService $analyticsService;

    public function __construct(CustomerAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display a listing of customers with advanced filtering and search
     */
    public function index(Request $request)
    {
        $query = Customer::with(['accountManager', 'primaryBranch', 'salesRep']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('segment')) {
            $query->where('segment', $request->segment);
        }

        if ($request->filled('account_manager_id')) {
            $query->where('account_manager_id', $request->account_manager_id);
        }

        if ($request->filled('primary_branch_id')) {
            $query->where('primary_branch_id', $request->primary_branch_id);
        }

        if ($request->filled('risk_level')) {
            $query->whereIn('id', $this->getCustomersByRiskLevel($request->risk_level));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $customers = $query->paginate(15);

        // Get filter options
        $filterOptions = [
            'statuses' => ['active', 'inactive', 'suspended', 'blacklisted'],
            'customer_types' => ['vip', 'regular', 'inactive', 'prospect'],
            'segments' => ['High-Value', 'Standard', 'Low-Value'],
            'account_managers' => User::where('user_type', 'admin')->select('id', 'name')->get(),
            'branches' => Branch::active()->select('id', 'name')->get(),
            'risk_levels' => ['low', 'medium', 'high'],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'customers' => $customers,
                'filter_options' => $filterOptions,
            ]);
        }

        return view('backend.customers.index', compact('customers', 'filterOptions'));
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        $accountManagers = User::where('user_type', 'admin')->select('id', 'name')->get();
        $branches = Branch::active()->select('id', 'name')->get();
        $salesReps = User::whereIn('user_type', ['admin', 'manager'])->select('id', 'name')->get();

        return view('backend.customers.create', compact('accountManagers', 'branches', 'salesReps'));
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string|max:500',
            'shipping_address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|in:Small,Medium,Large,Enterprise',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|in:net_15,net_30,net_60,cod',
            'customer_type' => 'nullable|in:vip,regular,inactive,prospect',
            'segment' => 'nullable|string|max:50',
            'priority_level' => 'nullable|integer|min:1|max:3',
            'account_manager_id' => 'nullable|exists:users,id',
            'primary_branch_id' => 'nullable|exists:branches,id',
            'sales_rep_id' => 'nullable|exists:users,id',
            'communication_channels' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer = Customer::create([
                'company_name' => $request->company_name,
                'contact_person' => $request->contact_person,
                'email' => $request->email,
                'phone' => $request->phone,
                'mobile' => $request->mobile,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address ?: $request->billing_address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?: 'Saudi Arabia',
                'tax_id' => $request->tax_id,
                'industry' => $request->industry,
                'company_size' => $request->company_size,
                'credit_limit' => $request->credit_limit ?: 0,
                'payment_terms' => $request->payment_terms ?: 'net_30',
                'customer_type' => $request->customer_type ?: 'regular',
                'segment' => $request->segment,
                'priority_level' => $request->priority_level ?: 3,
                'account_manager_id' => $request->account_manager_id,
                'primary_branch_id' => $request->primary_branch_id,
                'sales_rep_id' => $request->sales_rep_id,
                'communication_channels' => $request->communication_channels ?: ['email'],
                'notes' => $request->notes,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => $customer->load(['accountManager', 'primaryBranch', 'salesRep'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified customer with full details and analytics
     */
    public function show(Customer $customer)
    {
        $customer->load([
            'accountManager',
            'primaryBranch',
            'salesRep',
            'shipments' => function ($query) {
                $query->latest()->take(10);
            },
            'contracts' => function ($query) {
                $query->latest()->take(5);
            },
            'quotations' => function ($query) {
                $query->latest()->take(5);
            },
            'invoices' => function ($query) {
                $query->latest()->take(5);
            },
            'addressBook'
        ]);

        // Get customer analytics
        $analytics = $this->analyticsService->getCustomerAnalytics($customer);

        if (request()->wantsJson()) {
            return response()->json([
                'customer' => $customer,
                'analytics' => $analytics
            ]);
        }

        return view('backend.customers.show', compact('customer', 'analytics'));
    }

    /**
     * Show the form for editing the customer
     */
    public function edit(Customer $customer)
    {
        $accountManagers = User::where('user_type', 'admin')->select('id', 'name')->get();
        $branches = Branch::active()->select('id', 'name')->get();
        $salesReps = User::whereIn('user_type', ['admin', 'manager'])->select('id', 'name')->get();

        return view('backend.customers.edit', compact('customer', 'accountManagers', 'branches', 'salesReps'));
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('customers')->ignore($customer->id)],
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string|max:500',
            'shipping_address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|in:Small,Medium,Large,Enterprise',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|in:net_15,net_30,net_60,cod',
            'customer_type' => 'nullable|in:vip,regular,inactive,prospect',
            'segment' => 'nullable|string|max:50',
            'priority_level' => 'nullable|integer|min:1|max:3',
            'account_manager_id' => 'nullable|exists:users,id',
            'primary_branch_id' => 'nullable|exists:branches,id',
            'sales_rep_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,suspended,blacklisted',
            'communication_channels' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer->update([
                'company_name' => $request->company_name,
                'contact_person' => $request->contact_person,
                'email' => $request->email,
                'phone' => $request->phone,
                'mobile' => $request->mobile,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address ?: $request->billing_address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'tax_id' => $request->tax_id,
                'industry' => $request->industry,
                'company_size' => $request->company_size,
                'credit_limit' => $request->credit_limit ?: 0,
                'payment_terms' => $request->payment_terms,
                'customer_type' => $request->customer_type,
                'segment' => $request->segment,
                'priority_level' => $request->priority_level,
                'account_manager_id' => $request->account_manager_id,
                'primary_branch_id' => $request->primary_branch_id,
                'sales_rep_id' => $request->sales_rep_id,
                'status' => $request->status,
                'communication_channels' => $request->communication_channels,
                'notes' => $request->notes,
            ]);

            // Update customer statistics
            $customer->updateStatistics();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'customer' => $customer->load(['accountManager', 'primaryBranch', 'salesRep'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified customer
     */
    public function destroy(Customer $customer): JsonResponse
    {
        // Check if customer can be deleted
        if (!$this->canDeleteCustomer($customer)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with active relationships.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer analytics dashboard
     */
    public function analytics(Request $request, Customer $customer): JsonResponse
    {
        $dateRange = $request->get('date_range', 30); // days
        $analytics = $this->analyticsService->getCustomerAnalytics($customer, $dateRange);

        return response()->json(['analytics' => $analytics]);
    }

    /**
     * Send communication to customer
     */
    public function sendCommunication(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:email,sms,whatsapp',
            'message' => 'required|string|max:1000',
            'subject' => 'required_if:type,email|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer->sendCommunication(
                $request->type,
                $request->message,
                ['subject' => $request->subject]
            );

            return response()->json([
                'success' => true,
                'message' => 'Communication sent successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send communication: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update customer credit limit
     */
    public function updateCreditLimit(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'credit_limit' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if new limit would cause issues
        if ($customer->current_balance > $request->credit_limit) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot set credit limit below current balance.'
            ], 422);
        }

        $customer->update([
            'credit_limit' => $request->credit_limit,
            'notes' => ($customer->notes ? $customer->notes . "\n\n" : '') .
                      "Credit limit updated to {$request->credit_limit} on " . now()->format('Y-m-d H:i') .
                      " - Reason: {$request->reason}"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Credit limit updated successfully.',
            'customer' => $customer
        ]);
    }

    /**
     * Export customers to Excel
     */
    public function export(Request $request)
    {
        $query = Customer::query();

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('segment')) {
            $query->where('segment', $request->segment);
        }

        return Excel::download(new CustomersExport($query), 'customers_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Import customers from Excel
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Excel::import(new CustomersImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Customers imported successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer shipment history
     */
    public function shipmentHistory(Customer $customer): JsonResponse
    {
        $shipments = $customer->shipments()
            ->with(['originBranch', 'destinationBranch'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['shipments' => $shipments]);
    }

    /**
     * Bulk update customers
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
            'action' => 'required|in:update_status,update_type,update_segment,assign_manager',
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customers = Customer::whereIn('id', $request->customer_ids);

            switch ($request->action) {
                case 'update_status':
                    $customers->update(['status' => $request->value]);
                    break;
                case 'update_type':
                    $customers->update(['customer_type' => $request->value]);
                    break;
                case 'update_segment':
                    $customers->update(['segment' => $request->value]);
                    break;
                case 'assign_manager':
                    $customers->update(['account_manager_id' => $request->value]);
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk update completed successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper Methods

    /**
     * Get customers by risk level
     */
    private function getCustomersByRiskLevel(string $riskLevel): array
    {
        return Customer::all()->filter(function ($customer) use ($riskLevel) {
            return $customer->getRiskLevel() === $riskLevel;
        })->pluck('id')->toArray();
    }

    /**
     * Check if customer can be safely deleted
     */
    private function canDeleteCustomer(Customer $customer): bool
    {
        return $customer->shipments()->count() === 0 &&
               $customer->contracts()->count() === 0 &&
               $customer->quotations()->count() === 0 &&
               $customer->invoices()->where('status', '!=', 'paid')->count() === 0;
    }
}
