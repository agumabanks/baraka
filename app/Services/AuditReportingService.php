<?php

namespace App\Services;

use App\Models\AuditTrailLog;
use App\Models\AccessibilityComplianceLog;
use App\Models\ComplianceViolation;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AuditReportingService
{
    /**
     * Generate comprehensive audit report
     */
    public function generateAuditReport(array $filters = []): array
    {
        $reportData = [];
        
        // Date range filter
        $dateFrom = $filters['date_from'] ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? now()->format('Y-m-d');
        
        // Get audit logs
        $auditLogs = AuditTrailLog::whereBetween('occurred_at', [$dateFrom, $dateTo])
                                 ->when($filters['user_id'] ?? null, fn($q, $userId) => 
                                     $q->where('user_id', $userId))
                                 ->when($filters['action_type'] ?? null, fn($q, $actionType) => 
                                     $q->where('action_type', $actionType))
                                 ->when($filters['severity'] ?? null, fn($q, $severity) => 
                                     $q->where('severity', $severity))
                                 ->get();
        
        // Get accessibility compliance data
        $accessibilityTests = AccessibilityComplianceLog::whereBetween('tested_at', [$dateFrom, $dateTo])
                                                       ->get();
        
        // Get compliance violations
        $violations = ComplianceViolation::whereBetween('discovered_at', [$dateFrom, $dateTo])
                                        ->get();
        
        $reportData = [
            'report_metadata' => [
                'generated_at' => now()->toISOString(),
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                'total_audit_logs' => $auditLogs->count(),
                'total_accessibility_tests' => $accessibilityTests->count(),
                'total_violations' => $violations->count(),
                'generated_by' => auth()->user()?->email ?? 'system',
            ],
            
            'audit_summary' => [
                'actions_by_type' => $auditLogs->groupBy('action_type')->map->count(),
                'actions_by_severity' => $auditLogs->groupBy('severity')->map->count(),
                'actions_by_module' => $auditLogs->groupBy('module')->map->count(),
                'critical_actions' => $auditLogs->where('severity', 'critical')->count(),
                'user_activity' => $auditLogs->groupBy('user_id')->map->count(),
                'daily_activity' => $auditLogs->groupBy(fn($log) => $log->occurred_at->format('Y-m-d'))
                                           ->map->count(),
            ],
            
            'accessibility_summary' => [
                'average_compliance_score' => round($accessibilityTests->avg('compliance_score'), 2),
                'compliance_by_score_range' => [
                    'excellent_90_100' => $accessibilityTests->whereBetween('compliance_score', [90, 100])->count(),
                    'good_70_89' => $accessibilityTests->whereBetween('compliance_score', [70, 89])->count(),
                    'needs_improvement_50_69' => $accessibilityTests->whereBetween('compliance_score', [50, 69])->count(),
                    'poor_0_49' => $accessibilityTests->where('compliance_score', '<', 50)->count(),
                ],
                'violations_by_type' => $accessibilityTests->flatMap->violations
                    ->groupBy('id')
                    ->map->count()
                    ->sortDesc()
                    ->take(10),
                'pages_by_compliance' => $accessibilityTests->groupBy('page_url')
                    ->map(fn($tests) => round($tests->avg('compliance_score'), 2)),
            ],
            
            'compliance_violations' => [
                'violations_by_framework' => $violations->groupBy('compliance_framework')->map->count(),
                'violations_by_severity' => $violations->groupBy('severity')->map->count(),
                'violations_by_type' => $violations->groupBy('violation_type')->map->count(),
                'resolution_status' => [
                    'open' => $violations->whereNull('resolved_at')->where('is_false_positive', false)->count(),
                    'resolved' => $violations->whereNotNull('resolved_at')->count(),
                    'false_positive' => $violations->where('is_false_positive', true)->count(),
                ],
            ],
            
            'recommendations' => $this->generateRecommendations($auditLogs, $accessibilityTests, $violations),
        ];
        
        return $reportData;
    }

    /**
     * Export audit report to Excel
     */
    public function exportToExcel(array $filters = []): string
    {
        $reportData = $this->generateAuditReport($filters);
        
        $fileName = 'audit-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $filePath = "reports/{$fileName}";
        
        // Create export data structure
        $exportData = new class($reportData) implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            private $data;
            
            public function __construct($data) {
                $this->data = $data;
            }
            
            public function readCell($column, $row, $worksheetName = '') {
                return true; // Allow all cells to be read
            }
        };
        
        // Generate Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add report metadata
        $sheet->setCellValue('A1', 'Audit Report');
        $sheet->setCellValue('A2', 'Generated: ' . $reportData['report_metadata']['generated_at']);
        $sheet->setCellValue('A3', 'Date Range: ' . $reportData['report_metadata']['date_range']['from'] . ' to ' . $reportData['report_metadata']['date_range']['to']);
        $sheet->setCellValue('A4', 'Generated By: ' . $reportData['report_metadata']['generated_by']);
        
        // Add audit summary
        $row = 6;
        $sheet->setCellValue('A' . $row, 'Audit Summary');
        $row += 2;
        
        foreach ($reportData['audit_summary'] as $key => $value) {
            $sheet->setCellValue('A' . $row, ucwords(str_replace('_', ' ', $key)));
            if (is_array($value)) {
                $col = 'B';
                foreach ($value as $k => $v) {
                    $sheet->setCellValue($col . $row, $k . ': ' . $v);
                    $col++;
                }
            } else {
                $sheet->setCellValue('B' . $row, $value);
            }
            $row++;
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $content = $this->streamToString($writer);
        
        Storage::put($filePath, $content);
        
        return $filePath;
    }

    /**
     * Export audit report to PDF
     */
    public function exportToPDF(array $filters = []): string
    {
        $reportData = $this->generateAuditReport($filters);
        
        $fileName = 'audit-report-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        $filePath = "reports/{$fileName}";
        
        // Generate PDF using a simple HTML approach
        $html = $this->generateReportHTML($reportData);
        
        // For now, we'll save as HTML and note that PDF generation would need a library like DomPDF
        Storage::put($filePath . '.html', $html);
        
        return $filePath . '.html';
    }

    /**
     * Export audit logs to CSV
     */
    public function exportAuditLogsToCSV(array $filters = []): string
    {
        $query = AuditTrailLog::query()
            ->with('user')
            ->when($filters['date_from'] ?? null, fn($q, $dateFrom) => 
                $q->where('occurred_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn($q, $dateTo) => 
                $q->where('occurred_at', '<=', $dateTo))
            ->when($filters['user_id'] ?? null, fn($q, $userId) => 
                $q->where('user_id', $userId))
            ->when($filters['action_type'] ?? null, fn($q, $actionType) => 
                $q->where('action_type', $actionType))
            ->orderBy('occurred_at', 'desc');
        
        $logs = $query->get();
        
        $fileName = 'audit-logs-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $filePath = "reports/{$fileName}";
        
        $csvData = [];
        $csvData[] = [
            'Log ID',
            'User Email',
            'Action Type',
            'Resource Type',
            'Resource ID',
            'Module',
            'Severity',
            'IP Address',
            'Timestamp',
            'Description'
        ];
        
        foreach ($logs as $log) {
            $csvData[] = [
                $log->log_id,
                $log->user?->email ?? 'Anonymous',
                $log->action_type,
                $log->resource_type,
                $log->resource_id ?? '',
                $log->module,
                $log->severity,
                $log->ip_address,
                $log->occurred_at->format('Y-m-d H:i:s'),
                $log->action_description
            ];
        }
        
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(fn($field) => '"' . str_replace('"', '""', $field) . '"', $row)) . "\n";
        }
        
        Storage::put($filePath, $csvContent);
        
        return $filePath;
    }

    /**
     * Generate accessibility compliance report
     */
    public function generateAccessibilityReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? now()->format('Y-m-d');
        
        $tests = AccessibilityComplianceLog::whereBetween('tested_at', [$dateFrom, $dateTo])
                                         ->get();
        
        $violations = ComplianceViolation::where('compliance_framework', 'WCAG')
                                       ->whereBetween('discovered_at', [$dateFrom, $dateTo])
                                       ->get();
        
        return [
            'report_metadata' => [
                'generated_at' => now()->toISOString(),
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                'total_tests' => $tests->count(),
                'total_violations' => $violations->count(),
            ],
            
            'compliance_summary' => [
                'overall_score' => round($tests->avg('compliance_score'), 2),
                'score_distribution' => [
                    'excellent_90_100' => $tests->whereBetween('compliance_score', [90, 100])->count(),
                    'good_70_89' => $tests->whereBetween('compliance_score', [70, 89])->count(),
                    'acceptable_50_69' => $tests->whereBetween('compliance_score', [50, 69])->count(),
                    'poor_below_50' => $tests->where('compliance_score', '<', 50)->count(),
                ],
                'pages_requiring_attention' => $tests->where('compliance_score', '<', 70)->groupBy('page_url')->count(),
            ],
            
            'violation_analysis' => [
                'critical_violations' => $tests->flatMap->violations->filter(fn($v) => ($v['impact'] ?? '') === 'critical')->count(),
                'violation_trends' => $tests->flatMap->violations->groupBy('id')->map->count()->sortDesc()->take(5),
                'wcag_criteria_coverage' => $this->analyzeWCAGCoverage($tests),
            ],
            
            'recommendations' => $this->generateAccessibilityRecommendations($tests, $violations),
        ];
    }

    /**
     * Generate security audit report
     */
    public function generateSecurityAuditReport(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? now()->format('Y-m-d');
        
        $auditLogs = AuditTrailLog::whereBetween('occurred_at', [$dateFrom, $dateTo])
                                 ->whereIn('severity', ['warning', 'error', 'critical'])
                                 ->get();
        
        $violations = ComplianceViolation::whereIn('compliance_framework', ['GDPR', 'SOX', 'HIPAA'])
                                       ->whereBetween('discovered_at', [$dateFrom, $dateTo])
                                       ->get();
        
        return [
            'security_events' => [
                'failed_logins' => $auditLogs->where('action_type', 'failed_login')->count(),
                'privilege_escalations' => $auditLogs->where('action_type', 'privilege_escalation')->count(),
                'data_access_violations' => $auditLogs->where('action_type', 'unauthorized_access')->count(),
                'system_errors' => $auditLogs->where('severity', 'error')->count(),
            ],
            
            'compliance_status' => [
                'gdpr_violations' => $violations->where('compliance_framework', 'GDPR')->count(),
                'sox_violations' => $violations->where('compliance_framework', 'SOX')->count(),
                'hipaa_violations' => $violations->where('compliance_framework', 'HIPAA')->count(),
            ],
            
            'risk_assessment' => [
                'high_risk_events' => $auditLogs->where('severity', 'critical')->count(),
                'medium_risk_events' => $auditLogs->where('severity', 'error')->count(),
                'low_risk_events' => $auditLogs->where('severity', 'warning')->count(),
            ],
        ];
    }

    /**
     * Generate recommendations based on audit data
     */
    private function generateRecommendations($auditLogs, $accessibilityTests, $violations): array
    {
        $recommendations = [];
        
        // Accessibility recommendations
        $avgScore = $accessibilityTests->avg('compliance_score');
        if ($avgScore < 70) {
            $recommendations[] = [
                'category' => 'Accessibility',
                'priority' => 'High',
                'title' => 'Improve Overall Accessibility Score',
                'description' => 'Current average score is below acceptable threshold (70%)',
                'action' => 'Conduct comprehensive accessibility audit and implement fixes',
            ];
        }
        
        // Security recommendations
        $failedLogins = $auditLogs->where('action_type', 'failed_login')->count();
        if ($failedLogins > 100) {
            $recommendations[] = [
                'category' => 'Security',
                'priority' => 'High',
                'title' => 'High Number of Failed Login Attempts',
                'description' => 'More than 100 failed login attempts detected',
                'action' => 'Implement account lockout policies and monitor for brute force attacks',
            ];
        }
        
        // Compliance recommendations
        $criticalViolations = $violations->where('severity', 'critical')->count();
        if ($criticalViolations > 0) {
            $recommendations[] = [
                'category' => 'Compliance',
                'priority' => 'Critical',
                'title' => 'Critical Compliance Violations Detected',
                'description' => 'Immediate action required to resolve critical violations',
                'action' => 'Prioritize resolution of all critical compliance violations',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Generate HTML report
     */
    private function generateReportHTML(array $reportData): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Audit Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; }
        .metric { display: inline-block; margin: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Audit Report</h1>
        <p>Generated: ' . $reportData['report_metadata']['generated_at'] . '</p>
        <p>Date Range: ' . $reportData['report_metadata']['date_range']['from'] . ' to ' . $reportData['report_metadata']['date_range']['to'] . '</p>
    </div>
    
    <div class="section">
        <h2>Summary</h2>
        <div class="metric">
            <strong>Total Audit Logs:</strong> ' . $reportData['report_metadata']['total_audit_logs'] . '
        </div>
        <div class="metric">
            <strong>Accessibility Tests:</strong> ' . $reportData['report_metadata']['total_accessibility_tests'] . '
        </div>
        <div class="metric">
            <strong>Compliance Violations:</strong> ' . $reportData['report_metadata']['total_violations'] . '
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Analyze WCAG compliance coverage
     */
    private function analyzeWCAGCoverage($tests): array
    {
        $allCriteria = [];
        
        foreach ($tests as $test) {
            foreach ($test->violations as $violation) {
                if (isset($violation['tags'])) {
                    foreach ($violation['tags'] as $tag) {
                        if (str_starts_with($tag, 'wcag')) {
                            $allCriteria[] = $tag;
                        }
                    }
                }
            }
        }
        
        return array_count_values($allCriteria);
    }

    /**
     * Generate accessibility-specific recommendations
     */
    private function generateAccessibilityRecommendations($tests, $violations): array
    {
        $recommendations = [];
        
        $avgScore = $tests->avg('compliance_score');
        
        if ($avgScore < 70) {
            $recommendations[] = [
                'priority' => 'High',
                'title' => 'Improve Overall Accessibility Score',
                'description' => 'Address all identified violations to reach acceptable compliance level',
            ];
        }
        
        $criticalViolations = $tests->flatMap->violations->filter(fn($v) => ($v['impact'] ?? '') === 'critical');
        if ($criticalViolations->count() > 0) {
            $recommendations[] = [
                'priority' => 'Critical',
                'title' => 'Fix Critical Accessibility Issues',
                'description' => 'Critical violations prevent users with disabilities from accessing content',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Convert writer to string
     */
    private function streamToString($writer): string
    {
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
}