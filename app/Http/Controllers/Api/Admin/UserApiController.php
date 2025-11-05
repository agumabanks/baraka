<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Status as StatusEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\User\StoreAdminUserRequest;
use App\Http\Requests\Api\Admin\User\UpdateAdminUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\PaginationResource;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\Hub;
use App\Models\Backend\Role;
use App\Models\Backend\Upload;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('admin.users.viewAny');

        $query = User::query()
            ->where('user_type', UserType::ADMIN)
            ->with(['role', 'hub', 'department', 'designation']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        if ($request->filled('hub_id')) {
            $query->where('hub_id', $request->input('hub_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->input('designation_id'));
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $users = $query->orderByDesc('id')->paginate($perPage);

        return UserResource::collection($users)->additional([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'pagination' => new PaginationResource($users),
        ]);
    }

    public function meta(): JsonResponse
    {
        $this->authorize('admin.users.viewAny');

        $roles = Role::orderBy('name')->get(['id', 'name', 'slug', 'status']);
        $hubs = Hub::active()->orderBy('name')->get(['id', 'name']);
        $departments = Department::active()->orderBy('title')->get(['id', 'title']);
        $designations = Designation::active()->orderBy('title')->get(['id', 'title']);

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'hubs' => $hubs,
                'departments' => $departments,
                'designations' => $designations,
                'statuses' => [
                    ['value' => StatusEnum::ACTIVE, 'label' => 'Active'],
                    ['value' => StatusEnum::INACTIVE, 'label' => 'Inactive'],
                ],
            ],
        ]);
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $this->authorize('admin.users.create');

        $data = $request->validated();

        try {
            $user = DB::transaction(function () use ($request, $data) {
                $role = Role::find($data['role_id']);

                $user = new User();
                $user->name = $data['name'];
                $user->email = $data['email'];
                $user->password = Hash::make($data['password']);
                $user->mobile = $data['mobile'];
                $user->nid_number = $data['nid_number'] ?? null;
                $user->designation_id = $data['designation_id'];
                $user->department_id = $data['department_id'];
                $user->hub_id = $data['hub_id'] ?? null;
                $user->joining_date = $data['joining_date'];
                $user->address = $data['address'];
                $user->role_id = $data['role_id'];
                $user->salary = $data['salary'] ?? 0;
                $user->status = (int) $data['status'];
                $user->user_type = UserType::ADMIN;

                if ($request->hasFile('image')) {
                    $user->image_id = $this->storeAvatar($request->file('image'));
                }

                if (! empty($data['hub_id'])) {
                    $user->permissions = $this->defaultHubPermissions();
                } elseif ($role && is_array($role->permissions)) {
                    $user->permissions = $role->permissions;
                } else {
                    $user->permissions = [];
                }

                $user->save();

                return $user;
            });

            $user->load(['role', 'hub', 'department', 'designation']);

            return (new UserResource($user))->additional([
                'success' => true,
                'message' => 'User created successfully.',
            ])->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        $user = User::with(['role', 'hub', 'department', 'designation'])
            ->where('user_type', UserType::ADMIN)
            ->find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('admin.users.view', $user);

        return (new UserResource($user))->additional([
            'success' => true,
        ])->response();
    }

    public function update(UpdateAdminUserRequest $request, $id): JsonResponse
    {
        $user = User::where('user_type', UserType::ADMIN)->find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('admin.users.update', $user);

        $data = $request->validated();

        try {
            $user = DB::transaction(function () use ($request, $user, $data) {
                $role = Role::find($data['role_id']);

                $user->name = $data['name'];
                $user->email = $data['email'];
                $user->mobile = $data['mobile'];
                $user->nid_number = $data['nid_number'] ?? $user->nid_number;

                if ($user->id !== 1) {
                    $user->designation_id = $data['designation_id'];
                    $user->department_id = $data['department_id'];
                    $user->hub_id = $data['hub_id'] ?? null;
                    $user->status = (int) ($data['status'] ?? $user->status);
                }

                $user->joining_date = $data['joining_date'];
                $user->address = $data['address'];
                $user->role_id = $data['role_id'];
                $user->salary = $data['salary'] ?? $user->salary;

                if (! empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                }

                if ($request->hasFile('image')) {
                    $user->image_id = $this->storeAvatar($request->file('image'), $user->image_id);
                }

                if ($user->hub_id) {
                    $user->permissions = $this->defaultHubPermissions();
                } elseif ($role && is_array($role->permissions)) {
                    $user->permissions = $role->permissions;
                } else {
                    $user->permissions = [];
                }

                $user->save();

                return $user;
            });

            $user->load(['role', 'hub', 'department', 'designation']);

            return (new UserResource($user))->additional([
                'success' => true,
                'message' => 'User updated successfully.',
            ])->response();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id): JsonResponse
    {
        $user = User::where('user_type', UserType::ADMIN)->with('upload')->find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($user->id === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin cannot be deleted.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->authorize('admin.users.delete', $user);

        try {
            DB::transaction(function () use ($user) {
                if ($user->upload) {
                    $this->deleteUpload($user->upload);
                }

                $user->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function defaultHubPermissions(): array
    {
        return [
            'dashboard_read',
            'hub_payment_read',
            'parcel_read',
            'cash_received_from_delivery_man_read',
            'cash_received_from_delivery_man_create',
            'cash_received_from_delivery_man_update',
            'cash_received_from_delivery_man_delete',
            'hub_payment_request_read',
            'hub_payment_request_create',
            'hub_payment_request_delete',
        ];
    }

    private function storeAvatar(?UploadedFile $image, ?int $existingUploadId = null): ?int
    {
        if (! $image) {
            return $existingUploadId;
        }

        $directory = public_path('uploads/users');

        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new \RuntimeException('Unable to create user upload directory.');
            }
        }

        $filename = now()->format('YmdHis').'_'.
            Str::random(8).'.'.$image->getClientOriginalExtension();

        $image->move($directory, $filename);

        $relativePath = 'uploads/users/'.$filename;

        if ($existingUploadId) {
            $upload = Upload::find($existingUploadId);
            if ($upload) {
                $this->deleteUploadFile($upload);
                $upload->original = $relativePath;
                $upload->save();

                return $upload->id;
            }
        }

        $upload = new Upload();
        $upload->original = $relativePath;
        $upload->save();

        return $upload->id;
    }

    private function deleteUpload(Upload $upload): void
    {
        $this->deleteUploadFile($upload);
        $upload->delete();
    }

    private function deleteUploadFile(Upload $upload): void
    {
        $path = (string) $upload->original;
        if ($path && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
