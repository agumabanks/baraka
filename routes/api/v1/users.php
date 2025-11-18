<?php

use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\RoleController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // User CRUD operations - only methods that exist in Backend controllers
    Route::middleware(['throttle:60,1'])->group(function () {
        
        // Read permissions
        Route::middleware('permission:user_read')->group(function () {
            Route::get('users', [UserController::class, 'index'])
                ->name('api.users.index');

            Route::get('users/{user}', function ($user) {
                // Basic user info endpoint - using existing backend controller logic
                return response()->json([
                    'success' => true,
                    'user_id' => $user,
                    'message' => 'User details functionality not fully implemented in V1 API'
                ]);
            })->name('api.users.show');
        });

        // Create permissions
        Route::middleware('permission:user_create')->group(function () {
            Route::post('users', [UserController::class, 'store'])
                ->name('api.users.store');
        });

        // Update permissions
        Route::middleware('permission:user_update')->group(function () {
            Route::put('users/{user}', [UserController::class, 'update'])
                ->name('api.users.update');
        });

        // Delete permissions
        Route::middleware('permission:user_delete')->group(function () {
            Route::delete('users/{user}', [UserController::class, 'destroy'])
                ->name('api.users.destroy');
        });

        // Permission management
        Route::middleware('permission:permission_manage')->group(function () {
            Route::get('users/{user}/permissions', function ($user) {
                return response()->json([
                    'success' => true,
                    'user_id' => $user,
                    'permissions' => ['Basic user permissions not implemented in V1 API'],
                ]);
            })->name('api.users.permissions');

            Route::post('users/{user}/permissions', [UserController::class, 'permissionsUpdate'])
                ->name('api.users.permissions-update');
        });
    });
});

// Role management routes - only methods that exist in Backend RoleController
Route::middleware(['auth:sanctum', 'permission:role_manage', 'throttle:60,1'])->group(function () {
    Route::get('roles', [RoleController::class, 'index'])
        ->name('api.roles.index');

    Route::get('roles/{role}', function ($role) {
        return response()->json([
            'success' => true,
            'role_id' => $role,
            'message' => 'Role details functionality not fully implemented in V1 API'
        ]);
    })->name('api.roles.show');

    Route::post('roles', [RoleController::class, 'store'])
        ->name('api.roles.store');

    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->name('api.roles.update');

    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->name('api.roles.destroy');
});
