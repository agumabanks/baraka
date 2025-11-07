<?php

namespace App\Http\Controllers\Api\V1\OperationalReporting;

use App\Http\Controllers\Controller;
use App\Services\OperationalReporting\OriginDestinationAnalyticsService;
use App\Services\OperationalReporting\RouteEfficiencyService;
use App\Services\OperationalReporting\OnTimeDeliveryService;
use App\Services\OperationalReporting\ExceptionAnalysisService;
use App\Services\OperationalReporting\DriverPerformanceService;
use App\Services\OperationalReporting\ContainerUtilizationService;
use App\Services\OperationalReporting\TransitTimeService;
use App\Services\OperationalReporting\ExportService;
use App\Services\OperationalReporting\DrillDownService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OperationalReportingController extends Controller
{
    public function __construct(
        private OriginDestinationAnalyticsService $originDestinationService,
        private RouteEfficiencyService $routeEfficiencyService,
        private OnTimeDeliveryService $onTimeDeliveryService,
        private ExceptionAnalysisService $exceptionAnalysisService,
        private DriverPerformanceService $driverPerformanceService,
        private ContainerUtilizationService $containerUtilizationService,
        private TransitTimeService $transitTimeService,
        private ExportService $exportService,
        private DrillDownService $drillDownService
    ) {}

    /**
     * Get origin-destination volume analytics
     */
    public function getVolumeAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'granularity' => 'string|in:hourly,daily,weekly,monthly',
                'filters' => 'array',
                'filters.client_key' => 'string',
                'filters.carrier_key' => 'string',
                'filters.branch_key' => 'string'
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
            $granularity = $request->input('granularity', 'daily');

            $analytics = $this->originDestinationService->getVolumeAnalytics($dateRange, $granularity, $filters);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving volume analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate geographic heat map data
     */
    public function getGeographicHeatMap(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'map_type' => 'string|in:route,branch,client',
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
            $mapType = $request->input('map_type', 'route');
            $filters = $request->input('filters', []);

            $heatMapData = $this->originDestinationService->generateHeatMapData($dateRange, $mapType, $filters);

            return response()->json([
                'success' => true,
                'data' => $heatMapData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating heat map data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get route efficiency score
     */
    public function getRouteEfficiency(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'route_key' => 'required|string',
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $routeKey = $request->input('route_key');
            $dateRange = $request->input('date_range');

            $efficiencyScore = $this->routeEfficiencyService->calculateEfficiencyScore($routeKey, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $efficiencyScore,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating route efficiency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Identify route bottlenecks
     */
    public function getRouteBottlenecks(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8',
                'filters.client_key' => 'string',
                'filters.carrier_key' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);

            $bottlenecks = $this->routeEfficiencyService->identifyBottlenecks($filters);

            return response()->json([
                'success' => true,
                'data' => $bottlenecks,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error identifying bottlenecks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get on-time delivery rate
     */
    public function getOnTimeDeliveryRate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8',
                'filters.client_key' => 'string',
                'filters.route_key' => 'string',
                'filters.driver_key' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);

            $onTimeRate = $this->onTimeDeliveryService->calculateOnTimeRate($filters);

            return response()->json([
                'success' => true,
                'data' => $onTimeRate,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating on-time delivery rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform variance analysis
     */
    public function getVarianceAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'dimension' => 'required|string|in:route,driver,client,branch,carrier',
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
            $dimension = $request->input('dimension');
            $filters = $request->input('filters', []);

            $varianceAnalysis = $this->onTimeDeliveryService->performVarianceAnalysis($dateRange, $dimension);

            return response()->json([
                'success' => true,
                'data' => $varianceAnalysis,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing variance analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get historical trends and forecasting
     */
    public function getHistoricalTrends(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'string|in:hourly,daily,weekly,monthly',
                'days' => 'integer|min:1|max:365'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $period = $request->input('period', 'daily');
            $days = $request->input('days', 30);

            $trends = $this->onTimeDeliveryService->getHistoricalTrends($period, $days);

            return response()->json([
                'success' => true,
                'data' => $trends,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving historical trends',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Monitor SLA compliance
     */
    public function getSLACompliance(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'client_key' => 'required|string',
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clientKey = $request->input('client_key');
            $dateRange = $request->input('date_range', []);

            $slaCompliance = $this->onTimeDeliveryService->monitorSLACompliance($clientKey, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $slaCompliance,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error monitoring SLA compliance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Categorize exceptions
     */
    public function getExceptionAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8',
                'filters.client_key' => 'string',
                'filters.route_key' => 'string',
                'filters.driver_key' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);

            $exceptionAnalysis = $this->exceptionAnalysisService->categorizeExceptions($filters);

            return response()->json([
                'success' => true,
                'data' => $exceptionAnalysis,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing exceptions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get root cause analysis
     */
    public function getRootCauseAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'exception_type' => 'required|string',
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $exceptionType = $request->input('exception_type');
            $filters = $request->input('filters', []);

            $rootCauseAnalysis = $this->exceptionAnalysisService->performRootCauseAnalysis($exceptionType, $filters);

            return response()->json([
                'success' => true,
                'data' => $rootCauseAnalysis,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing root cause analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get driver performance metrics
     */
    public function getDriverPerformance(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'driver_key' => 'required|string',
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'metric_type' => 'string|in:stops_per_hour,miles_per_gallon,hos_compliance,safety_incidents'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $driverKey = $request->input('driver_key');
            $dateRange = $request->input('date_range');
            $metricType = $request->input('metric_type', 'stops_per_hour');

            $performanceData = match($metricType) {
                'stops_per_hour' => $this->driverPerformanceService->calculateStopsPerHour($driverKey, $dateRange),
                'miles_per_gallon' => $this->driverPerformanceService->trackMilesPerGallon($driverKey, $dateRange),
                'hos_compliance' => $this->driverPerformanceService->monitorHoursOfService($driverKey, $dateRange),
                'safety_incidents' => $this->driverPerformanceService->monitorSafetyIncidents(['driver_key' => $driverKey]),
                default => $this->driverPerformanceService->calculateStopsPerHour($driverKey, $dateRange)
            };

            return response()->json([
                'success' => true,
                'data' => $performanceData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving driver performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get driver ranking
     */
    public function getDriverRanking(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'string|in:weekly,monthly,quarterly',
                'months' => 'integer|min:1|max:12'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $period = $request->input('period', 'monthly');
            $months = $request->input('months', 3);

            $ranking = $this->driverPerformanceService->generateDriverRanking($period, $months);

            return response()->json([
                'success' => true,
                'data' => $ranking,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating driver ranking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get container utilization
     */
    public function getContainerUtilization(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'container_id' => 'required|string',
                'date_range' => 'required|array',
                'date_range.start' => 'required|string|size:8',
                'date_range.end' => 'required|string|size:8',
                'analysis_type' => 'string|in:utilization,cost_efficiency,load_optimization'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $containerId = $request->input('container_id');
            $dateRange = $request->input('date_range');
            $analysisType = $request->input('analysis_type', 'utilization');

            $utilizationData = match($analysisType) {
                'utilization' => $this->containerUtilizationService->calculateUtilizationRate($containerId, $dateRange),
                'cost_efficiency' => $this->containerUtilizationService->analyzeCostEfficiency($containerId, $dateRange),
                'load_optimization' => $this->containerUtilizationService->getLoadOptimizationSuggestions($containerId),
                default => $this->containerUtilizationService->calculateUtilizationRate($containerId, $dateRange)
            };

            return response()->json([
                'success' => true,
                'data' => $utilizationData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing container utilization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get optimization suggestions
     */
    public function getOptimizationSuggestions(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'route_id' => 'string',
                'analysis_type' => 'string|in:route_optimization,capacity_planning,load_balancing'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $routeId = $request->input('route_id');
            $analysisType = $request->input('analysis_type', 'route_optimization');

            $suggestions = match($analysisType) {
                'route_optimization' => $this->containerUtilizationService->generateOptimizationSuggestions($routeId),
                'capacity_planning' => $this->containerUtilizationService->performCapacityPlanning([
                    'start' => now()->subDays(30)->format('Ymd'),
                    'end' => now()->format('Ymd')
                ]),
                'load_balancing' => $this->transitTimeService->identifyImprovementOpportunities(),
                default => $this->containerUtilizationService->generateOptimizationSuggestions($routeId)
            };

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating optimization suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transit time analysis
     */
    public function getTransitTimeAnalysis(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'entity_key' => 'required|string',
                'type' => 'string|in:route,carrier,origin,destination,driver',
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8',
                'analysis_type' => 'string|in:average_time,bottlenecks,variance_analysis,benchmarking'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $entityKey = $request->input('entity_key');
            $type = $request->input('type', 'route');
            $dateRange = $request->input('date_range', []);
            $analysisType = $request->input('analysis_type', 'average_time');

            $transitData = match($analysisType) {
                'average_time' => $this->transitTimeService->calculateAverageTransitTime($entityKey, $type, $dateRange),
                'bottlenecks' => $this->transitTimeService->identifyTransitBottlenecks(['entity_key' => $entityKey]),
                'variance_analysis' => $this->transitTimeService->performVarianceAnalysis($entityKey, $type, $dateRange),
                'benchmarking' => $this->transitTimeService->benchmarkPerformance([$entityKey], $type, $dateRange),
                default => $this->transitTimeService->calculateAverageTransitTime($entityKey, $type, $dateRange)
            };

            return response()->json([
                'success' => true,
                'data' => $transitData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing transit times',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance benchmarking
     */
    public function getPerformanceBenchmarking(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'entity_keys' => 'required|array',
                'entity_keys.*' => 'string',
                'type' => 'string|in:route,carrier,origin,destination,driver',
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $entityKeys = $request->input('entity_keys');
            $type = $request->input('type', 'route');
            $dateRange = $request->input('date_range', []);

            $benchmarking = $this->transitTimeService->benchmarkPerformance($entityKeys, $type, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $benchmarking,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing performance benchmarking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get improvement opportunities
     */
    public function getImprovementOpportunities(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'filters' => 'array',
                'filters.date_range' => 'array',
                'filters.date_range.start' => 'string|size:8',
                'filters.date_range.end' => 'string|size:8',
                'filters.client_key' => 'string',
                'filters.carrier_key' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->input('filters', []);

            $opportunities = $this->transitTimeService->identifyImprovementOpportunities($filters);

            return response()->json([
                'success' => true,
                'data' => $opportunities,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error identifying improvement opportunities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export operational data
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'report_type' => 'required|string|in:volume_analytics,route_efficiency,on_time_delivery,exceptions,driver_performance,container_utilization,transit_times',
                'format' => 'string|in:excel,csv,pdf',
                'filters' => 'array',
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reportType = $request->input('report_type');
            $format = $request->input('format', 'excel');
            $filters = $request->input('filters', []);
            $dateRange = $request->input('date_range', []);

            $exportResult = $this->exportService->exportReport($reportType, $format, $filters, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $exportResult,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get drill-down data
     */
    public function getDrillDownData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'entity_type' => 'required|string|in:shipment,route,driver,client,branch,carrier',
                'entity_id' => 'required|string',
                'level' => 'string|in:aggregate,detail,summary',
                'filters' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $entityType = $request->input('entity_type');
            $entityId = $request->input('entity_id');
            $level = $request->input('level', 'detail');
            $filters = $request->input('filters', []);

            $drillDownData = $this->drillDownService->getDrillDownData($entityType, $entityId, $level, $filters);

            return response()->json([
                'success' => true,
                'data' => $drillDownData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving drill-down data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary dashboard data
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_range' => 'array',
                'date_range.start' => 'string|size:8',
                'date_range.end' => 'string|size:8',
                'client_key' => 'string',
                'branch_key' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateRange = $request->input('date_range', [
                'start' => now()->subDays(7)->format('Ymd'),
                'end' => now()->format('Ymd')
            ]);
            $clientKey = $request->input('client_key');
            $branchKey = $request->input('branch_key');

            $filters = array_filter([
                'date_range' => $dateRange,
                'client_key' => $clientKey,
                'branch_key' => $branchKey
            ]);

            // Get summary data from all services
            $summary = [
                'volume_analytics' => $this->originDestinationService->getVolumeAnalytics($dateRange, 'daily', $filters),
                'route_efficiency' => $this->routeEfficiencyService->identifyBottlenecks($filters),
                'on_time_delivery' => $this->onTimeDeliveryService->calculateOnTimeRate($filters),
                'exceptions' => $this->exceptionAnalysisService->categorizeExceptions($filters),
                'driver_performance' => $this->driverPerformanceService->generateDriverRanking('monthly', 1),
                'container_utilization' => $this->containerUtilizationService->performCapacityPlanning($dateRange),
                'transit_times' => $this->transitTimeService->identifyImprovementOpportunities($filters)
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}