<?php

use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\BranchManagerController;
use App\Http\Controllers\Api\V1\BranchWorkerController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // Branch CRUD operations
    Route::middleware(['throttle:60,1'])->group(function () {
        
        // Read permissions
        Route::middleware('permission:branch_read')->group(function () {
            Route::get('branches', [BranchController::class, 'index'])
                ->name('api.branches.index');

            Route::get('branches/{branch}', [BranchController::class, 'show'])
                ->name('api.branches.show');

            Route::get('branches/search', [BranchController::class, 'search'])
                ->name('api.branches.search');

            Route::get('branches/available-for-assignment', [BranchController::class, 'availableForAssignment'])
                ->name('api.branches.available-for-assignment');

            Route::get('branches/near', [BranchController::class, 'nearby'])
                ->name('api.branches.nearby');

            Route::get('branches/with-stats', [BranchController::class, 'withStats'])
                ->name('api.branches.with-stats');

            // Branch-specific data
            Route::get('branches/{branch}/managers', [BranchController::class, 'managers'])
                ->name('api.branches.managers');

            Route::get('branches/{branch}/workers', [BranchController::class, 'workers'])
                ->name('api.branches.workers');

            Route::get('branches/{branch}/shipments', [BranchController::class, 'shipments'])
                ->name('api.branches.shipments');

            Route::get('branches/{branch}/current-shipments', [BranchController::class, 'currentShipments'])
                ->name('api.branches.current-shipments');

            Route::get('branches/{branch}/stats', [BranchController::class, 'stats'])
                ->name('api.branches.stats');

            Route::get('branches/{branch}/activity', [BranchController::class, 'activity'])
                ->name('api.branches.activity');

            Route::get('branches/{branch}/overview', [BranchController::class, 'overview'])
                ->name('api.branches.overview');

            // Reports
            Route::get('branches/{branch}/reports/performance', [BranchController::class, 'performanceReport'])
                ->name('api.branches.performance-report');

            Route::get('branches/{branch}/reports/operations', [BranchController::class, 'operationsReport'])
                ->name('api.branches.operations-report');

            Route::get('branches/export', [BranchController::class, 'export'])
                ->name('api.branches.export');
        });

        // Create permissions
        Route::middleware('permission:branch_create')->group(function () {
            Route::post('branches', [BranchController::class, 'store'])
                ->name('api.branches.store');

            Route::post('branches/import', [BranchController::class, 'import'])
                ->name('api.branches.import');
        });

        // Update permissions
        Route::middleware('permission:branch_update')->group(function () {
            Route::put('branches/{branch}', [BranchController::class, 'update'])
                ->name('api.branches.update');

            Route::patch('branches/{branch}/status', [BranchController::class, 'updateStatus'])
                ->name('api.branches.update-status');

            Route::patch('branches/{branch}/contact', [BranchController::class, 'updateContact'])
                ->name('api.branches.update-contact');

            Route::patch('branches/{branch}/location', [BranchController::class, 'updateLocation'])
                ->name('api.branches.update-location');

            Route::patch('branches/{branch}/services', [BranchController::class, 'updateServices'])
                ->name('api.branches.update-services');

            // Manager management
            Route::post('branches/{branch}/managers', [BranchController::class, 'addManager'])
                ->name('api.branches.add-manager');

            Route::delete('branches/{branch}/managers/{manager}', [BranchController::class, 'removeManager'])
                ->name('api.branches.remove-manager');

            Route::put('branches/{branch}/managers/{manager}/role', [BranchController::class, 'updateManagerRole'])
                ->name('api.branches.update-manager-role');

            // Worker management
            Route::post('branches/{branch}/workers', [BranchController::class, 'addWorker'])
                ->name('api.branches.add-worker');

            Route::delete('branches/{branch}/workers/{worker}', [BranchController::class, 'removeWorker'])
                ->name('api.branches.remove-worker');

            Route::put('branches/{branch}/workers/{worker}/status', [BranchController::class, 'updateWorkerStatus'])
                ->name('api.branches.update-worker-status');

            // Capacity management
            Route::put('branches/{branch}/capacity', [BranchController::class, 'updateCapacity'])
                ->name('api.branches.update-capacity');

            Route::put('branches/{branch}/operating-hours', [BranchController::class, 'updateOperatingHours'])
                ->name('api.branches.update-operating-hours');
        });

        // Delete permissions
        Route::middleware('permission:branch_delete')->group(function () {
            Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
                ->name('api.branches.destroy');

            Route::post('branches/{branch}/archive', [BranchController::class, 'archive'])
                ->name('api.branches.archive');

            Route::post('branches/{branch}/restore', [BranchController::class, 'restore'])
                ->name('api.branches.restore');
        });

        // Analytics permissions
        Route::middleware('permission:branch_analytics')->group(function () {
            Route::get('branches/analytics/performance', [BranchController::class, 'analytics'])
                ->name('api.branches.analytics');

            Route::get('branches/analytics/comparison', [BranchController::class, 'comparison'])
                ->name('api.branches.comparison');

            Route::get('branches/analytics/trends', [BranchController::class, 'trends'])
                ->name('api.branches.trends');

            Route::get('branches/analytics/utilization', [BranchController::class, 'utilization'])
                ->name('api.branches.utilization');
        });

        // System admin permissions
        Route::middleware('permission:system_admin')->group(function () {
            Route::post('branches/{branch}/sync-data', [BranchController::class, 'syncData'])
                ->name('api.branches.sync-data');

            Route::post('branches/bulk-operations', [BranchController::class, 'bulkOperations'])
                ->name('api.branches.bulk-operations');
        });
    });

    // Branch Manager specific routes
    Route::middleware(['auth:sanctum', 'permission:branch_manage', 'throttle:60,1'])->group(function () {
        Route::prefix('branches/{branch}/managers')->group(function () {
            Route::get('/', [BranchManagerController::class, 'index'])
                ->name('api.branch-managers.index');

            Route::post('/', [BranchManagerController::class, 'store'])
                ->name('api.branch-managers.store');

            Route::get('{manager}', [BranchManagerController::class, 'show'])
                ->name('api.branch-managers.show');

            Route::put('{manager}', [BranchManagerController::class, 'update'])
                ->name('api.branch-managers.update');

            Route::delete('{manager}', [BranchManagerController::class, 'destroy'])
                ->name('api.branch-managers.destroy');

            Route::patch('{manager}/permissions', [BranchManagerController::class, 'updatePermissions'])
                ->name('api.branch-managers.permissions');

            Route::get('{manager}/activity', [BranchManagerController::class, 'activity'])
                ->name('api.branch-managers.activity');
        });
    });

    // Branch Worker specific routes
    Route::middleware(['auth:sanctum', 'permission:branch_manage', 'throttle:60,1'])->group(function () {
        Route::prefix('branches/{branch}/workers')->group(function () {
            Route::get('/', [BranchWorkerController::class, 'index'])
                ->name('api.branch-workers.index');

            Route::post('/', [BranchWorkerController::class, 'store'])
                ->name('api.branch-workers.store');

            Route::get('{worker}', [BranchWorkerController::class, 'show'])
                ->name('api.branch-workers.show');

            Route::put('{worker}', [BranchWorkerController::class, 'update'])
                ->name('api.branch-workers.update');

            Route::delete('{worker}', [BranchWorkerController::class, 'destroy'])
                ->name('api.branch-workers.destroy');

            Route::patch('{worker}/status', [BranchWorkerController::class, 'updateStatus'])
                ->name('api.branch-workers.update-status');

            Route::patch('{worker}/availability', [BranchWorkerController::class, 'updateAvailability'])
                ->name('api.branch-workers.update-availability');

            Route::get('{worker}/assignments', [BranchWorkerController::class, 'assignments'])
                ->name('api.branch-workers.assignments');

            Route::get('{worker}/performance', [BranchWorkerController::class, 'performance'])
                ->name('api.branch-workers.performance');
        });
    });
});

