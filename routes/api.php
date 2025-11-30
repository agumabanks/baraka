<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminNavigationController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\Sales\CustomerController as SalesCustomerController;
use App\Http\Controllers\Api\Sales\QuotationController as SalesQuotationController;
use App\Http\Controllers\Api\Sales\ContractController as SalesContractController;
use App\Http\Controllers\Api\Sales\AddressBookController as SalesAddressBookController;
use App\Http\Controllers\Api\Admin\BranchManagerApiController;
use App\Http\Controllers\Api\Admin\BranchWorkerApiController;
use App\Http\Controllers\Api\Admin\UserApiController;
use App\Http\Controllers\Api\Admin\RoleApiController;
use App\Http\Controllers\Api\V1\UnifiedPricingController;
use App\Http\Controllers\Api\V1\SystemHealthController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\WebhookManagementController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\EdiController;
use App\Http\Controllers\Api\V1\BranchOperationsController;
use App\Http\Controllers\Api\V1\EnhancedMobileScanningController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V10\ShipmentsApiController;
use App\Http\Controllers\Api\V10\BranchNetworkController;
use App\Http\Controllers\Api\V10\BranchManagementController;
use App\Http\Controllers\Api\V10\DriverController;
use App\Http\Controllers\Api\V10\DriverRosterController;
use App\Http\Controllers\Api\V10\DriverTimeLogController;
use App\Http\Controllers\Api\V10\MerchantManagementController;
use App\Http\Controllers\Api\V10\WorkflowBoardController;
use App\Http\Controllers\Api\V10\WorkflowTaskController;
use App\Http\Controllers\Api\V10\SearchController as V10SearchController;
use App\Http\Controllers\Api\V10\InvoiceController as V10InvoiceController;
use App\Http\Controllers\Api\V10\PaymentAccountController as V10PaymentAccountController;
use App\Http\Controllers\Api\V10\PaymentRequestController as V10PaymentRequestController;
use App\Http\Controllers\Api\V10\StatementsController as V10StatementsController;
use App\Http\Controllers\Api\V10\SettingsController as V10SettingsController;
use App\Http\Controllers\Api\V10\GeneralSettingCotroller;
use App\Http\Controllers\Api\V10\SupportController as V10SupportController;
use App\Http\Controllers\Api\V10\ReportController as V10ReportController;
use App\Http\Controllers\Api\V10\Analytics\OperationalAnalyticsController;
use App\Http\Controllers\Api\V10\Analytics\StreamingAnalyticsController;
use App\Http\Controllers\Api\V10\Analytics\HealthAlertController;
use App\Http\Controllers\Api\V10\OptimizedAnalyticsController;
use App\Http\Controllers\Api\V10\ParcelController as V10ParcelController;
use App\Http\Controllers\Backend\OperationsControlCenterController;
use App\Http\Controllers\Backend\UnifiedShipmentController;
use App\Http\Controllers\Admin\BookingWizardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ================================
// PUBLIC ROUTES (Rate Limited)
// ================================
Route::prefix('v1')->group(function () {
    
    // System Health & Status (Light rate limiting)
    Route::get('/health', [SystemHealthController::class, 'healthCheck'])
        ->middleware(['api.simple']);
    
    Route::get('/version', [SystemHealthController::class, 'version'])
        ->middleware(['api.simple']);
    
    Route::get('/business-rules', [SystemHealthController::class, 'businessRules'])
        ->middleware(['api.simple']);
    
    // Public Website Settings (for landing page)
    Route::get('/website-settings', function () {
        $settings = [
            'site_title' => \App\Support\SystemSettings::get('website.site_title', config('app.name')),
            'site_tagline' => \App\Support\SystemSettings::get('website.site_tagline', ''),
            'site_description' => \App\Support\SystemSettings::get('website.site_description', ''),
            'hero' => [
                'title' => \App\Support\SystemSettings::get('website.hero_title', 'Fast & Reliable Logistics'),
                'subtitle' => \App\Support\SystemSettings::get('website.hero_subtitle', ''),
                'background' => \App\Support\SystemSettings::get('website.hero_background', ''),
                'cta_primary' => [
                    'text' => \App\Support\SystemSettings::get('website.hero_cta_primary_text', 'Get a Quote'),
                    'url' => \App\Support\SystemSettings::get('website.hero_cta_primary_url', '/quote'),
                ],
                'cta_secondary' => [
                    'text' => \App\Support\SystemSettings::get('website.hero_cta_secondary_text', 'Track Shipment'),
                    'url' => \App\Support\SystemSettings::get('website.hero_cta_secondary_url', '/tracking'),
                ],
                'show_tracking_widget' => \App\Support\SystemSettings::get('website.hero_show_tracking_widget', true),
            ],
            'features' => [
                'enabled' => \App\Support\SystemSettings::get('website.features_enabled', true),
                'title' => \App\Support\SystemSettings::get('website.features_title', 'Why Choose Us'),
                'subtitle' => \App\Support\SystemSettings::get('website.features_subtitle', ''),
                'items' => \App\Support\SystemSettings::get('website.features', []),
            ],
            'services' => [
                'enabled' => \App\Support\SystemSettings::get('website.services_enabled', true),
                'title' => \App\Support\SystemSettings::get('website.services_title', 'Our Services'),
                'subtitle' => \App\Support\SystemSettings::get('website.services_subtitle', ''),
                'items' => \App\Support\SystemSettings::get('website.services', []),
            ],
            'stats' => [
                'enabled' => \App\Support\SystemSettings::get('website.stats_enabled', true),
                'items' => \App\Support\SystemSettings::get('website.stats', []),
            ],
            'about' => [
                'enabled' => \App\Support\SystemSettings::get('website.about_enabled', true),
                'title' => \App\Support\SystemSettings::get('website.about_title', ''),
                'content' => \App\Support\SystemSettings::get('website.about_content', ''),
                'image' => \App\Support\SystemSettings::get('website.about_image', ''),
            ],
            'testimonials' => [
                'enabled' => \App\Support\SystemSettings::get('website.testimonials_enabled', true),
                'title' => \App\Support\SystemSettings::get('website.testimonials_title', ''),
                'items' => \App\Support\SystemSettings::get('website.testimonials', []),
            ],
            'contact' => [
                'enabled' => \App\Support\SystemSettings::get('website.contact_enabled', true),
                'title' => \App\Support\SystemSettings::get('website.contact_title', ''),
                'subtitle' => \App\Support\SystemSettings::get('website.contact_subtitle', ''),
                'email' => \App\Support\SystemSettings::get('website.contact_email', ''),
                'phone' => \App\Support\SystemSettings::get('website.contact_phone', ''),
                'whatsapp' => \App\Support\SystemSettings::get('website.contact_whatsapp', ''),
                'address' => \App\Support\SystemSettings::get('website.contact_address', ''),
                'hours' => \App\Support\SystemSettings::get('website.contact_hours', ''),
                'map_embed' => \App\Support\SystemSettings::get('website.contact_map_embed', ''),
            ],
            'social' => [
                'facebook' => \App\Support\SystemSettings::get('website.social_facebook', ''),
                'twitter' => \App\Support\SystemSettings::get('website.social_twitter', ''),
                'instagram' => \App\Support\SystemSettings::get('website.social_instagram', ''),
                'linkedin' => \App\Support\SystemSettings::get('website.social_linkedin', ''),
                'youtube' => \App\Support\SystemSettings::get('website.social_youtube', ''),
                'tiktok' => \App\Support\SystemSettings::get('website.social_tiktok', ''),
            ],
            'footer' => [
                'about' => \App\Support\SystemSettings::get('website.footer_about', ''),
                'copyright' => \App\Support\SystemSettings::get('website.footer_copyright', ''),
                'links' => \App\Support\SystemSettings::get('website.footer_links', []),
            ],
            'analytics' => [
                'google_analytics_id' => \App\Support\SystemSettings::get('website.google_analytics_id', ''),
                'google_tag_manager_id' => \App\Support\SystemSettings::get('website.google_tag_manager_id', ''),
                'facebook_pixel_id' => \App\Support\SystemSettings::get('website.facebook_pixel_id', ''),
            ],
            'custom_css' => \App\Support\SystemSettings::get('website.custom_css', ''),
            'custom_js_head' => \App\Support\SystemSettings::get('website.custom_js_head', ''),
            'custom_js_body' => \App\Support\SystemSettings::get('website.custom_js_body', ''),
            'maintenance_mode' => \App\Support\SystemSettings::get('website.maintenance_mode', false),
            'maintenance_message' => \App\Support\SystemSettings::get('website.maintenance_message', ''),
        ];
        
        return response()->json($settings);
    })->middleware(['api.simple']);
    
    // Basic Rate Limiting for these routes
    Route::middleware(['advanced-rate-limit:quotes', 'api-security-validation'])->group(function () {
        
        // ================================
        // QUOTE GENERATION ENDPOINTS
        // ================================
        Route::post('/pricing/quote', [UnifiedPricingController::class, 'generateInstantQuote']);
        Route::post('/pricing/quote/bulk', [UnifiedPricingController::class, 'generateBulkQuotes'])
            ->middleware(['advanced-rate-limit:bulk_quotes']);
        Route::get('/pricing/quote/{id}', [UnifiedPricingController::class, 'getQuoteById']);
        Route::post('/pricing/calculate', [UnifiedPricingController::class, 'calculatePricing']);
        
    });
    
    // ================================
    // CONTRACT MANAGEMENT ENDPOINTS
    // ================================
    Route::middleware(['advanced-rate-limit:contracts', 'api-security-validation'])->group(function () {
        Route::get('/contracts', [UnifiedPricingController::class, 'getContracts']);
        Route::post('/contracts', [UnifiedPricingController::class, 'createContract']);
        Route::get('/contracts/{id}', [UnifiedPricingController::class, 'getContract']);
        Route::put('/contracts/{id}', [UnifiedPricingController::class, 'updateContract']);
        Route::delete('/contracts/{id}', [UnifiedPricingController::class, 'deleteContract']);
        Route::post('/contracts/{id}/activate', [UnifiedPricingController::class, 'activateContract']);
        Route::post('/contracts/{id}/renew', [UnifiedPricingController::class, 'renewContract']);
    });
    
    // ================================
    // PROMOTION MANAGEMENT ENDPOINTS
    // ================================
    Route::middleware(['advanced-rate-limit:promotions', 'api-security-validation'])->group(function () {
        Route::get('/promotions/validate', [UnifiedPricingController::class, 'validatePromoCode']);
        Route::post('/promotions/apply', [UnifiedPricingController::class, 'applyPromotion']);
        Route::get('/promotions/analytics', [UnifiedPricingController::class, 'getPromotionAnalytics']);
        Route::get('/promotions/milestones', [UnifiedPricingController::class, 'getMilestoneProgress']);
        Route::post('/promotions/milestones/track', [UnifiedPricingController::class, 'trackMilestoneProgress']);
    });
    
    // ================================
    // ANALYTICS ENDPOINTS
    // ================================
    Route::middleware(['advanced-rate-limit:analytics', 'api-security-validation'])->group(function () {
        Route::get('/analytics/roi', [AnalyticsController::class, 'getPromotionROI']);
        Route::get('/analytics/effectiveness', [AnalyticsController::class, 'getEffectivenessMetrics']);
        Route::get('/analytics/customer-insights', [AnalyticsController::class, 'getCustomerInsights']);
        Route::get('/analytics/performance', [AnalyticsController::class, 'getPerformanceMetrics']);
    });
    
    // ================================
    // INTEGRATION ENDPOINTS
    // ================================
    Route::middleware(['advanced-rate-limit:integration', 'api-security-validation'])->group(function () {
        Route::post('/integration/carriers/rates', [IntegrationController::class, 'getCarrierRates']);
        Route::post('/integration/partners/sync', [IntegrationController::class, 'syncPartnerData']);
        Route::get('/integration/status', [IntegrationController::class, 'getIntegrationStatus']);
        Route::post('/integration/edi/send', [IntegrationController::class, 'sendEDIData']);
        Route::get('/integration/edi/receive', [IntegrationController::class, 'receiveEDIData']);
    });
    
    // ================================
    // WEBHOOK MANAGEMENT ENDPOINTS
    // ================================
    Route::middleware(['advanced-rate-limit:webhooks', 'api-security-validation'])->group(function () {
        Route::post('/webhooks/register', [WebhookController::class, 'registerWebhook']);
        Route::get('/webhooks/events', [WebhookController::class, 'getWebhookEvents']);
        Route::get('/webhooks/test/{id}', [WebhookController::class, 'testWebhook']);
        Route::put('/webhooks/{id}', [WebhookController::class, 'updateWebhook']);
        Route::delete('/webhooks/{id}', [WebhookController::class, 'deleteWebhook']);
        Route::post('/webhooks/pricing-events', [WebhookController::class, 'handlePricingEvents']);
    });
    
    // ================================
    // ADVANCED ENDPOINTS
    // ================================
    Route::middleware(['advanced-rate-limit:quotes', 'api-security-validation'])->group(function () {
        Route::post('/pricing/quote/alternatives', [UnifiedPricingController::class, 'getServiceAlternatives']);
        Route::post('/pricing/quote/compare', [UnifiedPricingController::class, 'compareQuotes']);
        Route::post('/pricing/quote/optimize', [UnifiedPricingController::class, 'optimizePricing']);
    });

    Route::prefix('edi')->middleware(['auth:api'])->group(function () {
        Route::post('{documentType}', [EdiController::class, 'submit'])->name('api.v1.edi.submit');
        Route::get('transactions/{transaction}', [EdiController::class, 'show'])->name('api.v1.edi.show');
        Route::get('transactions/{transaction}/ack', [EdiController::class, 'acknowledgement'])->name('api.v1.edi.ack');
        Route::get('transactions/{transaction}/acknowledgement', [EdiController::class, 'acknowledgement'])->name('api.v1.edi.acknowledgement');
    });

    Route::prefix('devices')->middleware(['mobile.errors'])->group(function () {
        Route::post('/register', [EnhancedMobileScanningController::class, 'registerDevice']);
        Route::post('/authenticate', [EnhancedMobileScanningController::class, 'authenticateDevice']);
        Route::post('/deactivate', [EnhancedMobileScanningController::class, 'deactivateDevice']);
    });

    Route::prefix('mobile')->middleware(['mobile.errors'])->group(function () {
        Route::post('/scan', [EnhancedMobileScanningController::class, 'scan']);
        Route::post('/bulk-scan', [EnhancedMobileScanningController::class, 'bulkScan']);
        Route::post('/enhanced-offline-sync', [EnhancedMobileScanningController::class, 'enhancedOfflineSync']);
        Route::get('/device-info', [EnhancedMobileScanningController::class, 'getDeviceInfo']);
        Route::get('/offline-queue', [EnhancedMobileScanningController::class, 'getOfflineSyncQueue']);
        Route::post('/confirm-sync', [EnhancedMobileScanningController::class, 'confirmSync']);
    });
});

