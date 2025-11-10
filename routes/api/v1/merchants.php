<?php

use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\MerchantShopController;
use App\Http\Controllers\Api\V1\MerchantShipmentController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // Merchant CRUD operations
    Route::middleware(['throttle:60,1'])->group(function () {
        
        // Read permissions
        Route::middleware('permission:merchant_read')->group(function () {
            Route::get('merchants', [MerchantController::class, 'index'])
                ->name('api.merchants.index');

            Route::get('merchants/{merchant}', [MerchantController::class, 'show'])
                ->name('api.merchants.show');

            Route::get('merchants/search', [MerchantController::class, 'search'])
                ->name('api.merchants.search');

            Route::get('merchants/active', [MerchantController::class, 'activeMerchants'])
                ->name('api.merchants.active');

            Route::get('merchants/inactive', [MerchantController::class, 'inactiveMerchants'])
                ->name('api.merchants.inactive');

            Route::get('merchants/{merchant}/shops', [MerchantController::class, 'shops'])
                ->name('api.merchants.shops');

            Route::get('merchants/{merchant}/shipments', [MerchantController::class, 'shipments'])
                ->name('api.merchants.shipments');

            Route::get('merchants/{merchant}/payments', [MerchantController::class, 'payments'])
                ->name('api.merchants.payments');

            Route::get('merchants/{merchant}/balances', [MerchantController::class, 'balances'])
                ->name('api.merchants.balances');

            Route::get('merchants/{merchant}/stats', [MerchantController::class, 'stats'])
                ->name('api.merchants.stats');

            Route::get('merchants/{merchant}/overview', [MerchantController::class, 'overview'])
                ->name('api.merchants.overview');

            // Business metrics
            Route::get('merchants/{merchant}/dashboard', [MerchantController::class, 'dashboard'])
                ->name('api.merchants.dashboard');

            Route::get('merchants/{merchant}/performance', [MerchantController::class, 'performance'])
                ->name('api.merchants.performance');

            Route::get('merchants/reports/summary', [MerchantController::class, 'reportSummary'])
                ->name('api.merchants.report-summary');
        });

        // Create permissions
        Route::middleware('permission:merchant_create')->group(function () {
            Route::post('merchants', [MerchantController::class, 'store'])
                ->name('api.merchants.store');

            Route::post('mercents/bulk-import', [MerchantController::class, 'bulkImport'])
                ->name('api.merchants.bulk-import');
        });

        // Update permissions
        Route::middleware('permission:merchant_update')->group(function () {
            Route::put('merchants/{merchant}', [MerchantController::class, 'update'])
                ->name('api.merchants.update');

            Route::patch('merchants/{merchant}/status', [MerchantController::class, 'updateStatus'])
                ->name('api.merchants.update-status');

            Route::patch('merchants/{merchant}/contact', [MerchantController::class, 'updateContact'])
                ->name('api.merchants.update-contact');

            Route::patch('merchants/{merchant}/billing', [MerchantController::class, 'updateBilling'])
                ->name('api.merchants.update-billing');

            Route::patch('merchants/{merchant}/preferences', [MerchantController::class, 'updatePreferences'])
                ->name('api.merchants.update-preferences');

            // Shop assignments
            Route::post('merchants/{merchant}/shops/{shop}', [MerchantController::class, 'assignShop'])
                ->name('api.merchants.assign-shop');

            Route::delete('merchants/{merchant}/shops/{shop}', [MerchantController::class, 'unassignShop'])
                ->name('api.merchants.unassign-shop');
        });

        // Delete permissions
        Route::middleware('permission:merchant_delete')->group(function () {
            Route::delete('merchants/{merchant}', [MerchantController::class, 'destroy'])
                ->name('api.merchants.destroy');

            Route::post('merchants/{merchant}/archive', [MerchantController::class, 'archive'])
                ->name('api.merchants.archive');

            Route::post('merchants/{merchant}/restore', [MerchantController::class, 'restore'])
                ->name('api.merchants.restore');
        });

        // Analytics permissions
        Route::middleware('permission:merchant_analytics')->group(function () {
            Route::get('merchants/analytics/performance', [MerchantController::class, 'analytics'])
                ->name('api.merchants.analytics');

            Route::get('merchants/analytics/revenue', [MerchantController::class, 'revenueAnalytics'])
                ->name('api.merchants.revenue-analytics');

            Route::get('merchants/analytics/retention', [MerchantController::class, 'retentionAnalytics'])
                ->name('api.merchants.retention-analytics');
        });
    });

    // Merchant Shop Management
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::prefix('merchants/{merchant}/shops')->group(function () {
            Route::get('/', [MerchantShopController::class, 'index'])
                ->name('api.merchant-shops.index');

            Route::post('/', [MerchantShopController::class, 'store'])
                ->name('api.merchant-shops.store');

            Route::get('{shop}', [MerchantShopController::class, 'show'])
                ->name('api.merchant-shops.show');

            Route::put('{shop}', [MerchantShopController::class, 'update'])
                ->name('api.merchant-shops.update');

            Route::delete('{shop}', [MerchantShopController::class, 'destroy'])
                ->name('api.merchant-shops.destroy');

            Route::get('{shop}/shipments', [MerchantShopController::class, 'shipments'])
                ->name('api.merchant-shops.shipments');

            Route::post('{shop}/upload-logo', [MerchantShopController::class, 'uploadLogo'])
                ->name('api.merchant-shops.upload-logo');
        });
    });

    // Merchant Shipment Operations
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::prefix('merchants/{merchant}/shipments')->group(function () {
            Route::get('/', [MerchantShipmentController::class, 'index'])
                ->name('api.merchant-shipments.index');

            Route::post('/', [MerchantShipmentController::class, 'store'])
                ->name('api.merchant-shipments.store');

            Route::get('{shipment}', [MerchantShipmentController::class, 'show'])
                ->name('api.merchant-shipments.show');

            Route::put('{shipment}', [MerchantShipmentController::class, 'update'])
                ->name('api.merchant-shipments.update');

            Route::post('{shipment}/pickup-request', [MerchantShipmentController::class, 'pickupRequest'])
                ->name('api.merchant-shipments.pickup-request');

            Route::get('{shipment}/tracking', [MerchantShipmentController::class, 'tracking'])
                ->name('api.merchant-shipments.tracking');
        });
    });
});