// Public branch information (no authentication required)
Route::middleware(['api.prefix', 'throttle:200,1'])->group(function () {
    Route::get('branches/public', [BranchController::class, 'publicList'])
        ->name('api.branches.public');

    Route::get('branches/public/locations', [BranchController::class, 'publicLocations'])
        ->name('api.branches.public-locations');

    Route::get('branches/{branch}/public', [BranchController::class, 'publicShow'])
        ->name('api.branches.public-show');

    Route::get('branches/{branch}/contact-info', [BranchController::class, 'publicContactInfo'])
        ->name('api.branches.public-contact');
});

// Webhook endpoints for branch events
Route::middleware(['api.prefix'])->group(function () {
    Route::post('webhooks/branches/created', [BranchController::class, 'branchCreatedWebhook'])
        ->name('api.webhooks.branches.created');

    Route::post('webhooks/branches/updated', [BranchController::class, 'branchUpdatedWebhook'])
        ->name('api.webhooks.branches.updated');

    Route::post('webhooks/branches/manager-assigned', [BranchController::class, 'managerAssignedWebhook'])
        ->name('api.webhooks.branches.manager-assigned');

    Route::post('webhooks/branches/worker-added', [BranchController::class, 'workerAddedWebhook'])
        ->name('api.webhooks.branches.worker-added');
});
