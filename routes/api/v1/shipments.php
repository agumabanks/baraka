<?php

use App\Http\Controllers\Api\V1\ShipmentController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // Shipment CRUD operations
    Route::middleware(['throttle:60,1'])->group(function () {
        
        // Read permissions
        Route::middleware('permission:shipment_read')->group(function () {
            // Basic CRUD
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

            Route::get('shipments/{shipment}/timeline', [ShipmentController::class, 'timeline'])
                ->name('api.shipments.timeline');

            Route::get('shipments/available-for-pickup', [ShipmentController::class, 'availableForPickup'])
                ->name('api.shipments.available-for-pickup');

            Route::get('shipments/expired', [ShipmentController::class, 'expired'])
                ->name('api.shipments.expired');

            Route::get('shipments/returns', [ShipmentController::class, 'returns'])
                ->name('api.shipments.returns');

            // Export and reports
            Route::get('shipments/export', [ShipmentController::class, 'export'])
                ->name('api.shipments.export');

            Route::get('shipments/reports/summary', [ShipmentController::class, 'summaryReport'])
                ->name('api.shipments.summary-report');

            Route::get('shipments/reports/detailed', [ShipmentController::class, 'detailedReport'])
                ->name('api.shipments.detailed-report');

            Route::get('shipments/{shipment}/label', [ShipmentController::class, 'generateLabel'])
                ->name('api.shipments.generate-label');

            Route::get('shipments/{shipment}/invoice', [ShipmentController::class, 'generateInvoice'])
                ->name('api.shipments.generate-invoice');

            Route::get('shipments/{shipment}/receipt', [ShipmentController::class, 'generateReceipt'])
                ->name('api.shipments.generate-receipt');
        });

        // Create permissions
        Route::middleware('permission:shipment_create')->group(function () {
            Route::post('shipments', [ShipmentController::class, 'store'])
                ->name('api.shipments.store');

            Route::post('shipments/bulk', [ShipmentController::class, 'bulkStore'])
                ->name('api.shipments.bulk-store');

            Route::post('shipments/import', [ShipmentController::class, 'importFromCsv'])
                ->name('api.shipments.import');

            Route::get('shipments/import/template', [ShipmentController::class, 'importTemplate'])
                ->name('api.shipments.import-template');
        });

        // Update permissions
        Route::middleware('permission:shipment_update')->group(function () {
            Route::put('shipments/{shipment}', [ShipmentController::class, 'update'])
                ->name('api.shipments.update');

            Route::patch('shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])
                ->name('api.shipments.update-status');

            Route::patch('shipments/{shipment}/priority', [ShipmentController::class, 'updatePriority'])
                ->name('api.shipments.update-priority');

            Route::patch('shipments/{shipment}/notes', [ShipmentController::class, 'updateNotes'])
                ->name('api.shipments.update-notes');

            Route::patch('shipments/{shipment}/customer-info', [ShipmentController::class, 'updateCustomerInfo'])
                ->name('api.shipments.update-customer-info');

            Route::patch('shipments/{shipment}/recipient-info', [ShipmentController::class, 'updateRecipientInfo'])
                ->name('api.shipments.update-recipient-info');

            // Status transition endpoints
            Route::post('shipments/{shipment}/assign-driver', [ShipmentController::class, 'assignDriver'])
                ->name('api.shipments.assign-driver');

            Route::post('shipments/{shipment}/unassign-driver', [ShipmentController::class, 'unassignDriver'])
                ->name('api.shipments.unassign-driver');

            Route::post('shipments/{shipment}/schedule-pickup', [ShipmentController::class, 'schedulePickup'])
                ->name('api.shipments.schedule-pickup');

            Route::post('shipments/{shipment}/pickup-confirmation', [ShipmentController::class, 'confirmPickup'])
                ->name('api.shipments.confirm-pickup');

            Route::post('shipments/{shipment}/mark-in-transit', [ShipmentController::class, 'markInTransit'])
                ->name('api.shipments.mark-in-transit');

            Route::post('shipments/{shipment}/out-for-delivery', [ShipmentController::class, 'outForDelivery'])
                ->name('api.shipments.out-for-delivery');

            Route::post('shipments/{shipment}/mark-delivered', [ShipmentController::class, 'markDelivered'])
                ->name('api.shipments.mark-delivered');

            Route::post('shipments/{shipment}/mark-returned', [ShipmentController::class, 'markReturned'])
                ->name('api.shipments.mark-returned');

            Route::post('shipments/{shipment}/reschedule', [ShipmentController::class, 'reschedule'])
                ->name('api.shipments.reschedule');

            Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])
                ->name('api.shipments.cancel');

            Route::post('shipments/{shipment}/hold', [ShipmentController::class, 'hold'])
                ->name('api.shipments.hold');

            Route::post('shipments/{shipment}/release', [ShipmentController::class, 'release'])
                ->name('api.shipments.release');
        });

        // Delete permissions
        Route::middleware('permission:shipment_delete')->group(function () {
            Route::delete('shipments/{shipment}', [ShipmentController::class, 'destroy'])
                ->name('api.shipments.destroy');

            Route::post('shipments/bulk-delete', [ShipmentController::class, 'bulkDelete'])
                ->name('api.shipments.bulk-delete');

            Route::post('shipments/{shipment}/soft-delete', [ShipmentController::class, 'softDelete'])
                ->name('api.shipments.soft-delete');

            Route::post('shipments/{shipment}/restore', [ShipmentController::class, 'restore'])
                ->name('api.shipments.restore');
        });

        // Bulk operations
        Route::middleware('permission:shipment_bulk')->group(function () {
            Route::post('shipments/bulk-status', [ShipmentController::class, 'bulkUpdateStatus'])
                ->name('api.shipments.bulk-status');

            Route::post('shipments/bulk-assign', [ShipmentController::class, 'bulkAssign'])
                ->name('api.shipments.bulk-assign');

            Route::post('shipments/bulk-route', [ShipmentController::class, 'bulkCreateRoute'])
                ->name('api.shipments.bulk-route');

            Route::post('shipments/bulk-print', [ShipmentController::class, 'bulkPrint'])
                ->name('api.shipments.bulk-print');
        });

        // Analytics and reporting permissions
        Route::middleware('permission:shipment_analytics')->group(function () {
            Route::get('shipments/stats/dashboard', [ShipmentController::class, 'dashboardStats'])
                ->name('api.shipments.dashboard-stats');

            Route::get('shipments/stats/analytics', [ShipmentController::class, 'analytics'])
                ->name('api.shipments.analytics');

            Route::get('shipments/stats/performance', [ShipmentController::class, 'performanceStats'])
                ->name('api.shipments.performance-stats');

            Route::get('shipments/stats/geographic', [ShipmentController::class, 'geographicStats'])
                ->name('api.shipments.geographic-stats');

            Route::get('shipments/stats/trends', [ShipmentController::class, 'trends'])
                ->name('api.shipments.trends');

            Route::get('shipments/stats/branch-comparison', [ShipmentController::class, 'branchComparison'])
                ->name('api.shipments.branch-comparison');
        });

        // Route management permissions
        Route::middleware('permission:route_manage')->group(function () {
            Route::get('shipments/routes', [ShipmentController::class, 'getRoutes'])
                ->name('api.shipments.routes');

            Route::post('shipments/routes', [ShipmentController::class, 'createRoute'])
                ->name('api.shipments.create-route');

            Route::put('shipments/routes/{route}', [ShipmentController::class, 'updateRoute'])
                ->name('api.shipments.update-route');

            Route::delete('shipments/routes/{route}', [ShipmentController::class, 'deleteRoute'])
                ->name('api.shipments.delete-route');

            Route::post('shipments/routes/{route}/optimize', [ShipmentController::class, 'optimizeRoute'])
                ->name('api.shipments.optimize-route');
        });

        // Self-service permissions (for merchant/customer portal)
        Route::middleware('permission:shipment_self')->group(function () {
            // Merchant can view and update their own shipments
            Route::get('my-shipments', [ShipmentController::class, 'myShipments'])
                ->name('api.shipments.my-shipments');

            Route::post('my-shipments', [ShipmentController::class, 'createMyShipment'])
                ->name('api.shipments.create-my-shipment');

            Route::put('my-shipments/{shipment}', [ShipmentController::class, 'updateMyShipment'])
                ->name('api.shipments.update-my-shipment');

            Route::post('my-shipments/{shipment}/track', [ShipmentController::class, 'trackMyShipment'])
                ->name('api.shipments.track-my-shipment');
        });
    });
});

