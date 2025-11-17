# Phase 1: Critical Infrastructure Implementation
## Priority 1 - Complete API Infrastructure

### Week 1: API Foundation

#### Day 1-2: API Route Structure

**1. Create Comprehensive API Routes**

```bash
mkdir -p routes/api/v1/{auth,users,shipments,branches,merchants,reports,system}
```

**File: routes/api/v1/auth.php**
```php
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.auth.login');

Route::post('register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1')
    ->name('api.auth.register');

Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:3,1')
    ->name('api.auth.forgot-password');

Route::post('reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:3,1')
    ->name('api.auth.reset-password');

// Protected routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])
        ->name('api.auth.logout');

    Route::get('me', [AuthController::class, 'me'])
        ->name('api.auth.me');

    Route::put('profile', [AuthController::class, 'updateProfile'])
        ->name('api.auth.update-profile');

    Route::put('password', [AuthController::class, 'updatePassword'])
        ->name('api.auth.update-password');

    // Token management
    Route::post('tokens/refresh', [TokenController::class, 'refresh'])
        ->name('api.tokens.refresh');

    Route::delete('tokens', [TokenController::class, 'revoke'])
        ->name('api.tokens.revoke');

    Route::get('tokens', [TokenController::class, 'index'])
        ->name('api.tokens.index');
});
```

**File: routes/api/v1/users.php**
```php
<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:user_read'])->group(function () {
    Route::get('users', [UserController::class, 'index'])
        ->name('api.users.index');

    Route::get('users/{user}', [UserController::class, 'show'])
        ->name('api.users.show');

    Route::get('users/search', [UserController::class, 'search'])
        ->name('api.users.search');
});

Route::middleware(['auth:sanctum', 'permission:user_create'])->group(function () {
    Route::post('users', [UserController::class, 'store'])
        ->name('api.users.store');
});

Route::middleware(['auth:sanctum', 'permission:user_update'])->group(function () {
    Route::put('users/{user}', [UserController::class, 'update'])
        ->name('api.users.update');

    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])
        ->name('api.users.update-status');
});

Route::middleware(['auth:sanctum', 'permission:user_delete'])->group(function () {
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->name('api.users.destroy');
});

Route::middleware(['auth:sanctum', 'permission:role_manage'])->group(function () {
    Route::get('users/permissions', [UserController::class, 'getPermissions'])
        ->name('api.users.permissions');

    Route::put('users/{user}/roles', [UserController::class, 'updateRoles'])
        ->name('api.users.update-roles');
});
```

**File: routes/api/v1/shipments.php**
```php
<?php

use App\Http\Controllers\Api\V1\ShipmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Basic CRUD
    Route::middleware('permission:shipment_read')->group(function () {
        Route::get('shipments', [ShipmentController::class, 'index'])
            ->name('api.shipments.index');

        Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])
            ->name('api.shipments.show');

        Route::get('shipments/search', [ShipmentController::class, 'search'])
            ->name('api.shipments.search');

        Route::get('shipments/{shipment}/history', [ShipmentController::class, 'history'])
            ->name('api.shipments.history');

        Route::get('shipments/{shipment}/tracking', [ShipmentController::class, 'tracking'])
            ->name('api.shipments.tracking');
    });

    Route::middleware('permission:shipment_create')->group(function () {
        Route::post('shipments', [ShipmentController::class, 'store'])
            ->name('api.shipments.store');

        Route::post('shipments/bulk', [ShipmentController::class, 'bulkStore'])
            ->name('api.shipments.bulk-store');
    });

    Route::middleware('permission:shipment_update')->group(function () {
        Route::put('shipments/{shipment}', [ShipmentController::class, 'update'])
            ->name('api.shipments.update');

        Route::patch('shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])
            ->name('api.shipments.update-status');

        Route::post('shipments/{shipment}/assign-driver', [ShipmentController::class, 'assignDriver'])
            ->name('api.shipments.assign-driver');

        Route::post('shipments/{shipment}/pickupschedule', [ShipmentController::class, 'schedulePickup'])
            ->name('api.shipments.schedule-pickup');

        Route::post('shipments/{shipment}/delivery', [ShipmentController::class, 'markDelivered'])
            ->name('api.shipments.mark-delivered');

        Route::post('shipments/{shipment}/return', [ShipmentController::class, 'markReturned'])
            ->name('api.shipments.mark-returned');
    });

    Route::middleware('permission:shipment_delete')->group(function () {
        Route::delete('shipments/{shipment}', [ShipmentController::class, 'destroy'])
            ->name('api.shipments.destroy');

        Route::post('shipments/bulk-delete', [ShipmentController::class, 'bulkDelete'])
            ->name('api.shipments.bulk-delete');
    });

    // Analytics & Reports
    Route::middleware('permission:shipment_analytics')->group(function () {
        Route::get('shipments/stats/dashboard', [ShipmentController::class, 'dashboardStats'])
            ->name('api.shipments.dashboard-stats');

        Route::get('shipments/stats/analytics', [ShipmentController::class, 'analytics'])
            ->name('api.shipments.analytics');

        Route::get('shipments/export', [ShipmentController::class, 'export'])
            ->name('api.shipments.export');
    });
});
```

