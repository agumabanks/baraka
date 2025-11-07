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
use App\Services\ThirdPartyIntegrationService as IntegrationService;
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
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Integration Interfaces for Third-Party Systems
 * 
 * Provides interfaces for carrier integration, partner systems,
 * customer portal APIs, and marketplace connectors.
 */
class IntegrationInterfacesController extends Controller
{
    public function __construct(
        private ThirdPartyIntegrationService $integrationService,
        private DynamicPricingService $pricingService,
        private ContractManagementService $contractService,
        private WebhookManagementService $webhookService
    ) {}

    // ================================
    // CARRIER INTEGRATION ENDPOINTS
    // ================================

    /**
     * Get real-time rates from multiple carriers
     */
    public function getCarrierRates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_data' => 'required|array',
            'shipment_data.origin' => 'required|string|size:3',
            'shipment_data.destination' => 'required|string|size:3',
            'shipment_data.weight' => 'required|numeric|min:0.1',
            'shipment_data.dimensions' => 'sometimes|array',
            'carriers' => 'sometimes|array',
            'carriers.*' => 'string|in:fedex,ups,dhl,usps,canada_post,royal_mail',
            'service_levels' => 'sometimes|array',
            'service_levels.*' => 'string|in:express,priority,standard,economy',
            'include_transit_times' => 'sometimes|boolean',
            'include_delivery_dates' => 'sometimes|boolean',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,CAD'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            $shipmentData = $validated['shipment_data'];
            
            $carrierRates = $this->integrationService->getMultipleCarrierRates([
                'origin' => $shipmentData['origin'],
                'destination' => $shipmentData['destination'],
                'weight' => $shipmentData['weight'],
                'dimensions' => $shipmentData['dimensions'] ?? null,
                'carriers' => $validated['carriers'] ?? null,
                'service_levels' => $validated['service_levels'] ?? null,
                'currency' => $validated['currency'] ?? 'USD'
            ]);

            $response = [
                'success' => true,
                'data' => $carrierRates,
                'meta' => [
                    'carriers_queried' => count($carrierRates),
                    'query_time_ms' => $carrierRates['query_time_ms'] ?? 0,
                    'timestamp' => now()->toISOString()
                ]
            ];

            if ($validated['include_transit_times'] ?? false) {
                $response['data']['transit_times'] = $this->integrationService->getTransitTimeEstimates(
                    $shipmentData['origin'],
                    $shipmentData['destination'],
                    $validated['carriers'] ?? []
                );
            }

