<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DynamicPricingService;
use App\Jobs\BulkQuoteCalculationJob;
use App\Jobs\WebhookNotificationJob;
use App\Jobs\QuoteValidationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Dynamic Pricing API Controller
 * 
 * Handles instant quote generation, bulk quotes, and pricing calculations
 */
class DynamicPricingController extends Controller
{
    public function __construct(
        private DynamicPricingService $pricingService
    ) {
        // Apply rate limiting middleware
        $this->middleware('throttle:60,1'); // 60 requests per minute
        $this->middleware('throttle:100,60'); // 100 requests per hour
    }

    /**
     * Calculate instant quote
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateQuote(Request $request): JsonResponse
    {
        // Rate limiting check
        $key = 'quote_calculation_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        $validatedData = $request->validate([
            'origin' => 'required|string|max:3',
            'destination' => 'required|string|max:3',
            'service_level' => ['required', Rule::in(['express', 'priority', 'standard', 'economy'])],
            'shipment_data' => 'required|array',
            'shipment_data.weight_kg' => 'required|numeric|min:0.1|max:70',
            'shipment_data.pieces' => 'required|integer|min:1|max:1000',
            'shipment_data.dimensions' => 'sometimes|array',
            'shipment_data.dimensions.length_cm' => 'sometimes|numeric|min:1|max:120',
            'shipment_data.dimensions.width_cm' => 'sometimes|numeric|min:1|max:80',
            'shipment_data.dimensions.height_cm' => 'sometimes|numeric|min:1|max:80',
            'shipment_data.declared_value' => 'sometimes|numeric|min:1|max:50000',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,CAD,JPY,AUD',
        ]);

        try {
            $quote = $this->pricingService->calculateInstantQuote(
                $validatedData['origin'],
                $validatedData['destination'],
                $validatedData['shipment_data'],
                $validatedData['service_level'],
                $validatedData['customer_id'] ?? null,
                $validatedData['currency'] ?? 'USD'
            );

            // Validate the generated quote
            $validation = $this->pricingService->validateQuote($quote);
            if (!$validation['valid']) {
                Log::warning('Quote validation failed', [
                    'errors' => $validation['errors'],
                    'quote_id' => $quote['quote_id']
                ]);
            }

            // Log successful quote calculation
            RateLimiter::hit($key, 60); // 1 minute window
            
            Log::info('Quote calculation successful', [
                'quote_id' => $quote['quote_id'],
                'customer_id' => $validatedData['customer_id'] ?? null,
                'route' => $validatedData['origin'] . '-' . $validatedData['destination'],
                'service_level' => $validatedData['service_level'],
                'total_amount' => $quote['final_total'],
                'processing_time_ms' => $quote['processing_time_ms']
            ]);

            // Dispatch webhook notification
            dispatch(new WebhookNotificationJob(
                'quote.calculated',
                $quote,
                [
                    'quote_id' => $quote['quote_id'],
                    'customer_id' => $validatedData['customer_id'] ?? null,
                    'origin' => $validatedData['origin'],
                    'destination' => $validatedData['destination'],
                    'service_level' => $validatedData['service_level']
                ]
            ));

            return response()->json([
                'success' => true,
                'data' => $quote,
                'validation' => $validation
            ]);

        } catch (\Exception $e) {
            Log::error('Quote calculation failed', [
                'error' => $e->getMessage(),
                'request_data' => $validatedData,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to calculate quote',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate bulk quotes
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generateBulkQuotes(Request $request): JsonResponse
    {
        // More strict rate limiting for bulk operations
        $key = 'bulk_quote_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'error' => 'Bulk quote rate limit exceeded. Maximum 5 requests per hour.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        $validatedData = $request->validate([
            'shipment_requests' => 'required|array|min:1|max:50',
            'shipment_requests.*.origin' => 'required|string|max:3',
            'shipment_requests.*.destination' => 'required|string|max:3',
            'shipment_requests.*.service_level' => ['required', Rule::in(['express', 'priority', 'standard', 'economy'])],
            'shipment_requests.*.shipment_data' => 'required|array',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,CAD,JPY,AUD',
        ]);

        try {
            // Generate unique job ID
            $jobId = uniqid('bulk_', true);
            
            // Dispatch bulk quote calculation job
            BulkQuoteCalculationJob::dispatch(
                $validatedData['shipment_requests'],
                $validatedData['customer_id'] ?? null,
                $validatedData['currency'] ?? 'USD',
                $jobId
            );

            RateLimiter::hit($key, 3600); // 1 hour window

            Log::info('Bulk quote job dispatched', [
                'job_id' => $jobId,
                'customer_id' => $validatedData['customer_id'] ?? null,
                'request_count' => count($validatedData['shipment_requests'])
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'job_id' => $jobId,
                    'status' => 'processing',
                    'message' => 'Bulk quote calculation started. Use the job_id to check results.',
                    'estimated_completion_time' => now()->addMinutes(5)->toISOString(),
                    'webhook_url' => route('api.v1.webhooks.quote-results', ['job_id' => $jobId])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk quote job dispatch failed', [
                'error' => $e->getMessage(),
                'request_count' => count($validatedData['shipment_requests'])
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to start bulk quote calculation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bulk quote results
     * 
     * @param string $jobId
     * @return JsonResponse
     */
    public function getBulkQuoteResults(string $jobId): JsonResponse
    {
        $results = \Illuminate\Support\Facades\Cache::get("bulk_quote_results_{$jobId}");
        
        if (!$results) {
            return response()->json([
                'success' => false,
                'error' => 'Results not found or expired',
                'job_id' => $jobId
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Get quote history for customer
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getQuoteHistory(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'service_level' => 'sometimes|string|in:express,priority,standard,economy',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after:date_from',
        ]);

        try {
            $customerId = $validatedData['customer_id'];
            $perPage = $validatedData['per_page'] ?? 20;
            $page = $validatedData['page'] ?? 1;

            $query = \App\Models\Quotation::where('customer_id', $customerId)
                ->orderBy('created_at', 'desc');

            if (isset($validatedData['service_level'])) {
                $query->where('service_type', $validatedData['service_level']);
            }

            if (isset($validatedData['date_from'])) {
                $query->whereDate('created_at', '>=', $validatedData['date_from']);
            }

            if (isset($validatedData['date_to'])) {
                $query->whereDate('created_at', '<=', $validatedData['date_to']);
            }

            $quotes = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'quotes' => $quotes->items(),
                    'pagination' => [
                        'current_page' => $quotes->currentPage(),
                        'per_page' => $quotes->perPage(),
                        'total' => $quotes->total(),
                        'last_page' => $quotes->lastPage(),
                        'from' => $quotes->firstItem(),
                        'to' => $quotes->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Quote history fetch failed', [
                'customer_id' => $validatedData['customer_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch quote history'
            ], 500);
        }
    }

    /**
     * Get competitor pricing data
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompetitorPricing(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'route' => 'required|string', // Format: "US-CA" or "DE-FR"
            'service_level' => ['required', Rule::in(['express', 'priority', 'standard', 'economy'])],
        ]);

        try {
            [$origin, $destination] = explode('-', $validatedData['route']);
            
            $benchmarking = $this->pricingService->getCompetitorBenchmarking(
                $validatedData['route'],
                $validatedData['service_level']
            );

            return response()->json([
                'success' => true,
                'data' => $benchmarking
            ]);

        } catch (\Exception $e) {
            Log::error('Competitor pricing fetch failed', [
                'route' => $validatedData['route'],
                'service_level' => $validatedData['service_level'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch competitor pricing data'
            ], 500);
        }
    }

    /**
     * Get current fuel index
     * 
     * @return JsonResponse
     */
    public function getFuelIndex(): JsonResponse
    {
        try {
            $fuelIndex = $this->pricingService->getCurrentFuelIndex();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_index' => $fuelIndex,
                    'base_index' => 100.0,
                    'surcharge_applicable' => $fuelIndex > 100.0,
                    'updated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Fuel index fetch failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch current fuel index'
            ], 500);
        }
    }

    /**
     * Get service level definitions
     * 
     * @return JsonResponse
     */
    public function getServiceLevels(): JsonResponse
    {
        try {
            $serviceLevels = config('dynamic-pricing.service_levels');
            
            return response()->json([
                'success' => true,
                'data' => $serviceLevels
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch service levels'
            ], 500);
        }
    }
}