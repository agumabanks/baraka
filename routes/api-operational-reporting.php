<?php

use App\Http\Controllers\Api\V1\OperationalReporting\OperationalReportingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Operational Reporting Module
|--------------------------------------------------------------------------
*/

// Operational Reporting Routes
Route::prefix('v1/reports/operational')->group(function () {
    
    // Volume Analytics Routes
    Route::get('/volumes', [OperationalReportingController::class, 'getVolumeAnalytics']);
    Route::get('/volumes/heatmap', [OperationalReportingController::class, 'getGeographicHeatMap']);
    Route::get('/volumes/export', [OperationalReportingController::class, 'exportData'])
        ->where('format', 'excel|csv|pdf');

    // Route Efficiency Routes
    Route::get('/route-efficiency/{routeKey}', [OperationalReportingController::class, 'getRouteEfficiency']);
    Route::get('/route-efficiency/bottlenecks', [OperationalReportingController::class, 'getRouteBottlenecks']);
    Route::get('/route-efficiency/benchmarking', [OperationalReportingController::class, 'getPerformanceBenchmarking']);

    // On-Time Delivery Routes
    Route::get('/on-time-delivery', [OperationalReportingController::class, 'getOnTimeDeliveryRate']);
    Route::get('/on-time-delivery/variance', [OperationalReportingController::class, 'getVarianceAnalysis']);
    Route::get('/on-time-delivery/trends', [OperationalReportingController::class, 'getHistoricalTrends']);
    Route::get('/on-time-delivery/sla/{clientKey}', [OperationalReportingController::class, 'getSLACompliance']);

    // Exception Analysis Routes
    Route::get('/exceptions', [OperationalReportingController::class, 'getExceptionAnalysis']);
    Route::get('/exceptions/root-cause/{exceptionType}', [OperationalReportingController::class, 'getRootCauseAnalysis']);

    // Driver Performance Routes
    Route::get('/driver-performance/{driverKey}', [OperationalReportingController::class, 'getDriverPerformance']);
    Route::get('/driver-performance/ranking', [OperationalReportingController::class, 'getDriverRanking']);

    // Container Utilization Routes
    Route::get('/container-utilization/{containerId}', [OperationalReportingController::class, 'getContainerUtilization']);
    Route::get('/optimization-suggestions', [OperationalReportingController::class, 'getOptimizationSuggestions']);

    // Transit Time Routes
    Route::get('/transit-times', [OperationalReportingController::class, 'getTransitTimeAnalysis']);
    Route::get('/transit-times/benchmarking', [OperationalReportingController::class, 'getPerformanceBenchmarking']);
    Route::get('/transit-times/improvements', [OperationalReportingController::class, 'getImprovementOpportunities']);

    // Export Routes
    Route::post('/export', [OperationalReportingController::class, 'exportData']);

    // Drill-Down Routes
    Route::get('/drilldown', [OperationalReportingController::class, 'getDrillDownData']);

    // Dashboard Summary
    Route::get('/dashboard/summary', [OperationalReportingController::class, 'getDashboardSummary']);
});