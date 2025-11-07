<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DynamicPricingController;

/*
|--------------------------------------------------------------------------
| Dynamic Pricing API Routes
|--------------------------------------------------------------------------
|
| Routes for the Dynamic Rate Calculation Module
|
*/

// Public API routes (with rate limiting)
Route::prefix('v1')->middleware(['throttle:60,1', 'throttle:100,60'])->group(function () {
    // Instant quote calculation
    Route::post('/quotes/calculate', [DynamicPricingController::class, 'calculateQuote'])
        ->name('api.v1.quotes.calculate');
    
    // Bulk quote generation
    Route::post('/quotes/bulk', [DynamicPricingController::class, 'generateBulkQuotes'])
        ->name('api.v1.quotes.bulk');
    
    // Get bulk quote results
    Route::get('/quotes/bulk/{jobId}/results', [DynamicPricingController::class, 'getBulkQuoteResults'])
        ->name('api.v1.quotes.bulk.results');
    
    // Quote history for customers
    Route::get('/quotes/history', [DynamicPricingController::class, 'getQuoteHistory'])
        ->name('api.v1.quotes.history');
    
    // Competitor pricing data
    Route::get('/competitor-pricing', [DynamicPricingController::class, 'getCompetitorPricing'])
        ->name('api.v1.competitor-pricing');
    
    // Current fuel index
    Route::get('/fuel-index', [DynamicPricingController::class, 'getFuelIndex'])
        ->name('api.v1.fuel-index');
    
    // Service level definitions
    Route::get('/service-levels', [DynamicPricingController::class, 'getServiceLevels'])
        ->name('api.v1.service-levels');
});

// Webhook endpoints (for real-time notifications)
Route::prefix('v1/webhooks')->middleware(['throttle:200,1'])->group(function () {
    // Quote completion webhook
    Route::post('/quote-completed', function (Request $request) {
        // Handle quote completion webhook
        return response()->json(['status' => 'received']);
    })->name('api.v1.webhooks.quote-completed');
    
    // Bulk quote results webhook
    Route::post('/quote-results', function (Request $request) {
        // Handle bulk quote results webhook
        return response()->json(['status' => 'received']);
    })->name('api.v1.webhooks.quote-results');
});

// Health check endpoint
Route::get('/v1/health', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => 'dynamic-pricing',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
})->name('api.v1.health');