<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\PredictiveAnalyticsService;
use App\Services\Analytics\ReportGenerationService;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;
    protected PredictiveAnalyticsService $predictiveService;
    protected ReportGenerationService $reportService;

    public function __construct(
        AnalyticsService $analyticsService,
        PredictiveAnalyticsService $predictiveService,
        ReportGenerationService $reportService
    ) {
        $this->analyticsService = $analyticsService;
        $this->predictiveService = $predictiveService;
        $this->reportService = $reportService;
    }

    /**
     * Executive dashboard view
     */
    public function dashboard(Request $request)
    {
        $filters = $request->only(['preset', 'start_date', 'end_date', 'branch_id']);
        
        return view('admin.analytics.dashboard', [
            'filters' => $filters,
            'presets' => $this->getDatePresets(),
        ]);
    }

    /**
     * Get executive dashboard data (API)
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $filters = $request->only(['preset', 'start_date', 'end_date', 'branch_id']);
        
        $data = $this->analyticsService->getExecutiveDashboard($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get branch comparison data
     */
    public function getBranchComparison(Request $request): JsonResponse
    {
        $filters = $request->only(['preset', 'start_date', 'end_date']);
        $dateRange = $this->getDateRange($filters);

        $data = $this->analyticsService->getBranchComparison($dateRange);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get driver performance data
     */
    public function getDriverPerformance(Request $request): JsonResponse
    {
        $filters = $request->only(['preset', 'start_date', 'end_date', 'branch_id']);
        $dateRange = $this->getDateRange($filters);

        $data = $this->analyticsService->getDriverPerformance($dateRange, $filters['branch_id'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Request $request): JsonResponse
    {
        $filters = $request->only(['preset', 'start_date', 'end_date']);
        $dateRange = $this->getDateRange($filters);

        $data = $this->analyticsService->getCustomerAnalytics($dateRange);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Predict delivery time for shipment
     */
    public function predictDelivery(Request $request, Shipment $shipment): JsonResponse
    {
        $prediction = $this->predictiveService->predictDeliveryTime($shipment);

        return response()->json([
            'success' => true,
            'data' => $prediction,
        ]);
    }

    /**
     * Get prediction accuracy statistics
     */
    public function getPredictionAccuracy(Request $request): JsonResponse
    {
        $filters = $request->only(['preset', 'start_date', 'end_date']);
        $dateRange = !empty($filters) ? $this->getDateRange($filters) : [];

        $data = $this->predictiveService->getPredictionAccuracy($dateRange);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Reports index page
     */
    public function reports(Request $request)
    {
        return view('admin.analytics.reports', [
            'presets' => $this->getDatePresets(),
        ]);
    }

    /**
     * Generate shipment report
     */
    public function generateShipmentReport(Request $request): JsonResponse
    {
        $filters = $request->only([
            'preset', 'start_date', 'end_date', 
            'branch_id', 'status', 'customer_id', 'payment_type'
        ]);

        $startTime = microtime(true);
        $executionId = $this->reportService->recordExecution('shipment', $filters, auth()->id());

        try {
            $report = $this->reportService->generateShipmentReport($filters);
            $executionTime = round((microtime(true) - $startTime) * 1000);

            $this->reportService->updateExecutionStatus(
                $executionId,
                'completed',
                null,
                'json',
                count($report['data']),
                $executionTime
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            $this->reportService->updateExecutionStatus(
                $executionId,
                'failed',
                null,
                null,
                null,
                null,
                $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate financial report
     */
    public function generateFinancialReport(Request $request): JsonResponse
    {
        $filters = $request->only(['preset', 'start_date', 'end_date', 'branch_id']);

        $startTime = microtime(true);
        $executionId = $this->reportService->recordExecution('financial', $filters, auth()->id());

        try {
            $report = $this->reportService->generateFinancialReport($filters);
            $executionTime = round((microtime(true) - $startTime) * 1000);

            $this->reportService->updateExecutionStatus(
                $executionId,
                'completed',
                null,
                'json',
                count($report['daily_revenue'] ?? []),
                $executionTime
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            $this->reportService->updateExecutionStatus($executionId, 'failed', null, null, null, null, $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(Request $request): JsonResponse
    {
        $filters = $request->only(['preset', 'start_date', 'end_date', 'branch_id']);

        $startTime = microtime(true);
        $executionId = $this->reportService->recordExecution('performance', $filters, auth()->id());

        try {
            $report = $this->reportService->generatePerformanceReport($filters);
            $executionTime = round((microtime(true) - $startTime) * 1000);

            $this->reportService->updateExecutionStatus(
                $executionId,
                'completed',
                null,
                'json',
                count($report['driver_performance'] ?? []),
                $executionTime
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            $this->reportService->updateExecutionStatus($executionId, 'failed', null, null, null, null, $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export report
     */
    public function exportReport(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:shipment,financial,performance',
            'format' => 'required|in:csv,xlsx',
        ]);

        $filters = $request->only(['preset', 'start_date', 'end_date', 'branch_id', 'status', 'customer_id']);
        $type = $request->input('type');
        $format = $request->input('format');

        // Generate report
        $report = match ($type) {
            'shipment' => $this->reportService->generateShipmentReport($filters),
            'financial' => $this->reportService->generateFinancialReport($filters),
            'performance' => $this->reportService->generatePerformanceReport($filters),
        };

        // Export
        $filename = "{$type}_report_" . now()->format('Y-m-d_His');
        
        $path = match ($format) {
            'csv' => $this->reportService->exportToCsv($report, $filename),
            'xlsx' => $this->reportService->exportToExcel($report, $filename),
        };

        return response()->json([
            'success' => true,
            'download_url' => url("admin/analytics/download?file=" . urlencode(basename($path))),
        ]);
    }

    /**
     * Download exported report
     */
    public function downloadReport(Request $request)
    {
        $file = $request->input('file');
        $path = storage_path("app/reports/{$file}");

        if (!file_exists($path)) {
            abort(404, 'Report not found');
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            return [
                'start' => \Carbon\Carbon::parse($filters['start_date'])->startOfDay(),
                'end' => \Carbon\Carbon::parse($filters['end_date'])->endOfDay(),
            ];
        }

        $preset = $filters['preset'] ?? 'last_30_days';

        return match ($preset) {
            'today' => ['start' => now()->startOfDay(), 'end' => now()->endOfDay()],
            'yesterday' => ['start' => now()->subDay()->startOfDay(), 'end' => now()->subDay()->endOfDay()],
            'last_7_days' => ['start' => now()->subDays(7)->startOfDay(), 'end' => now()->endOfDay()],
            'last_30_days' => ['start' => now()->subDays(30)->startOfDay(), 'end' => now()->endOfDay()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'last_month' => ['start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()],
            default => ['start' => now()->subDays(30)->startOfDay(), 'end' => now()->endOfDay()],
        };
    }

    /**
     * Get available date presets
     */
    protected function getDatePresets(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'this_year' => 'This Year',
            'custom' => 'Custom Range',
        ];
    }
}
