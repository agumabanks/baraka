<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Backend\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RiskAssessmentService
{
    protected array $riskCategories = [
        "operational_risk",
        "financial_risk", 
        "compliance_risk",
        "reputational_risk",
        "strategic_risk",
    ];

    public function assessOverallRisk(Carbon $startDate, Carbon $endDate): array
    {
        $riskScores = [];

        foreach ($this->riskCategories as $category) {
            $riskScores[$category] = $this->assessCategoryRisk($category, $startDate, $endDate);
        }

        $overallScore = array_sum(array_column($riskScores, "score")) / count($riskScores);
        $overallLevel = $this->calculateRiskLevel($overallScore);

        return [
            "assessment_period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "overall_risk_score" => $overallScore,
            "overall_risk_level" => $overallLevel,
            "category_risks" => $riskScores,
            "risk_mitigation_plan" => $this->generateRiskMitigationPlan($overallLevel, $riskScores),
            "assessed_at" => now(),
        ];
    }

    private function assessCategoryRisk(string $category, Carbon $startDate, Carbon $endDate): array
    {
        return match($category) {
            "operational_risk" => $this->assessOperationalRisk($startDate, $endDate),
            "financial_risk" => $this->assessFinancialRisk($startDate, $endDate),
            "compliance_risk" => $this->assessComplianceRisk($startDate, $endDate),
            "reputational_risk" => $this->assessReputationalRisk($startDate, $endDate),
            "strategic_risk" => $this->assessStrategicRisk($startDate, $endDate),
            default => ["score" => 0, "level" => "low", "factors" => []],
        };
    }

    private function assessOperationalRisk(Carbon $startDate, Carbon $endDate): array
    {
        $score = 0;
        $factors = [];

        // Check shipment delays
        $delayedShipments = Shipment::whereBetween("created_at", [$startDate, $endDate])
            ->where("current_status", "delivered")
            ->whereRaw("delivered_at > expected_delivery_date")
            ->count();

        $totalShipments = Shipment::whereBetween("created_at", [$startDate, $endDate])->count();

        if ($totalShipments > 0) {
            $delayRate = ($delayedShipments / $totalShipments) * 100;
            if ($delayRate > 20) {
                $score += 25;
                $factors[] = "High shipment delay rate: {$delayRate}%";
            }
        }

        // Check DG incidents
        $dgIncidents = DB::table("dg_incidents")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->count();

        if ($dgIncidents > 0) {
            $score += 20;
            $factors[] = "DG incidents reported: {$dgIncidents}";
        }

        return [
            "score" => min(100, $score),
            "level" => $this->calculateRiskLevel($score),
            "factors" => $factors,
        ];
    }

    private function assessFinancialRisk(Carbon $startDate, Carbon $endDate): array
    {
        $score = 0;
        $factors = [];

        // Check outstanding receivables
        $outstandingAmount = DB::table("invoices")
            ->whereBetween("invoice_date", [$startDate, $endDate])
            ->where("status", "!=", "paid")
            ->sum("total_amount");

        if ($outstandingAmount > 50000) {
            $score += 30;
            $factors[] = "High outstanding receivables: $" . number_format($outstandingAmount);
        }

        // Check cash flow
        $payments = DB::table("invoice_payments")
            ->whereBetween("payment_date", [$startDate, $endDate])
            ->sum("amount");

        $expenses = DB::table("expenses")
            ->whereBetween("expense_date", [$startDate, $endDate])
            ->sum("amount");

        $netCashFlow = $payments - $expenses;

        if ($netCashFlow < 0) {
            $score += 25;
            $factors[] = "Negative cash flow: $" . number_format($netCashFlow);
        }

        return [
            "score" => min(100, $score),
            "level" => $this->calculateRiskLevel($score),
            "factors" => $factors,
        ];
    }

    private function assessComplianceRisk(Carbon $startDate, Carbon $endDate): array
    {
        $score = 0;
        $factors = [];

        // Check KYC compliance
        $pendingKYC = DB::table("customers")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->where("kyc_status", "pending")
            ->count();

        if ($pendingKYC > 10) {
            $score += 20;
            $factors[] = "Pending KYC verifications: {$pendingKYC}";
        }

        // Check regulatory violations
        $violations = DB::table("compliance_violations")
            ->whereBetween("occurred_at", [$startDate, $endDate])
            ->count();

        if ($violations > 0) {
            $score += 30;
            $factors[] = "Compliance violations: {$violations}";
        }

        return [
            "score" => min(100, $score),
            "level" => $this->calculateRiskLevel($score),
            "factors" => $factors,
        ];
    }

    private function assessReputationalRisk(Carbon $startDate, Carbon $endDate): array
    {
        $score = 0;
        $factors = [];

        // Check customer complaints
        $complaints = DB::table("support_chats")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->where("priority", "high")
            ->count();

        if ($complaints > 20) {
            $score += 25;
            $factors[] = "High customer complaints: {$complaints}";
        }

        // Check fraud alerts
        $fraudAlerts = DB::table("fraud_alerts")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->count();

        if ($fraudAlerts > 5) {
            $score += 20;
            $factors[] = "Fraud alerts: {$fraudAlerts}";
        }

        return [
            "score" => min(100, $score),
            "level" => $this->calculateRiskLevel($score),
            "factors" => $factors,
        ];
    }

    private function assessStrategicRisk(Carbon $startDate, Carbon $endDate): array
    {
        $score = 0;
        $factors = [];

        // Check market share changes
        $currentShipments = Shipment::whereBetween("created_at", [$startDate, $endDate])->count();
        $previousShipments = Shipment::whereBetween("created_at", [
            $startDate->copy()->subMonths(3), 
            $endDate->copy()->subMonths(3)
        ])->count();

        if ($previousShipments > 0) {
            $growthRate = (($currentShipments - $previousShipments) / $previousShipments) * 100;
            
            if ($growthRate < -10) {
                $score += 25;
                $factors[] = "Declining shipment volume: {$growthRate}% growth";
            }
        }

        // Check competitive threats
        $marketShare = 0.15; // Assume 15% market share
        if ($marketShare < 0.10) {
            $score += 20;
            $factors[] = "Low market share: " . ($marketShare * 100) . "%";
        }

        return [
            "score" => min(100, $score),
            "level" => $this->calculateRiskLevel($score),
            "factors" => $factors,
        ];
    }

    private function calculateRiskLevel(float $score): string
    {
        if ($score >= 70) return "high";
        if ($score >= 40) return "medium";
        return "low";
    }

    private function generateRiskMitigationPlan(string $overallLevel, array $categoryRisks): array
    {
        $plan = [];

        if ($overallLevel === "high") {
            $plan[] = "Implement immediate risk mitigation measures";
            $plan[] = "Increase monitoring frequency";
            $plan[] = "Review and update risk policies";
        }

        foreach ($categoryRisks as $category => $risk) {
            if ($risk["level"] === "high") {
                $plan[] = "Address {$category} risks immediately";
            }
        }

        return $plan;
    }

    public function logRiskIncident(array $incidentData): array
    {
        DB::table("risk_incidents")->insert([
            "incident_type" => $incidentData["type"],
            "category" => $incidentData["category"],
            "severity" => $incidentData["severity"],
            "description" => $incidentData["description"],
            "impact_assessment" => $incidentData["impact"] ?? null,
            "mitigation_actions" => json_encode($incidentData["mitigation"] ?? []),
            "reported_by" => $incidentData["reported_by"],
            "occurred_at" => $incidentData["occurred_at"] ?? now(),
            "created_at" => now(),
        ]);

        Log::warning("Risk incident logged", [
            "type" => $incidentData["type"],
            "category" => $incidentData["category"],
            "severity" => $incidentData["severity"],
        ]);

        return [
            "success" => true,
            "message" => "Risk incident logged successfully",
            "incident_id" => DB::getPdo()->lastInsertId(),
        ];
    }

    public function getRiskHeatMap(): array
    {
        $branches = Branch::active()->get();
        $heatmap = [];

        foreach ($branches as $branch) {
            $branchRisk = $this->assessBranchRisk($branch);
            $heatmap[] = [
                "branch_name" => $branch->name,
                "risk_score" => $branchRisk["score"],
                "risk_level" => $branchRisk["level"],
                "key_risks" => $branchRisk["factors"],
            ];
        }

        return [
            "heatmap" => $heatmap,
            "generated_at" => now(),
        ];
    }

    private function assessBranchRisk(Branch $branch): array
    {
        // Simplified branch risk assessment
        $delayedShipments = $branch->originShipments()
            ->where("current_status", "delivered")
            ->whereRaw("delivered_at > expected_delivery_date")
            ->count();

        $totalShipments = $branch->originShipments()->count();
        $delayRate = $totalShipments > 0 ? ($delayedShipments / $totalShipments) * 100 : 0;

        $score = min(100, $delayRate * 2); // Simple scoring

        return [
            "score" => $score,
            "level" => $this->calculateRiskLevel($score),
            "factors" => $delayRate > 15 ? ["High delay rate: {$delayRate}%"] : [],
        ];
    }
}
