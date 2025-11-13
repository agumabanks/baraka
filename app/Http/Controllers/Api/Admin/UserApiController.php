<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Status as StatusEnum;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\User\BulkAssignAdminUserRequest;
use App\Http\Requests\Api\Admin\User\StoreAdminUserRequest;
use App\Http\Requests\Api\Admin\User\UpdateAdminUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\PaginationResource;
use App\Models\Backend\Branch;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\Hub;
use App\Models\Backend\Role;
use App\Models\Backend\Upload;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('admin.users.viewAny');

        $query = User::query()
            ->where('user_type', UserType::ADMIN)
            ->with(['role', 'hub', 'department', 'designation', 'primaryBranch']);

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

        return response()->json([
            'success' => true,
            'data' => $this->buildMetaPayload(),
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
                $user->primary_branch_id = $data['primary_branch_id'] ?? null;
                $user->joining_date = $data['joining_date'];
                $user->address = $data['address'];
                if (! empty($data['preferred_language'])) {
                    $user->preferred_language = $data['preferred_language'];
                }
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

            $user->load(['role', 'hub', 'department', 'designation', 'primaryBranch']);

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
        $user = User::with(['role', 'hub', 'department', 'designation', 'primaryBranch'])
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
                    $user->primary_branch_id = $data['primary_branch_id'] ?? null;
                    $user->status = (int) ($data['status'] ?? $user->status);
                }

                $user->joining_date = $data['joining_date'];
                $user->address = $data['address'];
                if (! empty($data['preferred_language'])) {
                    $user->preferred_language = $data['preferred_language'];
                }
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

            $user->load(['role', 'hub', 'department', 'designation', 'primaryBranch']);

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

    public function bulkAssign(BulkAssignAdminUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $userIds = $data['user_ids'];

        $assignments = collect($data)->only([
            'role_id',
            'hub_id',
            'department_id',
            'designation_id',
            'status',
        ]);

        $rolePermissions = null;
        if ($assignments->has('role_id')) {
            $roleId = $assignments->get('role_id');
            if ($roleId) {
                $role = Role::find($roleId);
                $rolePermissions = $role && is_array($role->permissions)
                    ? array_values($role->permissions)
                    : [];
            } else {
                $rolePermissions = [];
            }
        }

        $users = DB::transaction(function () use ($userIds, $assignments, $rolePermissions) {
            $users = User::query()
                ->whereIn('id', $userIds)
                ->where('user_type', UserType::ADMIN)
                ->lockForUpdate()
                ->get();

            foreach ($users as $user) {
                foreach ($assignments as $field => $value) {
                    $user->{$field} = $value;
                }

                $hubId = $assignments->has('hub_id') ? $assignments->get('hub_id') : null;

                if ($hubId) {
                    $user->permissions = $this->defaultHubPermissions();
                } elseif ($assignments->has('role_id') && $rolePermissions !== null) {
                    $user->permissions = $rolePermissions;
                }

                $user->save();
            }

            return $users->load(['role', 'hub', 'department', 'designation', 'primaryBranch']);
        });

        $meta = $this->buildMetaPayload();

        return response()->json([
            'success' => true,
            'message' => 'Assignments updated successfully.',
            'data' => [
                'users' => UserResource::collection($users)->toArray($request),
                'meta' => $meta,
                'applied' => [
                    'fields' => array_keys($assignments->all()),
                    'count' => $users->count(),
                ],
            ],
        ]);
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

    private function buildMetaPayload(): array
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug', 'status', 'permissions']);
        $hubs = Hub::active()->orderBy('name')->get(['id', 'name']);
        $departments = Department::active()->orderBy('title')->get(['id', 'title']);
        $designations = Designation::active()->orderBy('title')->get(['id', 'title']);
        $branches = Branch::query()->orderBy('name')->get(['id', 'name', 'code', 'type']);

        $rolesPayload = $roles->map(static function (Role $role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'status' => (int) $role->status,
            ];
        })->values();

        $hubsPayload = $hubs->map(static function (Hub $hub) {
            return [
                'id' => $hub->id,
                'name' => $hub->name,
            ];
        })->values();

        $departmentsPayload = $departments->map(static function (Department $department) {
            return [
                'id' => $department->id,
                'title' => $department->title,
            ];
        })->values();

        $designationsPayload = $designations->map(static function (Designation $designation) {
            return [
                'id' => $designation->id,
                'title' => $designation->title,
            ];
        })->values();

        $branchesPayload = $branches->map(static function (Branch $branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'type' => $branch->type,
            ];
        })->values();

        $adminUsers = User::query()
            ->where('user_type', UserType::ADMIN)
            ->select(['id', 'name', 'status', 'role_id', 'hub_id', 'department_id', 'designation_id', 'joining_date'])
            ->with([
                'role:id,name,slug,permissions',
                'hub:id,name',
                'department:id,title',
                'designation:id,title',
            ])
            ->get();

        $totalCount = $adminUsers->count();
        $activeCount = $adminUsers->filter(static fn (User $user) => (int) $user->status === StatusEnum::ACTIVE)->count();
        $inactiveCount = $totalCount - $activeCount;

        $recentWindow = Carbon::now()->subDays(30)->startOfDay();

        $recentHiresCount = $adminUsers->filter(static function (User $user) use ($recentWindow) {
            if (! $user->joining_date) {
                return false;
            }

            try {
                return Carbon::parse($user->joining_date)->greaterThanOrEqualTo($recentWindow);
            } catch (\Throwable $e) {
                return false;
            }
        })->count();

        $teamSummary = $adminUsers
            ->groupBy(static fn (User $user) => ($user->department_id ?? 'null').'|'.($user->hub_id ?? 'null'))
            ->map(function ($users) use ($recentWindow) {
                /** @var \Illuminate\Support\Collection<int, User> $users */
                $first = $users->first();

                $department = $first?->department ? $first->department->only(['id', 'title']) : null;
                $hub = $first?->hub ? $first->hub->only(['id', 'name']) : null;

                $total = $users->count();
                $active = $users->filter(static fn (User $user) => (int) $user->status === StatusEnum::ACTIVE)->count();
                $inactive = $total - $active;

                $recent = $users->filter(static function (User $user) use ($recentWindow) {
                    if (! $user->joining_date) {
                        return false;
                    }

                    try {
                        return Carbon::parse($user->joining_date)->greaterThanOrEqualTo($recentWindow);
                    } catch (\Throwable $e) {
                        return false;
                    }
                })->count();

                $sampleUsers = $users
                    ->sortByDesc(static fn (User $user) => (int) $user->status)
                    ->take(3)
                    ->map(function (User $user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'initials' => $this->initials($user->name),
                            'status' => (int) $user->status,
                            'role' => $user->role?->name,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => ($department['id'] ?? 'null').'|'.($hub['id'] ?? 'null'),
                    'label' => $this->formatTeamLabel($department, $hub),
                    'department' => $department,
                    'hub' => $hub,
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $inactive,
                    'recent_hires' => $recent,
                    'active_ratio' => $total > 0 ? round(($active / $total) * 100, 1) : 0.0,
                    'sample_users' => $sampleUsers,
                ];
            })
            ->sortByDesc(static fn (array $team) => $team['total'])
            ->values()
            ->all();

        $roleSummary = $adminUsers
            ->groupBy(static fn (User $user) => $user->role_id ?? 'null')
            ->map(function ($users, $roleId) {
                /** @var \Illuminate\Support\Collection<int, User> $users */
                $first = $users->first();
                $role = $first?->role;

                $total = $users->count();
                $active = $users->filter(static fn (User $user) => (int) $user->status === StatusEnum::ACTIVE)->count();
                $inactive = $total - $active;

                $sampleUsers = $users
                    ->sortByDesc(static fn (User $user) => (int) $user->status)
                    ->take(3)
                    ->map(function (User $user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'initials' => $this->initials($user->name),
                            'status' => (int) $user->status,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'role_id' => $role?->id,
                    'label' => $role?->name ?? 'Unassigned',
                    'slug' => $role?->slug,
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $inactive,
                    'teams' => $users
                        ->groupBy(static fn (User $user) => ($user->department_id ?? 'null').'|'.($user->hub_id ?? 'null'))
                        ->count(),
                    'sample_users' => $sampleUsers,
                ];
            })
            ->sortByDesc(static fn (array $role) => $role['total'])
            ->values()
            ->all();

        $recentHires = $adminUsers
            ->filter(static function (User $user) use ($recentWindow) {
                if (! $user->joining_date) {
                    return false;
                }

                try {
                    return Carbon::parse($user->joining_date)->greaterThanOrEqualTo($recentWindow);
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->sortByDesc(static fn (User $user) => $user->joining_date ?? '')
            ->take(6)
            ->map(function (User $user) {
                $department = $user->department ? $user->department->only(['id', 'title']) : null;
                $hub = $user->hub ? $user->hub->only(['id', 'name']) : null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role?->name,
                    'team' => $this->formatTeamLabel($department, $hub),
                    'joining_date' => $this->formatDate($user->joining_date),
                ];
            })
            ->values()
            ->all();

        return [
            'roles' => $rolesPayload->toArray(),
            'hubs' => $hubsPayload->toArray(),
            'departments' => $departmentsPayload->toArray(),
            'designations' => $designationsPayload->toArray(),
            'branches' => $branchesPayload->toArray(),
            'statuses' => [
                ['value' => StatusEnum::ACTIVE, 'label' => 'Active'],
                ['value' => StatusEnum::INACTIVE, 'label' => 'Inactive'],
            ],
            'totals' => [
                'total' => $totalCount,
                'active' => $activeCount,
                'inactive' => $inactiveCount,
                'recent_hires' => $recentHiresCount,
                'active_ratio' => $totalCount > 0 ? round(($activeCount / $totalCount) * 100, 1) : 0.0,
            ],
            'team_summary' => $teamSummary,
            'role_summary' => $roleSummary,
            'people_pulse' => [
                'recent_hires' => $recentHires,
                'awaiting_activation' => $inactiveCount,
                'trend_window_days' => 30,
            ],
        ];
    }

    private function formatTeamLabel(?array $department, ?array $hub): string
    {
        $parts = [];

        if (! empty($department['title'])) {
            $parts[] = $department['title'];
        }

        if (! empty($hub['name'])) {
            $parts[] = $hub['name'];
        }

        if (empty($parts)) {
            return 'Unassigned';
        }

        return implode(' · ', $parts);
    }

    private function initials(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        $initials = collect($parts)
            ->filter()
            ->map(static fn ($part) => mb_strtoupper(mb_substr((string) $part, 0, 1)))
            ->take(2)
            ->implode('');

        if ($initials !== '') {
            return $initials;
        }

        return mb_strtoupper(mb_substr($name, 0, 1));
    }

    private function formatDate(?string $value, string $format = 'Y-m-d'): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return null;
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
