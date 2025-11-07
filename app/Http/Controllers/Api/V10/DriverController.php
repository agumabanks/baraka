<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\DriverStatus;
use App\Enums\EmploymentStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V10\Driver\StoreDriverRequest;
use App\Http\Requests\Api\V10\Driver\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Driver::class);

        $query = Driver::with(['branch', 'vehicle', 'user']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('status')) {
            $status = DriverStatus::fromString($request->input('status'));
            $query->where('status', $status->value);
        }

        if ($request->filled('employment_status')) {
            $employment = EmploymentStatus::fromString($request->input('employment_status'));
            $query->where('employment_status', $employment->value);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $drivers = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $drivers,
        ]);
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = $this->resolveUser($request);

            $driver = Driver::create([
                'user_id' => $user->id,
                'branch_id' => $request->branch_id,
                'name' => $request->input('name', $user->name),
                'phone' => $request->input('phone', $user->mobile),
                'email' => $request->input('email', $user->email),
                'status' => DriverStatus::fromString($request->input('status', DriverStatus::ACTIVE->value))->value,
                'employment_status' => EmploymentStatus::fromString($request->input('employment_status', EmploymentStatus::ACTIVE->value))->value,
                'license_number' => $request->input('license_number'),
                'license_expiry' => $request->input('license_expiry'),
                'vehicle_id' => $request->input('vehicle_id'),
                'documents' => $request->input('documents'),
                'metadata' => $request->input('metadata'),
                'code' => $request->input('code') ?? $this->generateDriverCode($request->branch_id),
                'onboarded_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $driver->fresh()->load(['branch', 'vehicle', 'user']),
                'message' => 'Driver created successfully',
            ], 201);
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create driver',
                'error' => $throwable->getMessage(),
            ], 500);
        }
    }

    public function show(Driver $driver): JsonResponse
    {
        $this->authorize('view', $driver);

        return response()->json([
            'success' => true,
            'data' => $driver->load(['branch', 'vehicle', 'user', 'rosters' => fn ($q) => $q->latest()->limit(10)]),
        ]);
    }

    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        DB::beginTransaction();

        try {
            $driver->fill($request->validated());

            if ($request->filled('status')) {
                $driver->status = DriverStatus::fromString($request->input('status'))->value;
            }

            if ($request->filled('employment_status')) {
                $driver->employment_status = EmploymentStatus::fromString($request->input('employment_status'))->value;
            }

            $driver->save();

            if ($driver->user && ($request->filled('name') || $request->filled('email') || $request->filled('phone'))) {
                $updates = array_filter([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone'),
                    'mobile' => $request->input('phone'),
                ], fn ($value) => $value !== null);

                if (! empty($updates)) {
                    $driver->user->fill($updates);
                }

                if ($request->filled('password')) {
                    $driver->user->password = Hash::make($request->password);
                }

                if (! empty($updates) || $request->filled('password')) {
                    $driver->user->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $driver->fresh()->load(['branch', 'vehicle', 'user']),
                'message' => 'Driver updated successfully',
            ]);
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update driver',
                'error' => $throwable->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus(Request $request, Driver $driver): JsonResponse
    {
        $this->authorize('toggleStatus', $driver);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:ACTIVE,INACTIVE,SUSPENDED,ON_LEAVE,OFFBOARDING'],
        ]);

        $driver->update([
            'status' => DriverStatus::fromString($validated['status'])->value,
        ]);

        return response()->json([
            'success' => true,
            'data' => $driver->fresh(),
            'message' => 'Driver status updated',
        ]);
    }

    protected function resolveUser(Request $request): User
    {
        if ($request->filled('user_id')) {
            $user = User::findOrFail($request->user_id);

            $updates = array_filter([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'mobile' => $request->input('phone'),
                'phone_e164' => $request->input('phone'),
            ], fn ($value) => $value !== null);

            if (! empty($updates)) {
                $user->fill($updates);
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if (! empty($updates) || $request->filled('password')) {
                $user->save();
            }

            return $user;
        }

        return User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->phone,
            'phone_e164' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => 1,
            'address' => $request->input('address'),
            'user_type' => UserType::DELIVERYMAN,
        ]);
    }

    protected function generateDriverCode(int $branchId): string
    {
        $sequence = Driver::where('branch_id', $branchId)->count() + 1;

        return sprintf('DRV-%s-%03d', $branchId, $sequence);
    }
}
