<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
|
| Here is where you can register settings-related routes for your application.
| These routes are loaded by the SettingsController controller.
|
*/

Route::prefix('settings')
    ->name('settings.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        
        // Settings Overview
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        
        // Main Settings Categories
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');
        
        Route::get('/branding', [SettingsController::class, 'branding'])->name('branding');
        Route::post('/branding', [SettingsController::class, 'updateBranding'])->name('branding.update');
        
        Route::get('/operations', [SettingsController::class, 'operations'])->name('operations');
        Route::post('/operations', [SettingsController::class, 'updateOperations'])->name('operations.update');
        
        Route::get('/finance', [SettingsController::class, 'finance'])->name('finance');
        Route::post('/finance', [SettingsController::class, 'updateFinance'])->name('finance.update');
        
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        
        Route::get('/integrations', [SettingsController::class, 'integrations'])->name('integrations');
        Route::post('/integrations', [SettingsController::class, 'updateIntegrations'])->name('integrations.update');
        
        // System Settings
        Route::get('/system', [SettingsController::class, 'system'])->name('system');
        Route::post('/system', [SettingsController::class, 'updateSystem'])->name('system.update');
        
        Route::get('/website', [SettingsController::class, 'website'])->name('website');
        Route::post('/website', [SettingsController::class, 'updateWebsite'])->name('website.update');
        
        // AJAX Endpoints
        Route::post('/test-connection', [SettingsController::class, 'testConnection'])->name('test-connection');
        Route::post('/validate-config', [SettingsController::class, 'validateConfig'])->name('validate-config');
        
        // Settings Management
        Route::get('/export', [SettingsController::class, 'export'])->name('export');
        Route::post('/import', [SettingsController::class, 'import'])->name('import');
        Route::post('/backup', [SettingsController::class, 'backup'])->name('backup');
        Route::post('/restore', [SettingsController::class, 'restore'])->name('restore');
        
        // Reset and Reset Actions
        Route::post('/reset/{category}', [SettingsController::class, 'reset'])->name('reset');
        Route::post('/clear-cache', [SettingsController::class, 'clearCache'])->name('clear-cache');
        Route::post('/optimize', [SettingsController::class, 'optimize'])->name('optimize');
    });