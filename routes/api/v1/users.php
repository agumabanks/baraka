<?php

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\RoleController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // User CRUD operations
    Route::middleware(['throttle:60,1'])->group(function () {
        
        // Read permissions
        Route::middleware('permission:user_read')->group(function () {
            Route::get('users', [UserController::class, 'index'])
                ->name('api.users.index');

            Route::get('users/{user}', [UserController::class, 'show'])
                ->name('api.users.show');

            Route::get('users/search', [UserController::class, 'search'])
                ->name('api.users.search');

            Route::get('users/export', [UserController::class, 'export'])
                ->name('api.users.export');

            Route::get('users/import/template', [UserController::class, 'importTemplate'])
                ->name('api.users.import-template');

            Route::get('users/{user}/activity', [UserController::class, 'getUserActivity'])
                ->name('api.users.activity');

            Route::get('users/{user}/permissions', [UserController::class, 'getUserPermissions'])
                ->name('api.users.permissions');

            Route::get('users/stats/summary', [UserController::class, 'statsSummary'])
                ->name('api.users.stats-summary');
        });

        // Create permissions
        Route::middleware('permission:user_create')->group(function () {
            Route::post('users', [UserController::class, 'store'])
                ->name('api.users.store');

            Route::post('users/import', [UserController::class, 'import'])
                ->name('api.users.import');

            Route::post('users/bulk', [UserController::class, 'bulkStore'])
                ->name('api.users.bulk-store');
        });

        // Update permissions
        Route::middleware('permission:user_update')->group(function () {
            Route::put('users/{user}', [UserController::class, 'update'])
                ->name('api.users.update');

            Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])
                ->name('api.users.update-status');

            Route::patch('users/{user}/role', [UserController::class, 'updateRole'])
                ->name('api.users.update-role');

            Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])
                ->name('api.users.update-permissions');

            Route::patch('users/{user}/branch', [UserController::class, 'updateBranch'])
                ->name('api.users.update-branch');

            Route::post('users/{user}/password/reset', [UserController::class, 'resetPassword'])
                ->name('api.users.reset-password');

            Route::post('users/{user}/avatar', [UserController::class, 'updateAvatar'])
                ->name('api.users.update-avatar');
        });

        // Delete permissions
        Route::middleware('permission:user_delete')->group(function () {
            Route::delete('users/{user}', [UserController::class, 'destroy'])
                ->name('api.users.destroy');

            Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])
                ->name('api.users.bulk-delete');

            Route::post('users/{user}/soft-delete', [UserController::class, 'softDelete'])
                ->name('api.users.soft-delete');

            Route::post('users/{user}/restore', [UserController::class, 'restore'])
                ->name('api.users.restore');
        });

        // Role management permissions
        Route::middleware('permission:role_manage')->group(function () {
            Route::get('users/{user}/roles', [UserController::class, 'getUserRoles'])
                ->name('api.users.roles');

            Route::post('users/{user}/roles/{role}', [UserController::class, 'attachRole'])
                ->name('api.users.attach-role');

            Route::delete('users/{user}/roles/{role}', [UserController::class, 'detachRole'])
                ->name('api.users.detach-role');

            Route::post('users/{user}/sync-roles', [UserController::class, 'syncRoles'])
                ->name('api.users.sync-roles');
        });

        // System admin permissions
        Route::middleware('permission:system_admin')->group(function () {
            Route::get('users/system-stats', [UserController::class, 'systemStats'])
                ->name('api.users.system-stats');

            Route::post('users/bulk-import', [UserController::class, 'bulkImport'])
                ->name('api.users.bulk-import');

            Route::get('users/import-progress', [UserController::class, 'importProgress'])
                ->name('api.users.import-progress');
        });
    });

    // User self-management (requires auth, no special permission)
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('me/profile', [UserController::class, 'myProfile'])
            ->name('api.users.my-profile');

        Route::put('me/profile', [UserController::class, 'updateMyProfile'])
            ->name('api.users.update-my-profile');

        Route::post('me/avatar', [UserController::class, 'updateMyAvatar'])
            ->name('api.users.update-my-avatar');

        Route::put('me/password', [UserController::class, 'updateMyPassword'])
            ->name('api.users.update-my-password');

        Route::put('me/preferences', [UserController::class, 'updatePreferences'])
            ->name('api.users.update-preferences');

        Route::get('me/activity', [UserController::class, 'myActivity'])
            ->name('api.users.my-activity');

        Route::get('me/notifications', [UserController::class, 'myNotifications'])
            ->name('api.users.my-notifications');

        Route::put('me/notifications/read', [UserController::class, 'markNotificationsRead'])
            ->name('api.users.mark-notifications-read');

        Route::post('me/logout-sessions', [UserController::class, 'logoutAllSessions'])
            ->name('api.users.logout-all-sessions');
    });
});

// User validation endpoints (public)
Route::middleware(['api.prefix', 'throttle:20,1'])->group(function () {
    Route::post('users/validate-email', [UserController::class, 'validateEmail'])
        ->name('api.users.validate-email');

    Route::post('users/validate-username', [UserController::class, 'validateUsername'])
        ->name('api.users.validate-username');

    Route::get('users/check-availability', [UserController::class, 'checkAvailability'])
        ->name('api.users.check-availability');

    Route::post('users/request-deletion', [UserController::class, 'requestAccountDeletion'])
        ->name('api.users.request-deletion');
});

// Role management routes
Route::middleware(['auth:sanctum', 'permission:role_manage', 'throttle:60,1'])->group(function () {
    Route::get('roles', [RoleController::class, 'index'])
        ->name('api.roles.index');

    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->name('api.roles.show');

    Route::post('roles', [RoleController::class, 'store'])
        ->name('api.roles.store');

    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->name('api.roles.update');

    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->name('api.roles.destroy');

    Route::get('roles/{role}/permissions', [RoleController::class, 'getRolePermissions'])
        ->name('api.roles.permissions');

    Route::put('roles/{role}/permissions', [RoleController::class, 'updateRolePermissions'])
        ->name('api.roles.update-permissions');

    Route::get('roles/permissions-list', [RoleController::class, 'getPermissionsList'])
        ->name('api.roles.permissions-list');
});

// Webhook endpoints for user events
Route::middleware(['api.prefix'])->group(function () {
    Route::post('webhooks/users/created', [UserController::class, 'userCreatedWebhook'])
        ->name('api.webhooks.users.created');

    Route::post('webhooks/users/updated', [UserController::class, 'userUpdatedWebhook'])
        ->name('api.webhooks.users.updated');

    Route::post('webhooks/users/deleted', [UserController::class, 'userDeletedWebhook'])
        ->name('api.webhooks.users.deleted');
});
