<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // Report operations - rate limited
    Route::middleware(['throttle:60,1'])->group(function () {
        
        // Dashboard and KPI permissions
        Route::middleware('permission:report_read')->group(function () {
            // Dashboard Reports
            Route::get('reports/dashboard', [ReportController::class, 'dashboard'])
                ->name('api.reports.dashboard');

            Route::get('reports/dashboard/kpi', [ReportController::class, 'kpi'])
                ->name('api.reports.kpi');

            Route::get('reports/dashboard/summary', [ReportController::class, 'dashboardSummary'])
                ->name('api.reports.dashboard-summary');

            Route::get('reports/dashboard/quick-stats', [ReportController::class, 'quickStats'])
                ->name('api.reports.quick-stats');

            // Financial Reports
            Route::get('reports/financial/revenue', [ReportController::class, 'revenue'])
                ->name('api.reports.revenue');

            Route::get('reports/financial/expenses', [ReportController::class, 'expenses'])
                ->name('api.reports.expenses');

            Route::get('reports/financial/profit', [ReportController::class, 'profit'])
                ->name('api.reports.profit');

            Route::get('reports/financial/transactions', [ReportController::class, 'transactions'])
                ->name('api.reports.transactions');

            Route::get('reports/financial/payment-summary', [ReportController::class, 'paymentSummary'])
                ->name('api.reports.payment-summary');

            Route::get('reports/financial/cod-analysis', [ReportController::class, 'codAnalysis'])
                ->name('api.reports.cod-analysis');

            // Shipment Reports
            Route::get('reports/shipments/overview', [ReportController::class, 'shipments'])
                ->name('api.reports.shipments');

            Route::get('reports/shipments/detailed', [ReportController::class, 'shipmentsDetailed'])
                ->name('api.reports.shipments-detailed');

            Route::get('reports/shipments/status-breakdown', [ReportController::class, 'shipmentsStatusBreakdown'])
                ->name('api.reports.shipments-status-breakdown');

            Route::get('reports/shipments/geographic', [ReportController::class, 'shipmentsGeographic'])
                ->name('api.reports.shipments-geographic');

            Route::get('reports/shipments/timeline', [ReportController::class, 'shipmentsTimeline'])
                ->name('api.reports.shipments-timeline');

            Route::get('reports/shipments/analytics', [ReportController::class, 'shipmentsAnalytics'])
                ->name('api.reports.shipments-analytics');

            Route::get('reports/shipments/performance', [ReportController::class, 'shipmentsPerformance'])
                ->name('api.reports.shipments-performance');

            // Delivery Reports
            Route::get('reports/delivery/overview', [ReportController::class, 'delivery'])
                ->name('api.reports.delivery');

            Route::get('reports/delivery/performance', [ReportController::class, 'deliveryPerformance'])
                ->name('api.reports.delivery-performance');

            Route::get('reports/delivery/time-analysis', [ReportController::class, 'deliveryTimeAnalysis'])
                ->name('api.reports.delivery-time-analysis');

            Route::get('reports/delivery/rate-analysis', [ReportController::class, 'deliveryRateAnalysis'])
                ->name('api.reports.delivery-rate-analysis');

            Route::get('reports/delivery/driver-performance', [ReportController::class, 'driverPerformance'])
                ->name('api.reports.driver-performance');

            // Branch Reports
            Route::get('reports/branches/overview', [ReportController::class, 'branches'])
                ->name('api.reports.branches');

            Route::get('reports/branches/comparison', [ReportController::class, 'branchesComparison'])
                ->name('api.reports.branches-comparison');

            Route::get('reports/branches/performance', [ReportController::class, 'branchesPerformance'])
                ->name('api.reports.branches-performance');

            Route::get('reports/branches/utilization', [ReportController::class, 'branchesUtilization'])
                ->name('api.reports.branches-utilization');

            Route::get('reports/branches/manager-performance', [ReportController::class, 'branchManagerPerformance'])
                ->name('api.reports.branch-manager-performance');

            // Customer/Merchant Reports
            Route::get('reports/customers/overview', [ReportController::class, 'customers'])
                ->name('api.reports.customers');

            Route::get('reports/merchants/performance', [ReportController::class, 'merchantPerformance'])
                ->name('api.reports.merchant-performance');

            Route::get('reports/merchants/top-performers', [ReportController::class, 'merchantTopPerformers'])
                ->name('api.reports.merchant-top-performers');

            Route::get('reports/merchants/retention', [ReportController::class, 'merchantRetention'])
                ->name('api.reports.merchant-retention');

            // Operational Reports
            Route::get('reports/operations/summary', [ReportController::class, 'operationsSummary'])
                ->name('api.reports.operations-summary');

            Route::get('reports/operations/efficiency', [ReportController::class, 'operationsEfficiency'])
                ->name('api.reports.operations-efficiency');

            Route::get('reports/operations/bottlenecks', [ReportController::class, 'operationsBottlenecks'])
                ->name('api.reports.operations-bottlenecks');

            Route::get('reports/operations/exceptions', [ReportController::class, 'operationsExceptions'])
                ->name('api.reports.operations-exceptions');

            // Asset Reports
            Route::get('reports/assets/overview', [ReportController::class, 'assets'])
                ->name('api.reports.assets');

            Route::get('reports/assets/utilization', [ReportController::class, 'assetUtilization'])
                ->name('api.reports.asset-utilization');

            Route::get('reports/assets/maintenance', [ReportController::class, 'assetMaintenance'])
                ->name('api.reports.asset-maintenance');

            // Notification Reports
            Route::get('reports/notifications/summary', [ReportController::class, 'notifications'])
                ->name('api.reports.notifications');

            Route::get('reports/notifications/delivery-status', [ReportController::class, 'notificationDelivery'])
                ->name('api.reports.notification-delivery');

            // Export functionality
            Route::post('reports/export', [ReportController::class, 'export'])
                ->name('api.reports.export');

            Route::get('reports/export/formats', [ReportController::class, 'exportFormats'])
                ->name('api.reports.export-formats');

            Route::get('reports/export/history', [ReportController::class, 'exportHistory'])
                ->name('api.reports.export-history');
        });

        // Advanced Analytics permissions
        Route::middleware('permission:report_analytics')->group(function () {
            // Trend Analysis
            Route::get('reports/analytics/trends', [ReportController::class, 'trends'])
                ->name('api.reports.trends');

            Route::get('reports/analytics/growth', [ReportController::class, 'growth'])
                ->name('api.reports.growth');

            Route::get('reports/analytics/seasonality', [ReportController::class, 'seasonality'])
                ->name('api.reports.seasonality');

            Route::get('reports/analytics/patterns', [ReportController::class, 'patterns'])
                ->name('api.reports.patterns');

            // Forecasting
            Route::get('reports/analytics/forecast', [ReportController::class, 'forecast'])
                ->name('api.reports.forecast');

            Route::get('reports/analytics/accuracy', [ReportController::class, 'forecastAccuracy'])
                ->name('api.reports.forecast-accuracy');

            // Custom Reports
            Route::get('reports/analytics/custom', [ReportController::class, 'custom'])
                ->name('api.reports.custom');

            Route::post('reports/analytics/custom/create', [ReportController::class, 'createCustom'])
                ->name('api.reports.create-custom');

            Route::put('reports/analytics/custom/{report}', [ReportController::class, 'updateCustom'])
                ->name('api.reports.update-custom');

            Route::delete('reports/analytics/custom/{report}', [ReportController::class, 'deleteCustom'])
                ->name('api.reports.delete-custom');

            Route::get('reports/analytics/custom/templates', [ReportController::class, 'customTemplates'])
                ->name('api.reports.custom-templates');

            // Performance Metrics
            Route::get('reports/analytics/performance-metrics', [ReportController::class, 'performanceMetrics'])
                ->name('api.reports.performance-metrics');

            Route::get('reports/analytics/sla-metrics', [ReportController::class, 'slaMetrics'])
                ->name('api.reports.sla-metrics');

            Route::get('reports/analytics/kpi-comparison', [ReportController::class, 'kpiComparison'])
                ->name('api.reports.kpi-comparison');

            // Benchmarking
            Route::get('reports/analytics/benchmark', [ReportController::class, 'benchmark'])
                ->name('api.reports.benchmark');

            Route::get('reports/analytics/industry-comparison', [ReportController::class, 'industryComparison'])
                ->name('api.reports.industry-comparison');
        });

        // Report management permissions
        Route::middleware('permission:report_manage')->group(function () {
            // Scheduled Reports
            Route::get('reports/scheduled', [ReportController::class, 'scheduledReports'])
                ->name('api.reports.scheduled');

            Route::post('reports/scheduled', [ReportController::class, 'createScheduled'])
                ->name('api.reports.create-scheduled');

            Route::put('reports/scheduled/{report}', [ReportController::class, 'updateScheduled'])
                ->name('api.reports.update-scheduled');

            Route::delete('reports/scheduled/{report}', [ReportController::class, 'deleteScheduled'])
                ->name('api.reports.delete-scheduled');

            Route::post('reports/scheduled/{report}/run', [ReportController::class, 'runScheduled'])
                ->name('api.reports.run-scheduled');

            // Report Templates
            Route::get('reports/templates', [ReportController::class, 'reportTemplates'])
                ->name('api.reports.templates');

            Route::post('reports/templates', [ReportController::class, 'createTemplate'])
                ->name('api.reports.create-template');

            Route::put('reports/templates/{template}', [ReportController::class, 'updateTemplate'])
                ->name('api.reports.update-template');

            Route::delete('reports/templates/{template}', [ReportController::class, 'deleteTemplate'])
                ->name('api.reports.delete-template');

            // Report Sharing
            Route::post('reports/{report}/share', [ReportController::class, 'shareReport'])
                ->name('api.reports.share-report');

            Route::get('reports/shared', [ReportController::class, 'sharedReports'])
                ->name('api.reports.shared');
        });

        // System admin permissions
        Route::middleware('permission:system_admin')->group(function () {
            // System Reports
            Route::get('reports/system/health', [ReportController::class, 'systemHealth'])
                ->name('api.reports.system-health');

            Route::get('reports/system/usage', [ReportController::class, 'systemUsage'])
                ->name('api.reports.system-usage');

            Route::get('reports/system/audit-log', [ReportController::class, 'systemAuditLog'])
                ->name('api.reports.system-audit-log');

            // Import/Export Management
            Route::post('reports/import', [ReportController::class, 'importReport'])
                ->name('api.reports.import');

            Route::get('reports/import/history', [ReportController::class, 'importHistory'])
                ->name('api.reports.import-history');
        });
    });
});

// Report generation via websockets (lower rate limit)
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('reports/generate-websocket/{reportId}', [ReportController::class, 'generateWebSocket'])
        ->name('api.reports.generate-websocket');

    Route::get('reports/{reportId}/generation-status', [ReportController::class, 'generationStatus'])
        ->name('api.reports.generation-status');
});

// Webhook endpoints for report events
Route::middleware(['api.prefix'])->group(function () {
    Route::post('webhooks/reports/generated', [ReportController::class, 'reportGeneratedWebhook'])
        ->name('api.webhooks.reports.generated');

    Route::post('webhooks/reports/scheduled-completed', [ReportController::class, 'scheduledReportCompletedWebhook'])
        ->name('api.webhooks.reports.scheduled-completed');

    Route::post('webhooks/reports/anomaly-detected', [ReportController::class, 'anomalyDetectedWebhook'])
        ->name('api.webhooks.reports.anomaly-detected');
});
