<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::where('user_type', 'customer')
            ->with(['hub', 'shipments']);

        // Apply ABAC filtering
        if (!auth()->user()->hasRole(['hq_admin','admin','super-admin'])) {
            $query->where(function ($q) {
                $q->whereHas('shipments', function ($shipmentQuery) {
                    $shipmentQuery->where('origin_branch_id', auth()->user()->hub_id)
                                  ->orWhere('dest_branch_id', auth()->user()->hub_id);
                })->orWhere('hub_id', auth()->user()->hub_id);
            });
        }

        // Apply search filter
        if ($request->filled('search')) {
            $searchResults = $this->searchService->search($request->search, [
                'type' => 'customer',
                'per_page' => 1000
            ], auth()->user());

            $customerIds = collect($searchResults->items())
                ->pluck('model.id')
                ->toArray();

            $query->whereIn('id', $customerIds);
        }

        // Apply filters
        if ($request->filled('branch_id')) {
            $query->where('hub_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->latest()->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        $branches = \App\Models\Backend\Hub::active()->get();

        return view('admin.customers.create', compact('branches'));
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'nullable|string|min:8',
            'hub_id' => 'nullable|exists:hubs,id',
            'pickup_address' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password ?: 'temp123'),
            'user_type' => 'customer',
            'hub_id' => $request->hub_id,
            'pickup_address' => $request->pickup_address,
            'delivery_address' => $request->delivery_address,
            'notes' => $request->notes,
            'status' => 'ACTIVE',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'customer' => $customer,
            'redirect' => route('admin.customers.show', $customer)
        ]);
    }

    /**
     * Display the specified customer.
     */
    public function show(User $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load([
            'hub',
            'shipments' => function ($query) {
                $query->latest()->take(10);
            },
            'shipments.originBranch',
            'shipments.destBranch'
        ]);

        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the customer.
     */
    public function edit(User $customer): View
    {
        $this->authorize('update', $customer);

        $branches = \App\Models\Backend\Hub::active()->get();

        return view('admin.customers.edit', compact('customer', 'branches'));
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, User $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $customer->id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $customer->id,
            'hub_id' => 'nullable|exists:hubs,id',
            'pickup_address' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $customer->update($request->only([
            'name', 'email', 'phone', 'hub_id', 'pickup_address',
            'delivery_address', 'notes', 'status'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'customer' => $customer
        ]);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(User $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }

    /**
     * Search customers for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:50',
            'limit' => 'nullable|integer|min:5|max:20',
        ]);

        $results = $this->searchService->search($request->q, [
            'type' => 'customer',
            'per_page' => $request->limit ?? 10,
        ], auth()->user());

        $customers = collect($results->items())->map(function ($item) {
            $customer = $item['model'];
            return [
                'id' => $customer->id,
                'text' => $customer->name . ' (' . $customer->email . ')',
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ];
        });

        return response()->json([
            'success' => true,
            'results' => $customers,
        ]);
    }
}