// ================================
// AUTHENTICATED ROUTES
// ================================
Route::prefix('v1')->middleware(['auth:sanctum', 'api-security-validation'])->group(function () {
    
    // Customer-specific endpoints
    Route::get('/customer/{id}/usage', [AnalyticsController::class, 'getCustomerUsage']);
    Route::get('/customer/{id}/preferences', [UnifiedPricingController::class, 'getCustomerPreferences']);
    Route::put('/customer/{id}/preferences', [UnifiedPricingController::class, 'updateCustomerPreferences']);
    
    // Admin endpoints
    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        
        // System management
        Route::get('/system/performance', [SystemHealthController::class, 'getPerformanceMetrics']);
        Route::get('/system/alerts', [SystemHealthController::class, 'getAlerts']);
        Route::put('/system/config', [SystemHealthController::class, 'updateSystemConfig']);
        
        // Promotion management
        Route::get('/promotions', [UnifiedPricingController::class, 'getAllPromotions']);
        Route::post('/promotions', [UnifiedPricingController::class, 'createPromotion']);
        Route::put('/promotions/{id}', [UnifiedPricingController::class, 'updatePromotion']);
        Route::delete('/promotions/{id}', [UnifiedPricingController::class, 'deletePromotion']);
        
        // Contract management
        Route::get('/contracts/analytics', [UnifiedPricingController::class, 'getContractAnalytics']);
        Route::post('/contracts/bulk-update', [UnifiedPricingController::class, 'bulkUpdateContracts']);
        
        // Integration management
        Route::get('/integration/logs', [IntegrationController::class, 'getIntegrationLogs']);
        Route::post('/integration/manage', [IntegrationController::class, 'manageIntegration']);
    });
});

