<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\BranchWorker;
use App\Models\User;
use App\Models\Backend\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BranchWorkerApiController extends Controller
{
    /**
     * Display a listing of branch workers
     */
    public function index(Request $request): JsonResponse
    {
        $query = BranchWorker::with(['user', 'branch']);

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by worker type
        if ($request->has('worker_type')) {
            $query->where('worker_type', $request->worker_type);
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
        $workers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $workers,
            'message' => 'Branch workers retrieved successfully'
        ]);
    }

    /**
     * Store a newly created branch worker
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
            'branch_id' => 'required|exists:branches,id',
            'worker_type' => 'required|in:delivery,pickup,sortation,customer_service',
            'address' => 'nullable|string',
            'vehicle_type' => 'nullable|string',
            'vehicle_number' => 'nullable|string',
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
                'role_id' => 4, // Worker role
            ]);

            // Create branch worker record
            $worker = BranchWorker::create([
                'user_id' => $user->id,
                'branch_id' => $request->branch_id,
                'worker_type' => $request->worker_type,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_number' => $request->vehicle_number,
                'status' => 'active',
                'availability_status' => 'available',
            ]);

            DB::commit();

            $worker->load(['user', 'branch']);

            return response()->json([
                'success' => true,
                'data' => $worker,
                'message' => 'Branch worker created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create branch worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified branch worker
     */
    public function show($id): JsonResponse
    {
        $worker = BranchWorker::with(['user', 'branch'])->find($id);

        if (!$worker) {
            return response()->json([
                'success' => false,
                'message' => 'Branch worker not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $worker,
            'message' => 'Branch worker retrieved successfully'
        ]);
    }

    /**
     * Update the specified branch worker
     */
    public function update(Request $request, $id): JsonResponse
    {
        $worker = BranchWorker::find($id);

        if (!$worker) {
            return response()->json([
                'success' => false,
                'message' => 'Branch worker not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $worker->user_id,
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:8',
            'branch_id' => 'sometimes|exists:branches,id',
            'worker_type' => 'sometimes|in:delivery,pickup,sortation,customer_service',
            'address' => 'nullable|string',
            'vehicle_type' => 'nullable|string',
            'vehicle_number' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,suspended',
            'availability_status' => 'sometimes|in:available,busy,offline',
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
                $worker->user->update($userUpdates);
            }

            // Update worker details
            $workerUpdates = array_filter([
                'branch_id' => $request->branch_id,
                'worker_type' => $request->worker_type,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_number' => $request->vehicle_number,
                'status' => $request->status,
                'availability_status' => $request->availability_status,
            ], function($value) {
                return $value !== null;
            });

            if (!empty($workerUpdates)) {
                $worker->update($workerUpdates);
            }

            DB::commit();

            $worker->load(['user', 'branch']);

            return response()->json([
                'success' => true,
                'data' => $worker,
                'message' => 'Branch worker updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified branch worker
     */
    public function destroy($id): JsonResponse
    {
        $worker = BranchWorker::find($id);

        if (!$worker) {
            return response()->json([
                'success' => false,
                'message' => 'Branch worker not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Soft delete or deactivate instead of hard delete
            $worker->update(['status' => 'inactive']);
            $worker->user->update(['status' => 0]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch worker deactivated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate branch worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available users for branch worker assignment
     */
    public function availableUsers(): JsonResponse
    {
        $users = User::whereDoesntHave('branchWorker')
            ->where('status', 1)
            ->where('role_id', 4)
            ->select('id', 'name', 'email', 'phone')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Available users retrieved successfully'
        ]);
    }
}
