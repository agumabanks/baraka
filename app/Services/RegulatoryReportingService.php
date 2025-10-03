<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RegulatoryReportingService
{
    protected array $requiredReports = [
        "sar_filing", // Suspicious Activity Reports
        "ctr_filing", // Currency Transaction Reports
        "msb_reporting", // Money Services Business reports
        " customs_compliance",
        "trade_compliance",
    ];

    public function generateRegulatoryReport(string $reportType, Carbon $startDate, Carbon $endDate): array
    {
        return match($reportType) {
            "sar_filing" => $this->generateSARReport($startDate, $endDate),
            "ctr_filing" => $this->generateCTRReport($startDate, $endDate),
            "msb_reporting" => $this->generateMSBReport($startDate, $endDate),
            "customs_compliance" => $this->generateCustomsComplianceReport($startDate, $endDate),
            "trade_compliance" => $this->generateTradeComplianceReport($startDate, $endDate),
            default => ["error" => "Unknown report type"],
        };
    }

    private function generateSARReport(Carbon $startDate, Carbon $endDate): array
    {
        // Generate Suspicious Activity Report
        $suspiciousActivities = DB::table("fraud_alerts")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->where("severity", "high")
            ->get();

        return [
            "report_type" => "SAR",
            "reporting_period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "total_suspicious_activities" => $suspiciousActivities->count(),
            "activities" => $suspiciousActivities,
            "generated_at" => now(),
            "due_date" => now()->addDays(30), // SARs typically due within 30 days
        ];
    }

    private function generateCTRReport(Carbon $startDate, Carbon $endDate): array
    {
        // Generate Currency Transaction Report for transactions over $10,000
        $largeTransactions = DB::table("invoice_payments")
            ->whereBetween("payment_date", [$startDate, $endDate])
            ->where("amount", ">", 10000)
            ->get();

        return [
            "report_type" => "CTR",
            "reporting_period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "total_large_transactions" => $largeTransactions->count(),
            "transactions" => $largeTransactions,
            "generated_at" => now(),
            "due_date" => now()->addDays(15), // CTRs typically due within 15 days
        ];
    }

    private function generateMSBReport(Carbon $startDate, Carbon $endDate): array
    {
        // Generate Money Services Business report
        $totalTransactions = DB::table("invoice_payments")
            ->whereBetween("payment_date", [$startDate, $endDate])
            ->count();

        $totalVolume = DB::table("invoice_payments")
            ->whereBetween("payment_date", [$startDate, $endDate])
            ->sum("amount");

        return [
            "report_type" => "MSB",
            "reporting_period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "total_transactions" => $totalTransactions,
            "total_volume" => $totalVolume,
            "generated_at" => now(),
            "due_date" => now()->addMonths(1), // Monthly MSB reports
        ];
    }

    private function generateCustomsComplianceReport(Carbon $startDate, Carbon $endDate): array
    {
        $customsService = app(CustomsComplianceService::class);
        return $customsService->getCustomsComplianceReport($startDate, $endDate);
    }

    private function generateTradeComplianceReport(Carbon $startDate, Carbon $endDate): array
    {
        // Generate trade compliance report
        $restrictedShipments = DB::table("shipments")
            ->join("commodities", "shipments.id", "=", "commodities.shipment_id")
            ->whereBetween("shipments.created_at", [$startDate, $endDate])
            ->whereIn("commodities.hs_code", ["9301", "9302", "9303"]) // Example restricted codes
            ->select("shipments.*", "commodities.hs_code")
            ->get();

        return [
            "report_type" => "Trade Compliance",
            "reporting_period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "restricted_shipments" => $restrictedShipments->count(),
            "shipments" => $restrictedShipments,
            "generated_at" => now(),
        ];
    }

    public function submitRegulatoryReport(array $reportData): array
    {
        DB::table("regulatory_reports")->insert([
            "report_type" => $reportData["report_type"],
            "reporting_period_start" => $reportData["reporting_period"]["start_date"],
            "reporting_period_end" => $reportData["reporting_period"]["end_date"],
            "report_data" => json_encode($reportData),
            "submission_status" => "submitted",
            "submitted_at" => now(),
            "due_date" => $reportData["due_date"] ?? null,
            "created_at" => now(),
        ]);

        Log::info("Regulatory report submitted", [
            "report_type" => $reportData["report_type"],
            "period" => $reportData["reporting_period"],
        ]);

        return [
            "success" => true,
            "message" => "Regulatory report submitted successfully",
            "report_id" => DB::getPdo()->lastInsertId(),
        ];
    }

    public function getComplianceDashboard(): array
    {
        $upcomingReports = DB::table("regulatory_reports")
            ->where("due_date", ">", now())
            ->where("due_date", "<=", now()->addDays(30))
            ->get();

        $overdueReports = DB::table("regulatory_reports")
            ->where("due_date", "<", now())
            ->where("submission_status", "!=", "submitted")
            ->get();

        return [
            "upcoming_reports" => $upcomingReports,
            "overdue_reports" => $overdueReports,
            "compliance_status" => $overdueReports->isEmpty() ? "compliant" : "non_compliant",
            "generated_at" => now(),
        ];
    }
}