            if ($validated['include_delivery_dates'] ?? false) {
                $response['data']['delivery_estimates'] = $this->integrationService->getDeliveryEstimates(
                    $shipmentData['origin'],
                    $shipmentData['destination'],
                    $validated['carriers'] ?? []
                );
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Carrier rate integration failed', [
                'error' => $e->getMessage(),
                'request_data' => $validator->validated()
            ]);

            return $this->errorResponse('Failed to retrieve carrier rates', $e->getMessage(), 500);
        }
    }

    /**
     * Track shipment across multiple carriers
     */
    public function trackShipment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tracking_numbers' => 'required|array|min:1|max:50',
            'tracking_numbers.*' => 'string|min:8|max:35',
            'carrier' => 'required|string|in:fedex,ups,dhl,usps,canada_post,royal_mail',
            'include_events' => 'sometimes|boolean',
            'include_delivery_info' => 'sometimes|boolean',
            'language' => 'sometimes|string|size:2|in:en,es,fr,de'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            
            $trackingResults = [];
            foreach ($validated['tracking_numbers'] as $trackingNumber) {
                $trackingResults[] = $this->integrationService->trackShipment(
                    $trackingNumber,
                    $validated['carrier'],
                    $validated['include_events'] ?? false,
                    $validated['language'] ?? 'en'
                );
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'carrier' => $validated['carrier'],
                    'tracking_results' => $trackingResults,
                    'total_tracked' => count($trackingResults)
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'language' => $validated['language'] ?? 'en'
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to track shipment', $e->getMessage(), 500);
        }
    }

    // ================================
    // PARTNER SYSTEM INTEGRATION
    // ================================

    /**
     * Sync data with partner systems
     */
    public function syncPartnerData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'partner_type' => 'required|string|in:marketplace,erp,crm,warehouse,wms',
            'data_type' => 'required|string|in:orders,customers,products,inventory,pricing',
            'sync_direction' => 'required|string|in:push,pull,bidirectional',
            'filters' => 'sometimes|array',
            'batch_id' => 'sometimes|string|max:100',
            'callback_url' => 'sometimes|url'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            
            $syncJob = $this->integrationService->syncWithPartner([
                'partner_type' => $validated['partner_type'],
                'data_type' => $validated['data_type'],
                'sync_direction' => $validated['sync_direction'],
                'filters' => $validated['filters'] ?? [],
                'batch_id' => $validated['batch_id'] ?? uniqid('sync_', true),
                'callback_url' => $validated['callback_url'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'sync_job_id' => $syncJob['job_id'],
                    'status' => 'initiated',
                    'estimated_completion' => $syncJob['estimated_completion'],
                    'partner_type' => $validated['partner_type'],
                    'data_type' => $validated['data_type']
                ],
                'meta' => [
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to sync partner data', $e->getMessage(), 500);
        }
    }

    /**
     * Get marketplace connectors status
     */
    public function getMarketplaceConnectors(): JsonResponse
    {
        try {
            $connectors = $this->integrationService->getMarketplaceConnectors();
            
            return response()->json([
                'success' => true,
                'data' => $connectors
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve marketplace connectors', $e->getMessage(), 500);
        }
    }

    // ================================
    // EDI INTEGRATION
    // ================================

    /**
     * Submit EDI request
     */
    public function submitEDIRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'edi_type' => 'required|string|in:850,855,856,810,820,997,214',
            'partner_id' => 'required|integer|exists:edi_partners,id',
            'document_data' => 'required|array',
            'document_data.*' => 'required',
            'version' => 'sometimes|string|in:4010,5010,6020',
            'encoding' => 'sometimes|string|in:ASCII,UTF-8',
            'compression' => 'sometimes|boolean',
            'callback_url' => 'sometimes|url'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $validated = $validator->validated();
            
            $ediSubmission = $this->integrationService->submitEDIRequest([
                'edi_type' => $validated['edi_type'],
                'partner_id' => $validated['partner_id'],
                'document_data' => $validated['document_data'],
                'version' => $validated['version'] ?? '5010',
                'encoding' => $validated['encoding'] ?? 'UTF-8',
                'compression' => $validated['compression'] ?? false,
                'callback_url' => $validated['callback_url'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => $ediSubmission
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to submit EDI request', $e->getMessage(), 500);
        }
    }

    // ================================
    // CUSTOMER PORTAL INTEGRATION
    // ================================

    /**
     * Customer portal quote generation
     */
    public function customerPortalQuote(Request $request): JsonResponse
    {
        // Uses the same validation as instant quote but with customer context
        $quoteRequest = new \App\Http\Requests\Pricing\InstantQuoteRequest();
        if (!$quoteRequest->validate()) {
            return $this->validationErrorResponse($quoteRequest->getValidator());
        }

        try {
            $validated = $quoteRequest->validated();
            $customer = auth('customer')->user();

            // Add customer context to the request
            $validated['customer_id'] = $customer->id ?? null;
            $validated['source'] = 'customer_portal';

            $quote = $this->pricingService->calculateInstantQuote(
                $validated['origin'],
                $validated['destination'],
                $validated['shipment_data'],
                $validated['service_level'],
                $validated['customer_id'],
                $validated['currency']
            );

            return response()->json([
                'success' => true,
                'data' => $quote
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate customer portal quote', $e->getMessage(), 500);
        }
    }

    /**
     * Get customer recommendations
     */
    public function getCustomerRecommendations(Request $request): JsonResponse
    {
        try {
            $customer = auth('customer')->user();
            $recommendations = $this->integrationService->getCustomerRecommendations($customer->id);
            
            return response()->json([
                'success' => true,
                'data' => $recommendations
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get customer recommendations', $e->getMessage(), 500);
        }
    }

    // ================================
    // INTEGRATION STATUS AND MONITORING
    // ================================

    /**
     * Get integration status
     */
    public function getIntegrationStatus(): JsonResponse
    {
        try {
            $status = $this->integrationService->getSystemStatus();
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get integration status', $e->getMessage(), 500);
        }
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(int $webhookId): JsonResponse
    {
        try {
            $testResult = $this->webhookService->testWebhook($webhookId);
            
            return response()->json([
                'success' => true,
                'data' => $testResult
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Webhook test failed', $e->getMessage(), 500);
        }
    }

    /**
     * Unregister webhook
     */
    public function unregisterWebhook(int $webhookId): JsonResponse
    {
        try {
            $this->webhookService->unregisterWebhook($webhookId);
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook unregistered successfully'
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to unregister webhook', $e->getMessage(), 500);
        }
    }

    // ================================
    // HELPER METHODS
    // ================================

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