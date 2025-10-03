<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FraudDetectionService
{
    protected array $riskIndicators = [
        "unusual_shipping_patterns",
        "high_value_shipments",
        "frequent_address_changes",
        "suspicious_payment_methods",
        "bulk_shipments",
    ];

    public function assessShipmentRisk(Shipment $shipment): array
    {
        $riskScore = 0;
        $riskFactors = [];

        // Check shipment value
        if ($shipment->price_amount > 5000) {
            $riskScore += 20;
            $riskFactors[] = "High value shipment";
        }

        // Check destination patterns
        $customerShipments = $shipment->customer->shipments()
            ->whereBetween("created_at", [now()->subDays(30), now()])
            ->count();

        if ($customerShipments > 50) {
            $riskScore += 15;
            $riskFactors[] = "High frequency customer";
        }

        // Check payment method
        if ($shipment->payment_method === "cash_on_delivery") {
            $riskScore += 10;
            $riskFactors[] = "COD payment method";
        }

        $riskLevel = $this->calculateRiskLevel($riskScore);

        return [
            "shipment_id" => $shipment->id,
            "risk_score" => $riskScore,
            "risk_level" => $riskLevel,
            "risk_factors" => $riskFactors,
            "recommendations" => $this->getRiskRecommendations($riskLevel),
            "assessed_at" => now(),
        ];
    }

    private function calculateRiskLevel(int $score): string
    {
        if ($score >= 30) return "high";
        if ($score >= 15) return "medium";
        return "low";
    }

    private function getRiskRecommendations(string $riskLevel): array
    {
        $recommendations = [];

        if ($riskLevel === "high") {
            $recommendations[] = "Require additional verification";
            $recommendations[] = "Enhanced monitoring required";
        } elseif ($riskLevel === "medium") {
            $recommendations[] = "Additional documentation requested";
        }

        return $recommendations;
    }

    public function logFraudAlert(Shipment $shipment, array $alertData): array
    {
        DB::table("fraud_alerts")->insert([
            "shipment_id" => $shipment->id,
            "alert_type" => $alertData["type"],
            "severity" => $alertData["severity"],
            "description" => $alertData["description"],
            "detected_at" => now(),
            "created_at" => now(),
        ]);

        Log::warning("Fraud alert detected", [
            "shipment_id" => $shipment->id,
            "type" => $alertData["type"],
            "severity" => $alertData["severity"],
        ]);

        return [
            "success" => true,
            "message" => "Fraud alert logged successfully",
            "alert_id" => DB::getPdo()->lastInsertId(),
        ];
    }

    public function getFraudDetectionReport(Carbon $startDate, Carbon $endDate): array
    {
        $alerts = DB::table("fraud_alerts")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->get();

        return [
            "period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "statistics" => [
                "total_alerts" => $alerts->count(),
                "high_severity_alerts" => $alerts->where("severity", "high")->count(),
                "medium_severity_alerts" => $alerts->where("severity", "medium")->count(),
                "low_severity_alerts" => $alerts->where("severity", "low")->count(),
                "alerts_by_type" => $alerts->groupBy("alert_type")->map->count(),
            ],
            "generated_at" => now(),
        ];
    }
}