// ================================
// WEBHOOK ENDPOINTS (External)
// ================================
Route::prefix('v1/webhooks')->group(function () {
    Route::post('/pricing-events', [WebhookController::class, 'handlePricingEvents'])
        ->middleware(['api-security-validation']);
    Route::post('/contract-events', [WebhookController::class, 'handleContractEvents'])
        ->middleware(['api-security-validation']);
    Route::post('/promotion-events', [WebhookController::class, 'handlePromotionEvents'])
        ->middleware(['api-security-validation']);
    Route::post('/system-events', [WebhookController::class, 'handleSystemEvents'])
        ->middleware(['api-security-validation']);
});

Route::prefix('v1/admin/webhooks')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        Route::get('/', [WebhookManagementController::class, 'index']);
        Route::post('/', [WebhookManagementController::class, 'store']);
        Route::get('deliveries', [WebhookManagementController::class, 'allDeliveries']);
        Route::get('metrics', [WebhookManagementController::class, 'metrics']);
        Route::get('{endpoint}', [WebhookManagementController::class, 'show']);
        Route::put('{endpoint}', [WebhookManagementController::class, 'update']);
        Route::delete('{endpoint}', [WebhookManagementController::class, 'destroy']);
        Route::post('{endpoint}/rotate-secret', [WebhookManagementController::class, 'rotateSecret']);
        Route::get('{endpoint}/deliveries', [WebhookManagementController::class, 'deliveries']);
        Route::post('deliveries/{delivery}/retry', [WebhookManagementController::class, 'retryDelivery']);
        Route::post('{endpoint}/test', [WebhookManagementController::class, 'testWebhook']);
        Route::get('health/status', [WebhookManagementController::class, 'health']);
        Route::get('metrics', [WebhookManagementController::class, 'metrics']);
    });

