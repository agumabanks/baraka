<?php

use App\Http\Controllers\Api\V1\MobileScanningController;
use App\Http\Controllers\Admin\EnhancedEdiController;
use Illuminate\Support\Facades\Route;

/**
 * Readiness Improvements Routes
 * 
 * Mobile Scanning APIs, EDI Integration, and Webhook Management
 * with proper rate limiting and security middleware
 */

Route::prefix('v1')->middleware(['api', 'api.rate_limit'])->group(function () {
    
    // Mobile Scanning APIs (high rate limit - 500/hour)
    Route::prefix('mobile')->group(function () {
        Route::post('/scan', [MobileScanningController::class, 'scan'])
            ->name('api.mobile.scan')
            ->middleware(['auth:sanctum']);
        
        Route::post('/bulk-scan', [MobileScanningController::class, 'bulkScan'])
            ->name('api.mobile.bulk-scan')
            ->middleware(['auth:sanctum']);
        
        Route::get('/shipment/{tracking}', [MobileScanningController::class, 'getShipmentDetails'])
            ->name('api.mobile.shipment-details')
            ->middleware(['auth:sanctum']);
        
        Route::get('/offline-sync-queue', [MobileScanningController::class, 'getOfflineSyncQueue'])
            ->name('api.mobile.offline-queue')
            ->middleware(['auth:sanctum']);
        
        Route::post('/confirm-sync', [MobileScanningController::class, 'confirmSync'])
            ->name('api.mobile.confirm-sync')
            ->middleware(['auth:sanctum']);
    });

    // EDI Integration (controlled rate limit)
    Route::prefix('edi')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/receive', [EnhancedEdiController::class, 'receiveDocs'])
            ->name('api.edi.receive');
        
        Route::get('/status/{transaction}', [EnhancedEdiController::class, 'getTransactionStatus'])
            ->name('api.edi.status');
        
        Route::get('/mappings', [EnhancedEdiController::class, 'listMappings'])
            ->name('api.edi.mappings');
        
        Route::post('/mappings', [EnhancedEdiController::class, 'saveMappings'])
            ->name('api.edi.mappings.save');
    });
});
