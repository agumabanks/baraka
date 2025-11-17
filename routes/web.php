<?php

use App\Http\Controllers\Backend\GeneralSettingsController;
use App\Http\Controllers\SpaController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Blade Routes
|--------------------------------------------------------------------------
|
| Dedicated Laravel Blade routes for the Settings module that bypass
| the SPA and work directly with Blade views using the new layout system.
|
*/

Route::middleware(['auth', 'verified'])->prefix('settings')->name('settings.')->group(function () {
    // Settings Overview
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::put('/', [GeneralSettingsController::class, 'update'])->name('update');
    Route::patch('/', [GeneralSettingsController::class, 'update'])->name('update.patch');
    
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

    // Language & Translations
    Route::get('/language', [SettingsController::class, 'language'])->name('language');
    Route::post('/language', [SettingsController::class, 'updateLanguage'])->name('language.update');
    
    // System Settings
    Route::get('/system', [SettingsController::class, 'system'])->name('system');
    Route::post('/system', [SettingsController::class, 'updateSystem'])->name('system.update');
    
    Route::get('/website', [SettingsController::class, 'website'])->name('website');
    Route::post('/website', [SettingsController::class, 'updateWebsite'])->name('website.update');
    
    // AJAX Endpoints
    Route::post('/test-connection', [SettingsController::class, 'testConnection'])->name('test-connection');
    Route::post('/clear-cache', [SettingsController::class, 'clearCache'])->name('clear-cache');
    Route::get('/export', [SettingsController::class, 'export'])->name('export');
});

/*
|--------------------------------------------------------------------------
| Legacy General Settings Routes (for backward compatibility)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('general-settings')->name('general-settings.')->group(function () {
    Route::get('/', [GeneralSettingsController::class, 'index'])->name('index');
    Route::put('/', [GeneralSettingsController::class, 'update'])->name('update');
    Route::patch('/', [GeneralSettingsController::class, 'update'])->name('update.patch');
});

/*
|--------------------------------------------------------------------------
| Legacy SPA General Settings Redirects
|--------------------------------------------------------------------------
|
| Ensure historical SPA URLs for General Settings now redirect to the
| new Blade-powered Settings index so users never see the old React
| General Settings screen.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/general-settings', function () {
        return redirect()->route('settings.index');
    });

    Route::get('/dashboard/general-settings/index', function () {
        return redirect()->route('settings.index');
    });
});

/*
|--------------------------------------------------------------------------
| SPA Entry Route
|--------------------------------------------------------------------------
|
| All browser routes are handled by the React single page application.
| Any request that is not an API call or settings route will be served 
| the compiled React bundle located under public/app/index.html.
| Settings routes are excluded from SPA handling above.
|
*/

Route::get('/{any?}', SpaController::class)
    ->where('any', '^(?!api|settings|general-settings).*')
    ->name('spa.entry');