// ================================
// TESTING & DEVELOPMENT ENDPOINTS
// ================================
Route::prefix('v1/test')->middleware(['api-security-validation'])->group(function () {
    if (app()->environment('local', 'staging')) {
        
        Route::post('/mock-quote', [UnifiedPricingController::class, 'generateMockQuote']);
        Route::post('/simulate-load', [SystemHealthController::class, 'simulateLoad']);
        Route::post('/generate-traffic', [SystemHealthController::class, 'generateTraffic']);
        Route::get('/performance-test', [SystemHealthController::class, 'performanceTest']);
        
    }
});

// ================================
// RATE LIMIT CONFIGURATION ENDPOINTS
// ================================
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin')->group(function () {
    Route::get('/rate-limits', [SystemHealthController::class, 'getRateLimitStatus']);
    Route::post('/rate-limits/reset', [SystemHealthController::class, 'resetRateLimits']);
    Route::put('/rate-limits/configure', [SystemHealthController::class, 'configureRateLimits']);

    Route::prefix('edi')->group(function () {
        Route::get('/transactions', [EdiController::class, 'list'])->name('api.v1.admin.edi.transactions');
        Route::get('/filters', [EdiController::class, 'filters']);
        Route::get('/metrics', [EdiController::class, 'metrics']);
        Route::get('/submission-history', [EdiController::class, 'submissionHistory']);
        Route::get('/trading-partners', [EdiController::class, 'tradingPartners']);
        Route::get('/document-types', [EdiController::class, 'documentTypes']);
        Route::post('/submit-batch', [EdiController::class, 'submitBatch']);
    });

    Route::prefix('branches/operations')->group(function () {
        Route::get('/', [BranchOperationsController::class, 'index']);
        Route::get('/analytics', [BranchOperationsController::class, 'analytics']);
        Route::get('/maintenance', [BranchOperationsController::class, 'maintenanceWindows']);
        Route::post('/maintenance', [BranchOperationsController::class, 'createMaintenanceWindow']);
        Route::get('/alerts', [BranchOperationsController::class, 'alerts']);
        Route::post('/alerts/{alert}/resolve', [BranchOperationsController::class, 'resolveAlert']);

        Route::post('/seed/start', [BranchOperationsController::class, 'startSeedOperation']);
        Route::post('/seed/dry-run', [BranchOperationsController::class, 'dryRunSeed']);
        Route::post('/seed/force-execute', [BranchOperationsController::class, 'forceSeedExecute']);
        Route::get('/seed/operations', [BranchOperationsController::class, 'seedOperations']);
        Route::get('/seed/{operation}', [BranchOperationsController::class, 'seedOperation']);
        Route::post('/seed/{operation}/cancel', [BranchOperationsController::class, 'cancelSeedOperation']);

        Route::get('/{branch}', [BranchOperationsController::class, 'show'])->whereNumber('branch');
        Route::put('/{branch}', [BranchOperationsController::class, 'update'])->whereNumber('branch');
        Route::get('/{branch}/performance', [BranchOperationsController::class, 'performance'])->whereNumber('branch');
        Route::get('/{branch}/capacity', [BranchOperationsController::class, 'capacity'])->whereNumber('branch');
        Route::get('/{branch}/alerts', [BranchOperationsController::class, 'alertsForBranch'])->whereNumber('branch');
        Route::get('/{branch}/configuration', [BranchOperationsController::class, 'configuration'])->whereNumber('branch');
        Route::put('/{branch}/configuration', [BranchOperationsController::class, 'updateConfiguration'])->whereNumber('branch');
    });
});

// ================================
// SPA AUTHENTICATION (SANCTUM)
// ================================
Route::prefix('auth')
    ->middleware('web')
    ->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
        Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
            Route::get('/user', [AuthController::class, 'user'])->name('api.auth.user');
            Route::patch('/preferences', [AuthController::class, 'updatePreferences'])->name('api.auth.preferences');
        });
    });

// ================================
// ADMIN NAVIGATION (SPA SIDE MENU)
// ================================
Route::middleware('auth:sanctum')->get('/navigation/admin', AdminNavigationController::class);

// ================================
// SALES WORKSPACE
// ================================
Route::middleware('auth:sanctum')->prefix('sales')->group(function () {
    Route::get('/customers', [SalesCustomerController::class, 'index']);
    Route::post('/customers', [SalesCustomerController::class, 'store']);
    Route::get('/customers/meta', [SalesCustomerController::class, 'meta']);
    Route::get('/customers/{customer}', [SalesCustomerController::class, 'show'])->whereNumber('customer');
    Route::put('/customers/{customer}', [SalesCustomerController::class, 'update'])->whereNumber('customer');
    Route::delete('/customers/{customer}', [SalesCustomerController::class, 'destroy'])->whereNumber('customer');

    Route::get('/quotations', [SalesQuotationController::class, 'index']);
    Route::post('/quotations', [SalesQuotationController::class, 'store']);

    Route::get('/contracts', [SalesContractController::class, 'index']);
    Route::post('/contracts', [SalesContractController::class, 'store']);

    Route::get('/address-book', [SalesAddressBookController::class, 'index']);
    Route::post('/address-book', [SalesAddressBookController::class, 'store']);
});