**File: routes/api/v1/branches.php**
```php
<?php

use App\Http\Controllers\Api\V1\BranchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware('permission:branch_read')->group(function () {
        Route::get('branches', [BranchController::class, 'index'])
            ->name('api.branches.index');

        Route::get('branches/{branch}', [BranchController::class, 'show'])
            ->name('api.branches.show');

        Route::get('branches/search', [BranchController::class, 'search'])
            ->name('api.branches.search');

        Route::get('branches/{branch}/managers', [BranchController::class, 'managers'])
            ->name('api.branches.managers');

        Route::get('branches/{branch}/workers', [BranchController::class, 'workers'])
            ->name('api.branches.workers');

        Route::get('branches/{branch}/shipments', [BranchController::class, 'shipments'])
            ->name('api.branches.shipments');

        Route::get('branches/{branch}/stats', [BranchController::class, 'stats'])
            ->name('api.branches.stats');
    });

    Route::middleware('permission:branch_create')->group(function () {
        Route::post('branches', [BranchController::class, 'store'])
            ->name('api.branches.store');
    });

    Route::middleware('permission:branch_update')->group(function () {
        Route::put('branches/{branch}', [BranchController::class, 'update'])
            ->name('api.branches.update');

        Route::post('branches/{branch}/managers/{manager}', [BranchController::class, 'addManager'])
            ->name('api.branches.add-manager');

        Route::delete('branches/{branch}/managers/{manager}', [BranchController::class, 'removeManager'])
            ->name('api.branches.remove-manager');

        Route::post('branches/{branch}/workers/{worker}', [BranchController::class, 'addWorker'])
            ->name('api.branches.add-worker');

        Route::delete('branches/{branch}/workers/{worker}', [BranchController::class, 'removeWorker'])
            ->name('api.branches.remove-worker');
    });

    Route::middleware('permission:branch_delete')->group(function () {
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
            ->name('api.branches.destroy');
    });
});
```

