<?php

use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\MetricsController;
use App\Http\Controllers\Api\V1\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Client\ShipmentController as ClientShipmentController;
use App\Http\Controllers\Api\V1\TrackingController;
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
        Route::get('metrics', [MetricsController::class, 'index']);
    });
});