// ================================
// ADMIN MANAGEMENT (SPA SETTINGS)
// ================================
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::prefix('branch-managers')->group(function () {
        Route::get('/', [BranchManagerApiController::class, 'index']);
        Route::post('/', [BranchManagerApiController::class, 'store']);
        Route::get('/create', [BranchManagerApiController::class, 'formMeta']);
        Route::get('/{branchManager}', [BranchManagerApiController::class, 'show'])->whereNumber('branchManager');
        Route::put('/{branchManager}', [BranchManagerApiController::class, 'update'])->whereNumber('branchManager');
        Route::delete('/{branchManager}', [BranchManagerApiController::class, 'destroy'])->whereNumber('branchManager');
        Route::post('/{branchManager}/balance/update', [BranchManagerApiController::class, 'updateBalance'])->whereNumber('branchManager');
        Route::get('/{branchManager}/settlements', [BranchManagerApiController::class, 'settlements'])->whereNumber('branchManager');
        Route::post('/bulk-status-update', [BranchManagerApiController::class, 'bulkStatusUpdate']);
    });

    Route::prefix('branch-workers')->group(function () {
        Route::get('/', [BranchWorkerApiController::class, 'index']);
        Route::post('/', [BranchWorkerApiController::class, 'store']);
        Route::get('/create', [BranchWorkerApiController::class, 'formMeta']);
        Route::get('/{branchWorker}', [BranchWorkerApiController::class, 'show'])->whereNumber('branchWorker');
        Route::put('/{branchWorker}', [BranchWorkerApiController::class, 'update'])->whereNumber('branchWorker');
        Route::delete('/{branchWorker}', [BranchWorkerApiController::class, 'destroy'])->whereNumber('branchWorker');
        Route::post('/{branchWorker}/unassign', [BranchWorkerApiController::class, 'unassign'])->whereNumber('branchWorker');
        Route::post('/{branchWorker}/assign-shipment', [BranchWorkerApiController::class, 'assignShipment'])->whereNumber('branchWorker');
        Route::post('/bulk-status-update', [BranchWorkerApiController::class, 'bulkStatusUpdate']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserApiController::class, 'index']);
        Route::post('/', [UserApiController::class, 'store']);
        Route::get('/meta', [UserApiController::class, 'meta']);
        Route::get('/{user}', [UserApiController::class, 'show'])->whereNumber('user');
        Route::match(['post', 'put'], '/{user}', [UserApiController::class, 'update'])->whereNumber('user');
        Route::delete('/{user}', [UserApiController::class, 'destroy'])->whereNumber('user');
        Route::post('/bulk-assign', [UserApiController::class, 'bulkAssign']);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleApiController::class, 'index']);
        Route::post('/', [RoleApiController::class, 'store']);
        Route::get('/meta', [RoleApiController::class, 'meta']);
        Route::get('/{role}', [RoleApiController::class, 'show'])->whereNumber('role');
        Route::put('/{role}', [RoleApiController::class, 'update'])->whereNumber('role');
        Route::delete('/{role}', [RoleApiController::class, 'destroy'])->whereNumber('role');
    });
});