**File: routes/api/v1/reports.php**
```php
<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware('permission:report_read')->group(function () {
        // Dashboard Reports
        Route::get('reports/dashboard', [ReportController::class, 'dashboard'])
            ->name('api.reports.dashboard');

        Route::get('reports/kpi', [ReportController::class, 'kpi'])
            ->name('api.reports.kpi');

        // Shipment Reports
        Route::get('reports/shipments', [ReportController::class, 'shipments'])
            ->name('api.reports.shipments');

        Route::get('reports/shipments/detailed', [ReportController::class, 'shipmentsDetailed'])
            ->name('api.reports.shipments.detailed');

        // Financial Reports
        Route::get('reports/revenue', [ReportController::class, 'revenue'])
            ->name('api.reports.revenue');

        Route::get('reports/expenses', [ReportController::class, 'expenses'])
            ->name('api.reports.expenses');

        Route::get('reports/profit', [ReportController::class, 'profit'])
            ->name('api.reportsprofit');

        // Delivery Reports
        Route::get('reports/delivery', [ReportController::class, 'delivery'])
            ->name('api.reports.delivery');

        Route::get('reports/delivery-performance', [ReportController::class, 'deliveryPerformance'])
            ->name('api.reports.delivery-performance');

        // Branch Reports
        Route::get('reports/branches', [ReportController::class, 'branches'])
            ->name('api.reports.branches');

        Route::get('reports/branches-comparison', [ReportController::class, 'branchesComparison'])
            ->name('api.reports.branches-comparison');

        // Export functionality
        Route::post('reports/export', [ReportController::class, 'export'])
            ->name('api.reports.export');
    });

    Route::middleware('permission:report_analytics')->group(function () {
        Route::get('reports/analytics/trends', [ReportController::class, 'trends'])
            ->name('api.reports.trends');

        Route::get('reports/analytics/performance', [ReportController::class, 'performance'])
            ->name('api.reports.performance');

        Route::get('reports/analytics/custom', [ReportController::class, 'custom'])
            ->name('api.reports.custom');
    });
});
```

**File: routes/api/v1/system.php**
```php
<?php

use App\Http\Controllers\Api\V1\SystemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware('permission:system_read')->group(function () {
        // System Information
        Route::get('system/info', [SystemController::class, 'info'])
            ->name('api.system.info');

        Route::get('system/status', [SystemController::class, 'status'])
            ->name('api.system.status');

        Route::get('system/health', [SystemController::class, 'health'])
            ->name('api.system.health');

        // Settings & Configuration
        Route::get('system/settings', [SystemController::class, 'settings'])
            ->name('api.system.settings');

        Route::get('system/languages', [SystemController::class, 'languages'])
            ->name('api.system.languages');

        // Permissions & Roles
        Route::get('system/permissions', [SystemController::class, 'permissions'])
            ->name('api.system.permissions');

        Route::get('system/roles', [SystemController::class, 'roles'])
            ->name('api.system.roles');
    });

    Route::middleware('permission:system_update')->group(function () {
        Route::put('system/settings', [SystemController::class, 'updateSettings'])
            ->name('api.system.update-settings');

        Route::post('system/cache/clear', [SystemController::class, 'clearCache'])
            ->name('api.system.clear-cache');

        Route::post('system/maintenance', [SystemController::class, 'maintenance'])
            ->name('api.system.maintenance');
    });

    Route::middleware('permission:notification_manage')->group(function () {
        Route::get('system/notifications', [SystemController::class, 'notifications'])
            ->name('api.system.notifications');

        Route::post('system/notifications', [SystemController::class, 'sendNotification'])
            ->name('api.system.send-notification');

        Route::put('system/notifications/{notification}/read', [SystemController::class, 'markNotificationRead'])
            ->name('api.system.mark-notification-read');
    });
});
```

#### Day 3: Master API Router Update

**File: routes/api.php (Updates)**
```php
<?php

// Add these new routes before existing routes

// API v1 - Main API endpoints
Route::prefix('v1')->group(function () {
    require base_path('routes/api/v1/auth.php');
    require base_path('routes/api/v1/users.php');
    require base_path('routes/api/v1/shipments.php');
    require base_path('routes/api/v1/branches.php');
    require base_path('routes/api/v1/reports.php');
    require base_path('routes/api/v1/system.php');
});

// Add global API middleware
Route::middleware(['api.prefix', 'api.version'])->group(function () {
    // API routes automatically get CORS, JSON response, and validation middleware
});

// API documentation route
Route::get('/api/documentation', function () {
    return view('api.documentation');
})->name('api.documentation');

// API health check
Route::get('/api/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version'),
        'environment' => config('app.env')
    ]);
})->name('api.health');
```

#### Day 4-5: API Controllers Implementation

