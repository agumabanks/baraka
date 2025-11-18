<?php

use App\Http\Controllers\Api\V1\Admin\ShipmentController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // Shipment CRUD operations
    Route::middleware(['throttle:60,1'])->group(function () {
        
        // Read permissions
        Route::middleware('permission:shipment_read')->group(function () {
            // Basic CRUD - only methods that exist in Admin/ShipmentController
            Route::get('shipments', [ShipmentController::class, 'index'])
                ->name('api.shipments.index');

            Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])
                ->name('api.shipments.show');

            Route::get('shipments/export', [ShipmentController::class, 'export'])
                ->name('api.shipments.export');
        });

        // Update permissions
        Route::middleware('permission:shipment_update')->group(function () {
            Route::patch('shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])
                ->name('api.shipments.update-status');
        });
    });
});

// Public customer tracking (no authentication required for basic tracking)
Route::middleware(['api.prefix', 'throttle:100,1'])->group(function () {
    Route::get('tracking/{trackingId}', function ($trackingId) {
        // Basic tracking endpoint - returns basic tracking info
        return response()->json([
            'success' => true,
            'tracking_id' => $trackingId,
            'message' => 'Tracking functionality not implemented yet'
        ]);
    })->name('api.shipments.public-tracking');
});
