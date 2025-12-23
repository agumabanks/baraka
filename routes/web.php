<?php

use App\Http\Controllers\Backend\GeneralSettingsController;
use App\Http\Controllers\Branch\ClientsController;
use App\Http\Controllers\Branch\FinanceController;
use App\Http\Controllers\Branch\FleetController;
use App\Http\Controllers\Branch\OperationsController;
use App\Http\Controllers\Branch\ShipmentController;
use App\Http\Controllers\Branch\WarehouseController;
use App\Http\Controllers\Branch\WorkforceController;
use App\Http\Controllers\Branch\Auth\BranchAuthController;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\BranchDashboardController;
use App\Http\Controllers\Api\V1\TrackingController as PublicTrackingController;
use App\Http\Controllers\SpaController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Security\AuditLogController;
use App\Http\Controllers\Security\SessionController;

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
    Route::delete('/language/{key}', [SettingsController::class, 'deleteTranslation'])->name('language.delete');
    Route::get('/language/export', [SettingsController::class, 'exportTranslations'])->name('language.export');
    Route::post('/language/import', [SettingsController::class, 'importTranslations'])->name('language.import');
    Route::get('/language/validate', [SettingsController::class, 'validateTranslations'])->name('language.validate');
    Route::post('/language/default-locale', [SettingsController::class, 'setDefaultLocale'])->name('language.default-locale');
    Route::post('/language/mode', [SettingsController::class, 'setLanguageMode'])->name('language.mode');
    Route::post('/language/sync-from-files', [\App\Http\Controllers\Backend\LanguageController::class, 'syncFromFiles'])->name('language.sync-from-files');
    
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

    Route::get('/dashboard', function () {
        return redirect('/admin/dashboard-blade');
    })->name('dashboard');

    Route::post('/logout', function () {
        auth()->guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

/*
|--------------------------------------------------------------------------
| Branch Dashboard (Laravel Blade)
|--------------------------------------------------------------------------
| Branch managers and operators land here instead of the React SPA.
*/

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Default /login redirects to admin login
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Admin Authentication
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('account.lockout')->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Branch Authentication
Route::prefix('branch')->name('branch.')->group(function () {
    Route::get('/login', [BranchAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [BranchAuthController::class, 'login'])->middleware('account.lockout')->name('login.submit');
    Route::post('/logout', [BranchAuthController::class, 'logout'])->name('logout');
});

// Client (Customer) Authentication
Route::prefix('client')->name('client.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Client\Auth\ClientAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Client\Auth\ClientAuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [\App\Http\Controllers\Client\Auth\ClientAuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Client\Auth\ClientAuthController::class, 'register'])->name('register.submit');
    Route::post('/logout', [\App\Http\Controllers\Client\Auth\ClientAuthController::class, 'logout'])->name('logout');
});

// Client Dashboard (authenticated customers)
Route::middleware(['auth:customer'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Client\ClientDashboardController::class, 'index'])->name('dashboard');

    // Client Portal
    Route::get('/shipments', [\App\Http\Controllers\Client\ClientPortalController::class, 'shipments'])->name('shipments.index');
    Route::get('/shipments/create', [\App\Http\Controllers\Client\ClientPortalController::class, 'createShipment'])->name('shipments.create');
    Route::post('/shipments', [\App\Http\Controllers\Client\ClientPortalController::class, 'storeShipment'])->name('shipments.store');
    Route::get('/shipments/{shipment}', [\App\Http\Controllers\Client\ClientPortalController::class, 'showShipment'])->name('shipments.show');

    Route::get('/tracking', [\App\Http\Controllers\Client\ClientPortalController::class, 'tracking'])->name('tracking');
    Route::get('/quotes', [\App\Http\Controllers\Client\ClientPortalController::class, 'quotes'])->name('quotes');
    Route::post('/quotes/calculate', [\App\Http\Controllers\Client\ClientPortalController::class, 'calculateQuote'])->name('quotes.calculate');

    Route::get('/addresses', [\App\Http\Controllers\Client\ClientPortalController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [\App\Http\Controllers\Client\ClientPortalController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/addresses/{address}', [\App\Http\Controllers\Client\ClientPortalController::class, 'deleteAddress'])->name('addresses.delete');

    Route::get('/invoices', [\App\Http\Controllers\Client\ClientPortalController::class, 'invoices'])->name('invoices');

    Route::get('/profile', [\App\Http\Controllers\Client\ClientPortalController::class, 'profile'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\Client\ClientPortalController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\Client\ClientPortalController::class, 'updatePassword'])->name('profile.password');

    Route::get('/support', [\App\Http\Controllers\Client\ClientPortalController::class, 'support'])->name('support');
});

Route::middleware(['auth', 'branch.context', 'branch.locale', 'branch.isolation'])
    ->prefix('branch')
    ->name('branch.')
    ->group(function () {
        Route::get('/dashboard', [BranchDashboardController::class, 'index'])->name('dashboard');
        Route::get('/operations', [OperationsController::class, 'index'])->name('operations');
        Route::get('/shipments', [OperationsController::class, 'shipments'])->name('shipments');
        Route::get('/exceptions', [OperationsController::class, 'exceptions'])->name('exceptions.index');
        Route::post('/operations/assign', [OperationsController::class, 'assign'])->name('operations.assign');
        Route::post('/operations/status', [OperationsController::class, 'updateStatus'])->name('operations.status');
        Route::post('/operations/prioritize', [OperationsController::class, 'reprioritize'])->name('operations.prioritize');
        Route::post('/operations/hold', [OperationsController::class, 'hold'])->name('operations.hold');
        Route::post('/operations/reroute', [OperationsController::class, 'reroute'])->name('operations.reroute');
        Route::post('/operations/alerts', [OperationsController::class, 'raiseAlert'])->name('operations.alerts.raise');
        Route::post('/operations/alerts/{alert}/resolve', [OperationsController::class, 'resolveAlert'])->name('operations.alerts.resolve');
        Route::post('/operations/scan', [OperationsController::class, 'scan'])->name('operations.scan');
        Route::post('/operations/handoff', [OperationsController::class, 'requestHandoff'])->name('operations.handoff.request');
        Route::post('/operations/handoff/{handoff}/approve', [OperationsController::class, 'approveHandoff'])->name('operations.handoff.approve');
        Route::post('/operations/handoff/{handoff}/complete', [OperationsController::class, 'completeHandoff'])->name('operations.handoff.complete');
        Route::get('/operations/handoffs/manifest', [OperationsController::class, 'batchHandoffManifest'])->name('operations.handoff.manifest.batch');
        Route::get('/operations/handoff/{handoff}/manifest', [OperationsController::class, 'handoffManifest'])->name('operations.handoff.manifest');
        Route::get('/operations/manifest/shipments', [OperationsController::class, 'shipmentManifest'])->name('operations.manifest.shipments');
        Route::get('/operations/manifest/route', [OperationsController::class, 'routeManifest'])->name('operations.manifest.route');
        
        // Maintenance Windows
        Route::get('/operations/maintenance', [OperationsController::class, 'maintenance'])->name('operations.maintenance');
        Route::post('/operations/maintenance', [OperationsController::class, 'storeMaintenance'])->name('operations.maintenance.store');
        Route::post('/operations/maintenance/{id}/start', [OperationsController::class, 'startMaintenance'])->name('operations.maintenance.start');
        Route::post('/operations/maintenance/{id}/complete', [OperationsController::class, 'completeMaintenance'])->name('operations.maintenance.complete');
        Route::post('/operations/maintenance/{id}/cancel', [OperationsController::class, 'cancelMaintenance'])->name('operations.maintenance.cancel');
        Route::get('/operations/maintenance/entities', [OperationsController::class, 'getMaintenanceEntities'])->name('operations.maintenance.entities');

        // Shipments POS (Frontdesk System)
        Route::prefix('pos')->name('pos.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'index'])->name('index');
            Route::get('/search-customer', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'searchCustomer'])->name('search-customer');
            Route::post('/quick-create-customer', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'quickCreateCustomer'])->name('quick-create-customer');
            Route::post('/calculate-rate', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'calculateRate'])->name('calculate-rate');
            Route::post('/create-shipment', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'createShipment'])->name('create-shipment');
            Route::get('/quick-track', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'quickTrack'])->name('quick-track');
            Route::get('/service-levels', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'getServiceLevels'])->name('service-levels');
            Route::get('/{shipment}/label', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'printLabel'])->name('label');
            Route::get('/{shipment}/receipt', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'printReceipt'])->name('receipt');
        });

        // Shipments (branch-scoped)
        Route::prefix('shipments')->name('shipments.')->group(function () {
            Route::get('/labels', [ShipmentController::class, 'labels'])->name('labels');
            Route::get('/', [ShipmentController::class, 'index'])->name('index');
            Route::get('/create', [ShipmentController::class, 'create'])->name('create');
            Route::post('/', [ShipmentController::class, 'store'])->name('store');
            Route::get('/{shipment}', [ShipmentController::class, 'show'])->name('show');
            Route::get('/{shipment}/edit', [ShipmentController::class, 'edit'])->name('edit');
            Route::put('/{shipment}', [ShipmentController::class, 'update'])->name('update');
            Route::get('/{shipment}/label', [ShipmentController::class, 'label'])->name('label');
        });

        // Booking Wizard (branch)
        Route::prefix('booking')->name('booking.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Branch\BookingWizardController::class, 'index'])->name('index');
            Route::post('/step1', [\App\Http\Controllers\Branch\BookingWizardController::class, 'step1'])->name('step1');
            Route::post('/step2', [\App\Http\Controllers\Branch\BookingWizardController::class, 'step2'])->name('step2');
            Route::post('/step3', [\App\Http\Controllers\Branch\BookingWizardController::class, 'step3'])->name('step3');
            Route::post('/step4', [\App\Http\Controllers\Branch\BookingWizardController::class, 'step4'])->name('step4');
            Route::get('/search-customers', [\App\Http\Controllers\Branch\BookingWizardController::class, 'searchCustomers'])->name('search-customers');
            Route::post('/quote', [\App\Http\Controllers\Branch\BookingWizardController::class, 'getQuote'])->name('quote');
            Route::post('/compare', [\App\Http\Controllers\Branch\BookingWizardController::class, 'compareServices'])->name('compare');
        });

        // Workforce routes
        Route::get('/workforce', [WorkforceController::class, 'index'])->name('workforce');
        Route::get('/workforce/schedule', [WorkforceController::class, 'scheduleView'])->name('workforce.schedule');
        Route::post('/workforce', [WorkforceController::class, 'store'])->name('workforce.store');
        Route::post('/workforce/schedule-shift', [WorkforceController::class, 'schedule'])->name('workforce.schedule.store');
        Route::post('/workforce/check-in', [WorkforceController::class, 'checkIn'])->name('workforce.checkin');
        Route::post('/workforce/check-out', [WorkforceController::class, 'checkOut'])->name('workforce.checkout');
        Route::patch('/workforce/{worker}', [WorkforceController::class, 'update'])->name('workforce.update');
        Route::delete('/workforce/{worker}', [WorkforceController::class, 'archive'])->name('workforce.archive');
        Route::post('/workforce/bulk-action', [WorkforceController::class, 'bulkAction'])->name('workforce.bulk-action');
        Route::get('/workforce/export', [WorkforceController::class, 'export'])->name('workforce.export');
        Route::get('/workforce/{worker}', [WorkforceController::class, 'show'])->name('workforce.show');
        Route::get('/workforce/{worker}/edit', [WorkforceController::class, 'edit'])->name('workforce.edit');

        // Clients routes
        Route::get('/clients', [ClientsController::class, 'index'])->name('clients');
        Route::get('/clients/index', [ClientsController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ClientsController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientsController::class, 'store'])->name('clients.store');
        Route::patch('/clients/{client}', [ClientsController::class, 'update'])->name('clients.update');
        Route::post('/clients/bulk-action', [ClientsController::class, 'bulkAction'])->name('clients.bulk-action');
        Route::get('/clients/export', [ClientsController::class, 'export'])->name('clients.export');
        Route::get('/clients/{client}', [ClientsController::class, 'show'])->name('clients.show');
        Route::get('/clients/{client}/edit', [ClientsController::class, 'edit'])->name('clients.edit');
        Route::get('/clients/{client}/quick-shipment', [ClientsController::class, 'quickShipment'])->name('clients.quick-shipment');
        Route::get('/clients/{client}/statement', [ClientsController::class, 'statement'])->name('clients.statement');
        Route::get('/clients/{client}/statement/download', [ClientsController::class, 'statement'])->name('clients.statement.download');
        Route::get('/clients/{client}/statement-preview', [ClientsController::class, 'statementPreview'])->name('clients.statement-preview');
        Route::get('/clients/{client}/contracts', [ClientsController::class, 'contracts'])->name('clients.contracts');
        Route::post('/clients/{client}/activity', [ClientsController::class, 'storeActivity'])->name('clients.activity.store');
        Route::post('/clients/{client}/reminder', [ClientsController::class, 'storeReminder'])->name('clients.reminder.store');
        Route::post('/clients/{client}/reminder/{reminder}/complete', [ClientsController::class, 'completeReminder'])->name('clients.reminder.complete');
        Route::post('/clients/{client}/credit', [ClientsController::class, 'adjustCredit'])->name('clients.adjust-credit');
        Route::post('/clients/{client}/refresh-stats', [ClientsController::class, 'refreshStats'])->name('clients.refresh-stats');

        // Finance routes
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
        Route::get('/finance/index', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('/finance/invoices', [FinanceController::class, 'storeInvoice'])->name('finance.invoice.store');
        Route::post('/finance/payments', [FinanceController::class, 'storePayment'])->name('finance.payment.store');
        Route::get('/finance/export', [FinanceController::class, 'export'])->name('finance.export');
        Route::get('/finance/cod', [FinanceController::class, 'cod'])->name('finance.cod');
        Route::post('/finance/cod/reconcile', [FinanceController::class, 'codReconcile'])->name('finance.cod.reconcile');
        Route::get('/finance/expenses', [FinanceController::class, 'expenses'])->name('finance.expenses');
        Route::post('/finance/expenses', [FinanceController::class, 'storeExpense'])->name('finance.expenses.store');
        Route::get('/finance/cash-position', [FinanceController::class, 'cashPosition'])->name('finance.cash-position');
        Route::get('/finance/daily-report', [FinanceController::class, 'dailyReport'])->name('finance.daily-report');

        // Branch Settlements (P&L)
        Route::prefix('settlements')->name('settlements.')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Branch\SettlementController::class, 'dashboard'])->name('dashboard');
            Route::get('/', [\App\Http\Controllers\Branch\SettlementController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Branch\SettlementController::class, 'create'])->name('create');
            Route::get('/pl-report', [\App\Http\Controllers\Branch\SettlementController::class, 'plReport'])->name('pl-report');
            Route::get('/expense-breakdown', [\App\Http\Controllers\Branch\SettlementController::class, 'expenseBreakdown'])->name('expense-breakdown');
            Route::get('/{settlement}', [\App\Http\Controllers\Branch\SettlementController::class, 'show'])->name('show');
            Route::post('/{settlement}/submit', [\App\Http\Controllers\Branch\SettlementController::class, 'submit'])->name('submit');
            Route::post('/{settlement}/notes', [\App\Http\Controllers\Branch\SettlementController::class, 'addNotes'])->name('notes');
        });

        // Warehouse routes
        Route::get('/warehouse', [WarehouseController::class, 'index'])->name('warehouse');
        Route::get('/warehouse/index', [WarehouseController::class, 'index'])->name('warehouse.index');
        Route::get('/warehouse/picking', [WarehouseController::class, 'picking'])->name('warehouse.picking');
        Route::post('/warehouse/picking', [WarehouseController::class, 'processPicking'])->name('warehouse.picking.store');
        Route::get('/warehouse/receiving', [WarehouseController::class, 'receiving'])->name('warehouse.receiving');
        Route::post('/warehouse/receiving', [WarehouseController::class, 'processReceiving'])->name('warehouse.receiving.process');
        Route::get('/warehouse/dispatch', [WarehouseController::class, 'dispatchView'])->name('warehouse.dispatch');
        Route::post('/warehouse/dispatch', [WarehouseController::class, 'processDispatch'])->name('warehouse.dispatch.process');
        Route::get('/warehouse/zones', [WarehouseController::class, 'zones'])->name('warehouse.zones');
        Route::post('/warehouse/zones', [WarehouseController::class, 'storeZone'])->name('warehouse.zones.store');
        Route::get('/warehouse/capacity', [WarehouseController::class, 'capacity'])->name('warehouse.capacity');
        Route::get('/warehouse/cycle-count', [WarehouseController::class, 'cycleCount'])->name('warehouse.cycle-count');
        Route::post('/warehouse/cycle-count', [WarehouseController::class, 'storeCycleCount'])->name('warehouse.cycle-count.store');
        Route::post('/warehouse/locations', [WarehouseController::class, 'storeLocation'])->name('warehouse.store');
        Route::patch('/warehouse/locations/{location}', [WarehouseController::class, 'updateLocation'])->name('warehouse.update');

        Route::get('/fleet', [FleetController::class, 'index'])->name('fleet');
        Route::get('/fleet/trips', [FleetController::class, 'trips'])->name('fleet.trips');
        Route::post('/fleet/trips', [FleetController::class, 'storeTrip'])->name('fleet.trips.store');
        Route::post('/fleet/trips/{trip}/start', [FleetController::class, 'startTrip'])->name('fleet.trips.start');
        Route::post('/fleet/trips/{trip}/complete', [FleetController::class, 'completeTrip'])->name('fleet.trips.complete');
        Route::get('/fleet/maintenance', [FleetController::class, 'maintenance'])->name('fleet.maintenance');
        Route::post('/fleet/maintenance', [FleetController::class, 'storeMaintenance'])->name('fleet.maintenance.store');
        Route::post('/fleet/maintenance/{maintenance}/complete', [FleetController::class, 'completeMaintenance'])->name('fleet.maintenance.complete');
        Route::patch('/fleet/vehicles/{vehicle}', [FleetController::class, 'updateVehicle'])->name('fleet.vehicle.update');
        Route::post('/fleet/rosters', [FleetController::class, 'storeRoster'])->name('fleet.roster.store');

        // Account / User controls
        Route::prefix('account')->name('account.')->group(function () {
            Route::get('/profile', [\App\Http\Controllers\Branch\AccountController::class, 'profile'])->name('profile');
            Route::put('/profile', [\App\Http\Controllers\Branch\AccountController::class, 'updateProfile'])->name('profile.update');
            
            Route::get('/security', [\App\Http\Controllers\Branch\AccountController::class, 'security'])->name('security');
            Route::get('/security/2fa', [\App\Http\Controllers\Branch\AccountController::class, 'twoFactorAuth'])->name('security.2fa');
            Route::post('/security/password', [\App\Http\Controllers\Account\PasswordController::class, 'update'])->name('security.password');
            Route::post('/security/password/strength', [\App\Http\Controllers\Account\PasswordController::class, 'checkStrength'])->name('security.password.strength');
            Route::post('/security/2fa/generate', [\App\Http\Controllers\Branch\AccountController::class, 'generate2FA'])->name('security.2fa.generate');
            Route::post('/security/2fa/enable', [\App\Http\Controllers\Branch\AccountController::class, 'enable2FA'])->name('security.2fa.enable');
            Route::post('/security/2fa/disable', [\App\Http\Controllers\Branch\AccountController::class, 'disable2FA'])->name('security.2fa.disable');
            Route::post('/security/sessions/{sessionId}/revoke', [\App\Http\Controllers\Branch\AccountController::class, 'revokeSession'])->name('security.session.revoke');
            // New security UI routes
            Route::get('/security/audit-logs', [AuditLogController::class, 'index'])
                ->middleware('hasPermission:view_audit_logs')
                ->name('security.audit-logs');
            Route::get('/security/sessions', [SessionController::class, 'index'])->name('security.sessions');
            
            Route::get('/notifications', [\App\Http\Controllers\Branch\AccountController::class, 'notifications'])->name('notifications');
            Route::put('/notifications', [\App\Http\Controllers\Branch\AccountController::class, 'updateNotifications'])->name('notifications.update');
            
            Route::get('/devices', [\App\Http\Controllers\Branch\AccountController::class, 'devices'])->name('devices');
            
            Route::get('/preferences', [\App\Http\Controllers\Branch\AccountController::class, 'preferences'])->name('preferences');
            Route::put('/preferences', [\App\Http\Controllers\Branch\AccountController::class, 'updatePreferences'])->name('preferences.update');
            
            Route::get('/support', [\App\Http\Controllers\Branch\AccountController::class, 'support'])->name('support');
            Route::post('/support', [\App\Http\Controllers\Branch\AccountController::class, 'submitSupport'])->name('support.submit');
            
            Route::get('/billing', [\App\Http\Controllers\Branch\AccountController::class, 'billing'])->name('billing');
        });

        // Consolidation Routes (Groupage Management)
        Route::prefix('consolidations')->name('consolidations.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Branch\ConsolidationController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Branch\ConsolidationController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Branch\ConsolidationController::class, 'store'])->name('store');
            Route::get('/{consolidation}', [\App\Http\Controllers\Branch\ConsolidationController::class, 'show'])->name('show');
            
            // Shipment Management
            Route::post('/{consolidation}/shipments/add', [\App\Http\Controllers\Branch\ConsolidationController::class, 'addShipment'])->name('shipments.add');
            Route::post('/{consolidation}/shipments/remove', [\App\Http\Controllers\Branch\ConsolidationController::class, 'removeShipment'])->name('shipments.remove');
            
            // Lifecycle Management
            Route::post('/{consolidation}/lock', [\App\Http\Controllers\Branch\ConsolidationController::class, 'lock'])->name('lock');
            Route::post('/{consolidation}/dispatch', [\App\Http\Controllers\Branch\ConsolidationController::class, 'dispatch'])->name('dispatch');
            Route::post('/{consolidation}/arrived', [\App\Http\Controllers\Branch\ConsolidationController::class, 'markArrived'])->name('arrived');
            
            // Deconsolidation
            Route::post('/{consolidation}/deconsolidate/start', [\App\Http\Controllers\Branch\ConsolidationController::class, 'startDeconsolidation'])->name('deconsolidate.start');
            Route::get('/{consolidation}/deconsolidate', [\App\Http\Controllers\Branch\ConsolidationController::class, 'deconsolidate'])->name('deconsolidate');
            Route::post('/{consolidation}/scan', [\App\Http\Controllers\Branch\ConsolidationController::class, 'scanShipment'])->name('scan');
            Route::post('/{consolidation}/release', [\App\Http\Controllers\Branch\ConsolidationController::class, 'releaseShipment'])->name('release');
            
            // Automation and Rules
            Route::post('/auto-consolidate', [\App\Http\Controllers\Branch\ConsolidationController::class, 'autoConsolidate'])->name('auto-consolidate');
            Route::get('/rules', [\App\Http\Controllers\Branch\ConsolidationController::class, 'rules'])->name('rules');
        });

        // Manifest Routes (Fleet Management)
        Route::prefix('manifests')->name('manifests.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Branch\ManifestController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Branch\ManifestController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Branch\ManifestController::class, 'store'])->name('store');
            Route::get('/{manifest}', [\App\Http\Controllers\Branch\ManifestController::class, 'show'])->name('show');
            Route::post('/{manifest}/dispatch', [\App\Http\Controllers\Branch\ManifestController::class, 'dispatchManifest'])->name('dispatch');
            Route::post('/{manifest}/arrive', [\App\Http\Controllers\Branch\ManifestController::class, 'arriveManifest'])->name('arrive');
        });

        // Branch Settings (DHL-grade)
        Route::prefix('settings')->name('settings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Branch\BranchSettingsController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Branch\BranchSettingsController::class, 'update'])->name('.save');
            Route::post('/general', [\App\Http\Controllers\Branch\BranchSettingsController::class, 'updateGeneral'])->name('.general');
            Route::post('/operations', [\App\Http\Controllers\Branch\BranchSettingsController::class, 'updateOperations'])->name('.operations');
            Route::post('/notifications', [\App\Http\Controllers\Branch\BranchSettingsController::class, 'updateNotifications'])->name('.notifications');
            Route::post('/labels', [\App\Http\Controllers\Branch\BranchSettingsController::class, 'updateLabels'])->name('.labels');
            Route::post('/security', [\App\Http\Controllers\Branch\BranchSettingsController::class, 'updateSecurity'])->name('.security');
        });
    });