// ================================
// V10 SHIPMENT & BOOKING ENDPOINTS
// ================================
Route::prefix('v10')
    ->middleware(['auth:sanctum', 'throttle:120,1'])
    ->group(function () {
        // Dashboard & workflow queue
        Route::prefix('dashboard')->group(function () {
            Route::get('/data', [DashboardApiController::class, 'index']);
            Route::get('/kpis', [DashboardApiController::class, 'kpis']);
            Route::get('/charts', [DashboardApiController::class, 'charts']);
            Route::get('/workflow-queue', [DashboardApiController::class, 'workflowQueue']);
        });

        Route::get('/workflow-board', [WorkflowBoardController::class, 'index']);

        Route::prefix('workflow-items')->group(function () {
            Route::get('/', [WorkflowTaskController::class, 'index']);
            Route::post('/', [WorkflowTaskController::class, 'store']);
            Route::post('/bulk-update', [WorkflowTaskController::class, 'bulkUpdate']);
            Route::post('/bulk-delete', [WorkflowTaskController::class, 'bulkDelete']);
            Route::get('/{workflowTask}', [WorkflowTaskController::class, 'show']);
            Route::put('/{workflowTask}', [WorkflowTaskController::class, 'update']);
            Route::delete('/{workflowTask}', [WorkflowTaskController::class, 'destroy']);
            Route::patch('/{workflowTask}/status', [WorkflowTaskController::class, 'updateStatus']);
            Route::patch('/{workflowTask}/assign', [WorkflowTaskController::class, 'assign']);
            Route::post('/{workflowTask}/comments', [WorkflowTaskController::class, 'comments']);
            Route::put('/{workflowTask}/comments/{comment}', [WorkflowTaskController::class, 'updateComment'])->whereNumber('comment');
            Route::delete('/{workflowTask}/comments/{comment}', [WorkflowTaskController::class, 'deleteComment'])->whereNumber('comment');
            Route::get('/{workflowTask}/history', [WorkflowTaskController::class, 'history']);
        });

        // Branch network & management
        Route::prefix('branches')->group(function () {
            Route::get('/', [BranchNetworkController::class, 'index']);
            Route::get('/hierarchy', [BranchNetworkController::class, 'hierarchy']);
            Route::get('/{branch}', [BranchNetworkController::class, 'show'])->whereNumber('branch');
            Route::post('/', [BranchManagementController::class, 'store']);
            Route::put('/{branch}', [BranchManagementController::class, 'update'])->whereNumber('branch');
            Route::patch('/{branch}/status', [BranchManagementController::class, 'toggleStatus'])->whereNumber('branch');
        });

        Route::prefix('merchants')->group(function () {
            Route::get('/', [MerchantManagementController::class, 'index']);
            Route::get('/{merchant}', [MerchantManagementController::class, 'show'])->whereNumber('merchant');
        });

        Route::prefix('drivers')->group(function () {
            Route::get('/', [DriverController::class, 'index']);
            Route::post('/', [DriverController::class, 'store']);
            Route::get('/{driver}', [DriverController::class, 'show'])->whereNumber('driver');
            Route::put('/{driver}', [DriverController::class, 'update'])->whereNumber('driver');
            Route::patch('/{driver}/status', [DriverController::class, 'toggleStatus'])->whereNumber('driver');
        });

        Route::get('/driver-rosters', [DriverRosterController::class, 'index']);
        Route::post('/driver-rosters', [DriverRosterController::class, 'store']);
        Route::put('/driver-rosters/{roster}', [DriverRosterController::class, 'update'])->whereNumber('roster');
        Route::delete('/driver-rosters/{roster}', [DriverRosterController::class, 'destroy'])->whereNumber('roster');

        Route::get('/driver-time-logs', [DriverTimeLogController::class, 'index']);
        Route::post('/driver-time-logs', [DriverTimeLogController::class, 'store']);

        // Operations control centre
        Route::prefix('operations')->group(function () {
            Route::get('/dispatch-board', [OperationsControlCenterController::class, 'getDispatchBoard']);
            Route::get('/exception-metrics', [OperationsControlCenterController::class, 'getExceptionMetrics']);
            Route::get('/alerts', [OperationsControlCenterController::class, 'getAlerts']);
            Route::get('/shipment-metrics', [OperationsControlCenterController::class, 'getShipmentMetrics']);
            Route::get('/worker-utilization', [OperationsControlCenterController::class, 'getWorkerUtilization']);
            Route::get('/notifications', [OperationsControlCenterController::class, 'getUserNotifications']);
            Route::get('/notification-history', [OperationsControlCenterController::class, 'getNotificationHistory']);
            Route::get('/notifications/unread-count', [OperationsControlCenterController::class, 'getUnreadNotificationCount']);
        });

        // Operational analytics
        Route::prefix('analytics/operational')->group(function () {
            Route::get('/metrics', [OperationalAnalyticsController::class, 'metrics']);
            Route::get('/origin-destination', [OperationalAnalyticsController::class, 'originDestination']);
            Route::get('/route-efficiency', [OperationalAnalyticsController::class, 'routeEfficiency']);
            Route::get('/on-time-delivery', [OperationalAnalyticsController::class, 'onTimeDelivery']);
            Route::get('/exception-analysis', [OperationalAnalyticsController::class, 'exceptionAnalysis']);
            Route::get('/driver-performance', [OperationalAnalyticsController::class, 'driverPerformance']);
            Route::get('/container-utilization', [OperationalAnalyticsController::class, 'containerUtilization']);
            Route::get('/transit-times', [OperationalAnalyticsController::class, 'transitTimeAnalysis']);
        });

        // Streaming analytics & configs (used by SPA dashboards)
        Route::prefix('analytics/streaming')->group(function () {
            Route::get('/metrics', [StreamingAnalyticsController::class, 'metrics']);
            Route::get('/status', [StreamingAnalyticsController::class, 'status']);
            Route::get('/websocket-config', [StreamingAnalyticsController::class, 'websocketConfig']);
            Route::get('/sse-config', [StreamingAnalyticsController::class, 'sseConfig']);
        });

        // Analytics performance + health mirror routes
        Route::prefix('analytics/performance')->group(function () {
            Route::get('/metrics', [OptimizedAnalyticsController::class, 'getPerformanceAnalytics']);
            Route::get('/recommendations', [OptimizedAnalyticsController::class, 'getOptimizationRecommendations']);
        });

        Route::prefix('analytics/health')->group(function () {
            Route::get('/', [OptimizedAnalyticsController::class, 'getSystemHealth']);
            Route::get('/alerts', [HealthAlertController::class, 'index']);
            Route::post('/alerts/{alertId}/acknowledge', [HealthAlertController::class, 'acknowledge'])->whereNumber('alertId');
        });

        // Optimized analytics & capacity services
        Route::prefix('analytics/optimized')->middleware(['permission:dashboard_read|branch_analytics|report_analytics|system_read'])->group(function () {
            Route::get('/branches', [OptimizedAnalyticsController::class, 'listAvailableBranches']);
            Route::get('/branch/performance', [OptimizedAnalyticsController::class, 'getBranchPerformanceAnalytics']);
            Route::post('/branch/batch', [OptimizedAnalyticsController::class, 'getBatchBranchAnalytics']);
            Route::get('/capacity/{branch}', [OptimizedAnalyticsController::class, 'getCapacityAnalysis'])->whereNumber('branch');
            Route::post('/precompute', [OptimizedAnalyticsController::class, 'precomputeAnalytics']);
            Route::delete('/cache', [OptimizedAnalyticsController::class, 'clearAnalyticsCache']);
            Route::get('/snapshot/{branch}/{date}', [OptimizedAnalyticsController::class, 'getMaterializedSnapshot'])->whereNumber('branch');
        });

        // Real-time analytics endpoints
        Route::prefix('realtime')->middleware(['permission:dashboard_read|branch_analytics|report_analytics|system_read'])->group(function () {
            Route::get('/branch/{branch}/analytics', [OptimizedAnalyticsController::class, 'getRealTimeAnalytics'])->whereNumber('branch');
            Route::get('/metrics', [StreamingAnalyticsController::class, 'metrics']);
            Route::get('/status', [StreamingAnalyticsController::class, 'status']);
            Route::get('/websocket-config', [StreamingAnalyticsController::class, 'websocketConfig']);
            Route::get('/sse-config', [StreamingAnalyticsController::class, 'sseConfig']);
        });

        // Performance monitoring endpoints
        Route::prefix('performance')->middleware(['permission:dashboard_read|branch_analytics|report_analytics|system_read'])->group(function () {
            Route::get('/analytics', [OptimizedAnalyticsController::class, 'getPerformanceAnalytics']);
            Route::get('/realtime', [OptimizedAnalyticsController::class, 'getRealTimePerformance']);
            Route::get('/recommendations', [OptimizedAnalyticsController::class, 'getOptimizationRecommendations']);
            Route::get('/health', [OptimizedAnalyticsController::class, 'getSystemHealth']);
        });

        // Unified shipment workflows
        Route::prefix('unified-shipments')->group(function () {
            Route::get('/hub-sortation', [UnifiedShipmentController::class, 'getHubSortation']);
            Route::get('/inter-branch-transfers', [UnifiedShipmentController::class, 'getInterBranchTransfers']);
            Route::get('/workflow-analytics', [UnifiedShipmentController::class, 'getWorkflowAnalytics']);
            Route::get('/workflow-alerts', [UnifiedShipmentController::class, 'getWorkflowAlerts']);
        });

        // Shipment endpoints
        Route::get('/shipments', [ShipmentsApiController::class, 'index']);
        Route::post('/shipments', [ShipmentsApiController::class, 'store']);
        Route::get('/shipments/statistics', [ShipmentsApiController::class, 'statistics']);

        // Global search
        Route::get('/search', [V10SearchController::class, 'search']);
        Route::get('/search/autocomplete', [V10SearchController::class, 'autocomplete']);
        Route::get('/search/advanced', [V10SearchController::class, 'advanced']);
        Route::get('/search/stats', [V10SearchController::class, 'stats']);

        // Parcel utilities
        Route::get('/parcel/logs/{parcel}', [V10ParcelController::class, 'logs'])->whereNumber('parcel');
        Route::get('/parcel/tracking/{trackingId}', [V10ParcelController::class, 'track']);

        // Finance & settlements
        Route::get('/invoice-list/index', [V10InvoiceController::class, 'invoiceLists']);
        Route::get('/invoice-details/{invoice}', [V10InvoiceController::class, 'invoiceDetails'])->whereNumber('invoice');
        Route::get('/payment-accounts/index', [V10PaymentAccountController::class, 'index']);
        Route::get('/payment-request/index', [V10PaymentRequestController::class, 'index']);
        Route::get('/statements/index', [V10StatementsController::class, 'index']);
        Route::post('/statements/filter', [V10StatementsController::class, 'filter']);
        Route::post('/statement-reports', [V10ReportController::class, 'TotalSummeryStatementReports']);

        // Settings & configuration
        Route::get('/general-settings', [GeneralSettingCotroller::class, 'index']);
        Route::put('/general-settings', [GeneralSettingCotroller::class, 'update']);
        Route::get('/all-currencies', [GeneralSettingCotroller::class, 'currencies']);
        Route::get('/settings/cod-charges', [V10SettingsController::class, 'codCharges']);
        Route::get('/settings/delivery-charges', [V10SettingsController::class, 'deliveryCharges']);

        // Support centre
        Route::get('/support/index', [V10SupportController::class, 'index']);
        Route::get('/support/create', [V10SupportController::class, 'create']);
        Route::post('/support/store', [V10SupportController::class, 'store']);
        Route::get('/support/edit/{support}', [V10SupportController::class, 'edit'])->whereNumber('support');
        Route::put('/support/update/{support}', [V10SupportController::class, 'update'])->whereNumber('support');
        Route::delete('/support/delete/{support}', [V10SupportController::class, 'destroy'])->whereNumber('support');
        Route::get('/support/view/{support}', [V10SupportController::class, 'view'])->whereNumber('support');
        Route::post('/support/reply', [V10SupportController::class, 'supportReply']);

        // Booking workflow
        Route::prefix('booking')->group(function () {
            Route::post('/step1', [BookingWizardController::class, 'step1']);
            Route::post('/step2', [BookingWizardController::class, 'step2']);
            Route::post('/step3', [BookingWizardController::class, 'step3']);
            Route::post('/step4', [BookingWizardController::class, 'step4']);
            Route::post('/step5', [BookingWizardController::class, 'step5']);
            Route::get('/download-labels/{shipment}', [BookingWizardController::class, 'downloadLabels'])
                ->whereNumber('shipment');
        });
    });

