<?php

namespace App\Services\Analytics;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduledReportService
{
    protected ReportGenerationService $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get all scheduled reports
     */
    public function getScheduledReports(?int $userId = null): array
    {
        $query = DB::table('scheduled_reports');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Create a scheduled report
     */
    public function createSchedule(array $data): array
    {
        $id = DB::table('scheduled_reports')->insertGetId([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'report_type' => $data['report_type'],
            'filters' => json_encode($data['filters'] ?? []),
            'schedule_type' => $data['schedule_type'], // daily, weekly, monthly
            'schedule_day' => $data['schedule_day'] ?? null, // 0-6 for weekly, 1-28 for monthly
            'schedule_time' => $data['schedule_time'] ?? '08:00',
            'email_recipients' => json_encode($data['recipients'] ?? []),
            'export_format' => $data['format'] ?? 'pdf',
            'is_active' => true,
            'last_run_at' => null,
            'next_run_at' => $this->calculateNextRun(
                $data['schedule_type'],
                $data['schedule_day'] ?? null,
                $data['schedule_time'] ?? '08:00'
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Scheduled report created', [
            'id' => $id,
            'name' => $data['name'],
            'type' => $data['report_type'],
        ]);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Update a scheduled report
     */
    public function updateSchedule(int $id, array $data): array
    {
        $updateData = [
            'updated_at' => now(),
        ];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['filters'])) {
            $updateData['filters'] = json_encode($data['filters']);
        }
        if (isset($data['schedule_type'])) {
            $updateData['schedule_type'] = $data['schedule_type'];
        }
        if (isset($data['schedule_day'])) {
            $updateData['schedule_day'] = $data['schedule_day'];
        }
        if (isset($data['schedule_time'])) {
            $updateData['schedule_time'] = $data['schedule_time'];
        }
        if (isset($data['recipients'])) {
            $updateData['email_recipients'] = json_encode($data['recipients']);
        }
        if (isset($data['format'])) {
            $updateData['export_format'] = $data['format'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        // Recalculate next run
        $updateData['next_run_at'] = $this->calculateNextRun(
            $data['schedule_type'] ?? 'daily',
            $data['schedule_day'] ?? null,
            $data['schedule_time'] ?? '08:00'
        );

        DB::table('scheduled_reports')->where('id', $id)->update($updateData);

        return ['success' => true];
    }

    /**
     * Delete a scheduled report
     */
    public function deleteSchedule(int $id): array
    {
        DB::table('scheduled_reports')->where('id', $id)->delete();
        return ['success' => true];
    }

    /**
     * Run all due scheduled reports
     */
    public function runDueReports(): array
    {
        $dueReports = DB::table('scheduled_reports')
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->get();

        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($dueReports as $report) {
            $results['processed']++;

            try {
                $this->executeScheduledReport($report);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Scheduled report failed', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Execute a single scheduled report
     */
    protected function executeScheduledReport(object $report): void
    {
        $filters = json_decode($report->filters, true) ?? [];
        $recipients = json_decode($report->email_recipients, true) ?? [];

        // Generate the report
        $reportData = match ($report->report_type) {
            'shipment' => $this->reportService->generateShipmentReport($filters),
            'financial' => $this->reportService->generateFinancialReport($filters),
            'performance' => $this->reportService->generatePerformanceReport($filters),
            default => throw new \Exception("Unknown report type: {$report->report_type}"),
        };

        // Export to file
        $filename = $this->generateFilename($report);
        $filePath = match ($report->export_format) {
            'csv' => $this->reportService->exportToCsv($reportData, $filename),
            'xlsx' => $this->reportService->exportToExcel($reportData, $filename),
            'pdf' => $this->exportToPdf($reportData, $filename, $report->report_type),
            default => $this->reportService->exportToCsv($reportData, $filename),
        };

        // Send to recipients
        if (!empty($recipients)) {
            $this->sendReportEmail($report, $filePath, $recipients);
        }

        // Update last run and next run
        DB::table('scheduled_reports')->where('id', $report->id)->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(
                $report->schedule_type,
                $report->schedule_day,
                $report->schedule_time
            ),
            'updated_at' => now(),
        ]);

        // Log execution
        DB::table('report_executions')->insert([
            'scheduled_report_id' => $report->id,
            'report_type' => $report->report_type,
            'filters' => $report->filters,
            'user_id' => $report->user_id,
            'started_at' => now(),
            'completed_at' => now(),
            'status' => 'completed',
            'output_format' => $report->export_format,
            'output_path' => $filePath,
            'row_count' => count($reportData['data'] ?? []),
            'created_at' => now(),
        ]);

        Log::info('Scheduled report executed', [
            'report_id' => $report->id,
            'name' => $report->name,
            'recipients' => count($recipients),
        ]);
    }

    /**
     * Send report email
     */
    protected function sendReportEmail(object $report, string $filePath, array $recipients): void
    {
        $subject = "Scheduled Report: {$report->name} - " . now()->format('Y-m-d');
        $body = $this->buildEmailBody($report);

        foreach ($recipients as $email) {
            try {
                Mail::raw($body, function ($message) use ($email, $subject, $filePath) {
                    $message->to($email)
                        ->subject($subject)
                        ->attach(storage_path("app/{$filePath}"));
                });
            } catch (\Exception $e) {
                Log::warning("Failed to send report email to {$email}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build email body
     */
    protected function buildEmailBody(object $report): string
    {
        $reportType = ucfirst($report->report_type);
        $generated = now()->format('Y-m-d H:i:s');

        return <<<EMAIL
Hello,

Your scheduled {$reportType} Report "{$report->name}" has been generated.

Report Details:
- Type: {$reportType}
- Generated: {$generated}
- Schedule: {$report->schedule_type}

Please find the report attached to this email.

---
This is an automated message from Baraka Logistics.
EMAIL;
    }

    /**
     * Calculate next run time
     */
    protected function calculateNextRun(string $scheduleType, ?int $scheduleDay, string $scheduleTime): Carbon
    {
        $time = Carbon::parse($scheduleTime);
        $next = now()->setTime($time->hour, $time->minute, 0);

        // If time already passed today, start from tomorrow
        if ($next->isPast()) {
            $next->addDay();
        }

        switch ($scheduleType) {
            case 'daily':
                // Already set correctly
                break;

            case 'weekly':
                // scheduleDay: 0 = Sunday, 6 = Saturday
                $targetDay = $scheduleDay ?? 1; // Default to Monday
                while ($next->dayOfWeek !== $targetDay) {
                    $next->addDay();
                }
                break;

            case 'monthly':
                // scheduleDay: 1-28
                $targetDay = min($scheduleDay ?? 1, 28);
                if ($next->day > $targetDay) {
                    $next->addMonth();
                }
                $next->day = $targetDay;
                break;
        }

        return $next;
    }

    /**
     * Generate filename
     */
    protected function generateFilename(object $report): string
    {
        $date = now()->format('Y-m-d_His');
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $report->name);
        return "{$name}_{$date}";
    }

    /**
     * Export to PDF
     */
    protected function exportToPdf(array $data, string $filename, string $reportType): string
    {
        // If DomPDF is available, generate PDF
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $view = match ($reportType) {
                'shipment' => 'pdf.reports.shipment',
                'financial' => 'pdf.reports.financial',
                'performance' => 'pdf.reports.performance',
                default => 'pdf.reports.generic',
            };

            // Check if view exists, otherwise use generic
            if (!view()->exists($view)) {
                $view = 'pdf.reports.generic';
            }

            // If generic view doesn't exist, create simple HTML
            if (!view()->exists($view)) {
                $html = $this->generateSimpleReportHtml($data, $reportType);
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            } else {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, ['data' => $data]);
            }

            $path = "reports/{$filename}.pdf";
            Storage::put($path, $pdf->output());

            return $path;
        }

        // Fallback to CSV if PDF not available
        return $this->reportService->exportToCsv($data, $filename);
    }

    /**
     * Generate simple HTML for PDF
     */
    protected function generateSimpleReportHtml(array $data, string $reportType): string
    {
        $title = ucfirst($reportType) . ' Report';
        $date = now()->format('Y-m-d H:i:s');
        $summary = $data['summary'] ?? [];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .summary { margin: 20px 0; }
        .summary-item { display: inline-block; margin-right: 30px; }
    </style>
</head>
<body>
    <h1>{$title}</h1>
    <p>Generated: {$date}</p>
    
    <div class="summary">
HTML;

        foreach ($summary as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $html .= "<div class='summary-item'><strong>{$label}:</strong> {$value}</div>";
        }

        $html .= '</div>';

        // Add data table if present
        if (!empty($data['data'])) {
            $html .= '<table><thead><tr>';
            $firstRow = $data['data'][0] ?? [];
            foreach (array_keys($firstRow) as $header) {
                $html .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($data['data'] as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars($value ?? '') . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Get report execution history
     */
    public function getExecutionHistory(int $reportId, int $limit = 10): array
    {
        return DB::table('report_executions')
            ->where('scheduled_report_id', $reportId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Run a specific report manually
     */
    public function runManually(int $reportId): array
    {
        $report = DB::table('scheduled_reports')->where('id', $reportId)->first();

        if (!$report) {
            return ['success' => false, 'message' => 'Report not found'];
        }

        try {
            $this->executeScheduledReport($report);
            return ['success' => true, 'message' => 'Report executed successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
