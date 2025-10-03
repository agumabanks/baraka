<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Commodity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DGComplianceService
{
    protected array $dangerousGoodsClasses = [
        "1" => ["Explosives", "1.1-1.6"],
        "2" => ["Gases", "2.1-2.3"],
        "3" => ["Flammable Liquids", "3"],
        "4" => ["Flammable Solids", "4.1-4.3"],
        "5" => ["Oxidizing Substances", "5.1-5.2"],
        "6" => ["Toxic Substances", "6.1-6.2"],
        "7" => ["Radioactive Material", "7"],
        "8" => ["Corrosive Substances", "8"],
        "9" => ["Miscellaneous", "9"],
    ];

    public function checkShipmentDGCompliance(Shipment $shipment): array
    {
        $commodities = $shipment->commodities;
        $violations = [];
        $warnings = [];
        $complianceStatus = "compliant";

        foreach ($commodities as $commodity) {
            $dgCheck = $this->checkCommodityDGCompliance($commodity, $shipment);
            
            if ($dgCheck["status"] === "violation") {
                $violations[] = $dgCheck;
                $complianceStatus = "non_compliant";
            } elseif ($dgCheck["status"] === "warning") {
                $warnings[] = $dgCheck;
                if ($complianceStatus === "compliant") {
                    $complianceStatus = "conditional";
                }
            }
        }

        // Check for incompatible combinations
        $compatibilityCheck = $this->checkDGCompatibility($commodities);
        if (!$compatibilityCheck["compatible"]) {
            $violations[] = [
                "type" => "incompatibility",
                "message" => "Dangerous goods incompatibility detected",
                "details" => $compatibilityCheck["issues"],
            ];
            $complianceStatus = "non_compliant";
        }

        return [
            "shipment_id" => $shipment->id,
            "compliance_status" => $complianceStatus,
            "violations" => $violations,
            "warnings" => $warnings,
            "checked_at" => now(),
        ];
    }

    private function checkCommodityDGCompliance(Commodity $commodity, Shipment $shipment): array
    {
        if (!$commodity->is_dangerous_good) {
            return ["status" => "compliant", "message" => "Not a dangerous good"];
        }

        $issues = [];

        // Check UN number validity
        if (!$commodity->un_number || !preg_match("/^UN\d{4}$/", $commodity->un_number)) {
            $issues[] = "Invalid or missing UN number";
        }

        // Check proper shipping name
        if (!$commodity->proper_shipping_name) {
            $issues[] = "Missing proper shipping name";
        }

        // Check hazard class
        if (!$commodity->hazard_class || !isset($this->dangerousGoodsClasses[$commodity->hazard_class])) {
            $issues[] = "Invalid or missing hazard class";
        }

        // Check packaging requirements
        if (!$commodity->packaging_group) {
            $issues[] = "Missing packaging group";
        }

        // Check quantity limits
        $quantityCheck = $this->checkQuantityLimits($commodity, $shipment);
        if (!$quantityCheck["allowed"]) {
            $issues[] = $quantityCheck["message"];
        }

        // Check transportation mode restrictions
        $modeCheck = $this->checkTransportationMode($commodity, $shipment);
        if (!$modeCheck["allowed"]) {
            $issues[] = $modeCheck["message"];
        }

        if (empty($issues)) {
            return ["status" => "compliant", "message" => "All DG requirements met"];
        }

        return [
            "status" => "violation",
            "commodity_id" => $commodity->id,
            "issues" => $issues,
            "message" => "DG compliance violations found",
        ];
    }

    private function checkQuantityLimits(Commodity $commodity, Shipment $shipment): array
    {
        // Simplified quantity checks based on hazard class
        $limits = [
            "1" => ["max" => 0, "message" => "Explosives not permitted"],
            "2" => ["max" => 1000, "message" => "Gas cylinders limited to 1000kg"],
            "3" => ["max" => 1000, "message" => "Flammable liquids limited to 1000L"],
            "4" => ["max" => 500, "message" => "Flammable solids limited to 500kg"],
            "5" => ["max" => 1000, "message" => "Oxidizers limited to 1000kg"],
            "6" => ["max" => 100, "message" => "Toxic substances limited to 100kg"],
            "7" => ["max" => 50, "message" => "Radioactive materials limited to 50kg"],
            "8" => ["max" => 1000, "message" => "Corrosives limited to 1000kg"],
            "9" => ["max" => 1000, "message" => "Miscellaneous DG limited to 1000kg"],
        ];

        $class = $commodity->hazard_class;
        $quantity = $commodity->weight_kg ?? 0;

        if (isset($limits[$class])) {
            if ($limits[$class]["max"] === 0) {
                return ["allowed" => false, "message" => $limits[$class]["message"]];
            }
            if ($quantity > $limits[$class]["max"]) {
                return ["allowed" => false, "message" => $limits[$class]["message"]];
            }
        }

        return ["allowed" => true];
    }

    private function checkTransportationMode(Commodity $commodity, Shipment $shipment): array
    {
        // Check if DG can be transported by the selected mode
        $restrictions = [
            "1" => ["air" => false, "message" => "Explosives cannot be transported by air"],
            "2" => ["air" => true], // Gases can be transported by air with restrictions
            "7" => ["air" => false, "message" => "Radioactive materials have air transport restrictions"],
        ];

        $class = $commodity->hazard_class;
        $mode = $shipment->transportation_mode ?? "ground";

        if (isset($restrictions[$class][$mode]) && $restrictions[$class][$mode] === false) {
            return [
                "allowed" => false,
                "message" => $restrictions[$class]["message"] ?? "DG not allowed for this transport mode"
            ];
        }

        return ["allowed" => true];
    }

    private function checkDGCompatibility(Collection $commodities): array
    {
        $issues = [];
        $dgCommodities = $commodities->filter->is_dangerous_good;

        if ($dgCommodities->count() <= 1) {
            return ["compatible" => true];
        }

        // Check for incompatible combinations
        $incompatibilities = [
            ["1", "7"], // Explosives with radioactive
            ["3", "5"], // Flammable liquids with oxidizers (requires special handling)
            ["6", "8"], // Toxic with corrosive
        ];

        foreach ($incompatibilities as $incompatible) {
            $hasFirst = $dgCommodities->contains("hazard_class", $incompatible[0]);
            $hasSecond = $dgCommodities->contains("hazard_class", $incompatible[1]);

            if ($hasFirst && $hasSecond) {
                $issues[] = "Hazard classes {$incompatible[0]} and {$incompatible[1]} are incompatible";
            }
        }

        return [
            "compatible" => empty($issues),
            "issues" => $issues,
        ];
    }

    public function generateDGDeclaration(Shipment $shipment): array
    {
        $commodities = $shipment->commodities->filter->is_dangerous_good;

        if ($commodities->isEmpty()) {
            return ["required" => false, "message" => "No dangerous goods in shipment"];
        }

        $declaration = [
            "shipment_id" => $shipment->id,
            "shipper_declaration" => [
                "shipper_name" => $shipment->customer->name ?? "Unknown",
                "shipper_address" => $shipment->customer->address ?? "Unknown",
                "declaration_date" => now()->toDateString(),
            ],
            "dangerous_goods" => [],
            "emergency_contact" => [
                "name" => "Baraka Courier Emergency Response",
                "phone" => "+1-800-DG-RESPONSE",
                "email" => "dg.emergency@baraka.courier.com",
            ],
        ];

        foreach ($commodities as $commodity) {
            $declaration["dangerous_goods"][] = [
                "un_number" => $commodity->un_number,
                "proper_shipping_name" => $commodity->proper_shipping_name,
                "hazard_class" => $commodity->hazard_class,
                "packaging_group" => $commodity->packaging_group,
                "quantity" => $commodity->weight_kg,
                "unit" => "kg",
            ];
        }

        return [
            "required" => true,
            "declaration" => $declaration,
            "generated_at" => now(),
        ];
    }

    public function getDGTrainingStatus($user): array
    {
        // Simplified training status check
        return [
            "user_id" => $user->id,
            "dg_training_completed" => true, // Assume completed for demo
            "last_training_date" => now()->subMonths(6),
            "training_expiry" => now()->addMonths(6),
            "certifications" => ["IATA DG", "IMDG Code"],
        ];
    }

    public function logDGIncident(Shipment $shipment, array $incidentData): array
    {
        DB::table("dg_incidents")->insert([
            "shipment_id" => $shipment->id,
            "incident_type" => $incidentData["type"],
            "severity" => $incidentData["severity"],
            "description" => $incidentData["description"],
            "location" => $incidentData["location"] ?? null,
            "reported_by" => $incidentData["reported_by"],
            "occurred_at" => $incidentData["occurred_at"] ?? now(),
            "created_at" => now(),
        ]);

        Log::warning("DG incident reported", [
            "shipment_id" => $shipment->id,
            "type" => $incidentData["type"],
            "severity" => $incidentData["severity"],
        ]);

        return [
            "success" => true,
            "message" => "DG incident logged successfully",
            "incident_id" => DB::getPdo()->lastInsertId(),
        ];
    }

    public function getDGComplianceReport(Carbon $startDate, Carbon $endDate): array
    {
        $shipments = Shipment::whereBetween("created_at", [$startDate, $endDate])->get();
        
        $dgShipments = $shipments->filter(function ($shipment) {
            return $shipment->commodities->contains("is_dangerous_good", true);
        });

        $complianceStats = [
            "total_dg_shipments" => $dgShipments->count(),
            "compliant_shipments" => 0,
            "non_compliant_shipments" => 0,
            "incidents_reported" => 0,
        ];

        foreach ($dgShipments as $shipment) {
            $compliance = $this->checkShipmentDGCompliance($shipment);
            if ($compliance["compliance_status"] === "compliant") {
                $complianceStats["compliant_shipments"]++;
            } else {
                $complianceStats["non_compliant_shipments"]++;
            }
        }

        return [
            "period" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
            ],
            "statistics" => $complianceStats,
            "compliance_rate" => $complianceStats["total_dg_shipments"] > 0 
                ? ($complianceStats["compliant_shipments"] / $complianceStats["total_dg_shipments"]) * 100 
                : 0,
            "generated_at" => now(),
        ];
    }
}