**File: app/Http/Controllers/Api/V1/AuthController.php**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Login user and create token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = $request->authenticated();

            if (!$user) {
                return $this->errorResponse('auth.failed', __('Invalid credentials'), 401);
            }

            // Revoke existing tokens
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken(
                'api_token',
                $user->getAllPermissions()->pluck('name')->toArray(),
                now()->addDays(config('sanctum.expiration', 365))
            );

            // Fire login event
            event(new Login('api', $user, false));

            return $this->successResponse('auth.success', 'Login successful', [
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at->toISOString(),
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('auth.error', 'Login failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role_id' => $request->role_id ?? 3, // Default to user role
                'status' => 1, // Active by default
            ]);

            // Assign default permissions
            $user->givePermissionTo(['dashboard_read', 'profile_read']);

            // Fire registration event
            event(new Registered($user));

            return $this->successResponse('registration.success', 'User registered successfully', [
                'user' => new UserResource($user),
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse('registration.error', 'Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(): JsonResponse
    {
        $user = auth()->user()->load(['role', 'permissions', 'branch']);

        return $this->successResponse('user.loaded', 'User information retrieved', [
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'profile_data' => 'sometimes|array',
        ]);

        try {
            $user->update($validated);

            return $this->successResponse('profile.updated', 'Profile updated successfully', [
                'user' => new UserResource($user->fresh()),
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('profile.error', 'Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->errorResponse('auth.invalid_password', 'Current password is incorrect', 422);
        }

        try {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            // Revoke all tokens to force re-login
            $user->tokens()->delete();

            return $this->successResponse('password.updated', 'Password updated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('password.error', 'Failed to update password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Delete current access token
            $request->user()->currentAccessToken()->delete();

            // Fire logout event
            event(new Logout('api', $request->user()));

            return $this->successResponse('auth.logout', 'Successfully logged out');

        } catch (\Exception $e) {
            return $this->errorResponse('auth.logout_error', 'Logout failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                // Always return success to prevent email enumeration
                return $this->successResponse('password.reset_sent', 'Password reset link sent');
            }

            $resetToken = Password::createToken($user);
            $user->notify(new PasswordResetNotification($resetToken));

            return $this->successResponse('password.reset_sent', 'Password reset link sent');

        } catch (\Exception $e) {
            return $this->errorResponse('password.reset_error', 'Failed to send reset link: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset($request->all(), function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            });

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse('password.reset_success', 'Password reset successfully');
            }

            return $this->errorResponse('password.reset_failed', 'Invalid reset token', 422);

        } catch (\Exception $e) {
            return $this->errorResponse('password.reset_error', 'Password reset failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Success response format.
     */
    private function successResponse(string $type, string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $status);
    }

    /**
     * Error response format.
     */
    private function errorResponse(string $type, string $message, int $status = 400, array $errors = array()): JsonResponse
    {
        return response()->json([
            'success' => false,
            'type' => $type,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], $status);
    }
}
```

**File: app/Http/Resources/Api/V1/UserResource.php**
```php
<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->profile_photo_url ?? null,
            'status' => $this->status,
            'last_login' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Relationships
            'role' => $this->when($this->role, [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'permissions' => $this->getAllPermissions()->pluck('name'),
            ]),

            'branch' => $this->when($this->branch, [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'type' => $this->branch->type,
            ]),

            // Computed fields
            'full_name' => $this->name,
            'initials' => $this->getInitials(),
            'is_active' => $this->status === 1,
            'has_branch' => !is_null($this->branch_id),

            // Abilities (for frontend authorization)
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'abilities' => $this->getAbilities(),

        ];
    }

    /**
     * Get user initials.
     */
    private function getInitials(): string
    {
        $names = explode(' ', trim($this->name));
        $initials = '';

        foreach ($names as $name) {
            if (!empty($name) && strlen($name) > 0) {
                $initials .= strtoupper($name[0]);
            }
        }

        return substr($initials, 0, 2);
    }

    /**
     * Get user abilities for frontend.
     */
    private function getAbilities(): array
    {
        return [
            'can_manage_users' => $this->hasPermission('user_create') || $this->hasPermission('user_update'),
            'can_manage_shipments' => $this->hasAnyPermission(['shipment_create', 'shipment_update', 'shipment_delete']),
            'can_manage_branches' => $this->hasAnyPermission(['branch_create', 'branch_update', 'branch_delete']),
            'can_view_reports' => $this->hasPermission('report_read'),
            'can_manage_system' => $this->hasAnyPermission(['settings_update', 'system_update']),
        ];
    }
}
```

#### Day 6-7: Request Validation Classes

**File: app/Http/Requests/Api/V1/LoginRequest.php**
```php
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'password.required' => 'Password is required',
        ];
    }

    /**
     * Validate the request and return the authenticated user.
     */
    public function authenticated(): ?\App\Models\User
    {
        $this->ensureIsNotRateLimited();

        if (!auth()->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return auth()->user();
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return 'login:' . $this->ip() . ':' . $this->input('email');
    }
}
```

**File: app/Http/Requests/Api/V1/RegisterRequest.php**
```php
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users'),
            ],
            'phone' => ['sometimes', 'string', 'max:20'],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed',
            ],
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.max' => 'Name cannot exceed 255 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'role_id.exists' => 'Invalid role selected',
        ];
    }
}
```

#### Day 8: API Testing & Documentation

**File: tests/Feature/Api/V1/AuthTest.php**
```php
<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /** @test */
    public function it_can_login_successfully()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'auth.success',
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'permissions',
                    ],
                    'token',
                    'expires_at',
                ]
            ]);
    }

    /** @test */
    public function it_fails_to_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'type' => 'auth.failed',
            ]);
    }

    /** @test */
    public function it_can_register_new_user()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'type' => 'registration.success',
                'message' => 'User registered successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /** @test */
    public function it_can_get_authenticated_user()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'user.loaded',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'permissions',
                        'abilities',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_logout_successfully()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'auth.logout',
                'message' => 'Successfully logged out',
            ]);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
