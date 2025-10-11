<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\BranchManager;
use App\Models\User;
use App\Models\Backend\Branch;
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
     * Get available users for branch manager assignment
     */
    public function availableUsers(): JsonResponse
    {
        $users = User::whereDoesntHave('branchManager')
            ->where('status', 1)
            ->where('role_id', 3)
            ->select('id', 'name', 'email', 'phone')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Available users retrieved successfully'
        ]);
    }
}
