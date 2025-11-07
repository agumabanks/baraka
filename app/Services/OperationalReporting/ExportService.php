<?php

namespace App\Services\OperationalReporting;

use App\Services\OperationalReporting\OriginDestinationAnalyticsService;
use App\Services\OperationalReporting\RouteEfficiencyService;
use App\Services\OperationalReporting\OnTimeDeliveryService;
use App\Services\OperationalReporting\ExceptionAnalysisService;
use App\Services\OperationalReporting\DriverPerformanceService;
use App\Services\OperationalReporting\ContainerUtilizationService;
use App\Services\OperationalReporting\TransitTimeService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OperationalReportExport;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    private const EXPORT_DIR = 'exports/operational-reports/';
    private const MAX_EXPORT_ROWS = 50000;

    public function __construct(
        private OriginDestinationAnalyticsService $originDestinationService,
        private RouteEfficiencyService $routeEfficiencyService,
        private OnTimeDeliveryService $onTimeDeliveryService,
        private ExceptionAnalysisService $exceptionAnalysisService,
        private DriverPerformanceService $driverPerformanceService,
        private ContainerUtilizationService $containerUtilizationService,
        private TransitTimeService $transitTimeService
    ) {}

    /**
     * Export operational report data
     */
    public function exportReport(string $reportType, string $format, array $filters, array $dateRange = []): array
    {
        try {
            $data = $this->getReportData($reportType, $filters, $dateRange);
            $fileName = $this->generateFileName($reportType, $format);
            $filePath = self::EXPORT_DIR . $fileName;

            if ($data === null) {
                throw new \Exception("No data available for export");
            }

            if ($this->exceedsRowLimit($data)) {
                throw new \Exception("Export data exceeds maximum limit of " . self::MAX_EXPORT_ROWS . " rows");
            }

            switch ($format) {
                case 'excel':
                    $result = $this->exportToExcel($data, $fileName, $filePath, $reportType);
                    break;
                case 'csv':
                    $result = $this->exportToCSV($data, $fileName, $filePath, $reportType);
                    break;
                case 'pdf':
                    $result = $this->exportToPDF($data, $fileName, $filePath, $reportType);
                    break;
                default:
                    throw new \Exception("Unsupported export format: {$format}");
            }

            return [
                'success' => true,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_url' => Storage::url($filePath),
                'export_type' => $reportType,
                'format' => $format,
                'record_count' => $this->getRecordCount($data),
                'file_size' => Storage::disk('local')->size($filePath),
                'generated_at' => now()->toISOString(),
                'download_expires_at' => now()->addDays(7)->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage(), [
                'report_type' => $reportType,
                'format' => $format,
                'filters' => $filters,
                'date_range' => $dateRange,
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Export failed: " . $e->getMessage());
        }
    }

    /**
     * Export volume analytics data
     */
    public function exportVolumeAnalytics(array $dateRange, array $filters, string $format): array
    {
        $data = $this->originDestinationService->getVolumeAnalytics($dateRange, 'detailed', $filters);
        $fileName = "volume_analytics_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
        $filePath = self::EXPORT_DIR . $fileName;

        $exportData = $this->formatVolumeAnalyticsForExport($data);

        return $this->performExport($exportData, $fileName, $filePath, $format, 'volume_analytics');
    }

    /**
     * Export route efficiency data
     */
    public function exportRouteEfficiency(array $filters, string $format): array
    {
        $data = $this->routeEfficiencyService->identifyBottlenecks($filters);
        $fileName = "route_efficiency_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
        $filePath = self::EXPORT_DIR . $fileName;

        $exportData = $this->formatRouteEfficiencyForExport($data);

        return $this->performExport($exportData, $fileName, $filePath, $format, 'route_efficiency');
    }

    /**
     * Export on-time delivery data
     */
    public function exportOnTimeDelivery(array $filters, string $format): array
    {
        $onTimeData = $this->onTimeDeliveryService->calculateOnTimeRate($filters);
        $varianceData = $this->onTimeDeliveryService->performVarianceAnalysis($filters['date_range'] ?? [], 'route');
        
        $fileName = "on_time_delivery_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
        $filePath = self::EXPORT_DIR . $fileName;

        $exportData = [
            'on_time_analysis' => $this->formatOnTimeForExport($onTimeData),
            'variance_analysis' => $this->formatVarianceForExport($varianceData)
        ];

        return $this->performExport($exportData, $fileName, $filePath, $format, 'on_time_delivery');
    }

    /**
     * Export exception analysis data
     */
    public function exportExceptionAnalysis(array $filters, string $format): array
    {
        $data = $this->exceptionAnalysisService->categorizeExceptions($filters);
        $financialImpact = $this->exceptionAnalysisService->calculateFinancialImpact($filters);
        
        $fileName = "exception_analysis_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
        $filePath = self::EXPORT_DIR . $fileName;

        $exportData = [
            'exception_categorization' => $this->formatExceptionsForExport($data),
            'financial_impact' => $this->formatFinancialImpactForExport($financialImpact)
        ];

        return $this->performExport($exportData, $fileName, $filePath, $format, 'exception_analysis');
    }

    /**
     * Export driver performance data
     */
    public function exportDriverPerformance(string $period, int $months, string $format): array
    {
        $rankingData = $this->driverPerformanceService->generateDriverRanking($period, $months);
        $fileName = "driver_performance_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
        $filePath = self::EXPORT_DIR . $fileName;

        $exportData = $this->formatDriverPerformanceForExport($rankingData);

        return $this->performExport($exportData, $fileName, $filePath, $format, 'driver_performance');
    }

    /**
     * Export container utilization data
     */
    public function exportContainerUtilization(array $dateRange, array $filters, string $format): array
    {
        $data = $this->containerUtilizationService->performCapacityPlanning($dateRange);
        $fileName = "container_utilization_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
        $filePath = self::EXPORT_DIR . $fileName;

        $exportData = $this->formatContainerUtilizationForExport($data);

        return $this->performExport($exportData, $fileName, $filePath, $format, 'container_utilization');
    }

    /**
     * Export transit time data
     */
    public function exportTransitTime(array $filters, string $format): array
    {
        $data = $this->transitTimeService->identifyImprovementOpportunities($filters);
        $fileName = "transit_time_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
        $filePath = self::EXPORT_DIR . $fileName;

        $exportData = $this->formatTransitTimeForExport($data);

        return $this->performExport($exportData, $fileName, $filePath, $format, 'transit_time');
    }

    // Private helper methods
    private function getReportData(string $reportType, array $filters, array $dateRange = []): ?array
    {
        return match($reportType) {
            'volume_analytics' => $this->originDestinationService->getVolumeAnalytics(
                $dateRange['date_range'] ?? $dateRange, 
                'detailed', 
                $filters
            ),
            'route_efficiency' => $this->routeEfficiencyService->identifyBottlenecks($filters),
            'on_time_delivery' => $this->onTimeDeliveryService->calculateOnTimeRate($filters),
            'exceptions' => $this->exceptionAnalysisService->categorizeExceptions($filters),
            'driver_performance' => $this->driverPerformanceService->generateDriverRanking('monthly', 3),
            'container_utilization' => $this->containerUtilizationService->performCapacityPlanning(
                $dateRange['date_range'] ?? $dateRange
            ),
            'transit_times' => $this->transitTimeService->identifyImprovementOpportunities($filters),
            default => null
        };
    }

    private function generateFileName(string $reportType, string $format): string
    {
        return $reportType . "_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
    }

    private function exceedsRowLimit(array $data): bool
    {
        $rows = $this->getRecordCount($data);
        return $rows > self::MAX_EXPORT_ROWS;
    }

    private function getRecordCount(array $data): int
    {
        $count = 0;
        $this->countRecordsRecursive($data, $count);
        return $count;
    }

    private function countRecordsRecursive(array $data, int &$count): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isRecordArray($value)) {
                    $count += count($value);
                } else {
                    $this->countRecordsRecursive($value, $count);
                }
            }
        }
    }

    private function isRecordArray(array $array): bool
    {
        // Check if this is an array of records (has consistent structure)
        if (empty($array)) return false;
        
        $firstKeys = array_keys($array[0]);
        foreach ($array as $record) {
            if (!is_array($record) || array_keys($record) !== $firstKeys) {
                return false;
            }
        }
        
        return true;
    }

    private function exportToExcel(array $data, string $fileName, string $filePath, string $reportType): array
    {
        $export = new OperationalReportExport($data, $reportType);
        Excel::store($export, $filePath, 'local');
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'format' => 'excel'
        ];
    }

    private function exportToCSV(array $data, string $fileName, string $filePath, string $reportType): array
    {
        $handle = fopen(storage_path('app/' . $filePath), 'w');
        
        // Add BOM for UTF-8
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        $this->writeCSVData($handle, $data);
        
        fclose($handle);
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'format' => 'csv'
        ];
    }

    private function writeCSVData($handle, array $data, int $depth = 0): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isRecordArray($value)) {
                    $this->writeCSVRecords($handle, $value, $key);
                } else {
                    $this->writeCSVData($handle, $value, $depth + 1);
                }
            }
        }
    }

    private function writeCSVRecords($handle, array $records, string $section = ''): void
    {
        if (!empty($section)) {
            fputcsv($handle, [$section . ' Section']);
            fputcsv($handle, []); // Empty row for separation
        }

        foreach ($records as $record) {
            fputcsv($handle, $record);
        }
        
        fputcsv($handle, []); // Empty row for separation
    }

    private function exportToPDF(array $data, string $fileName, string $filePath, string $reportType): array
    {
        // For PDF export, we'll create a simple text representation
        $pdfContent = $this->generatePDFContent($data, $reportType);
        Storage::disk('local')->put($filePath, $pdfContent);
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'format' => 'pdf'
        ];
    }

    private function generatePDFContent(array $data, string $reportType): string
    {
        $content = "Operational Report: " . str_replace('_', ' ', ucfirst($reportType)) . "\n";
        $content .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= str_repeat('=', 80) . "\n\n";
        
        $content .= $this->formatDataAsText($data);
        
        return $content;
    }

    private function formatDataAsText(array $data, int $depth = 0): string
    {
        $content = '';
        $indent = str_repeat('  ', $depth);
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $content .= $indent . ucwords(str_replace('_', ' ', $key)) . ":\n";
                $content .= $this->formatDataAsText($value, $depth + 1);
            } else {
                $content .= $indent . ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }
        
        return $content;
    }

    private function performExport(array $data, string $fileName, string $filePath, string $format, string $reportType): array
    {
        if (count($data) === 0) {
            throw new \Exception("No data available for export");
        }

        if ($this->exceedsRowLimit($data)) {
            throw new \Exception("Export data exceeds maximum limit of " . self::MAX_EXPORT_ROWS . " rows");
        }

        $fileSize = 0;
        $fileUrl = '';

        try {
            switch ($format) {
                case 'excel':
                    $result = $this->exportToExcel($data, $fileName, $filePath, $reportType);
                    break;
                case 'csv':
                    $result = $this->exportToCSV($data, $fileName, $filePath, $reportType);
                    break;
                case 'pdf':
                    $result = $this->exportToPDF($data, $fileName, $filePath, $reportType);
                    break;
                default:
                    throw new \Exception("Unsupported format: {$format}");
            }

            $fileSize = Storage::disk('local')->size($filePath);
            $fileUrl = Storage::url($filePath);

            return [
                'success' => true,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_url' => $fileUrl,
                'file_size' => $fileSize,
                'format' => $format,
                'report_type' => $reportType,
                'record_count' => $this->getRecordCount($data),
                'generated_at' => now()->toISOString(),
                'download_expires_at' => now()->addDays(7)->toISOString()
            ];

        } catch (\Exception $e) {
            throw new \Exception("Export failed: " . $e->getMessage());
        }
    }

    // Data formatting methods
    private function formatVolumeAnalyticsForExport(array $data): array
    {
        return [
            'summary' => $data['summary'] ?? [],
            'volume_by_date' => $data['volume_by_date'] ?? [],
            'route_analysis' => $data['route_analysis'] ?? [],
            'geographic_heat_map' => $data['geographic_heat_map'] ?? [],
            'trends' => $data['trends'] ?? []
        ];
    }

    private function formatRouteEfficiencyForExport(array $data): array
    {
        return [
            'bottlenecks' => $data['bottleneck_analysis'] ?? [],
            'system_metrics' => $data['system_wide_metrics'] ?? [],
            'prioritized_actions' => $data['prioritized_actions'] ?? []
        ];
    }

    private function formatOnTimeForExport(array $data): array
    {
        return [
            'overall_metrics' => $data['overall_metrics'] ?? [],
            'detailed_breakdown' => $data['detailed_breakdown'] ?? [],
            'trends' => $data['trends'] ?? []
        ];
    }

    private function formatVarianceForExport(array $data): array
    {
        return [
            'variance_analysis' => $data['variance_analysis'] ?? [],
            'temporal_patterns' => $data['temporal_patterns'] ?? [],
            'root_causes' => $data['variance_sources'] ?? []
        ];
    }

    private function formatExceptionsForExport(array $data): array
    {
        return [
            'categorized_exceptions' => $data['categorized_exceptions'] ?? [],
            'summary' => $data['summary'] ?? [],
            'trends' => $data['trends'] ?? [],
            'risk_assessment' => $data['risk_assessment'] ?? []
        ];
    }

    private function formatFinancialImpactForExport(array $data): array
    {
        return [
            'direct_costs' => $data['direct_costs'] ?? [],
            'indirect_costs' => $data['indirect_costs'] ?? [],
            'cost_breakdown' => $data['cost_breakdown'] ?? [],
            'roi_analysis' => $data['roi_analysis'] ?? []
        ];
    }

    private function formatDriverPerformanceForExport(array $data): array
    {
        return [
            'driver_rankings' => $data['driver_rankings'] ?? [],
            'fleet_summary' => $data['fleet_summary'] ?? [],
            'performance_categories' => $this->categorizeDriverPerformance($data['driver_rankings'] ?? [])
        ];
    }

    private function categorizeDriverPerformance(array $rankings): array
    {
        $excellent = [];
        $good = [];
        $needs_improvement = [];
        
        foreach ($rankings as $driver) {
            $score = $driver['composite_score'] ?? 0;
            
            if ($score >= 90) {
                $excellent[] = $driver;
            } elseif ($score >= 75) {
                $good[] = $driver;
            } else {
                $needs_improvement[] = $driver;
            }
        }
        
        return [
            'excellent_performers' => $excellent,
            'good_performers' => $good,
            'needs_improvement' => $needs_improvement
        ];
    }

    private function formatContainerUtilizationForExport(array $data): array
    {
        return [
            'capacity_analysis' => $data['capacity_analysis'] ?? [],
            'demand_forecast' => $data['demand_forecast'] ?? [],
            'bottleneck_identification' => $data['bottleneck_identification'] ?? [],
            'recommendations' => $data['recommendations'] ?? []
        ];
    }

    private function formatTransitTimeForExport(array $data): array
    {
        return [
            'improvement_opportunities' => $data['opportunity_summary'] ?? [],
            'bottleneck_improvements' => $data['bottleneck_improvements'] ?? [],
            'route_optimizations' => $data['route_optimizations'] ?? [],
            'expected_benefits' => $data['expected_benefits'] ?? []
        ];
    }
}