// Public merchant information (limited)
Route::middleware(['api.prefix', 'throttle:100,1'])->group(function () {
    Route::get('merchants/public', [MerchantController::class, 'publicList'])
        ->name('api.merchants.public');

    Route::get('merchants/{merchant}/public-info', [MerchantController::class, 'publicInfo'])
        ->name('api.merchants.public-info');

    Route::get('merchants/{merchant}/public-shops', [MerchantController::class, 'publicShops'])
        ->name('api.merchants.public-shops');
});

// Merchant self-service portal
Route::middleware(['auth:sanctum', 'permission:merchant_portal', 'throttle:30,1'])->group(function () {
    Route::prefix('my-merchant')->group(function () {
        // Merchant can manage their own profile
        Route::get('/profile', [MerchantController::class, 'myProfile'])
            ->name('api.my-merchant.profile');

        Route::put('/profile', [MerchantController::class, 'updateMyProfile'])
            ->name('api.my-merchant.update-profile');

        Route::get('/dashboard', [MerchantController::class, 'myDashboard'])
            ->name('api.my-merchant.dashboard');

        // Merchant can manage their shops
        Route::get('/shops', [MerchantShopController::class, 'myShops'])
            ->name('api.my-merchant.shops');

        Route::post('/shops', [MerchantShopController::class, 'createMyShop'])
            ->name('api.my-merchant.create-shop');

        Route::put('/shops/{shop}', [MerchantShopController::class, 'updateMyShop'])
            ->name('api.my-merchant.update-shop');

        // Merchant can manage their shipments
        Route::get('/shipments', [MerchantShipmentController::class, 'myShipments'])
            ->name('api.my-merchant.shipments');

        Route::post('/shipments', [MerchantShipmentController::class, 'createMyShipment'])
            ->name('api.my-merchant.create-shipment');

        // Merchant can view their financial data
        Route::get('/balances', [MerchantController::class, 'myBalances'])
            ->name('api.my-merchant.balances');

        Route::get('/transactions', [MerchantController::class, 'myTransactions'])
            ->name('api.my-merchant.transactions');

        Route::get('/invoices', [MerchantController::class, 'myInvoices'])
            ->name('api.my-merchant.invoices');

        // Merchant notifications
        Route::get('/notifications', [MerchantController::class, 'myNotifications'])
            ->name('api.my-merchant.notifications');
    });
});

// Webhook endpoints for merchant events
Route::middleware(['api.prefix'])->group(function () {
    Route::post('webhooks/merchants/registered', [MerchantController::class, 'merchantRegisteredWebhook'])
        ->name('api.webhooks.merchants.registered');

    Route::post('webhooks/merchants/status-changed', [MerchantController::class, 'merchantStatusChangedWebhook'])
        ->name('api.webhooks.merchants.status-changed');

    Route::post('webhooks/merchants/shipment-created', [MerchantController::class, 'merchantShipmentCreatedWebhook'])
        ->name('api.webhooks.merchants.shipment-created');
});
