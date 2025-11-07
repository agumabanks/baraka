<?php

use App\Http\Controllers\Api\V1\UnifiedPricingController;
use App\Http\Controllers\Api\V1\PromotionWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Unified Pricing API Routes
|--------------------------------------------------------------------------
|
| Comprehensive API endpoints for the enhanced logistics pricing system
| Organized by functionality: quotes, contracts, promotions, analytics, etc.
|
*/

// Health and monitoring endpoints
Route::get('/health', [UnifiedPricingController::class, 'healthCheck']);
Route::get('/business-rules', [UnifiedPricingController::class, 'getBusinessRules']);

// Quote Generation Endpoints
Route::prefix('pricing')->group(function () {
    Route::post('/quote', [UnifiedPricingController::class, 'generateInstantQuote'])
        ->name('api.v1.pricing.quote');
    
    Route::post('/quote/bulk', [UnifiedPricingController::class, 'generateBulkQuotes'])
        ->name('api.v1.pricing.quote.bulk');
    
    Route::get('/quote/{id}', [UnifiedPricingController::class, 'getQuote'])
        ->name('api.v1.pricing.quote.id');
    
    Route::post('/calculate', [UnifiedPricingController::class, 'calculate'])
        ->name('api.v1.pricing.calculate');
});

// Contract Management Endpoints
Route::prefix('contracts')->group(function () {
    Route::get('/', [UnifiedPricingController::class, 'getContracts'])
        ->name('api.v1.contracts');
    
    Route::post('/', [UnifiedPricingController::class, 'createContract'])
        ->name('api.v1.contracts.create');
    
    Route::get('/{id}', [UnifiedPricingController::class, 'show'])
        ->name('api.v1.contracts.show');
    
    Route::put('/{id}', [UnifiedPricingController::class, 'updateContract'])
        ->name('api.v1.contracts.update');
    
    Route::delete('/{id}', [UnifiedPricingController::class, 'deleteContract'])
        ->name('api.v1.contracts.delete');
    
    Route::post('/{id}/activate', [UnifiedPricingController::class, 'activateContract'])
        ->name('api.v1.contracts.activate');
    
    Route::post('/{id}/renew', [UnifiedPricingController::class, 'renewContract'])
        ->name('api.v1.contracts.renew');
    
    Route::get('/{id}/compliance', [UnifiedPricingController::class, 'compliance'])
        ->name('api.v1.contracts.compliance');
    
    Route::get('/{id}/discounts', [UnifiedPricingController::class, 'discounts'])
        ->name('api.v1.contracts.discounts');
});

// Promotion Management Endpoints
Route::prefix('promotions')->group(function () {
    Route::get('/validate', [UnifiedPricingController::class, 'validatePromoCode'])
        ->name('api.v1.promotions.validate');
    
    Route::post('/apply', [UnifiedPricingController::class, 'applyPromoDiscount'])
        ->name('api.v1.promotions.apply');
    
    Route::get('/analytics', [UnifiedPricingController::class, 'getPromotionAnalytics'])
        ->name('api.v1.promotions.analytics');
    
    Route::get('/roi', [UnifiedPricingController::class, 'getPromotionROI'])
        ->name('api.v1.promotions.roi');
    
    Route::get('/milestones', [UnifiedPricingController::class, 'getMilestones'])
        ->name('api.v1.promotions.milestones');
    
    Route::post('/milestones/track', [UnifiedPricingController::class, 'trackMilestone'])
        ->name('api.v1.promotions.milestones.track');
    
    Route::get('/customer/{customer_id}/milestones', [UnifiedPricingController::class, 'getCustomerMilestones'])
        ->name('api.v1.promotions.customer.milestones');
    
    Route::post('/stacking/check', [UnifiedPricingController::class, 'checkAntiStackingRules'])
        ->name('api.v1.promotions.stacking.check');
    
    Route::post('/batch/track', [UnifiedPricingController::class, 'batchTrackMilestones'])
        ->name('api.v1.promotions.batch.track');
});

// Analytics and Insights Endpoints
Route::prefix('analytics')->group(function () {
    Route::get('/roi', [UnifiedPricingController::class, 'getPromotionROI'])
        ->name('api.v1.analytics.roi');
    
    Route::get('/effectiveness', [UnifiedPricingController::class, 'getPromotionEffectiveness'])
        ->name('api.v1.analytics.effectiveness');
    
    Route::get('/customer-insights', [UnifiedPricingController::class, 'getCustomerInsights'])
        ->name('api.v1.analytics.customer-insights');
    
    Route::get('/segment-performance', [UnifiedPricingController::class, 'getSegmentPerformance'])
        ->name('api.v1.analytics.segment-performance');
    
    Route::post('/ab-test', [UnifiedPricingController::class, 'runABTest'])
        ->name('api.v1.analytics.ab-test');
    
    Route::get('/ab-test/{test_id}/results', [UnifiedPricingController::class, 'getABTestResults'])
        ->name('api.v1.analytics.ab-test.results');
});

// Configuration and Settings Endpoints
Route::prefix('config')->group(function () {
    Route::get('/business-rules', [UnifiedPricingController::class, 'getBusinessRules'])
        ->name('api.v1.config.business-rules');
    
    Route::put('/business-rules', [UnifiedPricingController::class, 'updateBusinessRules'])
        ->name('api.v1.config.business-rules.update')
        ->middleware(['auth:sanctum', 'admin']);
    
    Route::get('/service-levels', [UnifiedPricingController::class, 'getServiceLevels'])
        ->name('api.v1.config.service-levels');
    
    Route::get('/fuel-index', [UnifiedPricingController::class, 'getFuelIndex'])
        ->name('api.v1.config.fuel-index');
});