// Public customer tracking (no authentication required for basic tracking)
Route::middleware(['api.prefix', 'throttle:100,1'])->group(function () {
    Route::get('tracking/{trackingId}', [ShipmentController::class, 'publicTracking'])
        ->name('api.shipments.public-tracking');

    Route::post('tracking/notifications', [ShipmentController::class, 'trackingNotification'])
        ->name('api.shipments.tracking-notification');

    Route::get('tracking/verify', [ShipmentController::class, 'verifyTrackingId'])
        ->name('api.shipments.verify-tracking');
});

// Webhook endpoints for shipment events
Route::middleware(['api.prefix'])->group(function () {
    Route::post('webhooks/shipments/status-changed', [ShipmentController::class, 'statusChangedWebhook'])
        ->name('api.webhooks.shipments.status-changed');

    Route::post('webhooks/shipments/delivered', [ShipmentController::class, 'deliveredWebhook'])
        ->name('api.webhooks.shipments.delivered');

    Route::post('webhooks/shipments/exception', [ShipmentController::class, 'exceptionWebhook'])
        ->name('api.webhooks.shipments.exception');

    Route::post('webhooks/shipments/location-updated', [ShipmentController::class, 'locationUpdatedWebhook'])
        ->name('api.webhooks.shipments.location-updated');
});