// ================================
// FALLBACK ERROR HANDLING
// ================================
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'error' => 'Endpoint not found',
        'message' => 'The requested API endpoint does not exist',
        'timestamp' => now()->toISOString(),
        'api_version' => '1.0',
        'documentation_url' => config('app.url') . '/docs/api',
    ], 404);
});

// ================================
// ACCESSIBILITY & AUDIT ROUTES
// ================================
Route::prefix('v1/admin/accessibility')->middleware(['auth:sanctum', 'role:admin', 'audit.logging', 'accessibility.validation'])->group(function () {
    Route::post('/test/run', [App\Http\Controllers\Admin\AccessibilityController::class, 'runTest']);
    Route::get('/compliance/summary', [App\Http\Controllers\Admin\AccessibilityController::class, 'getComplianceSummary']);
    Route::get('/trends', [App\Http\Controllers\Admin\AccessibilityController::class, 'getAccessibilityTrends']);
    Route::post('/schedule', [App\Http\Controllers\Admin\AccessibilityController::class, 'scheduleRecurringTest']);
    Route::get('/tests', [App\Http\Controllers\Admin\AccessibilityController::class, 'getTestResults']);
    Route::get('/violations', [App\Http\Controllers\Admin\AccessibilityController::class, 'getComplianceViolations']);
    Route::put('/violations/{violationId}/resolve', [App\Http\Controllers\Admin\AccessibilityController::class, 'resolveViolation']);
    Route::get('/overview', [App\Http\Controllers\Admin\AccessibilityController::class, 'getComplianceOverview']);
});

