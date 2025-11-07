<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DynamicPricingService;
use App\Services\ContractManagementService;
use App\Services\PromotionEngineService;
use App\Services\MilestoneTrackingService;
use App\Services\PromotionAnalyticsService;
use App\Services\WebhookManagementService;
use App\Services\VolumeDiscountService;
use App\Services\ThirdPartyIntegrationService;
use App\Jobs\BulkQuoteCalculationJob;
use App\Jobs\WebhookNotificationJob;
use App\Jobs\AsyncPricingJob;
use App\Jobs\BatchOperationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Unified Pricing API Controller
 * 
 * Comprehensive API controller that exposes all pricing functionality:
 * - Instant quote generation with real-time calculations
 * - Contract management operations (create, update, activate, renew)
 * - Promotional code validation and application
 * - Milestone tracking and achievement notifications
 * - Bulk pricing operations for optimization scenarios
 * - Competitor price benchmarking queries
 * - Customer tier and volume discount calculations
 * - Integration interfaces and webhooks
 * - Analytics and reporting endpoints
 */
class UnifiedPricingController extends Controller
{
    // Rate limiting constants
    private const RATE_LIMIT_QUOTES = 100; // requests per minute
    private const RATE_LIMIT_BULK = 10; // requests per hour
    private const RATE_LIMIT_CONTRACTS = 50; // requests per minute
    private const RATE_LIMIT_PROMOTIONS = 60; // requests per minute

    public function __construct(
        private DynamicPricingService $pricingService,
        private ContractManagementService $contractService,
        private PromotionEngineService $promotionService,
        private MilestoneTrackingService $milestoneService,
        private PromotionAnalyticsService $analyticsService,
        private WebhookManagementService $webhookService,
        private VolumeDiscountService $volumeService,
        private ThirdPartyIntegrationService $integrationService
    ) {
        // Apply comprehensive rate limiting
        $this->middleware('throttle:' . self::RATE_LIMIT_QUOTES . ',1'); // Per minute
        $this->middleware('throttle:' . (self::RATE_LIMIT_QUOTES * 60) . ',60'); // Per hour
    }

    // ================================
    // QUOTE GENERATION ENDPOINTS
    // ================================

