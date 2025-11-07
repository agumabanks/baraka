<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PromotionalCampaign;
use App\Services\PromotionEngineService;
use App\Services\PromotionAnalyticsService;
use App\Services\MilestoneTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Promotion Management API Controller
 * 
 * Handles all promotion-related operations including:
 * - Promotion CRUD operations
 * - Code validation and application
 * - Integration with pricing and analytics
 * - Bulk operations and automation
 */
class PromotionController extends Controller
{
    public function __construct(
        private PromotionEngineService $promotionEngine,
        private PromotionAnalyticsService $analyticsService,
        private MilestoneTrackingService $milestoneService
    ) {}

    /**
     * Validate and apply promotional code
     */
    public function validatePromoCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|min:3|max:50',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'order_data' => 'nullable|array',
            'order_data.total_amount' => 'nullable|numeric|min:0',
            'order_data.shipping_cost' => 'nullable|numeric|min:0',
            'order_data.dimensions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $orderData = $validatedData['order_data'] ?? [];

            $result = $this->promotionEngine->validatePromotionalCode(
                $validatedData['code'],
                $validatedData['customer_id'] ?? null,
                $orderData
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => $result['valid'] ? 'Promo code is valid' : 'Invalid promo code'
            ]);

        } catch (\Exception $e) {
            Log::error('Promo code validation failed', [
                'code' => $request->input('code'),
                'customer_id' => $request->input('customer_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error validating promo code',
                'error' => $e->getMessage()
            ], 500);
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $contextData = $validatedData['context_data'] ?? [];

            $result = $this->promotionEngine->applyPromotionalDiscount(
                $validatedData['campaign_type'],
                $validatedData['amount'],
                $validatedData['customer_id'] ?? null,
                $contextData
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Promotional discount applied successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Promo discount application failed', [
                'campaign_type' => $request->input('campaign_type'),
                'amount' => $request->input('amount'),
                'customer_id' => $request->input('customer_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error applying promotional discount',
                'error' => $e->getMessage()
            ], 500);
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $shipmentData = $validatedData['shipment_data'] ?? [];

            $result = $this->promotionEngine->trackMilestoneProgress(
                $validatedData['customer_id'],
                $shipmentData
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Milestone progress tracked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Milestone tracking failed', [
                'customer_id' => $request->input('customer_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error tracking milestone progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get promotion ROI analytics
     */
    public function getPromotionROI(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'promotion_id' => 'required|integer|exists:promotional_campaigns,id',
            'timeframe' => 'nullable|string|in:7d,30d,90d,1y',
            'detailed' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            
            $result = $this->analyticsService->calculatePromotionROI(
                $validatedData['promotion_id'],
                $validatedData['timeframe'] ?? '30d',
                $validatedData['detailed'] ?? true
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Promotion ROI data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Promotion ROI retrieval failed', [
                'promotion_id' => $request->input('promotion_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving promotion ROI data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate promotion code
     */
    public function generatePromoCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template' => 'required|array',
            'template.*.type' => 'required|string|in:static,random,date,sequential',
            'template.*.value' => 'required_if:type,static|string',
            'template.*.length' => 'required_if:type,random|integer|min:3|max:20',
            'template.*.format' => 'required_if:type,date|string',
            'template.*.prefix' => 'required_if:type,sequential|string',
            'constraints' => 'nullable|array',
            'constraints.max_attempts' => 'nullable|integer|min:1|max:100',
            'constraints.unique_only' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $template = $validatedData['template'];
            $constraints = $validatedData['constraints'] ?? [];

            $code = $this->promotionEngine->generatePromotionCode($template, $constraints);

            return response()->json([
                'success' => true,
                'data' => [
                    'promo_code' => $code,
                    'template' => $template,
                    'generated_at' => now()->toISOString()
                ],
                'message' => 'Promo code generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Promo code generation failed', [
                'template' => $request->input('template'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error generating promo code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get milestone progress for customer
     */
    public function getCustomerMilestoneProgress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
            'category' => 'nullable|string|in:shipment_count,volume,revenue,tenure',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            
            $result = $this->milestoneService->getMilestoneProgress(
                $validatedData['customer_id'],
                $validatedData['category'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Milestone progress retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Customer milestone progress retrieval failed', [
                'customer_id' => $request->input('customer_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving milestone progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get milestone leaderboard
     */
    public function getMilestoneLeaderboard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|in:shipment_count,volume,revenue,tenure',
            'limit' => 'nullable|integer|min:1|max:100',
            'timeframe' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            
            $result = $this->milestoneService->getMilestoneLeaderboard(
                $validatedData['category'],
                $validatedData['limit'] ?? 10,
                $validatedData['timeframe'] ?? 30
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Milestone leaderboard retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Milestone leaderboard retrieval failed', [
                'category' => $request->input('category'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving milestone leaderboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get promotion dashboard data
     */
    public function getPromotionDashboard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $days = $validatedData['days'] ?? 7;
            
            $result = $this->analyticsService->getDashboardData($days);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Promotion dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Promotion dashboard data retrieval failed', [
                'days' => $request->input('days'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enforce promotion expiry
     */
    public function enforcePromotionExpiry(): JsonResponse
    {
        try {
            $result = $this->promotionEngine->enforcePromotionExpiry();

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Promotion expiry enforcement completed'
            ]);

        } catch (\Exception $e) {
            Log::error('Promotion expiry enforcement failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error enforcing promotion expiry',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize promotion strategy
     */
    public function optimizePromotionStrategy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_segment' => 'required|string|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            
            $result = $this->promotionEngine->optimizePromotionStrategy(
                $validatedData['customer_segment']
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Promotion strategy optimization completed'
            ]);

        } catch (\Exception $e) {
            Log::error('Promotion strategy optimization failed', [
                'customer_segment' => $request->input('customer_segment'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error optimizing promotion strategy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get promotion recommendations for customer
     */
    public function getCustomerPromotionRecommendations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            
            $result = $this->milestoneService->getMilestoneRecommendations(
                $validatedData['customer_id']
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Promotion recommendations retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Customer promotion recommendations failed', [
                'customer_id' => $request->input('customer_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving promotion recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check anti-stacking rules
     */
    public function checkAntiStackingRules(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|integer|exists:customers,id',
            'promotion_id' => 'required|integer|exists:promotional_campaigns,id',
            'order_data' => 'nullable|array',
            'order_data.total_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $promotion = PromotionalCampaign::findOrFail($validatedData['promotion_id']);
            
            $result = $this->promotionEngine->checkAntiStackingRules(
                $validatedData['customer_id'] ?? null,
                $promotion,
                $validatedData['order_data'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => $result['valid'] ? 'Promotion can be stacked' : 'Promotion stacking not allowed'
            ]);

        } catch (\Exception $e) {
            Log::error('Anti-stacking rules check failed', [
                'promotion_id' => $request->input('promotion_id'),
                'customer_id' => $request->input('customer_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking anti-stacking rules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch process milestone tracking
     */
    public function batchTrackMilestones(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $customerIds = $validatedData['customer_ids'];
            
            $result = $this->milestoneService->batchTrackMilestones($customerIds);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Batch milestone tracking completed'
            ]);

        } catch (\Exception $e) {
            Log::error('Batch milestone tracking failed', [
                'customer_count' => count($request->input('customer_ids', [])),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing batch milestone tracking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get promotion segment performance
     */
    public function getSegmentPerformance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'segment_type' => 'nullable|string|in:customer_type,region,industry,size',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            $segmentType = $validatedData['segment_type'] ?? 'customer_type';
            
            $result = $this->analyticsService->getSegmentPerformance($segmentType);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Segment performance data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Segment performance retrieval failed', [
                'segment_type' => $request->input('segment_type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving segment performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run A/B test for promotion optimization
     */
    public function runABTest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'test_name' => 'required|string|min:3|max:100',
            'variants' => 'required|array',
            'variants.traffic_allocation' => 'required|array',
            'eligibility_criteria' => 'required|array',
            'eligibility_criteria.customer_types' => 'nullable|array',
            'eligibility_criteria.min_total_spent' => 'nullable|numeric|min:0',
            'eligibility_criteria.min_shipments' => 'nullable|integer|min:0',
            'success_metric' => 'nullable|string|in:conversion_rate,avg_order_value,revenue',
            'duration_days' => 'nullable|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            
            $result = $this->analyticsService->runABTest(
                $validatedData['test_name'],
                $validatedData['variants'],
                $validatedData['eligibility_criteria'],
                $validatedData['success_metric'] ?? 'conversion_rate',
                $validatedData['duration_days'] ?? 14
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'A/B test started successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('A/B test initiation failed', [
                'test_name' => $request->input('test_name'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error starting A/B test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get A/B test results
     */
    public function getABTestResults(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'test_id' => 'required|string|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validatedData = $validator->validated();
            
            $result = $this->analyticsService->getABTestResults($validatedData['test_id']);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'A/B test results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('A/B test results retrieval failed', [
                'test_id' => $request->input('test_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving A/B test results',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}