<?php

use App\Http\Controllers\Api\V1\FileController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // File operations with lower rate limit for uploads
    Route::middleware(['throttle:30,1'])->group(function () {
        
        // File Management permissions
        Route::middleware('permission:file_upload')->group(function () {
            // Single file upload
            Route::post('files/upload', [FileController::class, 'upload'])
                ->name('api.files.upload');

            Route::post('files/upload-avatar', [FileController::class, 'uploadAvatar'])
                ->name('api.files.upload-avatar');

            Route::post('files/upload-logo', [FileController::class, 'uploadLogo'])
                ->name('api.files.upload-logo');

            // Multiple file upload
            Route::post('files/upload-bulk', [FileController::class, 'uploadBulk'])
                ->name('api.files.upload-bulk');

            // File listing and info
            Route::get('files', [FileController::class, 'index'])
                ->name('api.files.index');

            Route::get('files/search', [FileController::class, 'search'])
                ->name('api.files.search');

            Route::get('files/{file}', [FileController::class, 'show'])
                ->name('api.files.show');

            Route::get('files/{file}/download', [FileController::class, 'download'])
                ->name('api.files.download');

            Route::get('files/{file}/url', [FileController::class, 'getUrl'])
                ->name('api.files.get-url');

            // File modifications
            Route::put('files/{file}', [FileController::class, 'update'])
                ->name('api.files.update');

            Route::patch('files/{file}/metadata', [FileController::class, 'updateMetadata'])
                ->name('api.files.update-metadata');

            // Image processing
            Route::post('files/{file}/resize', [FileController::class, 'resize'])
                ->name('api.files.resize');

            Route::post('files/{file}/optimize', [FileController::class, 'optimize'])
                ->name('api.files.optimize');

            Route::post('files/{file}/watermark', [FileController::class, 'addWatermark'])
                ->name('api.files.add-watermark');

            // File deletion
            Route::delete('files/{file}', [FileController::class, 'destroy'])
                ->name('api.files.destroy');

            Route::post('files/bulk-delete', [FileController::class, 'bulkDelete'])
                ->name('api.files.bulk-delete');
        });

        // File Organization permissions
        Route::middleware('permission:file_manage')->group(function () {
            // Folder operations
            Route::get('files/folders', [FileController::class, 'folders'])
                ->name('api.files.folders');

            Route::post('files/folders', [FileController::class, 'createFolder'])
                ->name('api.files.create-folder');

            Route::put('files/folders/{folder}', [FileController::class, 'updateFolder'])
                ->name('api.files.update-folder');

            Route::delete('files/folders/{folder}', [FileController::class, 'deleteFolder'])
                ->name('api.files.delete-folder');

            Route::post('files/{file}/move', [FileController::class, 'moveFile'])
                ->name('api.files.move');

            Route::post('files/bulk-move', [FileController::class, 'bulkMove'])
                ->name('api.files.bulk-move');

            Route::post('files/{file}/copy', [FileController::class, 'copyFile'])
                ->name('api.files.copy');

            Route::post('files/bulk-copy', [FileController::class, 'bulkCopy'])
                ->name('api.files.bulk-copy');
        });

        // System Admin permissions
        Route::middleware('permission:system_admin')->group(function () {
            // Storage management
            Route::get('files/storage/info', [FileController::class, 'storageInfo'])
                ->name('api.files.storage-info');

            Route::post('files/storage/cleanup', [FileController::class, 'cleanupStorage'])
                ->name('api.files.cleanup-storage');

            Route::post('files/storage/optimize', [FileController::class, 'optimizeStorage'])
                ->name('api.files.optimize-storage');

            // File statistics
            Route::get('files/statistics/summary', [FileController::class, 'statistics'])
                ->name('api.files.statistics');

            Route::get('files/statistics/usage', [FileController::class, 'usageStatistics'])
                ->name('api.files.usage-statistics');

            // Import/Export operations
            Route::post('files/export', [FileController::class, 'exportFiles'])
                ->name('api.files.export');

            Route::post('files/import', [FileController::class, 'importFiles'])
                ->name('api.files.import');
        });
    });

    // File viewing (higher rate limit, permission-based access)
    Route::middleware(['throttle:120,1'])->group(function () {
        Route::get('files/{file}/preview', [FileController::class, 'preview'])
            ->name('api.files.preview');

        Route::get('files/{file}/thumbnail', [FileController::class, 'thumbnail'])
            ->name('api.files.thumbnail');

        Route::get('files/{file}/metadata', [FileController::class, 'metadata'])
            ->name('api.files.metadata');
    });
});

// Public file access (limited and controlled)
Route::middleware(['api.prefix', 'throttle:200,1'])->group(function () {
    // Public file access for downloads and previews
    Route::get('files/public/{file}', [FileController::class, 'publicAccess'])
        ->name('api.files.public')
        ->where('file', '^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$');

    // Public image serving
    Route::get('files/images/{file}', [FileController::class, 'serveImage'])
        ->name('api.files.image')
        ->where('file', '^[a-zA-Z0-9\-_]+\.(jpg|jpeg|png|gif|webp)$');

    // Document preview (for specific allowed types)
    Route::get('files/docs/preview/{file}', [FileController::class, 'previewDocument'])
        ->name('api.files.preview-document')
        ->where('file', '^[a-zA-Z0-9\-_]+\.(pdf|doc|docx|xls|xlsx)$');
});

// File upload progress (webhook for progress updates)
Route::middleware(['api.prefix'])->group(function () {
    Route::post('webhooks/files/upload-progress', [FileController::class, 'uploadProgressWebhook'])
        ->name('api.webhooks.files.upload-progress');

    Route::post('webhooks/files/processed', [FileController::class, 'fileProcessedWebhook'])
        ->name('api.webhooks.files.processed');

    Route::post('webhooks/files/deleted', [FileController::class, 'fileDeletedWebhook'])
        ->name('api.webhooks.files.deleted');
});
