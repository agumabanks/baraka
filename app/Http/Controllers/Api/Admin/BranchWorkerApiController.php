<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BranchWorkerRole;
use App\Enums\EmploymentStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\BranchWorker;
use App\Models\Backend\Branch;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BranchWorkerApiController extends Controller
{
    /**
     * Display a listing of branch workers
     */
    public function index(Request $request): JsonResponse
    {
        $query = BranchWorker::with(['user', 'branch']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->branch_id);
        }

        if ($request->filled('role')) {
            $query->byRole($request->string('role')->value());
        }

        if ($request->filled('employment_status')) {
            $query->withEmploymentStatus($request->string('employment_status')->value());
        }

        if ($request->filled('status')) {
            $status = strtolower($request->string('status')->value());
            $legacy = match ($status) {
                'active', '1', 'true' => Status::ACTIVE,
                default => Status::INACTIVE,
            };
            $query->where('status', $legacy);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->value();
            $query->where(function ($builder) use ($search) {
                $builder->where('contact_phone', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('mobile', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->integer('per_page', 15);
        $workers = $query->latest('assigned_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $workers,
            'message' => 'Branch workers retrieved successfully'
        ]);
    }

    /**
     * Provide metadata for branch worker creation screens.
     */
    public function formMeta(): JsonResponse
    {
        $branches = Branch::query()
            ->select(['id', 'name', 'code', 'type'])
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch) => [
                'value' => $branch->id,
                'label' => $branch->name,
                'code' => $branch->code,
                'type' => $branch->type,
            ]);

        $users = User::whereDoesntHave('branchWorker')
            ->where('status', Status::ACTIVE)
            ->select('id', 'name', 'email', 'mobile', 'phone', 'phone_e164', 'preferred_language', 'primary_branch_id')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'value' => $user->id,
                'label' => $user->name,
                'email' => $user->email,
                'phone' => $user->mobile ?? $user->phone ?? $user->phone_e164,
                'preferred_language' => $user->preferred_language,
                'primary_branch_id' => $user->primary_branch_id,
            ]);

        $roles = collect(BranchWorkerRole::cases())
            ->map(fn (BranchWorkerRole $role) => [
                'value' => $role->value,
                'label' => Str::headline($role->value),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'branches' => $branches,
                'users' => $users,
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * Store a newly created branch worker
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->validationRules(null, $request->filled('user_id')));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = $this->resolveUser($request);

            $role = BranchWorkerRole::fromString($request->input('role', $request->input('worker_type', 'ops_agent')));
            $employmentStatus = $request->filled('employment_status')
                ? EmploymentStatus::fromString($request->employment_status)
                : EmploymentStatus::ACTIVE;

            $worker = BranchWorker::create([
                'user_id' => $user->id,
                'branch_id' => $request->branch_id,
                'role' => $role->value,
                'designation' => $request->input('designation'),
                'employment_status' => $employmentStatus->value,
                'contact_phone' => $request->input('contact_phone', $user->mobile ?? $user->phone ?? $user->phone_e164),
                'id_number' => $request->input('id_number'),
                'permissions' => $request->input('permissions'),
                'work_schedule' => $request->input('work_schedule'),
                'notes' => $request->input('notes'),
                'metadata' => $request->input('metadata'),
                'status' => match (strtolower((string) $request->input('status', 'active'))) {
                    'inactive', 'suspended' => Status::INACTIVE,
                    default => Status::ACTIVE,
                },
                'assigned_at' => now(),
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
        $worker = BranchWorker::with('user')->find($id);

        if (!$worker) {
            return response()->json([
                'success' => false,
                'message' => 'Branch worker not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), $this->validationRules($worker, true));

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
                'role' => ($request->filled('role') || $request->filled('worker_type'))
                    ? BranchWorkerRole::fromString($request->input('role', $request->input('worker_type')))->value
                    : null,
                'designation' => $request->input('designation'),
                'employment_status' => $request->filled('employment_status') ? EmploymentStatus::fromString($request->employment_status)->value : null,
                'contact_phone' => $request->input('contact_phone'),
                'id_number' => $request->input('id_number'),
                'permissions' => $request->input('permissions'),
                'work_schedule' => $request->input('work_schedule'),
                'notes' => $request->input('notes'),
                'metadata' => $request->input('metadata'),
                'status' => $request->filled('status') ? (strtolower($request->status) === 'active' ? Status::ACTIVE : Status::INACTIVE) : null,
            ], fn ($value) => $value !== null);

            if (! empty($workerUpdates)) {
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
            $worker->update([
                'status' => Status::INACTIVE,
                'employment_status' => EmploymentStatus::INACTIVE->value,
                'unassigned_at' => now(),
            ]);
            $worker->user?->update(['status' => Status::INACTIVE]);

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

    protected function validationRules(?BranchWorker $worker = null, bool $hasExistingUser = false): array
    {
        $isUpdate = $worker !== null;
        $userId = $worker?->user_id;

        $nameRule = $hasExistingUser ? 'sometimes' : ($isUpdate ? 'sometimes' : 'required');
        $contactRule = $hasExistingUser ? 'sometimes' : ($isUpdate ? 'sometimes' : 'required');
        $passwordRule = $hasExistingUser ? 'sometimes' : ($isUpdate ? 'sometimes' : 'required');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => [$nameRule, 'string', 'max:255'],
            'email' => [$hasExistingUser ? 'sometimes' : ($isUpdate ? 'sometimes' : 'required'), 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => [$contactRule, 'string', 'max:20'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'password' => [$passwordRule, 'string', 'min:8'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'role' => ['required_without:worker_type', 'string', 'max:60'],
            'worker_type' => ['nullable', 'string', 'max:60'],
            'employment_status' => ['nullable', 'string', 'max:40'],
            'designation' => ['nullable', 'string', 'max:120'],
            'id_number' => ['nullable', 'string', 'max:60'],
            'permissions' => ['nullable', 'array'],
            'work_schedule' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'preferred_language' => ['nullable', 'string', Rule::in(User::SUPPORTED_LANGUAGES)],
        ];
    }

    protected function resolveUser(Request $request): User
    {
        if ($request->filled('user_id')) {
            /** @var User $user */
            $user = User::findOrFail($request->user_id);

            $updates = array_filter([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'mobile' => $request->input('phone'),
                'address' => $request->input('address'),
            ], fn ($value) => $value !== null);

            if (! empty($updates)) {
                $user->fill($updates);
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->filled('preferred_language')) {
                $user->preferred_language = $request->preferred_language;
            }

            if ($request->filled('branch_id')) {
                $user->primary_branch_id = (int) $request->branch_id;
            }

            if (! $user->user_type) {
                $user->user_type = UserType::DELIVERYMAN;
            }

            if ($user->status === null) {
                $user->status = Status::ACTIVE;
            }

            if ($user->isDirty()) {
                $user->save();
            }

            return $user;
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->mobile = $request->phone;
        $user->password = Hash::make($request->password);
        $user->address = $request->input('address');
        $user->status = Status::ACTIVE;
        $user->role_id = $request->input('role_id', 4);
        $user->user_type = UserType::DELIVERYMAN;
        $user->preferred_language = $request->input('preferred_language', 'en');
        $user->primary_branch_id = (int) $request->branch_id;
        $user->save();

        return $user;
    }

    /**
     * Get available users for branch worker assignment
     */
    public function availableUsers(): JsonResponse
    {
        $users = User::whereDoesntHave('branchWorker')
            ->where('status', Status::ACTIVE)
            ->select('id', 'name', 'email', 'mobile', 'phone', 'phone_e164')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->mobile ?? $user->phone ?? $user->phone_e164,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Available users retrieved successfully'
        ]);
    }

    /**
     * Bulk status updater.
     */
    public function bulkStatusUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'worker_ids' => ['required', 'array', 'min:1'],
            'worker_ids.*' => ['integer', 'exists:branch_workers,id'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $status = match (strtolower($data['status'])) {
            'active' => Status::ACTIVE,
            default => Status::INACTIVE,
        };

        $updated = BranchWorker::whereIn('id', $data['worker_ids'])
            ->update(['status' => $status]);

        return response()->json([
            'success' => true,
            'message' => 'Workers updated successfully',
            'data' => ['updated' => $updated],
        ]);
    }

    /**
     * Assign a shipment to the given worker.
     */
    public function assignShipment(Request $request, BranchWorker $worker): JsonResponse
    {
        $data = $request->validate([
            'shipment_id' => ['required', 'integer', 'exists:shipments,id'],
        ]);

        $shipment = Shipment::findOrFail($data['shipment_id']);
        $shipment->assigned_worker_id = $worker->id;
        $shipment->assigned_at = now();
        $shipment->save();

        return response()->json([
            'success' => true,
            'message' => 'Shipment assigned successfully',
            'data' => [
                'shipment_id' => $shipment->id,
                'worker_id' => $worker->id,
            ],
        ]);
    }

    /**
     * Remove worker assignment from any shipments.
     */
    public function unassign(BranchWorker $worker): JsonResponse
    {
        $updated = Shipment::where('assigned_worker_id', $worker->id)
            ->update([
                'assigned_worker_id' => null,
                'assigned_at' => null,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker unassigned from shipments',
            'data' => ['shipments_updated' => $updated],
        ]);
    }
}
