<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\BranchManager;
use App\Models\Backend\Branch;
use App\Models\User;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BranchManagerApiController extends Controller
{
    /**
     * Display a listing of branch managers
     */
    public function index(Request $request): JsonResponse
    {
        $query = BranchManager::with(['user', 'branch']);

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $managers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $managers,
            'message' => 'Branch managers retrieved successfully'
        ]);
    }

    /**
     * Store a newly created branch manager
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
            'branch_id' => 'required|exists:branches,id',
            'address' => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'opening_balance' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'address' => $request->address,
                'status' => 1,
                'role_id' => 3, // Branch Manager role
            ]);

            // Create branch manager record
            $manager = BranchManager::create([
                'user_id' => $user->id,
                'branch_id' => $request->branch_id,
                'commission_rate' => $request->commission_rate ?? 0,
                'opening_balance' => $request->opening_balance ?? 0,
                'current_balance' => $request->opening_balance ?? 0,
                'status' => 'active',
            ]);

            DB::commit();

            $manager->load(['user', 'branch']);

            return response()->json([
                'success' => true,
                'data' => $manager,
                'message' => 'Branch manager created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create branch manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified branch manager
     */
    public function show($id): JsonResponse
    {
        $manager = BranchManager::with(['user', 'branch'])->find($id);

        if (!$manager) {
            return response()->json([
                'success' => false,
                'message' => 'Branch manager not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $manager,
            'message' => 'Branch manager retrieved successfully'
        ]);
    }

    /**
     * Update the specified branch manager
     */
    public function update(Request $request, $id): JsonResponse
    {
        $manager = BranchManager::find($id);

        if (!$manager) {
            return response()->json([
                'success' => false,
                'message' => 'Branch manager not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $manager->user_id,
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:8',
            'branch_id' => 'sometimes|exists:branches,id',
            'address' => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update user details if provided
            $userUpdates = array_filter([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
            ], function($value) {
                return $value !== null;
            });

            if ($request->has('password')) {
                $userUpdates['password'] = Hash::make($request->password);
            }

            if (!empty($userUpdates)) {
                $manager->user->update($userUpdates);
            }

            // Update manager details
            $managerUpdates = array_filter([
                'branch_id' => $request->branch_id,
                'commission_rate' => $request->commission_rate,
                'status' => $request->status,
            ], function($value) {
                return $value !== null;
            });

            if (!empty($managerUpdates)) {
                $manager->update($managerUpdates);
            }

            DB::commit();

            $manager->load(['user', 'branch']);

            return response()->json([
                'success' => true,
                'data' => $manager,
                'message' => 'Branch manager updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified branch manager
     */
    public function destroy($id): JsonResponse
    {
        $manager = BranchManager::find($id);

        if (!$manager) {
            return response()->json([
                'success' => false,
                'message' => 'Branch manager not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Soft delete or deactivate instead of hard delete
            $manager->update(['status' => 'inactive']);
            $manager->user->update(['status' => 0]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch manager deactivated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate branch manager: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Provide available branches and users for the create form.
     */
    public function formMeta(): JsonResponse
    {
        $branches = Branch::query()
            ->select(['id', 'name', 'code', 'type', 'status'])
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch) => [
                'value' => $branch->id,
                'label' => $branch->name,
                'code' => $branch->code,
                'type' => $branch->type,
                'status' => $branch->status,
            ]);

        $users = User::whereDoesntHave('branchManager')
            ->where('status', 1)
            ->select('id', 'name', 'email', 'phone')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'value' => $user->id,
                'label' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'branches' => $branches,
                'users' => $users,
            ],
        ]);
    }

    /**
     * Adjust a manager's balance.
     */
    public function updateBalance(Request $request, BranchManager $manager): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'type' => ['required', 'in:credit,debit,adjustment'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use (&$manager, $data) {
            $amount = (float) $data['amount'];
            if ($data['type'] === 'debit') {
                $amount *= -1;
            }

            $manager->current_balance = round(max(0, ($manager->current_balance ?? 0) + $amount), 2);
            $metadata = $manager->metadata ?? [];
            $metadata['balance_adjustments'][] = [
                'type' => $data['type'],
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
                'updated_at' => now()->toIso8601String(),
            ];
            $manager->metadata = $metadata;
            $manager->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Balance updated successfully',
            'data' => [
                'current_balance' => (float) $manager->current_balance,
            ],
        ]);
    }

    /**
     * Basic settlement feed for a branch manager.
     */
    public function settlements(BranchManager $manager): JsonResponse
    {
        $shipments = Shipment::query()
            ->where('origin_branch_id', $manager->branch_id)
            ->orWhere('assigned_worker_id', $manager->user_id)
            ->latest('created_at')
            ->take(25)
            ->get(['id', 'tracking_number', 'price_amount', 'currency', 'current_status', 'created_at']);

        $items = $shipments->map(function (Shipment $shipment) {
            return [
                'id' => $shipment->id,
                'reference' => $shipment->tracking_number,
                'amount' => (float) ($shipment->price_amount ?? 0),
                'currency' => $shipment->currency ?? 'UGX',
                'status' => $shipment->current_status,
                'date' => optional($shipment->created_at)->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'settlements' => $items,
                'total_amount' => $items->sum('amount'),
            ],
        ]);
    }

    /**
     * Bulk status update for branch managers.
     */
    public function bulkStatusUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'manager_ids' => ['required', 'array', 'min:1'],
            'manager_ids.*' => ['integer', 'exists:branch_managers,id'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $updated = BranchManager::whereIn('id', $data['manager_ids'])
            ->update(['status' => $data['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Statuses updated successfully',
            'data' => [
                'updated' => $updated,
            ],
        ]);
    }
}
