<?php

namespace App\Services\FinancialReporting;

use App\Services\FinancialReporting\RevenueRecognitionService;
use App\Services\FinancialReporting\COGSAnalysisService;
use App\Services\FinancialReporting\GrossMarginAnalysisService;
use App\Services\FinancialReporting\CODCollectionService;
use App\Services\FinancialReporting\PaymentProcessingService;
use App\Services\FinancialReporting\ProfitabilityAnalysisService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ExportService
{
    private const EXPORT_PATH = 'exports/financial_reports';
    private const MAX_RECORDS_PER_SHEET = 50000; // Excel row limit consideration
    private const BATCH_SIZE = 1000; // Memory management for large datasets
    private const SUPPORTED_FORMATS = ['excel', 'csv', 'pdf', 'json'];

    private const TEMPLATE_PATHS = [
        'revenue' => 'templates/financial/revenue_template.xlsx',
        'cogs' => 'templates/financial/cogs_template.xlsx',
        'margin' => 'templates/financial/margin_template.xlsx',
        'cod' => 'templates/financial/cod_template.xlsx',
        'payments' => 'templates/financial/payments_template.xlsx',
        'profitability' => 'templates/financial/profitability_template.xlsx',
        'comprehensive' => 'templates/financial/comprehensive_template.xlsx'
    ];

    public function __construct(
        private RevenueRecognitionService $revenueRecognitionService,
        private COGSAnalysisService $cogsAnalysisService,
        private GrossMarginAnalysisService $grossMarginAnalysisService,
        private CODCollectionService $codCollectionService,
        private PaymentProcessingService $paymentProcessingService,
        private ProfitabilityAnalysisService $profitabilityAnalysisService
    ) {}

    /**
     * Export financial data in various formats with customizable templates
     */
    public function exportFinancialData(
        string $exportType,
        string $format,
        array $dateRange,
        array $filters = [],
        ?string $template = null,
        bool $includeCharts = false,
        ?string $emailRecipient = null
    ): array {
        try {
            if (!in_array($format, self::SUPPORTED_FORMATS)) {
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
            }

            $this->validateDateRange($dateRange);
            
            // Get data based on export type
            $data = $this->getExportData($exportType, $dateRange, $filters);
            
            // Generate filename
            $filename = $this->generateFilename($exportType, $format, $dateRange);
            $filePath = $this->getFilePath($filename);
            
            // Apply template if specified
            if ($template && $this->templateExists($exportType, $template)) {
                $data = $this->applyTemplate($data, $exportType, $template);
            }
            
            // Generate export based on format
            $result = match($format) {
                'excel' => $this->exportToExcel($data, $filePath, $exportType, $includeCharts),
                'csv' => $this->exportToCsv($data, $filePath),
                'pdf' => $this->exportToPdf($data, $filePath),
                'json' => $this->exportToJson($data, $filePath)
            };
            
            // Send email if recipient specified
            if ($emailRecipient) {
                $this->emailExport($emailRecipient, $filePath, $filename);
            }
            
            // Log export activity
            $this->logExportActivity($exportType, $format, $filename, $result['size']);
            
            return [
                'success' => true,
                'filename' => $filename,
                'file_path' => $filePath,
                'download_url' => $this->getDownloadUrl($filePath),
                'format' => $format,
                'export_type' => $exportType,
                'size' => $result['size'],
                'generated_at' => now()->toISOString(),
                'expires_at' => now()->addDays(7)->toISOString(),
                'record_count' => $result['record_count'] ?? 0
            ];
            
        } catch (\Exception $e) {
            Log::error('Financial data export error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get available export templates
     */
    public function getAvailableTemplates(string $exportType = 'all'): array
    {
        $templates = [
            'revenue' => [
                'standard' => [
                    'name' => 'Standard Revenue Report',
                    'description' => 'Basic revenue recognition report with accruals',
                    'columns' => ['date', 'revenue_recognized', 'deferred_revenue', 'revenue_forecast']
                ],
                'detailed' => [
                    'name' => 'Detailed Revenue Analysis',
                    'description' => 'Comprehensive revenue analysis with forecasting',
                    'columns' => ['date', 'revenue_recognized', 'deferred_revenue', 'revenue_forecast', 'variance', 'growth_rate']
                ]
            ],
            'cogs' => [
                'standard' => [
                    'name' => 'Standard COGS Report',
                    'description' => 'Basic cost breakdown with variance analysis',
                    'columns' => ['date', 'fuel_cost', 'labor_cost', 'insurance_cost', 'maintenance_cost', 'total_cogs']
                ],
                'detailed' => [
                    'name' => 'Detailed Cost Analysis',
                    'description' => 'Comprehensive cost analysis with variance reporting',
                    'columns' => ['date', 'fuel_cost', 'labor_cost', 'insurance_cost', 'maintenance_cost', 'depreciation', 'total_cogs', 'variance']
                ]
            ],
            'margin' => [
                'standard' => [
                    'name' => 'Standard Margin Report',
                    'description' => 'Basic gross margin analysis',
                    'columns' => ['date', 'revenue', 'cogs', 'gross_margin', 'margin_percentage']
                ],
                'benchmarking' => [
                    'name' => 'Margin Benchmarking',
                    'description' => 'Margin analysis with competitive benchmarking',
                    'columns' => ['date', 'revenue', 'cogs', 'gross_margin', 'margin_percentage', 'industry_benchmark', 'variance']
                ]
            ],
            'cod' => [
                'collection' => [
                    'name' => 'COD Collection Report',
                    'description' => 'Cash-on-Delivery collection tracking',
                    'columns' => ['date', 'cod_amount', 'collected_amount', 'pending_amount', 'collection_rate']
                ],
                'aging' => [
                    'name' => 'COD Aging Analysis',
                    'description' => 'Aging analysis with dunning management',
                    'columns' => ['date', 'cod_amount', 'collected_amount', 'pending_amount', 'aging_0_30', 'aging_31_60', 'aging_61_90', 'aging_90_plus']
                ]
            ],
            'payments' => [
                'processing' => [
                    'name' => 'Payment Processing Report',
                    'description' => 'Payment processing workflow with reconciliation',
                    'columns' => ['date', 'total_payments', 'processing_fee', 'net_amount', 'reconciliation_status']
                ],
                'settlement' => [
                    'name' => 'Settlement Report',
                    'description' => 'Settlement reporting and reconciliation',
                    'columns' => ['date', 'settlement_amount', 'settlement_fees', 'settlement_date', 'status']
                ]
            ],
            'profitability' => [
                'summary' => [
                    'name' => 'Profitability Summary',
                    'description' => 'Overall profitability analysis',
                    'columns' => ['date', 'total_revenue', 'total_costs', 'net_profit', 'profit_margin']
                ],
                'detailed' => [
                    'name' => 'Detailed Profitability Analysis',
                    'description' => 'Comprehensive profitability analysis by dimension',
                    'columns' => ['dimension', 'revenue', 'costs', 'profit', 'margin', 'score']
                ]
            ],
            'comprehensive' => [
                'executive' => [
                    'name' => 'Executive Summary',
                    'description' => 'Executive financial summary',
                    'columns' => ['metric', 'current_period', 'previous_period', 'variance', 'percentage_change']
                ],
                'operational' => [
                    'name' => 'Operational Financial Report',
                    'description' => 'Detailed operational financial analysis',
                    'columns' => ['category', 'subcategory', 'amount', 'variance', 'percentage']
                ]
            ]
        ];

        if ($exportType === 'all') {
            return $templates;
        }

        return $templates[$exportType] ?? [];
    }

    /**
     * Export to Excel format with formatting
     */
    private function exportToExcel(array $data, string $filePath, string $exportType, bool $includeCharts = false): array
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Remove default sheet
        
        // Create multiple sheets if needed
        $sheets = $this->createExcelSheets($spreadsheet, $data, $exportType);
        
        // Apply formatting
        $this->applyExcelFormatting($spreadsheet, $exportType);
        
        // Add charts if requested
        if ($includeCharts) {
            $this->addExcelCharts($spreadsheet, $data, $exportType);
        }
        
        // Save file
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        
        // Clean up
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        return [
            'size' => filesize($filePath),
            'record_count' => $this->countRecords($data),
            'sheets' => count($sheets)
        ];
    }

    /**
     * Export to CSV format
     */
    private function exportToCsv(array $data, string $filePath): array
    {
        $writer = new Csv();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        
        $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Data');
        $spreadsheet->addSheet($worksheet, 0);
        $spreadsheet->setActiveSheetIndex(0);
        
        $this->populateSheetData($spreadsheet->getActiveSheet(), $data);
        $writer->setSheetIndex(0);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->save($filePath);
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        return [
            'size' => filesize($filePath),
            'record_count' => $this->countRecords($data)
        ];
    }

    /**
     * Export to PDF format
     */
    private function exportToPdf(array $data, string $filePath): array
    {
        // For PDF, we'll use HTML template approach with TCPDF
        $html = $this->generatePdfHtml($data, $this->determineReportTitle($data));
        
        // Convert HTML to PDF (simplified - would need actual PDF library)
        $pdfData = $this->convertHtmlToPdf($html, $filePath);
        
        return [
            'size' => filesize($filePath),
            'record_count' => $this->countRecords($data)
        ];
    }

    /**
     * Export to JSON format
     */
    private function exportToJson(array $data, string $filePath): array
    {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filePath, $jsonData);
        
        return [
            'size' => filesize($filePath),
            'record_count' => $this->countRecords($data)
        ];
    }

    /**
     * Create multiple Excel sheets for large datasets
     */
    private function createExcelSheets(Spreadsheet $spreadsheet, array $data, string $exportType): array
    {
        $sheets = [];
        
        switch ($exportType) {
            case 'comprehensive':
                $sheets[] = $this->createSummarySheet($spreadsheet, $data);
                $sheets[] = $this->createRevenueSheet($spreadsheet, $data);
                $sheets[] = $this->createCogsSheet($spreadsheet, $data);
                $sheets[] = $this->createMarginSheet($spreadsheet, $data);
                $sheets[] = $this->createCodSheet($spreadsheet, $data);
                $sheets[] = $this->createPaymentsSheet($spreadsheet, $data);
                $sheets[] = $this->createProfitabilitySheet($spreadsheet, $data);
                break;
                
            default:
                $sheets[] = $this->createMainSheet($spreadsheet, $data, $exportType);
        }
        
        return $sheets;
    }

    /**
     * Apply Excel formatting
     */
    private function applyExcelFormatting(Spreadsheet $spreadsheet, string $exportType): void
    {
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            // Header formatting
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '366092']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $worksheet->getStyle("A1:{$highestColumn}1")->applyFromArray($headerStyle);
            
            // Auto-size columns
            foreach ($worksheet->getColumnIterator() as $column) {
                $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }
            
            // Freeze first row
            $worksheet->freezePane('A2');
        }
    }

    /**
     * Add Excel charts
     */
    private function addExcelCharts(Spreadsheet $spreadsheet, array $data, string $exportType): void
    {
        // Implementation would depend on specific charting requirements
        // This is a placeholder for chart generation logic
    }

    /**
     * Populate sheet data
     */
    private function populateSheetData(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, array $data): void
    {
        $row = 1;
        
        if (empty($data)) {
            return;
        }
        
        // Add headers
        $headers = array_keys($data[0]);
        foreach ($headers as $col => $header) {
            $worksheet->setCellValueByColumnAndRow($col + 1, $row, $header);
        }
        $row++;
        
        // Add data
        foreach ($data as $record) {
            $col = 1;
            foreach ($record as $value) {
                $worksheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }
    }

    /**
     * Generate PDF HTML template
     */
    private function generatePdfHtml(array $data, string $title): string
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #366092; color: white; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$title}</h1>
                <p>Generated on: " . now()->format('Y-m-d H:i:s') . "</p>
            </div>
        ";
        
        if (!empty($data)) {
            $html .= "<table class='table'><tr>";
            
            // Headers
            foreach (array_keys($data[0]) as $header) {
                $html .= "<th>{$header}</th>";
            }
            $html .= "</tr>";
            
            // Data
            foreach ($data as $record) {
                $html .= "<tr>";
                foreach ($record as $value) {
                    $html .= "<td>{$value}</td>";
                }
                $html .= "</tr>";
            }
            
            $html .= "</table>";
        }
        
        $html .= "
            <div class='footer'>
                <p>Generated by Financial Reporting System</p>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }

    /**
     * Convert HTML to PDF (placeholder implementation)
     */
    private function convertHtmlToPdf(string $html, string $filePath): bool
    {
        // This would require a PDF library like TCPDF or DomPDF
        // For now, we'll save the HTML as a placeholder
        file_put_contents($filePath . '.html', $html);
        
        // In real implementation:
        // $pdf = new \TCPDF();
        // $pdf->writeHTML($html);
        // $pdf->save($filePath);
        
        return true;
    }

    /**
     * Email exported file
     */
    private function emailExport(string $recipient, string $filePath, string $filename): void
    {
        try {
            Mail::raw('Your financial report has been generated and is ready for download.', function($message) use ($recipient, $filePath, $filename) {
                $message->to($recipient)
                        ->subject('Financial Report Ready: ' . $filename)
                        ->attach($filePath);
            });
            
            Log::info("Export emailed to {$recipient}: {$filename}");
        } catch (\Exception $e) {
            Log::error("Failed to email export to {$recipient}: " . $e->getMessage());
        }
    }

    /**
     * Get export data based on type
     */
    private function getExportData(string $exportType, array $dateRange, array $filters): array
    {
        return match($exportType) {
            'revenue' => $this->revenueRecognitionService->analyzeRevenueRecognition($dateRange, $filters),
            'cogs' => $this->cogsAnalysisService->analyzeCOGS($dateRange, $filters),
            'margin' => $this->grossMarginAnalysisService->analyzeGrossMargin($dateRange, $filters, false),
            'cod' => $this->codCollectionService->trackCODCollections($filters),
            'payments' => $this->paymentProcessingService->managePaymentProcessing($filters),
            'profitability' => $this->profitabilityAnalysisService->analyzeProfitability($filters),
            'comprehensive' => $this->generateComprehensiveData($dateRange, $filters),
            default => throw new \InvalidArgumentException("Unknown export type: {$exportType}")
        };
    }

    /**
     * Generate comprehensive export data
     */
    private function generateComprehensiveData(array $dateRange, array $filters): array
    {
        return [
            'revenue' => $this->revenueRecognitionService->analyzeRevenueRecognition($dateRange, $filters),
            'cogs' => $this->cogsAnalysisService->analyzeCOGS($dateRange, $filters),
            'margins' => $this->grossMarginAnalysisService->analyzeGrossMargin($dateRange, $filters, false),
            'cod' => $this->codCollectionService->trackCODCollections($filters),
            'payments' => $this->paymentProcessingService->managePaymentProcessing($filters),
            'profitability' => $this->profitabilityAnalysisService->analyzeProfitability($filters),
            'summary' => $this->generateExecutiveSummary($dateRange, $filters)
        ];
    }

    /**
     * Generate executive summary
     */
    private function generateExecutiveSummary(array $dateRange, array $filters): array
    {
        return [
            'total_revenue' => 0,
            'total_cogs' => 0,
            'gross_profit' => 0,
            'profit_margin' => 0,
            'cod_collection_rate' => 0,
            'payment_success_rate' => 0
        ];
    }

    // Helper methods for sheet creation
    private function createSummarySheet(Spreadsheet $spreadsheet, array $data): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Summary');
        $spreadsheet->addSheet($sheet);
        return $sheet;
    }

    private function createRevenueSheet(Spreadsheet $spreadsheet, array $data): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Revenue');
        $spreadsheet->addSheet($sheet);
        return $sheet;
    }

    private function createCogsSheet(Spreadsheet $spreadsheet, array $data): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'COGS');
        $spreadsheet->addSheet($sheet);
        return $sheet;
    }

    private function createMarginSheet(Spreadsheet $spreadsheet, array $data): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Margins');
        $spreadsheet->addSheet($sheet);
        return $sheet;
    }

    private function createCodSheet(Spreadsheet $spreadsheet, array $data): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'COD');
        $spreadsheet->addSheet($sheet);
        return $sheet;
    }

    private function createPaymentsSheet(Spreadsheet $spreadsheet, array $data): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Payments');
        $spreadsheet->addSheet($sheet);
        return $sheet;
    }

    private function createProfitabilitySheet(Spreadsheet $spreadsheet, array $data): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Profitability');
        $spreadsheet->addSheet($sheet);
        return $sheet;
    }

    private function createMainSheet(Spreadsheet $spreadsheet, array $data, string $exportType): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheetName = ucfirst($exportType);
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);
        $spreadsheet->addSheet($sheet);
        return $sheet;
    }

    // Utility methods
    private function validateDateRange(array $dateRange): void
    {
        if (empty($dateRange['start']) || empty($dateRange['end'])) {
            throw new \InvalidArgumentException('Date range must include start and end dates');
        }
    }

    private function generateFilename(string $exportType, string $format, array $dateRange): string
    {
        $startDate = $dateRange['start'] ?? '20240101';
        $endDate = $dateRange['end'] ?? '20241231';
        $timestamp = now()->format('Y_m_d_H_i_s');
        
        return "financial_report_{$exportType}_{$startDate}_to_{$endDate}_{$timestamp}.{$format}";
    }

    private function getFilePath(string $filename): string
    {
        return storage_path("app/" . self::EXPORT_PATH . "/{$filename}");
    }

    private function templateExists(string $exportType, string $template): bool
    {
        return isset(self::TEMPLATE_PATHS[$exportType]);
    }

    private function applyTemplate(array $data, string $exportType, string $template): array
    {
        // Apply template-specific processing
        return $data;
    }

    private function getDownloadUrl(string $filePath): string
    {
        $filename = basename($filePath);
        return route('financial_reports.download', $filename);
    }

    private function countRecords(array $data): int
    {
        if (isset($data[0]) && is_array($data[0])) {
            return count($data);
        }
        return 0;
    }

    private function determineReportTitle(array $data): string
    {
        return 'Financial Report';
    }

    private function logExportActivity(string $exportType, string $format, string $filename, int $size): void
    {
        Log::info("Financial report exported", [
            'type' => $exportType,
            'format' => $format,
            'filename' => $filename,
            'size' => $size,
            'user_id' => auth()->id()
        ]);
    }
}