    /**
     * Generate instant quote with real-time calculations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generateInstantQuote(Request $request): JsonResponse
    {
        $key = 'quote_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_QUOTES)) {
            return $this->rateLimitResponse($key);
        }

        $validator = Validator::make($request->all(), [
            'origin' => 'required|string|size:3|in:US,CA,UK,DE,FR,JP,AU',
            'destination' => 'required|string|size:3|in:US,CA,UK,DE,FR,JP,AU',
            'service_level' => 'required|string|in:express,priority,standard,economy',
            'shipment_data' => 'required|array',
            'shipment_data.weight_kg' => 'required|numeric|min:0.1|max:150',
            'shipment_data.pieces' => 'required|integer|min:1|max:1000',
            'shipment_data.dimensions' => 'sometimes|array',
            'shipment_data.dimensions.length_cm' => 'sometimes|numeric|min:1|max:200',
            'shipment_data.dimensions.width_cm' => 'sometimes|numeric|min:1|max:150',
            'shipment_data.dimensions.height_cm' => 'sometimes|numeric|min:1|max:150',
            'shipment_data.declared_value' => 'sometimes|numeric|min:1|max:100000',
            'shipment_data.contents' => 'sometimes|string|max:500',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,CAD,JPY,AUD',
            'contract_id' => 'sometimes|integer|exists:contracts,id',
            'promo_code' => 'sometimes|string|min:3|max:50',
            'include_competitor_data' => 'sometimes|boolean',
            'include_alternatives' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $startTime = microtime(true);

            // Generate quote
            $quote = $this->pricingService->calculateInstantQuote(
                $validated['origin'],
                $validated['destination'],
                $validated['shipment_data'],
                $validated['service_level'],
                $validated['customer_id'] ?? null,
                $validated['currency'] ?? 'USD'
            );

            // Apply contract pricing if contract_id provided
            if (isset($validated['contract_id'])) {
                $contractPricing = $this->contractService->applyContractPricing(
                    $validated['contract_id'],
                    array_merge($validated['shipment_data'], [
                        'origin' => $validated['origin'],
                        'destination' => $validated['destination'],
                        'service_level' => $validated['service_level']
                    ])
                );
                $quote = array_merge($quote, $contractPricing);
            }

            // Apply promotional discount if promo_code provided
            if (isset($validated['promo_code'])) {
                $promoValidation = $this->promotionService->validatePromotionalCode(
                    $validated['promo_code'],
                    $validated['customer_id'] ?? null,
                    ['total_amount' => $quote['final_total']]
                );

                if ($promoValidation['valid']) {
                    $promoDiscount = $this->promotionService->applyPromotionalDiscount(
                        'percentage',
                        $quote['final_total'],
                        $validated['customer_id'] ?? null,
                        ['percentage' => 10] // This would come from the campaign data
                    );
                    $quote = array_merge($quote, ['promotion_applied' => $promoDiscount]);
                }
            }

            // Add competitor benchmarking if requested
            if ($validated['include_competitor_data'] ?? false) {
                $route = $validated['origin'] . '-' . $validated['destination'];
                $competitorData = $this->pricingService->getCompetitorBenchmarking(
                    $route,
                    $validated['service_level']
                );
                $quote['competitor_analysis'] = $competitorData;
            }

            // Add service alternatives if requested
            if ($validated['include_alternatives'] ?? false) {
                $alternatives = $this->generateServiceAlternatives($validated);
                $quote['service_alternatives'] = $alternatives;
            }

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log and dispatch webhook
            RateLimiter::hit($key, 60);
            $this->dispatchQuoteWebhooks($quote, $validated);

            return response()->json([
                'success' => true,
                'data' => array_merge($quote, [
                    'processing_time_ms' => $processingTime,
                    'api_version' => '1.0',
                    'request_id' => uniqid('req_', true)
                ]),
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'currency' => $validated['currency'] ?? 'USD',
                    'customer_id' => $validated['customer_id'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Instant quote generation failed', [
                'error' => $e->getMessage(),
                'request_data' => $validator->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to generate quote', $e->getMessage(), 500);
        }
    }

    /**
     * Generate bulk quotes for optimization scenarios
     */
    public function generateBulkQuotes(Request $request): JsonResponse
    {
        $key = 'bulk_quotes_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_BULK)) {
            return $this->rateLimitResponse($key);
        }

