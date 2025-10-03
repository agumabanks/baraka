<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomsComplianceService
{
    public function checkCustomsRequirements(Shipment $shipment): array
    {
        $requirements = [];
        $violations = [];

        // Check if customs clearance is required
        if ($this->requiresCustomsClearance($shipment)) {
            $requirements[] = "Customs declaration required";

            // Check commercial invoice
            if (!$this->hasCommercialInvoice($shipment)) {
                $violations[] = "Commercial invoice missing";
            }

            // Check customs value declaration
            if (!$this->hasCustomsValue($shipment)) {
                $violations[] = "Customs value declaration missing";
            }

            // Check HS codes
            if (!$this->hasValidHSCodes($shipment)) {
                $violations[] = "Invalid or missing HS codes";
            }

            // Check restricted items
            $restrictedCheck = $this->checkRestrictedItems($shipment);
            if (!$restrictedCheck["allowed"]) {
                $violations[] = $restrictedCheck["message"];
            }
        }

        return [
            "shipment_id" => $shipment->id,
            "requires_customs" => $this->requiresCustomsClearance($shipment),
            "requirements" => $requirements,
            "violations" => $violations,
            "compliance_status" => empty($violations) ? "compliant" : "non_compliant",
        ];
    }

    private function requiresCustomsClearance(Shipment $shipment): bool
    {
        // Simplified check - in reality would check origin/destination countries
        return $shipment->origin_country !== $shipment->destination_country;
    }

    private function hasCommercialInvoice(Shipment $shipment): bool
    {
        return $shipment->customsDocs()->where("document_type", "commercial_invoice")->exists();
    }

    private function hasCustomsValue(Shipment $shipment): bool
    {
        return !empty($shipment->customs_value);
    }

    private function hasValidHSCodes(Shipment $shipment): bool
    {
        foreach ($shipment->commodities as $commodity) {
            if (empty($commodity->hs_code) || strlen($commodity->hs_code) < 6) {
                return false;
            }
        }
        return true;
    }

    private function checkRestrictedItems(Shipment $shipment): array
    {
        $restrictedHSCodes = ["9301", "9302", "9303"]; // Weapons example
        
        foreach ($shipment->commodities as $commodity) {
            if (in_array($commodity->hs_code, $restrictedHSCodes)) {
                return [
                    "allowed" => false,
                    "message" => "Shipment contains restricted items (HS Code: {$commodity->hs_code})"
                ];
            }
        }
        
        return ["allowed" => true];
    }

    public function generateCustomsDeclaration(Shipment $shipment): array
    {
        if (!$this->requiresCustomsClearance($shipment)) {
            return ["required" => false, "message" => "Customs clearance not required"];
        }

        $declaration = [
            "shipment_id" => $shipment->id,
            "declaration_type" => "import",
            "exporter" => [
                "name" => $shipment->customer->name ?? "Unknown",
                "address" => $shipment->customer->address ?? "Unknown",
                "country" => $shipment->origin_country,
            ],
            "importer" => [
                "name" => $shipment->recipient_name ?? "Unknown",
                "address" => $shipment->recipient_address ?? "Unknown",
                "country" => $shipment->destination_country,
            ],
            "commodities" => [],
            "total_value" => $shipment->customs_value ?? 0,
            "currency" => $shipment->currency ?? "USD",
            "declaration_date" => now()->toDateString(),
        ];

        foreach ($shipment->commodities as $commodity) {
            $declaration["commodities"][] = [
                "description" => $commodity->description,
                "hs_code" => $commodity->hs_code,
                "quantity" => $commodity->quantity,
                "unit_value" => $commodity->unit_value ?? 0,
                "total_value" => ($commodity->quantity ?? 0) * ($commodity->unit_value ?? 0),
            ];
        }

        return [
            "required" => true,
            "declaration" => $declaration,
            "generated_at" => now(),
        ];
    }

    public function calculateCustomsDuties(Shipment $shipment): array
    {
        $duties = [];
        $totalDuty = 0;

        foreach ($shipment->commodities as $commodity) {
            $hsCode = $commodity->hs_code;
            $value = ($commodity->quantity ?? 0) * ($commodity->unit_value ?? 0);
            
            // Simplified duty calculation - in reality would use customs tariff database
            $dutyRate = $this->getDutyRate($hsCode, $shipment->destination_country);
            $dutyAmount = $value * ($dutyRate / 100);
            
            $duties[] = [
                "commodity_id" => $commodity->id,
                "hs_code" => $hsCode,
                "value" => $value,
                "duty_rate" => $dutyRate,
                "duty_amount" => $dutyAmount,
            ];
            
            $totalDuty += $dutyAmount;
        }

        return [
            "duties" => $duties,
            "total_duty" => $totalDuty,
            "currency" => $shipment->currency ?? "USD",
            "calculated_at" => now(),
        ];
    }

    private function getDutyRate(string $hsCode, string $destinationCountry): float
    {
        // Simplified duty rate lookup - in reality would query customs database
        return 5.0; // 5% default rate
    }

    public function submitCustomsDeclaration(Shipment $shipment): array
    {
        $declaration = $this->generateCustomsDeclaration($shipment);
        
        if (!$declaration["required"]) {
            return ["success" => false, "message" => "Customs declaration not required"];
        }

        // In reality, this would submit to customs authority API
        DB::table("customs_submissions")->insert([
            "shipment_id" => $shipment->id,
            "declaration_data" => json_encode($declaration["declaration"]),
            "submission_status" => "submitted",
            "submitted_at" => now(),
            "created_at" => now(),
        ]);

        $shipment->update([
            "customs_status" => "submitted",
            "customs_submitted_at" => now(),
        ]);

        return [
            "success" => true,
            "message" => "Customs declaration submitted successfully",
            "submission_id" => DB::getPdo()->lastInsertId(),
        ];
    }

    public function getCustomsComplianceReport(Carbon $startDate, Carbon $endDate): array
    {
        $shipments = Shipment::whereBetween("created_at", [$startDate, $endDate])->get();
        
        $customsShipments = $shipments->filter(function ($shipment) {
            return $this->requiresCustomsClearance($shipment);
        });

        $complianceStats = [
            "total_customs_shipments" => $customsShipments->count(),
            "compliant_shipments" => 0,
            "non_compliant_shipments" => 0,
            "pending_clearance" => 0,
            "cleared_shipments" => 0,
        ];

        foreach ($customsShipments as $shipment) {
            $compliance = $this->checkCustomsRequirements($shipment);
            
            if ($compliance["compliance_status"] === "compliant") {
                $complianceStats["compliant_shipments"]++;
            } else {
                $complianceStats["non_compliant_shipments"]++;
            }

            if ($shipment->customs_status === "cleared") {
                $complianceStats["cleared_shipments"]++;
            } elseif ($shipment->customs_status === "submitted") {
                $complianceStats["pending_clearance"]++;
            }
        }

        return [
            "period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "statistics" => $complianceStats,
            "clearance_rate" => $complianceStats["total_customs_shipments"] > 0 
                ? ($complianceStats["cleared_shipments"] / $complianceStats["total_customs_shipments"]) * 100 
                : 0,
            "generated_at" => now(),
        ];
    }
}
