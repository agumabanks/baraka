<?php

use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\MetricsController;
use App\Http\Controllers\Api\V1\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Client\ShipmentController as ClientShipmentController;
use App\Http\Controllers\Api\V1\DispatchController;
use App\Http\Controllers\Api\V1\PickupController;
use App\Http\Controllers\Api\V1\PodController;
use App\Http\Controllers\Api\V1\QuotesController;
use App\Http\Controllers\Api\V1\ShipmentEventController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes for tracking
Route::get('tracking/{token}', [TrackingController::class, 'show'])->name('tracking.show');

// Authenticated client routes (api guard with device binding)
Route::middleware(['auth:api', 'bind_device'])->group(function () {
    // Auth routes
    Route::post('login', [AuthController::class, 'login'])->withoutMiddleware(['auth:api', 'bind_device']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::patch('me', [AuthController::class, 'updateMe']);

    // Routes with idempotency for write operations
    Route::middleware('idempotency')->group(function () {
        Route::post('shipments', [ClientShipmentController::class, 'store']);
        Route::post('shipments/{shipment}/cancel', [ClientShipmentController::class, 'cancel']);
    });

    // Other client routes without idempotency
    Route::get('shipments', [ClientShipmentController::class, 'index']);
    Route::get('shipments/{shipment}', [ClientShipmentController::class, 'show']);
    Route::get('shipments/{shipment}/events', [ShipmentEventController::class, 'index']);
    Route::get('shipments/{shipment}/pod', [PodController::class, 'show']);
    Route::get('quotes', [QuotesController::class, 'index']);
    Route::get('quotes/{quote}', [QuotesController::class, 'show']);
    Route::get('pickups', [PickupController::class, 'index']);
    Route::get('pickups/{pickup}', [PickupController::class, 'show']);
    Route::get('tasks', [TaskController::class, 'index']);
    Route::get('tasks/{task}', [TaskController::class, 'show']);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);

    // Routes with idempotency for write operations
    Route::middleware('idempotency')->group(function () {
        Route::post('quotes', [QuotesController::class, 'store']);
        Route::post('pickups', [PickupController::class, 'store']);
        Route::post('shipments/{shipment}/events', [ShipmentEventController::class, 'store']);
    });

    // POD routes with idempotency
    Route::middleware('idempotency')->group(function () {
        Route::post('tasks/{task}/pod', [PodController::class, 'store']);
        Route::post('pod/{pod}/verify', [PodController::class, 'verify']);
    });
});

// Authenticated admin routes (admin guard with role check)
Route::middleware(['auth:admin'])->group(function () {
    Route::middleware('role:admin')->group(function () {
        // Routes with idempotency for write operations
        Route::middleware('idempotency')->group(function () {
            Route::patch('customers/{customer}', [CustomerController::class, 'update']);
            Route::patch('shipments/{shipment}/status', [AdminShipmentController::class, 'updateStatus']);
        });

        // Other admin routes without idempotency
        Route::get('customers', [CustomerController::class, 'index']);
        Route::get('customers/{customer}', [CustomerController::class, 'show']);
        Route::get('shipments', [AdminShipmentController::class, 'index']);
        Route::get('shipments/{shipment}', [AdminShipmentController::class, 'show']);
        Route::post('shipments/export', [AdminShipmentController::class, 'export']);
        Route::get('dispatch/unassigned', [DispatchController::class, 'unassigned']);
        Route::get('dispatch/drivers', [DispatchController::class, 'drivers']);
        Route::get('metrics', [MetricsController::class, 'index']);

        // Routes with idempotency for write operations
        Route::middleware('idempotency')->group(function () {
            Route::post('dispatch/assign', [DispatchController::class, 'assign']);
        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('dashboard/statements', [DashboardController::class, 'statements']);
        Route::get('dashboard/charts/income-expense', [DashboardController::class, 'incomeExpenseChart']);
            Route::post('dispatch/optimize', [DispatchController::class, 'optimize']);
        });
    });
});