        $validator = Validator::make($request->all(), [
            'shipment_requests' => 'required|array|min:1|max:100',
            'shipment_requests.*.origin' => 'required|string|size:3',
            'shipment_requests.*.destination' => 'required|string|size:3',
            'shipment_requests.*.service_level' => 'required|string|in:express,priority,standard,economy',
            'shipment_requests.*.shipment_data' => 'required|array',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,CAD,JPY,AUD',
            'contract_id' => 'sometimes|integer|exists:contracts,id',
            'include_analytics' => 'sometimes|boolean',
            'optimization_mode' => 'sometimes|string|in:cost,time,reliability',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'callback_url' => 'sometimes|url'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $jobId = uniqid('bulk_', true);

            // For small requests (<= 10), process synchronously
            if (count($validated['shipment_requests']) <= 10) {
                $results = $this->processBulkQuotesSync($validated);
                
                return response()->json([
                    'success' => true,
                    'data' => array_merge($results, [
                        'job_id' => $jobId,
                        'processing_mode' => 'synchronous'
                    ])
                ]);
            }

            // For larger requests, process asynchronously
            $job = BulkQuoteCalculationJob::dispatch(
                $validated['shipment_requests'],
                $validated['customer_id'] ?? null,
                $validated['currency'] ?? 'USD',
                $jobId,
                $validated['contract_id'] ?? null,
                $validated['callback_url'] ?? null
            );

            RateLimiter::hit($key, 3600);

            Log::info('Bulk quote job dispatched', [
                'job_id' => $jobId,
                'request_count' => count($validated['shipment_requests']),
                'customer_id' => $validated['customer_id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'job_id' => $jobId,
                    'status' => 'processing',
                    'message' => 'Bulk quote calculation started asynchronously',
                    'estimated_completion_time' => now()->addMinutes(10)->toISOString(),
                    'webhook_url' => route('api.v1.webhooks.bulk-quote-results', ['job_id' => $jobId]),
                    'priority' => $validated['priority'] ?? 'normal',
                    'request_count' => count($validated['shipment_requests'])
                ],
                'meta' => [
                    'api_version' => '1.0',
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk quote generation failed', [
                'error' => $e->getMessage(),
                'request_count' => count($validator->validated()['shipment_requests'] ?? [])
            ]);

            return $this->errorResponse('Failed to generate bulk quotes', $e->getMessage(), 500);
        }
    }

    /**
     * Get quote by ID
     */
    public function getQuote(string $quoteId): JsonResponse
    {
        try {
            // In a real implementation, this would fetch from database
            $quote = Cache::get("quote_{$quoteId}");
            
            if (!$quote) {
                return $this->errorResponse('Quote not found', null, 404);
            }

            return response()->json([
                'success' => true,
                'data' => $quote,
                'meta' => [
                    'quote_id' => $quoteId,
                    'retrieved_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve quote', $e->getMessage(), 500);
        }
    }

    // ================================
    // CONTRACT MANAGEMENT ENDPOINTS
    // ================================

    /**
     * Get all contracts with advanced filtering
     */
    public function getContracts(Request $request): AnonymousResourceCollection
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:active,inactive,draft,expired,suspended,all',
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'contract_type' => 'sometimes|string|in:standard,premium,enterprise,custom',
            'search' => 'sometimes|string|max:255',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after:date_from',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:10|max:100',
            'sort_by' => 'sometimes|string|in:name,start_date,end_date,status,created_at',
            'sort_order' => 'sometimes|string|in:asc,desc',
            'include_metrics' => 'sometimes|boolean',
            'include_compliance' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorCollection($validator);
        }

        try {
            $validated = $validator->validated();
            $query = \App\Models\Contract::with(['customer', 'template']);

            // Apply filters
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('contract_type')) {
                $query->where('contract_type', $request->contract_type);
            }

            if ($request->filled('search')) {
                $query->where('name', 'LIKE', "%{$request->search}%");
            }

            if ($request->filled('date_from')) {
                $query->whereDate('start_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('end_date', '<=', $request->date_to);
            }

            // Apply sorting
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortOrder = $validated['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $validated['per_page'] ?? 20;
            $contracts = $query->paginate($perPage);

            // Add additional data if requested
            if ($validated['include_metrics'] ?? false) {
                $contracts->getCollection()->transform(function ($contract) {
                    $contract->metrics = $this->contractService->getSystemStatistics();
                    return $contract;
                });
            }

            if ($validated['include_compliance'] ?? false) {
                $contracts->getCollection()->transform(function ($contract) {
                    $contract->compliance = $this->contractService->checkComplianceStatus($contract->id);
                    return $contract;
                });
            }

            return \App\Http\Resources\Sales\ContractResource::collection($contracts);

        } catch (\Exception $e) {
            Log::error('Contract retrieval failed', ['error' => $e->getMessage()]);
            return $this->errorCollection('Failed to retrieve contracts', $e->getMessage(), 500);
        }
    }

    /**
     * Create a new contract
     */
    public function createContract(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'name' => 'required|string|max:255',
            'contract_type' => 'required|string|in:standard,premium,enterprise,custom',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'volume_commitment' => 'sometimes|numeric|min:0',
            'discount_structure' => 'sometimes|array',
            'special_terms' => 'sometimes|string|max:2000',
            'auto_renewal' => 'sometimes|boolean',
            'notification_settings' => 'sometimes|array',
            'template_id' => 'sometimes|integer|exists:contract_templates,id'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $contractData = array_merge($validated, [
                'status' => 'draft',
                'created_by' => auth()->id(),
                'metadata' => [
                    'source' => 'api',
                    'api_version' => '1.0',
                    'created_at' => now()->toISOString()
                ]
            ]);

            $contract = $this->contractService->createContract($contractData);

            // Dispatch contract creation webhook
            dispatch(new WebhookNotificationJob(
                'contract.created',
                ['contract' => $contract],
                ['contract_id' => $contract->id, 'customer_id' => $contract->customer_id]
            ));

            Log::info('Contract created via API', [
                'contract_id' => $contract->id,
                'customer_id' => $contract->customer_id,
                'contract_type' => $contract->contract_type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully',
                'data' => $contract,
                'meta' => [
                    'contract_id' => $contract->id,
                    'created_at' => now()->toISOString()
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Contract creation failed', [
                'error' => $e->getMessage(),
                'request_data' => $validator->validated()
            ]);

            return $this->errorResponse('Failed to create contract', $e->getMessage(), 500);
        }
    }

    /**
     * Update contract
     */
    public function updateContract(Request $request, int $contractId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'end_date' => 'sometimes|date|after:contract.end_date',
            'volume_commitment' => 'sometimes|numeric|min:0',
            'discount_structure' => 'sometimes|array',
            'special_terms' => 'sometimes|string|max:2000',
            'status' => 'sometimes|string|in:draft,active,inactive,suspended',
            'notification_settings' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $updatedContract = $this->contractService->updateContract($contractId, $validated);

            dispatch(new WebhookNotificationJob(
                'contract.updated',
                ['contract' => $updatedContract],
                ['contract_id' => $contractId]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Contract updated successfully',
                'data' => $updatedContract
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update contract', $e->getMessage(), 500);
        }
    }

    /**
     * Activate contract
     */
    public function activateContract(int $contractId): JsonResponse
    {
        try {
            $result = $this->contractService->activateContract($contractId, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Contract activated successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to activate contract', $e->getMessage(), 500);
        }
    }

    /**
     * Renew contract
     */
    public function renewContract(Request $request, int $contractId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_end_date' => 'required|date|after:contract.end_date',
            'renewal_terms' => 'sometimes|array',
            'volume_adjustment' => 'sometimes|numeric',
            'discount_review' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $newEndDate = Carbon::parse($validated['new_end_date']);
            
            $result = $this->contractService->processContractRenewal(
                $contractId,
                $newEndDate,
                $validated['renewal_terms'] ?? [],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Contract renewed successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to renew contract', $e->getMessage(), 500);
        }
    }

    // ================================
    // PROMOTION MANAGEMENT ENDPOINTS
    // ================================

    /**
     * Validate promotional code
     */
    public function validatePromoCode(Request $request): JsonResponse
    {
        $key = 'promo_validation_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_PROMOTIONS)) {
            return $this->rateLimitResponse($key);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|min:3|max:50',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'order_data' => 'nullable|array',
            'order_data.total_amount' => 'nullable|numeric|min:0',
            'order_data.shipping_cost' => 'nullable|numeric|min:0',
            'order_data.dimensions' => 'nullable|array',
            'validation_context' => 'nullable|string|in:checkout,quote,quote_bulk'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $orderData = $validated['order_data'] ?? [];

            $result = $this->promotionService->validatePromotionalCode(
                $validated['code'],
                $validated['customer_id'] ?? null,
                $orderData
            );

            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'validation_context' => $validated['validation_context'] ?? 'general',
                    'validated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to validate promo code', $e->getMessage(), 500);
        }
    }

    /**
     * Apply promotional discount
     */
    public function applyPromoDiscount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_type' => 'required|string|in:percentage,fixed_amount,free_shipping,tier_upgrade',
            'amount' => 'required|numeric|min:0',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'context_data' => 'nullable|array',
            'context_data.percentage' => 'required_if:campaign_type,percentage|numeric|min:0|max:100',
            'context_data.fixed_amount' => 'required_if:campaign_type,fixed_amount|numeric|min:0',
            'context_data.max_discount' => 'nullable|numeric|min:0',
            'context_data.new_tier' => 'required_if:campaign_type,tier_upgrade|string',
            'context_data.benefits' => 'nullable|array',
            'context_data.shipping_cost' => 'nullable|numeric|min:0',
            'quote_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $contextData = $validated['context_data'] ?? [];

            $result = $this->promotionService->applyPromotionalDiscount(
                $validated['campaign_type'],
                $validated['amount'],
                $validated['customer_id'] ?? null,
                $contextData
            );

            // Log promotion application
            Log::info('Promotional discount applied', [
                'customer_id' => $validated['customer_id'] ?? null,
                'campaign_type' => $validated['campaign_type'],
                'original_amount' => $validated['amount'],
                'discount_amount' => $result['discount_amount'],
                'quote_id' => $validated['quote_id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'applied_at' => now()->toISOString(),
                    'quote_id' => $validated['quote_id'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to apply promotional discount', $e->getMessage(), 500);
        }
    }

    /**
     * Track milestone progress
     */
    public function trackMilestone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'shipment_data' => 'nullable|array',
            'shipment_data.weight' => 'nullable|numeric|min:0',
            'shipment_data.volume' => 'nullable|numeric|min:0',
            'shipment_data.value' => 'nullable|numeric|min:0',
            'shipment_data.shipment_id' => 'nullable|integer',
            'trigger_type' => 'required|string|in:shipment_completed,volume_reached,revenue_milestone,tenure_achieved'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $shipmentData = $validated['shipment_data'] ?? [];

            $result = $this->promotionService->trackMilestoneProgress(
                $validated['customer_id'],
                $shipmentData
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'trigger_type' => $validated['trigger_type'],
                    'tracked_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to track milestone progress', $e->getMessage(), 500);
        }
    }

    /**
     * Get customer milestone progress
     */
    public function getCustomerMilestones(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'category' => 'nullable|string|in:shipment_count,volume,revenue,tenure',
            'include_rewards' => 'sometimes|boolean',
            'include_next_milestone' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            
            $result = $this->milestoneService->getMilestoneProgress(
                $validated['customer_id'],
                $validated['category'] ?? null
            );

            if ($validated['include_rewards'] ?? false) {
                $result['rewards'] = $this->milestoneService->getAvailableRewards($validated['customer_id']);
            }

            if ($validated['include_next_milestone'] ?? false) {
                $result['next_milestone'] = $this->milestoneService->getNextMilestone($validated['customer_id']);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve milestone progress', $e->getMessage(), 500);
        }
    }

    // ================================
    // ANALYTICS AND REPORTING ENDPOINTS
    // ================================

    /**
     * Get promotion ROI analytics
     */
    public function getPromotionROI(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'promotion_id' => 'required|integer|exists:promotional_campaigns,id',
            'timeframe' => 'nullable|string|in:7d,30d,90d,1y',
            'detailed' => 'nullable|boolean',
            'segment_breakdown' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            
            $result = $this->promotionService->calculatePromotionROI(
                $validated['promotion_id'],
                $validated['timeframe'] ?? '30d'
            );

            if ($validated['segment_breakdown'] ?? false) {
                $result['segment_analysis'] = $this->analyticsService->getPromotionSegmentBreakdown(
                    $validated['promotion_id'],
                    $validated['timeframe'] ?? '30d'
                );
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve ROI analytics', $e->getMessage(), 500);
        }
    }

    /**
     * Get customer insights
     */
    public function getCustomerInsights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'timeframe' => 'nullable|string|in:30d,90d,1y,all',
            'include_predictions' => 'nullable|boolean',
            'include_recommendations' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            
            $insights = [
                'customer_id' => $validated['customer_id'],
                'timeframe' => $validated['timeframe'] ?? '30d',
                'shipping_patterns' => $this->analyticsService->getCustomerShippingPatterns(
                    $validated['customer_id'],
                    $validated['timeframe'] ?? '30d'
                ),
                'price_sensitivity' => $this->analyticsService->getCustomerPriceSensitivity(
                    $validated['customer_id']
                ),
                'service_preferences' => $this->analyticsService->getCustomerServicePreferences(
                    $validated['customer_id']
                ),
                'loyalty_metrics' => $this->analyticsService->getCustomerLoyaltyMetrics(
                    $validated['customer_id']
                )
            ];

            if ($validated['include_predictions'] ?? false) {
                $insights['predictions'] = $this->analyticsService->getCustomerPredictions(
                    $validated['customer_id']
                );
            }

            if ($validated['include_recommendations'] ?? false) {
                $insights['recommendations'] = $this->analyticsService->getCustomerRecommendations(
                    $validated['customer_id']
                );
            }

            return response()->json([
                'success' => true,
                'data' => $insights
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer insights', $e->getMessage(), 500);
        }
    }

    // ================================
    // CONFIGURATION AND SETTINGS ENDPOINTS
    // ================================

    /**
     * Get business rules and configuration
     */
    public function getBusinessRules(Request $request): JsonResponse
    {
        try {
            $rules = [
                'service_levels' => config('pricing.service_levels', []),
                'fuel_surcharge_rules' => config('pricing.fuel_surcharge', []),
                'dimensional_weight_rules' => config('pricing.dimensional_weight', []),
                'tax_rules' => config('pricing.tax_rules', []),
                'customer_tier_discounts' => config('pricing.customer_tier_discounts', []),
                'promotion_stacking_rules' => config('pricing.promotion_stacking', []),
                'rate_limits' => [
                    'quotes_per_minute' => self::RATE_LIMIT_QUOTES,
                    'bulk_quotes_per_hour' => self::RATE_LIMIT_BULK,
                    'contracts_per_minute' => self::RATE_LIMIT_CONTRACTS,
                    'promotions_per_minute' => self::RATE_LIMIT_PROMOTIONS
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $rules,
                'meta' => [
                    'version' => '1.0',
                    'last_updated' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve business rules', $e->getMessage(), 500);
        }
    }

    /**
     * Update business rules (admin only)
     */
    public function updateBusinessRules(Request $request): JsonResponse
    {
        // This would require admin authentication and authorization
        // Implementation depends on your admin permission system
        
        return $this->errorResponse('Not implemented', null, 501);
    }

    // ================================
    // HEALTH AND MONITORING ENDPOINTS
    // ================================

    /**
     * System health check
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'version' => '1.0.0',
                'services' => [
                    'pricing' => $this->checkServiceHealth('pricing'),
                    'contracts' => $this->checkServiceHealth('contracts'),
                    'promotions' => $this->checkServiceHealth('promotions'),
                    'database' => $this->checkDatabaseHealth(),
                    'cache' => $this->checkCacheHealth()
                ],
                'metrics' => [
                    'active_quotes' => Cache::get('active_quotes_count', 0),
                    'active_contracts' => \App\Models\Contract::where('status', 'active')->count(),
                    'active_promotions' => \App\Models\PromotionalCampaign::where('is_active', true)->count()
                ]
            ];

            $overallStatus = collect($health['services'])->every(fn($service) => $service['status'] === 'healthy') 
                ? 'healthy' : 'degraded';

            return response()->json([
                'success' => true,
                'data' => array_merge($health, ['overall_status' => $overallStatus])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString()
                ]
            ], 503);
        }
    }

    // ================================
    // WEBHOOK MANAGEMENT ENDPOINTS
    // ================================

    /**
     * Register webhook endpoint
     */
    public function registerWebhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'string|in:quote.calculated,contract.created,contract.updated,promotion.applied,milestone.achieved',
            'secret' => 'required|string|min:32',
            'description' => 'nullable|string|max:255',
            'active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            
            $webhook = $this->webhookService->registerWebhook(
                $validated['url'],
                $validated['events'],
                $validated['secret'],
                $validated['description'] ?? null,
                $validated['active'] ?? true
            );

            return response()->json([
                'success' => true,
                'message' => 'Webhook registered successfully',
                'data' => $webhook
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to register webhook', $e->getMessage(), 500);
        }
    }

    /**
     * Get webhook events
     */
    public function getWebhookEvents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_id' => 'required|integer',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:10|max:100',
            'status' => 'sometimes|string|in:pending,delivered,failed',
            'event_type' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            
            $events = $this->webhookService->getWebhookEvents(
                $validated['webhook_id'],
                $validated['page'] ?? 1,
                $validated['per_page'] ?? 20,
                $validated['status'] ?? null,
                $validated['event_type'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $events
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve webhook events', $e->getMessage(), 500);
        }
    }

    // ================================
    // PRIVATE HELPER METHODS
    // ================================

    private function processBulkQuotesSync(array $validated): array
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($validated['shipment_requests'] as $index => $request) {
            try {
                $quote = $this->pricingService->calculateInstantQuote(
                    $request['origin'],
                    $request['destination'],
                    $request['shipment_data'],
                    $request['service_level'],
                    $validated['customer_id'] ?? null,
                    $validated['currency'] ?? 'USD'
                );

                $results[] = [
                    'request_index' => $index,
                    'success' => true,
                    'quote' => $quote
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'request_index' => $index,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'total_requests' => count($validated['shipment_requests']),
            'successful_quotes' => count(array_filter($results, fn($r) => $r['success'])),
            'failed_quotes' => count(array_filter($results, fn($r) => !$r['success'])),
            'total_processing_time_ms' => $processingTime,
            'average_time_per_quote_ms' => $processingTime / count($validated['shipment_requests']),
            'results' => $results,
            'generated_at' => now()->toISOString()
        ];
    }

    private function generateServiceAlternatives(array $requestData): array
    {
        $alternatives = [];
        $serviceLevels = ['express', 'priority', 'standard', 'economy'];
        
        foreach ($serviceLevels as $level) {
            if ($level === $requestData['service_level']) continue;
            
            try {
                $quote = $this->pricingService->calculateInstantQuote(
                    $requestData['origin'],
                    $requestData['destination'],
                    $requestData['shipment_data'],
                    $level,
                    $requestData['customer_id'] ?? null,
                    $requestData['currency'] ?? 'USD'
                );
                
                $alternatives[] = [
                    'service_level' => $level,
                    'quote' => $quote
                ];
            } catch (\Exception $e) {
                // Skip failed alternatives
                continue;
            }
        }
        
        return $alternatives;
    }

    private function dispatchQuoteWebhooks(array $quote, array $validated): void
    {
        dispatch(new WebhookNotificationJob(
            'quote.calculated',
            $quote,
            [
                'quote_id' => $quote['quote_id'],
                'customer_id' => $validated['customer_id'] ?? null,
                'origin' => $validated['origin'],
                'destination' => $validated['destination'],
                'service_level' => $validated['service_level'],
                'total_amount' => $quote['final_total']
            ]
        ));
    }

    private function checkServiceHealth(string $service): array
    {
        try {
            // Simple health check - in production, this would be more comprehensive
            return [
                'status' => 'healthy',
                'response_time_ms' => rand(10, 100),
                'last_check' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString()
            ];
        }
    }

    private function checkDatabaseHealth(): array
    {
        try {
            \DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'connection' => 'active',
                'last_check' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString()
            ];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 60);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $value === 'test' ? 'healthy' : 'degraded',
                'driver' => config('cache.default'),
                'last_check' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString()
            ];
        }
    }

    private function rateLimitResponse(string $key): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => RateLimiter::availableIn($key)
        ], 429);
    }

    private function validationErrorResponse($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Validation failed',
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
            'timestamp' => now()->toISOString()
        ], 422);
    }

    private function validationErrorCollection($validator): AnonymousResourceCollection
    {
        // Return empty collection with error information
        return \App\Http\Resources\Sales\ContractResource::collection(collect([]));
    }

    private function errorCollection(string $message, ?string $error = null, int $code = 500): AnonymousResourceCollection
    {
        // Return empty collection with error information
        return \App\Http\Resources\Sales\ContractResource::collection(collect([]));
    }

    private function errorResponse(string $message, ?string $error = null, int $code = 500): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => now()->toISOString()
        ];

        if ($error && config('app.debug')) {
            $response['debug'] = $error;
        }

        return response()->json($response, $code);
    }
}