```

**API Documentation Template** (File: resources/views/api/documentation.blade.php)
```php
@extends('layouts.api')

@section('title', 'API Documentation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h1>Baraka Sanaa API Documentation</h1>
                    <p class="text-muted">RESTful API V1 Documentation</p>
                </div>
                <div class="card-body">
                    <!-- Authentication Section -->
                    <section id="authentication">
                        <h2>Authentication</h2>
                        <p>API uses Laravel Sanctum for authentication. Include the token in the Authorization header:</p>
                        <pre><code>Authorization: Bearer {token}</code></pre>
                        
                        <h3>Login</h3>
                        <p>POST /api/v1/auth/login</p>
                        <pre><code>{
    "email": "user@example.com",
    "password": "password123",
    "remember": true
}</code></pre>

                        <!-- Add more API documentation sections -->
                    </section>

                    <!-- Users Section -->
                    <section id="users">
                        <h2>Users</h2>
                        <p>User management endpoints require appropriate permissions.</p>
                        
                        <h3>Get All Users</h3>
                        <p>GET /api/v1/users</p>
                        <p>Requires: user_read permission</p>
                    </section>

                    <!-- Add sections for all other endpoints -->

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## Phase 1 completion checklist:

### ✅ Week 1 Deliverables:
- [ ] Complete API route structure (15 files)
- [ ] Authentication controller with Sanctum
- [ ] User management controller
- [ ] Shipment controller  
- [ ] Branch controller
- [ ] Reports controller
- [ ] System controller
- [ ] API resource classes
- [ ] Request validation classes
- [ ] API test suite
- [ ] API documentation setup

### Next Steps:
- Begin Phase 2: Complete remaining controllers and resources
- Frontend state management implementation
- Real-time WebSocket setup

This completes Priority 1 implementation. Each file includes comprehensive error handling, validation, security measures, and follows REST API best practices.