/*
|--------------------------------------------------------------------------
| Public Tracking Portal (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('tracking')->name('tracking.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TrackingController::class, 'index'])->name('index');
    Route::post('/track', [\App\Http\Controllers\TrackingController::class, 'track'])->name('track');
    Route::get('/{trackingNumber}', [\App\Http\Controllers\TrackingController::class, 'show'])->name('show');
    Route::get('/{trackingNumber}/data', [\App\Http\Controllers\TrackingController::class, 'getTrackingData'])->name('data');
    Route::post('/subscribe', [\App\Http\Controllers\TrackingController::class, 'subscribeNotifications'])->name('subscribe');
});

// Signed public tracking link (branch-aware)
Route::get('/track/{token}', [PublicTrackingController::class, 'publicShow'])
    ->middleware('signed')
    ->name('public.track');

/*
|--------------------------------------------------------------------------
| Landing Page Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    $user = auth()->user();
    
    // Redirect authenticated users to their respective dashboards
    if ($user) {
        if ($user->hasRole(['admin', 'super-admin', 'hq_admin', 'support'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasPermission('branch_read')) {
            return redirect()->route('branch.dashboard');
        }
    }
    
    // Get all website settings from SystemSettings (includes all defaults)
    $settings = \App\Support\SystemSettings::website();
    
    return view('landing', compact('settings'));
})->name('landing');

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
    ->where('any', '^(?!api|settings|general-settings|branch|admin|tracking|track|dashboard|login|logout|register).*')
    ->name('spa.entry');

/*
|--------------------------------------------------------------------------
| React SPA Dashboard Routes
|--------------------------------------------------------------------------
| Serve the React SPA at /admin/dashboard/* for client-side routing
*/
Route::get('/admin/dashboard/{any?}', function () {
    $spaPath = public_path('app/index.html');
    if (file_exists($spaPath)) {
        return response()->file($spaPath);
    }
    return redirect('/login');
})->where('any', '.*')->middleware(['auth'])->name('admin.dashboard.spa');

