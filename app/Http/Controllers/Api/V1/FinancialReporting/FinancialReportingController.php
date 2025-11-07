<?php

namespace App\Http\Controllers\Api\V1\FinancialReporting;

use App\Http\Controllers\Controller;
use App\Services\FinancialReporting\RevenueRecognitionService;
use App\Services\FinancialReporting\COGSAnalysisService;
use App\Services\FinancialReporting\GrossMarginAnalysisService;
use App\Services\FinancialReporting\CODCollectionService;
use App\Services\FinancialReporting\PaymentProcessingService;
use App\Services\FinancialReporting\ProfitabilityAnalysisService;
use App\Services\FinancialReporting\ExportService;
use App\Services\FinancialReporting\AccountingIntegrationService;
use App\Services\FinancialReporting\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class FinancialReportingController extends Controller
{
    public function __construct(
        private RevenueRecognitionService $revenueRecognitionService,
        private COGSAnalysisService $cogsAnalysisService,
        private GrossMarginAnalysisService $grossMarginAnalysisService,
        private CODCollectionService $codCollectionService,
        private PaymentProcessingService $paymentProcessingService,
        private ProfitabilityAnalysisService $profitabilityAnalysisService,
        private ExportService $exportService,
        private AccountingIntegrationService $accountingIntegrationService,
        private AuditTrailService $auditTrailService
    ) {}

    // ===========================================
    // REVENUE RECOGNITION ENDPOINTS
    // ===========================================

    /**
     * Get revenue recognition with accrual calculations
     */
    public function getRevenueRecognition(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'filters' => 'array',
                'filters.client_key' => 'string',
                'filters.route_key' => 'string',
                'filters.service_type' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $dateRange = $request->input('date_range');

            $revenueData = $this->revenueRecognitionService->analyzeRevenueRecognition($dateRange, $filters);

            return response()->json([
                'success' => true,
                'data' => $revenueData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Revenue recognition API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving revenue recognition data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue forecasting and trending
     */
    public function getRevenueForecasting(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'string|in:hourly,daily,weekly,monthly',
                'forecast_periods' => 'integer|min:1|max:52',
                'confidence_level' => 'integer|min:80|max:99'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $period = $request->input('period', 'monthly');
            $forecastPeriods = $request->input('forecast_periods', 12);
            $confidenceLevel = $request->input('confidence_level', 95);

            $forecastingData = $this->revenueRecognitionService->forecastRevenue(
                $period, 
                $forecastPeriods, 
                $confidenceLevel
            );

            return response()->json([
                'success' => true,
                'data' => $forecastingData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Revenue forecasting API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating revenue forecast',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get deferred revenue tracking
     */
    public function getDeferredRevenue(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'filters' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range');
            $filters = $request->input('filters', []);

            $deferredRevenueData = $this->revenueRecognitionService->trackDeferredRevenue($dateRange, $filters);

            return response()->json([
                'success' => true,
                'data' => $deferredRevenueData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Deferred revenue API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving deferred revenue data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // COGS ANALYSIS ENDPOINTS
    // ===========================================

    /**
     * Get COGS breakdown with variance analysis
     */
    public function getCOGSAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'filters' => 'array',
                'filters.cost_category' => 'string|in:fuel,labor,insurance,maintenance,depreciation',
                'filters.client_key' => 'string',
                'filters.route_key' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range');
            $filters = $request->input('filters', []);

            $cogsData = $this->cogsAnalysisService->analyzeCOGS($dateRange, $filters);

            return response()->json([
                'success' => true,
                'data' => $cogsData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('COGS analysis API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving COGS analysis data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cost variance analysis (actual vs budgeted)
     */
    public function getCostVarianceAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'filters' => 'array',
                'filters.dimension' => 'string|in:route,client,service_type,branch'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range');
            $filters = $request->input('filters', []);
            $dimension = $filters['dimension'] ?? 'route';

            $varianceData = $this->cogsAnalysisService->performVarianceAnalysis($dateRange, $dimension);

            return response()->json([
                'success' => true,
                'data' => $varianceData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Cost variance analysis API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing cost variance analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // GROSS MARGIN ANALYSIS ENDPOINTS
    // ===========================================

    /**
     * Get gross margin analysis with trending and forecasting
     */
    public function getGrossMarginAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'filters' => 'array',
                'filters.segment' => 'string|in:customer,route,service_type,branch',
                'filters.include_forecasting' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range');
            $filters = $request->input('filters', []);
            $includeForecasting = $filters['include_forecasting'] ?? true;

            $marginData = $this->grossMarginAnalysisService->analyzeGrossMargin(
                $dateRange, 
                $filters, 
                $includeForecasting
            );

            return response()->json([
                'success' => true,
                'data' => $marginData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Gross margin analysis API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving gross margin analysis data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get margin forecasting with predictive analytics
     */
    public function getMarginForecasting(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'forecast_periods' => 'integer|min:1|max:24',
                'forecast_method' => 'string|in:linear,exponential,seasonal,machine_learning',
                'confidence_level' => 'integer|min:80|max:99'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $forecastPeriods = $request->input('forecast_periods', 12);
            $forecastMethod = $request->input('forecast_method', 'seasonal');
            $confidenceLevel = $request->input('confidence_level', 95);

            $forecastingData = $this->grossMarginAnalysisService->forecastMargin(
                $forecastPeriods,
                $forecastMethod,
                $confidenceLevel
            );

            return response()->json([
                'success' => true,
                'data' => $forecastingData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Margin forecasting API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating margin forecast',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get competitive margin benchmarking
     */
    public function getCompetitiveBenchmarking(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'benchmark_type' => 'string|in:industry,regional,national,custom',
                'comparison_period' => 'string|in:quarterly,annually',
                'margin_components' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $benchmarkType = $request->input('benchmark_type', 'industry');
            $comparisonPeriod = $request->input('comparison_period', 'quarterly');
            $marginComponents = $request->input('margin_components', []);

            $benchmarkingData = $this->grossMarginAnalysisService->performBenchmarking(
                $benchmarkType,
                $comparisonPeriod,
                $marginComponents
            );

            return response()->json([
                'success' => true,
                'data' => $benchmarkingData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Competitive benchmarking API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving competitive benchmarking data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // COD COLLECTION ENDPOINTS
    // ===========================================

    /**
     * Get COD collection tracking with aging buckets
     */
    public function getCODCollectionTracking(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8',
                'filters.client_key' => 'string',
                'include_dunning' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $includeDunning = $request->input('include_dunning', true);

            $codData = $this->codCollectionService->trackCODCollections($filters);
            
            if ($includeDunning) {
                $codData['dunning_analysis'] = $this->codCollectionService->manageDunningWorkflows($filters);
            }

            return response()->json([
                'success' => true,
                'data' => $codData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('COD collection tracking API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving COD collection data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform aging analysis with detailed breakdown
     */
    public function getAgingAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'aging_buckets' => 'array',
                'include_risk_analysis' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $agingBuckets = $request->input('aging_buckets', []);
            $includeRiskAnalysis = $request->input('include_risk_analysis', true);

            $agingData = $this->codCollectionService->performAgingAnalysis($filters);
            
            if ($includeRiskAnalysis) {
                $agingData['risk_assessment'] = $this->codCollectionService->calculateCollectionEfficiency($filters);
            }

            return response()->json([
                'success' => true,
                'data' => $agingData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Aging analysis API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing aging analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get write-off analysis and provisioning
     */
    public function getWriteOffAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'filters' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range');
            $filters = $request->input('filters', []);

            $writeOffData = $this->codCollectionService->analyzeWriteOffs($filters);

            return response()->json([
                'success' => true,
                'data' => $writeOffData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Write-off analysis API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error performing write-off analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // PAYMENT PROCESSING ENDPOINTS
    // ===========================================

    /**
     * Get payment processing workflow with reconciliation status
     */
    public function getPaymentProcessing(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8',
                'filters.payment_status' => 'string',
                'filters.payment_method' => 'string',
                'include_reconciliation' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $includeReconciliation = $request->input('include_reconciliation', true);

            $paymentData = $this->paymentProcessingService->managePaymentProcessing($filters);
            
            if ($includeReconciliation) {
                $paymentData['reconciliation'] = $this->paymentProcessingService->trackReconciliationStatus($filters);
            }

            return response()->json([
                'success' => true,
                'data' => $paymentData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Payment processing API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment processing data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get settlement reporting and reconciliation
     */
    public function getSettlementReporting(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8',
                'include_reconciliation' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);

            $settlementData = $this->paymentProcessingService->generateSettlementReporting($filters);

            return response()->json([
                'success' => true,
                'data' => $settlementData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Settlement reporting API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating settlement report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyze payment method performance
     */
    public function getPaymentMethodAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'analysis_type' => 'string|in:performance,cost,optimization',
                'include_recommendations' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $analysisType = $request->input('analysis_type', 'performance');
            $includeRecommendations = $request->input('include_recommendations', true);

            $methodAnalysis = $this->paymentProcessingService->analyzePaymentMethods($filters);

            return response()->json([
                'success' => true,
                'data' => $methodAnalysis,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Payment method analysis API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // PROFITABILITY ANALYSIS ENDPOINTS
    // ===========================================

    /**
     * Get comprehensive profitability analysis
     */
    public function getProfitabilityAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8',
                'filters.client_key' => 'string',
                'filters.route_key' => 'string',
                'include_optimization' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $includeOptimization = $request->input('include_optimization', true);

            $profitabilityData = $this->profitabilityAnalysisService->analyzeProfitability($filters);
            
            if ($includeOptimization) {
                $profitabilityData['optimization_recommendations'] = 
                    $this->profitabilityAnalysisService->generateProfitabilityOptimization($profitabilityData);
            }

            return response()->json([
                'success' => true,
                'data' => $profitabilityData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Profitability analysis API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving profitability analysis data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer profitability ranking
     */
    public function getCustomerProfitabilityRanking(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'ranking_method' => 'string|in:score,profit,margin,volume',
                'include_insights' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $rankingMethod = $request->input('ranking_method', 'score');
            $includeInsights = $request->input('include_insights', true);

            $rankingData = $this->profitabilityAnalysisService->generateCustomerProfitabilityRanking($filters);

            return response()->json([
                'success' => true,
                'data' => $rankingData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Customer profitability ranking API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating customer profitability ranking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get route profitability analysis
     */
    public function getRouteProfitabilityAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'analysis_type' => 'string|in:comprehensive,optimization,comparative'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $analysisType = $request->input('analysis_type', 'comprehensive');

            switch ($analysisType) {
                case 'optimization':
                    $routeData = $this->profitabilityAnalysisService->generateProfitabilityOptimization([]);
                    break;
                case 'comparative':
                    $routeData = $this->profitabilityAnalysisService->analyzeRouteProfitability($filters);
                    break;
                default:
                    $routeData = $this->profitabilityAnalysisService->analyzeRouteProfitability($filters);
            }

            return response()->json([
                'success' => true,
                'data' => $routeData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Route profitability analysis API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing route profitability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service type profitability comparison
     */
    public function getServiceTypeProfitability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'comparison_focus' => 'string|in:performance,mix,competitive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $comparisonFocus = $request->input('comparison_focus', 'performance');

            $serviceData = $this->profitabilityAnalysisService->compareServiceTypeProfitability($filters);

            return response()->json([
                'success' => true,
                'data' => $serviceData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Service type profitability API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error comparing service type profitability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get time-based profitability analysis
     */
    public function getTimeBasedProfitability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'time_granularity' => 'string|in:hourly,daily,weekly,monthly,quarterly',
                'include_forecasting' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);
            $timeGranularity = $request->input('time_granularity', 'monthly');
            $includeForecasting = $request->input('include_forecasting', true);

            $timeData = $this->profitabilityAnalysisService->analyzeTimeBasedProfitability($filters);

            return response()->json([
                'success' => true,
                'data' => $timeData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Time-based profitability API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing time-based profitability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // EXPORT ENDPOINTS
    // ===========================================

    /**
     * Export financial data in various formats
     */
    public function exportFinancialData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'export_type' => 'required|string|in:revenue,cogs,margin,cod,payments,profitability,comprehensive',
                'format' => 'required|string|in:excel,csv,pdf,json',
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'filters' => 'array',
                'template' => 'string',
                'include_charts' => 'boolean',
                'email_recipient' => 'email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $exportType = $request->input('export_type');
            $format = $request->input('format');
            $dateRange = $request->input('date_range');
            $filters = $request->input('filters', []);
            $template = $request->input('template');
            $includeCharts = $request->input('include_charts', false);
            $emailRecipient = $request->input('email_recipient');

            $exportResult = $this->exportService->exportFinancialData(
                $exportType,
                $format,
                $dateRange,
                $filters,
                $template,
                $includeCharts,
                $emailRecipient
            );

            return response()->json([
                'success' => true,
                'data' => $exportResult,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Data export API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting financial data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available export templates
     */
    public function getExportTemplates(Request $request): JsonResponse
    {
        try {
            $exportType = $request->input('export_type', 'all');
            
            $templates = $this->exportService->getAvailableTemplates($exportType);

            return response()->json([
                'success' => true,
                'data' => $templates,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Export templates API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving export templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // INTEGRATION ENDPOINTS
    // ===========================================

    /**
     * Sync financial data with accounting systems
     */
    public function syncAccountingData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'system' => 'required|string|in:quickbooks,sap,oracle,all',
                'sync_type' => 'string|in:revenue,expenses,assets,liabilities,full',
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8',
                'dry_run' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $system = $request->input('system');
            $syncType = $request->input('sync_type', 'full');
            $dateRange = $request->input('date_range', []);
            $dryRun = $request->input('dry_run', false);

            $syncResult = $this->accountingIntegrationService->syncData(
                $system,
                $syncType,
                $dateRange,
                $dryRun
            );

            return response()->json([
                'success' => true,
                'data' => $syncResult,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Accounting sync API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error syncing accounting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get integration status and logs
     */
    public function getIntegrationStatus(Request $request): JsonResponse
    {
        try {
            $system = $request->input('system');
            $includeLogs = $request->input('include_logs', true);

            $status = $this->accountingIntegrationService->getIntegrationStatus($system, $includeLogs);

            return response()->json([
                'success' => true,
                'data' => $status,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Integration status API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving integration status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // AUDIT TRAIL ENDPOINTS
    // ===========================================

    /**
     * Get audit trail for financial transactions
     */
    public function getAuditTrail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8',
                'filters' => 'array',
                'filters.transaction_type' => 'string',
                'filters.user_id' => 'string',
                'filters.entity_type' => 'string',
                'include_changes' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range', []);
            $filters = $request->input('filters', []);
            $includeChanges = $request->input('include_changes', true);

            $auditData = $this->auditTrailService->getAuditTrail($dateRange, $filters, $includeChanges);

            return response()->json([
                'success' => true,
                'data' => $auditData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Audit trail API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving audit trail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get compliance reporting
     */
    public function getComplianceReport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'compliance_type' => 'string|in:sox,gaap,ifrs,internal',
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8',
                'report_format' => 'string|in:summary,detailed,full'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $complianceType = $request->input('compliance_type', 'internal');
            $dateRange = $request->input('date_range', []);
            $reportFormat = $request->input('report_format', 'detailed');

            $complianceData = $this->auditTrailService->generateComplianceReport(
                $complianceType,
                $dateRange,
                $reportFormat
            );

            return response()->json([
                'success' => true,
                'data' => $complianceData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Compliance report API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating compliance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
    // UTILITY ENDPOINTS
    // ===========================================

    /**
     * Get comprehensive financial dashboard data
     */
    public function getFinancialDashboard(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8',
                'include_forecasting' => 'boolean',
                'dashboard_type' => 'string|in:executive,operational,detailed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range', []);
            $includeForecasting = $request->input('include_forecasting', true);
            $dashboardType = $request->input('dashboard_type', 'executive');

            // Aggregate data from all services for dashboard
            $dashboardData = [
                'revenue' => $this->revenueRecognitionService->analyzeRevenueRecognition($dateRange, []),
                'cogs' => $this->cogsAnalysisService->analyzeCOGS($dateRange, []),
                'margins' => $this->grossMarginAnalysisService->analyzeGrossMargin($dateRange, [], $includeForecasting),
                'cod' => $this->codCollectionService->trackCODCollections([]),
                'payments' => $this->paymentProcessingService->managePaymentProcessing([]),
                'profitability' => $this->profitabilityAnalysisService->analyzeProfitability([])
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'dashboard_type' => $dashboardType,
                    'summary' => $dashboardData,
                    'generated_at' => now()->toISOString()
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Financial dashboard API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating financial dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get financial metrics and KPIs
     */
    public function getFinancialMetrics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8',
                'metrics' => 'array',
                'comparison_period' => 'string|in:previous_period,previous_year,budget'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range', []);
            $metrics = $request->input('metrics', []);
            $comparisonPeriod = $request->input('comparison_period', 'previous_period');

            // Calculate key financial metrics
            $metricsData = [
                'revenue_metrics' => [
                    'total_revenue' => 0,
                    'revenue_growth' => 0,
                    'revenue_per_customer' => 0,
                    'deferred_revenue' => 0
                ],
                'cost_metrics' => [
                    'total_cogs' => 0,
                    'cost_reduction' => 0,
                    'cost_per_shipment' => 0,
                    'cost_trends' => []
                ],
                'profitability_metrics' => [
                    'gross_margin' => 0,
                    'operating_margin' => 0,
                    'net_profit_margin' => 0,
                    'profit_growth' => 0
                ],
                'cash_flow_metrics' => [
                    'cod_collection_rate' => 0,
                    'days_sales_outstanding' => 0,
                    'cash_conversion_cycle' => 0,
                    'collection_efficiency' => 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $metricsData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Financial metrics API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating financial metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}