// Webhook Management Endpoints
Route::prefix('webhooks')->group(function () {
    Route::post('/register', [UnifiedPricingController::class, 'registerWebhook'])
        ->name('api.v1.webhooks.register');
    
    Route::get('/events', [UnifiedPricingController::class, 'getWebhookEvents'])
        ->name('api.v1.webhooks.events');
    
    Route::post('/pricing-events', [PromotionWebhookController::class, 'handlePricingEvent'])
        ->name('api.v1.webhooks.pricing-events');
    
    Route::get('/test/{webhook_id}', [UnifiedPricingController::class, 'testWebhook'])
        ->name('api.v1.webhooks.test');
    
    Route::delete('/{webhook_id}', [UnifiedPricingController::class, 'unregisterWebhook'])
        ->name('api.v1.webhooks.unregister');
});

// Bulk Operations Endpoints
Route::prefix('bulk')->group(function () {
    Route::post('/quotes', [UnifiedPricingController::class, 'generateBulkQuotes'])
        ->name('api.v1.bulk.quotes');
    
    Route::get('/quotes/{job_id}/results', [UnifiedPricingController::class, 'getBulkQuoteResults'])
        ->name('api.v1.bulk.quotes.results');
    
    Route::post('/contracts/activate', [UnifiedPricingController::class, 'bulkActivateContracts'])
        ->name('api.v1.bulk.contracts.activate');
    
    Route::post('/milestones/track', [UnifiedPricingController::class, 'batchTrackMilestones'])
        ->name('api.v1.bulk.milestones.track');
    
    Route::post('/promotions/optimize', [UnifiedPricingController::class, 'bulkOptimizePromotions'])
        ->name('api.v1.bulk.promotions.optimize');
});

// Third-party Integration Endpoints
Route::prefix('integration')->group(function () {
    Route::post('/carriers/rates', [UnifiedPricingController::class, 'getCarrierRates'])
        ->name('api.v1.integration.carriers.rates');
    
    Route::post('/partners/sync', [UnifiedPricingController::class, 'syncPartnerData'])
        ->name('api.v1.integration.partners.sync');
    
    Route::get('/marketplace/connectors', [UnifiedPricingController::class, 'getMarketplaceConnectors'])
        ->name('api.v1.integration.marketplace.connectors');
    
    Route::post('/edi/submit', [UnifiedPricingController::class, 'submitEDIRequest'])
        ->name('api.v1.integration.edi.submit');
    
    Route::get('/status', [UnifiedPricingController::class, 'getIntegrationStatus'])
        ->name('api.v1.integration.status');
});

// Advanced Features Endpoints
Route::prefix('advanced')->group(function () {
    Route::post('/async/pricing', [UnifiedPricingController::class, 'processAsyncPricing'])
        ->name('api.v1.advanced.async.pricing');
    
    Route::post('/circuit-breaker/test', [UnifiedPricingController::class, 'testCircuitBreaker'])
        ->name('api.v1.advanced.circuit-breaker.test');
    
    Route::post('/fallback/pricing', [UnifiedPricingController::class, 'getFallbackPricing'])
        ->name('api.v1.advanced.fallback.pricing');
    
    Route::get('/performance/metrics', [UnifiedPricingController::class, 'getPerformanceMetrics'])
        ->name('api.v1.advanced.performance.metrics');
    
    Route::post('/optimize/route', [UnifiedPricingController::class, 'optimizeRoute'])
        ->name('api.v1.advanced.optimize.route');
});

// Customer Self-Service Endpoints
Route::prefix('customer-portal')->group(function () {
    Route::post('/quote', [UnifiedPricingController::class, 'generateInstantQuote'])
        ->name('api.v1.customer-portal.quote');
    
    Route::post('/quote/validate-promo', [UnifiedPricingController::class, 'validatePromoCode'])
        ->name('api.v1.customer-portal.validate-promo');
    
    Route::get('/history/quotes', [UnifiedPricingController::class, 'getQuoteHistory'])
        ->name('api.v1.customer-portal.history.quotes');
    
    Route::get('/milestones', [UnifiedPricingController::class, 'getCustomerMilestones'])
        ->name('api.v1.customer-portal.milestones');
    
    Route::get('/recommendations', [UnifiedPricingController::class, 'getCustomerRecommendations'])
        ->name('api.v1.customer-portal.recommendations');
});

// Admin and Management Endpoints (Require authentication)
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [UnifiedPricingController::class, 'getAdminDashboard'])
        ->name('api.v1.admin.dashboard');
    
    Route::get('/metrics/overview', [UnifiedPricingController::class, 'getMetricsOverview'])
        ->name('api.v1.admin.metrics.overview');
    
    Route::post('/maintenance/cleanup', [UnifiedPricingController::class, 'performMaintenanceCleanup'])
        ->name('api.v1.admin.maintenance.cleanup');
    
    Route::get('/logs/api', [UnifiedPricingController::class, 'getAPILogs'])
        ->name('api.v1.admin.logs.api');
    
    Route::post('/config/reset', [UnifiedPricingController::class, 'resetConfiguration'])
        ->name('api.v1.admin.config.reset');
});

// Version information endpoint
Route::get('/version', function () {
    return response()->json([
        'api_version' => '1.0',
        'build_version' => config('app.version', '1.0.0'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'documentation_url' => config('app.url') . '/api/documentation',
        'timestamp' => now()->toISOString()
    ]);
})->name('api.version');