/*
|--------------------------------------------------------------------------
| Admin User Management & Impersonation Routes
|--------------------------------------------------------------------------
*/
// Redirect /admin to the SPA dashboard
Route::get('/admin', function () {
    return redirect('/admin/dashboard');
})->middleware(['auth']);

// Redirect common admin paths to SPA dashboard equivalents
Route::get('/admin/settings/{any?}', function ($any = '') {
    return redirect('/admin/dashboard/settings' . ($any ? '/' . $any : ''));
})->where('any', '.*')->middleware(['auth']);

Route::get('/admin/support/{any?}', function ($any = '') {
    return redirect('/admin/dashboard/support' . ($any ? '/' . $any : ''));
})->where('any', '.*')->middleware(['auth']);

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,super-admin,hq_admin,support'])->group(function () {
    // Admin Dashboard (Blade) - Main Admin Dashboard
    Route::get('/dashboard-blade', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports');

    // Shipment Management
    Route::prefix('shipments')->name('shipments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ShipmentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\ShipmentController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\ShipmentController::class, 'store'])->name('store');
        Route::get('/{shipment}', [\App\Http\Controllers\Admin\ShipmentController::class, 'show'])->name('show');
        Route::get('/{shipment}/edit', [\App\Http\Controllers\Admin\ShipmentController::class, 'edit'])->name('edit');
        Route::put('/{shipment}', [\App\Http\Controllers\Admin\ShipmentController::class, 'update'])->name('update');
        Route::post('/bulk-update-status', [\App\Http\Controllers\Admin\ShipmentController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
        Route::get('/exceptions', [\App\Http\Controllers\Admin\ShipmentController::class, 'exceptions'])->name('exceptions');
    });

    // Shipments POS (Frontdesk System)
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'index'])->name('index');
        Route::get('/search-customer', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'searchCustomer'])->name('search-customer');
        Route::post('/quick-create-customer', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'quickCreateCustomer'])->name('quick-create-customer');
        Route::post('/calculate-rate', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'calculateRate'])->name('calculate-rate');
        Route::post('/create-shipment', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'createShipment'])->name('create-shipment');
        Route::get('/quick-track', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'quickTrack'])->name('quick-track');
        Route::get('/service-levels', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'getServiceLevels'])->name('service-levels');
        Route::get('/{shipment}/label', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'printLabel'])->name('label');
        Route::get('/{shipment}/receipt', [\App\Http\Controllers\Shared\ShipmentPosController::class, 'printReceipt'])->name('receipt');
    });

    // Real-Time Tracking
    Route::prefix('tracking')->name('tracking.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\ShipmentTrackingController::class, 'dashboard'])->name('dashboard');
        Route::get('/{shipment}', [\App\Http\Controllers\Admin\ShipmentTrackingController::class, 'show'])->name('show');
        Route::get('/{shipment}/data', [\App\Http\Controllers\Admin\ShipmentTrackingController::class, 'getTrackingData'])->name('data');
        Route::post('/multiple', [\App\Http\Controllers\Admin\ShipmentTrackingController::class, 'getMultipleTracking'])->name('multiple');
        Route::post('/{shipment}/refresh', [\App\Http\Controllers\Admin\ShipmentTrackingController::class, 'refresh'])->name('refresh');
    });

    // Dispatch & Route Optimization
    Route::prefix('dispatch')->name('dispatch.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DispatchController::class, 'index'])->name('index');
        
        // Route Optimization
        Route::post('/optimize-route', [\App\Http\Controllers\Admin\DispatchController::class, 'optimizeRoute'])->name('optimize-route');
        Route::post('/dynamic-reroute', [\App\Http\Controllers\Admin\DispatchController::class, 'dynamicReroute'])->name('dynamic-reroute');
        
        // Shipment Assignment
        Route::post('/auto-assign', [\App\Http\Controllers\Admin\DispatchController::class, 'autoAssign'])->name('auto-assign');
        Route::post('/bulk-auto-assign', [\App\Http\Controllers\Admin\DispatchController::class, 'bulkAutoAssign'])->name('bulk-auto-assign');
        Route::post('/manual-assign', [\App\Http\Controllers\Admin\DispatchController::class, 'manualAssign'])->name('manual-assign');
        Route::get('/suggestions', [\App\Http\Controllers\Admin\DispatchController::class, 'getSuggestions'])->name('suggestions');
        
        // Workload Management
        Route::get('/workload-distribution', [\App\Http\Controllers\Admin\DispatchController::class, 'getWorkloadDistribution'])->name('workload-distribution');
        Route::post('/rebalance-workload', [\App\Http\Controllers\Admin\DispatchController::class, 'rebalanceWorkload'])->name('rebalance-workload');
        
        // Hub Routing
        Route::get('/hub-routes', [\App\Http\Controllers\Admin\DispatchController::class, 'hubRoutes'])->name('hub-routes');
        Route::post('/hub-routes', [\App\Http\Controllers\Admin\DispatchController::class, 'storeHubRoute'])->name('hub-routes.store');
        Route::put('/hub-routes/{id}', [\App\Http\Controllers\Admin\DispatchController::class, 'updateHubRoute'])->name('hub-routes.update');
        Route::delete('/hub-routes/{id}', [\App\Http\Controllers\Admin\DispatchController::class, 'deleteHubRoute'])->name('hub-routes.delete');
        Route::get('/hub/{hub}/routes', [\App\Http\Controllers\Admin\DispatchController::class, 'getHubRoutes'])->name('hub.routes');
        Route::get('/hub/{hub}/capacity', [\App\Http\Controllers\Admin\DispatchController::class, 'getHubCapacity'])->name('hub.capacity');
        Route::post('/find-hub-route', [\App\Http\Controllers\Admin\DispatchController::class, 'findHubRoute'])->name('find-hub-route');
        Route::post('/find-alternative-hub', [\App\Http\Controllers\Admin\DispatchController::class, 'findAlternativeHub'])->name('find-alternative-hub');
        Route::post('/rebalance-hubs', [\App\Http\Controllers\Admin\DispatchController::class, 'rebalanceHubs'])->name('rebalance-hubs');
    });

    // Analytics & Reporting
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/data', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getDashboardData'])->name('data');
        Route::get('/branch-comparison', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getBranchComparison'])->name('branch-comparison');
        Route::get('/driver-performance', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getDriverPerformance'])->name('driver-performance');
        Route::get('/customer-analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getCustomerAnalytics'])->name('customer-analytics');
        
        // Predictive Analytics
        Route::get('/predict/{shipment}', [\App\Http\Controllers\Admin\AnalyticsController::class, 'predictDelivery'])->name('predict-delivery');
        Route::get('/prediction-accuracy', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getPredictionAccuracy'])->name('prediction-accuracy');
        
        // Reports
        Route::get('/reports', [\App\Http\Controllers\Admin\AnalyticsController::class, 'reports'])->name('reports');
        Route::get('/reports/shipment', [\App\Http\Controllers\Admin\AnalyticsController::class, 'generateShipmentReport'])->name('reports.shipment');
        Route::get('/reports/financial', [\App\Http\Controllers\Admin\AnalyticsController::class, 'generateFinancialReport'])->name('reports.financial');
        Route::get('/reports/performance', [\App\Http\Controllers\Admin\AnalyticsController::class, 'generatePerformanceReport'])->name('reports.performance');
        
        // Export
        Route::get('/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'exportReport'])->name('export');
        Route::get('/download', [\App\Http\Controllers\Admin\AnalyticsController::class, 'downloadReport'])->name('download');
    });

    // Branch Management
    Route::resource('branches', \App\Http\Controllers\Admin\BranchController::class);

    // Hub Management
    Route::resource('hubs', \App\Http\Controllers\Admin\HubController::class);

    // Merchant Management
    Route::prefix('merchants')->name('merchants.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MerchantController::class, 'index'])->name('index');
        Route::get('/{merchant}', [\App\Http\Controllers\Admin\MerchantController::class, 'show'])->name('show');
        Route::get('/{merchant}/statements', [\App\Http\Controllers\Admin\MerchantController::class, 'statements'])->name('statements');
    });

    // Client Management (Centralized Customer System)
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ClientController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\ClientController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\ClientController::class, 'store'])->name('store');
        Route::get('/search', [\App\Http\Controllers\Admin\ClientController::class, 'search'])->name('search');
        Route::get('/export', [\App\Http\Controllers\Admin\ClientController::class, 'export'])->name('export');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\ClientController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{client}', [\App\Http\Controllers\Admin\ClientController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [\App\Http\Controllers\Admin\ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [\App\Http\Controllers\Admin\ClientController::class, 'update'])->name('update');
        Route::delete('/{client}', [\App\Http\Controllers\Admin\ClientController::class, 'destroy'])->name('destroy');
        Route::patch('/{client}/reassign', [\App\Http\Controllers\Admin\ClientController::class, 'reassign'])->name('reassign');
        Route::get('/{client}/statement', [\App\Http\Controllers\Admin\ClientController::class, 'statementPreview'])->name('statement');
        Route::get('/{client}/statement/download', [\App\Http\Controllers\Admin\ClientController::class, 'statement'])->name('statement.download');
        Route::get('/{client}/contracts', [\App\Http\Controllers\Admin\ClientController::class, 'contracts'])->name('contracts');
        Route::post('/{client}/refresh-stats', [\App\Http\Controllers\Admin\ClientController::class, 'refreshStats'])->name('refresh-stats');
        Route::get('/{client}/quick-shipment', [\App\Http\Controllers\Admin\ClientController::class, 'quickShipment'])->name('quick-shipment');
        Route::post('/{client}/activity', [\App\Http\Controllers\Admin\ClientController::class, 'storeActivity'])->name('activity.store');
        Route::post('/{client}/reminder', [\App\Http\Controllers\Admin\ClientController::class, 'storeReminder'])->name('reminder.store');
        Route::post('/{client}/reminder/{reminder}/complete', [\App\Http\Controllers\Admin\ClientController::class, 'completeReminder'])->name('reminder.complete');
        Route::post('/{client}/adjust-credit', [\App\Http\Controllers\Admin\ClientController::class, 'adjustCredit'])->name('adjust-credit');
    });

    // Delivery Personnel
    Route::prefix('delivery-personnel')->name('delivery-personnel.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DeliveryPersonnelController::class, 'index'])->name('index');
        Route::get('/{personnel}', [\App\Http\Controllers\Admin\DeliveryPersonnelController::class, 'show'])->name('show');
    });

    // Finance Module
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/accounts', [\App\Http\Controllers\Admin\FinanceController::class, 'accounts'])->name('accounts');
        Route::get('/transactions', [\App\Http\Controllers\Admin\FinanceController::class, 'transactions'])->name('transactions');
        Route::get('/statements', [\App\Http\Controllers\Admin\FinanceController::class, 'statements'])->name('statements');
        
        // Enhanced Finance - COD Management
        Route::get('/dashboard', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/data', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'getDashboardData'])->name('dashboard.data');
        
        Route::get('/cod', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'codCollections'])->name('cod.index');
        Route::post('/cod/record', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'recordCollection'])->name('cod.record');
        Route::post('/cod/{collection}/verify', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'verifyCollection'])->name('cod.verify');
        Route::get('/cod/needs-verification', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'collectionsNeedingVerification'])->name('cod.needs-verification');
        Route::get('/cod/discrepancies', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'codDiscrepancies'])->name('cod.discrepancies');
        Route::post('/cod/remittance', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'recordRemittance'])->name('cod.remittance');
        Route::get('/cod/driver/{driver}', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'driverPendingCollections'])->name('cod.driver');
        Route::get('/cod/driver-accounts', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'driverCashAccounts'])->name('cod.driver-accounts');
        
        // Merchant Settlements
        Route::get('/settlements', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'settlements'])->name('settlements.index');
        Route::post('/settlements/generate', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'generateSettlement'])->name('settlements.generate');
        Route::post('/settlements/{settlement}/submit', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'submitSettlement'])->name('settlements.submit');
        Route::post('/settlements/{settlement}/approve', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'approveSettlement'])->name('settlements.approve');
        Route::post('/settlements/{settlement}/pay', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'paySettlement'])->name('settlements.pay');
        Route::get('/settlements/{settlement}/statement', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'settlementStatement'])->name('settlements.statement');
        Route::get('/settlements/pending', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'pendingSettlements'])->name('settlements.pending');
        Route::get('/merchant/{customer}/balance', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'merchantBalance'])->name('merchant.balance');
        
        // Currency Management
        Route::get('/exchange-rates', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'exchangeRates'])->name('exchange-rates');
        Route::post('/exchange-rates', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'setExchangeRate'])->name('exchange-rates.set');
        Route::post('/convert', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'convertCurrency'])->name('convert');
        Route::post('/exchange-rates/update', [\App\Http\Controllers\Admin\EnhancedFinanceController::class, 'updateRates'])->name('exchange-rates.update');

        // Branch Settlements (HQ View)
        Route::get('/consolidated', [\App\Http\Controllers\Admin\BranchSettlementController::class, 'dashboard'])->name('consolidated');
        Route::get('/branch-settlements', [\App\Http\Controllers\Admin\BranchSettlementController::class, 'index'])->name('branch-settlements');
        Route::get('/branch-settlements/{settlement}', [\App\Http\Controllers\Admin\BranchSettlementController::class, 'show'])->name('branch-settlements.show');
        Route::post('/branch-settlements/{settlement}/approve', [\App\Http\Controllers\Admin\BranchSettlementController::class, 'approve'])->name('branch-settlements.approve');
        Route::post('/branch-settlements/{settlement}/reject', [\App\Http\Controllers\Admin\BranchSettlementController::class, 'reject'])->name('branch-settlements.reject');
        Route::post('/branch-settlements/{settlement}/settle', [\App\Http\Controllers\Admin\BranchSettlementController::class, 'settle'])->name('branch-settlements.settle');
    });

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('store');
        Route::get('/branch-managers', [\App\Http\Controllers\Admin\UserManagementController::class, 'branchManagers'])->name('branch-managers');
        Route::get('/impersonation-logs', [\App\Http\Controllers\Admin\UserManagementController::class, 'impersonationLogs'])->name('impersonation-logs');
        Route::get('/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [\App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('update');
        Route::post('/{user}/toggle-status', [\App\Http\Controllers\Admin\UserManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{user}/reset-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'resetPassword'])->name('reset-password');
        Route::delete('/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('destroy');
    });
    
    Route::post('/users/{user}/impersonate', [\App\Http\Controllers\Admin\ImpersonationController::class, 'start'])->name('impersonation.start');
    Route::post('/impersonation/stop', [\App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])->name('impersonation.stop');
    
    // Security & Compliance
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SecurityController::class, 'dashboard'])->name('dashboard');
        Route::get('/overview', [\App\Http\Controllers\Admin\SecurityController::class, 'getOverview'])->name('overview');
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\SecurityController::class, 'getAuditLogs'])->name('audit-logs');
        Route::get('/sessions', [\App\Http\Controllers\Admin\SecurityController::class, 'getActiveSessions'])->name('sessions');
        Route::delete('/sessions/{session}', [\App\Http\Controllers\Admin\SecurityController::class, 'terminateSession'])->name('sessions.terminate');
        Route::post('/users/{user}/unlock', [\App\Http\Controllers\Admin\SecurityController::class, 'unlockAccount'])->name('users.unlock');
        
        // MFA Management
        Route::get('/mfa', [\App\Http\Controllers\Admin\MfaController::class, 'index'])->name('mfa');
        Route::post('/mfa/totp/generate', [\App\Http\Controllers\Admin\MfaController::class, 'generateTotp'])->name('mfa.totp.generate');
        Route::post('/mfa/totp/enable', [\App\Http\Controllers\Admin\MfaController::class, 'enableTotp'])->name('mfa.totp.enable');
        Route::post('/mfa/sms/setup', [\App\Http\Controllers\Admin\MfaController::class, 'setupSms'])->name('mfa.sms.setup');
        Route::post('/mfa/sms/enable', [\App\Http\Controllers\Admin\MfaController::class, 'enableSms'])->name('mfa.sms.enable');
        Route::post('/mfa/email/setup', [\App\Http\Controllers\Admin\MfaController::class, 'setupEmail'])->name('mfa.email.setup');
        Route::post('/mfa/email/enable', [\App\Http\Controllers\Admin\MfaController::class, 'enableEmail'])->name('mfa.email.enable');
        Route::delete('/mfa/devices/{device}', [\App\Http\Controllers\Admin\MfaController::class, 'removeDevice'])->name('mfa.devices.remove');
        Route::post('/mfa/devices/{device}/primary', [\App\Http\Controllers\Admin\MfaController::class, 'setPrimary'])->name('mfa.devices.primary');
        Route::post('/mfa/backup-codes', [\App\Http\Controllers\Admin\MfaController::class, 'regenerateBackupCodes'])->name('mfa.backup-codes');
        Route::post('/mfa/verify', [\App\Http\Controllers\Admin\MfaController::class, 'verify'])->name('mfa.verify');
        
        // MFA Policy Settings (Admin Toggle)
        Route::get('/mfa-settings', [\App\Http\Controllers\Admin\MfaController::class, 'policySettings'])->name('mfa-settings');
        Route::post('/mfa-settings', [\App\Http\Controllers\Admin\MfaController::class, 'updatePolicySettings'])->name('mfa-settings.update');
        
        // GDPR Compliance
        Route::get('/gdpr/user/{user}/export', [\App\Http\Controllers\Admin\SecurityController::class, 'exportUserData'])->name('gdpr.user.export');
        Route::get('/gdpr/customer/{customer}/export', [\App\Http\Controllers\Admin\SecurityController::class, 'exportCustomerData'])->name('gdpr.customer.export');
        Route::delete('/gdpr/user/{user}', [\App\Http\Controllers\Admin\SecurityController::class, 'deleteUserData'])->name('gdpr.user.delete');
        Route::get('/gdpr/retention', [\App\Http\Controllers\Admin\SecurityController::class, 'getDataRetentionReport'])->name('gdpr.retention');
        Route::post('/gdpr/purge', [\App\Http\Controllers\Admin\SecurityController::class, 'purgeExpiredData'])->name('gdpr.purge');
    });

    // Customs Clearance
    Route::prefix('customs')->name('customs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CustomsController::class, 'index'])->name('index');
        Route::get('/{shipment}', [\App\Http\Controllers\Admin\CustomsController::class, 'show'])->name('show');
        Route::post('/{shipment}/hold', [\App\Http\Controllers\Admin\CustomsController::class, 'hold'])->name('hold');
        Route::post('/{shipment}/request-documents', [\App\Http\Controllers\Admin\CustomsController::class, 'requestDocuments'])->name('request-documents');
        Route::post('/{shipment}/assess-duty', [\App\Http\Controllers\Admin\CustomsController::class, 'assessDuty'])->name('assess-duty');
        Route::post('/{shipment}/record-payment', [\App\Http\Controllers\Admin\CustomsController::class, 'recordPayment'])->name('record-payment');
        Route::post('/{shipment}/inspect', [\App\Http\Controllers\Admin\CustomsController::class, 'recordInspection'])->name('inspect');
        Route::post('/{shipment}/clear', [\App\Http\Controllers\Admin\CustomsController::class, 'clear'])->name('clear');
    });
});