// Export and Reporting Routes
Route::prefix('v1/admin/reports')->middleware(['auth:sanctum', 'role:admin', 'audit.logging'])->group(function () {
    Route::get('/audit/summary', [App\Http\Controllers\Admin\AuditController::class, 'getAuditSummary']);
    Route::get('/audit/logs', [App\Http\Controllers\Admin\AuditController::class, 'getAuditLogs']);
    Route::post('/export/audit', [App\Http\Controllers\Admin\AuditController::class, 'exportAuditReport']);
    Route::post('/export/accessibility', [App\Http\Controllers\Admin\AuditController::class, 'exportAccessibilityReport']);
    Route::post('/export/csv', [App\Http\Controllers\Admin\AuditController::class, 'exportToCSV']);
});

// Compliance Monitoring Routes
Route::prefix('v1/admin/compliance')->middleware(['auth:sanctum', 'role:admin', 'audit.logging'])->group(function () {
    Route::get('/monitoring/status', [App\Http\Controllers\Admin\ComplianceController::class, 'getMonitoringStatus']);
    Route::post('/monitoring/rules', [App\Http\Controllers\Admin\ComplianceController::class, 'createMonitoringRule']);
    Route::put('/monitoring/rules/{ruleId}', [App\Http\Controllers\Admin\ComplianceController::class, 'updateMonitoringRule']);
    Route::get('/violations', [App\Http\Controllers\Admin\ComplianceController::class, 'getAllViolations']);
});

// Real-time Accessibility Testing
Route::prefix('v1/accessibility')->middleware(['accessibility.validation'])->group(function () {
    Route::get('/test/{url}', [App\Http\Controllers\AccessibilityTestController::class, 'runRealTimeTest']);
    Route::get('/status/{url}', [App\Http\Controllers\AccessibilityTestController::class, 'getPageStatus']);
});

/*
|--------------------------------------------------------------------------
| ROUTE CLEANUP SCHEDULE
|--------------------------------------------------------------------------
|
| These are scheduled cleanup tasks for the API.
| You would register these with Laravel's scheduler in console kernel.
|
*/

// Route::get('/api/v1/health/detailed', [SystemHealthController::class, 'detailedHealthCheck'])
//     ->middleware(['throttle:30,1']); // 30 requests per minute

// Route::get('/api/v1/monitoring/cleanup', [SystemHealthController::class, 'cleanupOldLogs'])
//     ->middleware(['throttle:5,1']); // 5 requests per minute

// Route::post('/api/v1/monitoring/refresh-cache', [SystemHealthController::class, 'refreshCache'])
//     ->middleware(['throttle:10,1']); // 10 requests per minute

/*
|--------------------------------------------------------------------------
| API V2 - RESTful API with API Key Authentication
|--------------------------------------------------------------------------
*/

// API Key Management (Admin authenticated)
Route::prefix('v2/admin/api-keys')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\ApiKeyController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V2\ApiKeyController::class, 'store']);
        Route::get('/{apiKey}', [\App\Http\Controllers\Api\V2\ApiKeyController::class, 'show']);
        Route::put('/{apiKey}', [\App\Http\Controllers\Api\V2\ApiKeyController::class, 'update']);
        Route::delete('/{apiKey}', [\App\Http\Controllers\Api\V2\ApiKeyController::class, 'destroy']);
        Route::post('/{apiKey}/regenerate-secret', [\App\Http\Controllers\Api\V2\ApiKeyController::class, 'regenerateSecret']);
    });

// API v2 Endpoints (API Key authenticated)
Route::prefix('v2')
    ->middleware(['api.key'])
    ->group(function () {
        // Shipments
        Route::get('/shipments', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'index']);
        Route::post('/shipments', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'store']);
        Route::get('/shipments/{shipment}', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'show']);
        Route::put('/shipments/{shipment}', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'update']);
        Route::post('/shipments/{shipment}/status', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'updateStatus']);
        Route::get('/shipments/{shipment}/tracking', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'tracking']);
        Route::post('/shipments/{shipment}/cancel', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'cancel']);
        Route::post('/shipments/rate', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'calculateRate']);
        Route::post('/shipments/batch', [\App\Http\Controllers\Api\V2\ShipmentController::class, 'batchCreate']);
        
        // Webhooks
        Route::get('/webhooks/events', [\App\Http\Controllers\Api\V2\WebhookController::class, 'events']);
        Route::get('/webhooks', [\App\Http\Controllers\Api\V2\WebhookController::class, 'index']);
        Route::post('/webhooks', [\App\Http\Controllers\Api\V2\WebhookController::class, 'store']);
        Route::get('/webhooks/{webhook}', [\App\Http\Controllers\Api\V2\WebhookController::class, 'show']);
        Route::put('/webhooks/{webhook}', [\App\Http\Controllers\Api\V2\WebhookController::class, 'update']);
        Route::delete('/webhooks/{webhook}', [\App\Http\Controllers\Api\V2\WebhookController::class, 'destroy']);
        Route::post('/webhooks/{webhook}/test', [\App\Http\Controllers\Api\V2\WebhookController::class, 'test']);
        Route::get('/webhooks/{webhook}/deliveries', [\App\Http\Controllers\Api\V2\WebhookController::class, 'deliveries']);
        Route::post('/webhooks/{webhook}/rotate-secret', [\App\Http\Controllers\Api\V2\WebhookController::class, 'rotateSecret']);
        Route::post('/webhooks/deliveries/{delivery}/retry', [\App\Http\Controllers\Api\V2\WebhookController::class, 'retryDelivery']);
    });