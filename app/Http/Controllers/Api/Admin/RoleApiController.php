<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Api\Admin\Role\UpdateRoleRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Http\Resources\PaginationResource;
use App\Models\Backend\Role;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RoleApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('admin.roles.viewAny');

        $query = Role::query()->withCount('users');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $roles = $query->orderByDesc('id')->paginate($perPage);

        return RoleResource::collection($roles)->additional([
            'success' => true,
            'message' => 'Roles retrieved successfully.',
            'pagination' => new PaginationResource($roles),
        ]);
    }

    public function meta(): JsonResponse
    {
        $this->authorize('admin.roles.viewAny');

        $permissions = Permission::orderBy('attribute')->get(['id', 'attribute', 'keywords']);

        return response()->json([
            'success' => true,
            'data' => [
                'permissions' => $permissions,
                'statuses' => [
                    ['value' => Status::ACTIVE, 'label' => 'Active'],
                    ['value' => Status::INACTIVE, 'label' => 'Inactive'],
                ],
            ],
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('admin.roles.create');

        $data = $request->validated();
        $permissions = array_values($data['permissions'] ?? []);

        try {
            $role = DB::transaction(function () use ($data, $permissions) {
                $role = new Role();
                $role->name = $data['name'];
                $role->slug = Str::slug($data['name']);
                $role->status = (int) $data['status'];
                $role->permissions = $permissions;
                $role->save();

                $role->loadCount('users');

                return $role;
            });

            return (new RoleResource($role))->additional([
                'success' => true,
                'message' => 'Role created successfully.',
            ])->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('admin.roles.view', $role);

        $role->load('users');

        return (new RoleResource($role))->additional([
            'success' => true,
        ])->response();
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('admin.roles.update', $role);

        $data = $request->validated();
        $permissions = array_values($data['permissions'] ?? []);

        try {
            DB::transaction(function () use ($role, $data, $permissions) {
                $role->name = $data['name'];
                $role->slug = Str::slug($data['name']);
                $role->status = (int) $data['status'];
                $role->permissions = $permissions;
                $role->save();
            });

            $role->loadCount('users');

            return (new RoleResource($role))->additional([
                'success' => true,
                'message' => 'Role updated successfully.',
            ])->response();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('admin.roles.delete', $role);

        $role->loadCount('users');

        if ($role->users_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a role that is assigned to users.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function toggleStatus(Role $role): JsonResponse
    {
        $this->authorize('admin.roles.update', $role);

        $role->status = $role->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE;
        $role->save();

        return response()->json([
            'success' => true,
            'message' => 'Role status updated.',
            'data' => [
                'status' => (int) $role->status,
            ],
        ]);
    }
}
