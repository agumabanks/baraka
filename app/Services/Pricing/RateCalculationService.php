<?php

namespace App\Services\Pricing;

use App\Models\RateCard;
use App\Models\Backend\Branch;
use App\Models\Shipment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateCalculationService
{
    protected const VOLUMETRIC_FACTOR = 167; // 1 CBM = 167 kg (air freight standard)
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Calculate shipping rate for a shipment
     */
    public function calculateRate(array $shipmentData): array
    {
        try {
            $originBranch = Branch::find($shipmentData['origin_branch_id']);
            $destBranch = Branch::find($shipmentData['dest_branch_id']);

            if (!$originBranch || !$destBranch) {
                return $this->errorResponse('Invalid origin or destination branch');
            }

            // Calculate weights
            $weights = $this->calculateWeights($shipmentData);

            // Get applicable rate card
            $rateCard = $this->findRateCard($originBranch, $destBranch, $shipmentData['service_level'] ?? 'standard');

            if (!$rateCard) {
                // Use default pricing if no rate card
                return $this->calculateDefaultRate($weights, $shipmentData);
            }

            // Calculate base rate
            $baseRate = $this->calculateBaseRate($rateCard, $weights);

            // Calculate surcharges
            $surcharges = $this->calculateSurcharges($rateCard, $weights, $shipmentData);

            // Calculate insurance
            $insurance = $this->calculateInsurance($shipmentData);

            // Calculate taxes
            $taxes = $this->calculateTaxes($baseRate, $surcharges, $insurance, $originBranch, $destBranch);

            // Build response
            $subtotal = $baseRate['amount'] + array_sum(array_column($surcharges, 'amount'));
            $total = $subtotal + $insurance['amount'] + $taxes['amount'];

            return [
                'success' => true,
                'currency' => $rateCard->currency ?? 'USD',
                'weights' => $weights,
                'base_rate' => $baseRate,
                'surcharges' => $surcharges,
                'insurance' => $insurance,
                'taxes' => $taxes,
                'subtotal' => round($subtotal, 2),
                'total' => round($total, 2),
                'service_level' => $shipmentData['service_level'] ?? 'standard',
                'estimated_transit_days' => $this->getEstimatedTransitDays($rateCard, $originBranch, $destBranch),
                'sla' => $this->getSlaDetails($shipmentData['service_level'] ?? 'standard'),
                'rate_card_id' => $rateCard->id,
                'valid_until' => now()->addHours(24)->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error('Rate calculation failed', [
                'error' => $e->getMessage(),
                'data' => $shipmentData,
            ]);

            return $this->errorResponse('Rate calculation failed: ' . $e->getMessage());
        }
    }

    /**
     * Calculate weights (actual, volumetric, chargeable)
     */
    protected function calculateWeights(array $shipmentData): array
    {
        $actualWeight = 0;
        $volumetricWeight = 0;

        if (!empty($shipmentData['parcels'])) {
            foreach ($shipmentData['parcels'] as $parcel) {
                $actualWeight += $parcel['weight_kg'] ?? 0;

                // Calculate volumetric weight if dimensions provided
                $length = $parcel['length_cm'] ?? 0;
                $width = $parcel['width_cm'] ?? 0;
                $height = $parcel['height_cm'] ?? 0;

                if ($length > 0 && $width > 0 && $height > 0) {
                    $volumeCbm = ($length * $width * $height) / 1000000;
                    $volumetricWeight += $volumeCbm * self::VOLUMETRIC_FACTOR;
                }
            }
        } else {
            $actualWeight = $shipmentData['weight'] ?? $shipmentData['weight_kg'] ?? 0;

            $length = $shipmentData['length'] ?? $shipmentData['length_cm'] ?? 0;
            $width = $shipmentData['width'] ?? $shipmentData['width_cm'] ?? 0;
            $height = $shipmentData['height'] ?? $shipmentData['height_cm'] ?? 0;

            if ($length > 0 && $width > 0 && $height > 0) {
                $volumeCbm = ($length * $width * $height) / 1000000;
                $volumetricWeight = $volumeCbm * self::VOLUMETRIC_FACTOR;
            }
        }

        $chargeableWeight = max($actualWeight, $volumetricWeight);

        return [
            'actual_kg' => round($actualWeight, 2),
            'volumetric_kg' => round($volumetricWeight, 2),
            'chargeable_kg' => round($chargeableWeight, 2),
            'volumetric_factor' => self::VOLUMETRIC_FACTOR,
        ];
    }

    /**
     * Find applicable rate card
     */
    protected function findRateCard(Branch $origin, Branch $dest, string $serviceLevel): ?RateCard
    {
        $cacheKey = "rate_card_{$origin->id}_{$dest->id}_{$serviceLevel}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($origin, $dest, $serviceLevel) {
            // Try exact match first
            $rateCard = RateCard::where('origin_country', $origin->country_code ?? 'XX')
                ->where('dest_country', $dest->country_code ?? 'XX')
                ->where('service_level', $serviceLevel)
                ->where('is_active', true)
                ->first();

            // Try zone-based rate card
            if (!$rateCard) {
                $rateCard = RateCard::whereJsonContains('origin_zones', $origin->zone ?? 'default')
                    ->whereJsonContains('dest_zones', $dest->zone ?? 'default')
                    ->where('service_level', $serviceLevel)
                    ->where('is_active', true)
                    ->first();
            }

            // Fall back to default rate card
            if (!$rateCard) {
                $rateCard = RateCard::where('is_default', true)
                    ->where('service_level', $serviceLevel)
                    ->where('is_active', true)
                    ->first();
            }

            return $rateCard;
        });
    }

    /**
     * Calculate base rate from rate card
     */
    protected function calculateBaseRate(RateCard $rateCard, array $weights): array
    {
        $chargeableWeight = $weights['chargeable_kg'];

        // Get rate per kg based on weight breaks
        $ratePerKg = $this->getRatePerKg($rateCard, $chargeableWeight);

        // Calculate base amount
        $baseAmount = max(
            $rateCard->minimum_charge ?? 0,
            $chargeableWeight * $ratePerKg
        );

        return [
            'amount' => round($baseAmount, 2),
            'rate_per_kg' => round($ratePerKg, 2),
            'minimum_charge' => $rateCard->minimum_charge ?? 0,
            'description' => 'Base shipping rate',
        ];
    }

    /**
     * Get rate per kg based on weight breaks
     */
    protected function getRatePerKg(RateCard $rateCard, float $weight): float
    {
        $weightBreaks = $rateCard->weight_breaks ?? [];

        if (empty($weightBreaks)) {
            // Use zone matrix or default
            $zoneMatrix = $rateCard->zone_matrix ?? [];
            return $zoneMatrix['A'] ?? 5.00; // Default to zone A or $5/kg
        }

        // Sort weight breaks by weight threshold
        usort($weightBreaks, fn($a, $b) => ($a['up_to_kg'] ?? 0) <=> ($b['up_to_kg'] ?? 0));

        foreach ($weightBreaks as $break) {
            if ($weight <= ($break['up_to_kg'] ?? PHP_FLOAT_MAX)) {
                return $break['rate_per_kg'] ?? 5.00;
            }
        }

        // Return the last break's rate for weights exceeding all breaks
        $lastBreak = end($weightBreaks);
        return $lastBreak['rate_per_kg'] ?? 5.00;
    }

    /**
     * Calculate surcharges
     */
    protected function calculateSurcharges(RateCard $rateCard, array $weights, array $shipmentData): array
    {
        $surcharges = [];

        // Fuel surcharge
        $fuelSurchargePercent = $rateCard->fuel_surcharge_percent ?? 0;
        if ($fuelSurchargePercent > 0) {
            $baseRate = $weights['chargeable_kg'] * $this->getRatePerKg($rateCard, $weights['chargeable_kg']);
            $surcharges[] = [
                'type' => 'fuel_surcharge',
                'description' => "Fuel Surcharge ({$fuelSurchargePercent}%)",
                'amount' => round($baseRate * ($fuelSurchargePercent / 100), 2),
            ];
        }

        // Security surcharge
        $securitySurcharge = $rateCard->security_surcharge ?? 0;
        if ($securitySurcharge > 0) {
            $surcharges[] = [
                'type' => 'security_surcharge',
                'description' => 'Security Surcharge',
                'amount' => round($securitySurcharge, 2),
            ];
        }

        // Remote area surcharge
        if ($this->isRemoteArea($shipmentData)) {
            $remoteSurcharge = $rateCard->remote_area_surcharge ?? 25.00;
            $surcharges[] = [
                'type' => 'remote_area',
                'description' => 'Remote Area Surcharge',
                'amount' => round($remoteSurcharge, 2),
            ];
        }

        // Peak season surcharge
        if ($this->isPeakSeason()) {
            $peakSurcharge = $rateCard->peak_season_surcharge ?? 0;
            if ($peakSurcharge > 0) {
                $surcharges[] = [
                    'type' => 'peak_season',
                    'description' => 'Peak Season Surcharge',
                    'amount' => round($peakSurcharge, 2),
                ];
            }
        }

        // Oversize surcharge
        if ($this->isOversize($shipmentData)) {
            $oversizeSurcharge = $rateCard->oversize_surcharge ?? 50.00;
            $surcharges[] = [
                'type' => 'oversize',
                'description' => 'Oversize Handling',
                'amount' => round($oversizeSurcharge, 2),
            ];
        }

        // COD handling fee
        $codAmount = $shipmentData['cod_amount'] ?? 0;
        if ($codAmount > 0) {
            $codFeePercent = $rateCard->cod_fee_percent ?? 2;
            $codMinFee = $rateCard->cod_min_fee ?? 5;
            $codFee = max($codMinFee, $codAmount * ($codFeePercent / 100));
            $surcharges[] = [
                'type' => 'cod_handling',
                'description' => 'COD Handling Fee',
                'amount' => round($codFee, 2),
            ];
        }

        // Priority/Express surcharge
        $serviceLevel = $shipmentData['service_level'] ?? 'standard';
        $prioritySurcharge = $this->getServiceLevelSurcharge($serviceLevel, $rateCard);
        if ($prioritySurcharge > 0) {
            $surcharges[] = [
                'type' => 'service_level',
                'description' => ucfirst($serviceLevel) . ' Service',
                'amount' => round($prioritySurcharge, 2),
            ];
        }

        return $surcharges;
    }

    /**
     * Calculate insurance
     */
    protected function calculateInsurance(array $shipmentData): array
    {
        $declaredValue = $shipmentData['declared_value'] ?? 0;
        $insuranceType = $shipmentData['insurance_type'] ?? 'none';
        $requestedInsurance = $shipmentData['insurance_amount'] ?? 0;

        if ($insuranceType === 'none' && $requestedInsurance == 0) {
            return [
                'type' => 'none',
                'amount' => 0,
                'coverage' => 0,
                'description' => 'No insurance',
            ];
        }

        // Insurance rates by type
        $rates = [
            'basic' => ['rate' => 0.01, 'min' => 5, 'max_coverage' => 1000],     // 1%, min $5, max $1000
            'full' => ['rate' => 0.02, 'min' => 10, 'max_coverage' => 10000],    // 2%, min $10, max $10000
            'premium' => ['rate' => 0.03, 'min' => 25, 'max_coverage' => 100000], // 3%, min $25, max $100000
        ];

        $rate = $rates[$insuranceType] ?? $rates['basic'];
        $coverage = min($declaredValue, $rate['max_coverage']);
        $premium = max($rate['min'], $coverage * $rate['rate']);

        return [
            'type' => $insuranceType,
            'amount' => round($premium, 2),
            'coverage' => round($coverage, 2),
            'rate_percent' => $rate['rate'] * 100,
            'description' => ucfirst($insuranceType) . " Insurance (up to " . number_format($rate['max_coverage']) . ")",
        ];
    }

    /**
     * Calculate taxes
     */
    protected function calculateTaxes(array $baseRate, array $surcharges, array $insurance, Branch $origin, Branch $dest): array
    {
        $taxableAmount = $baseRate['amount'] + array_sum(array_column($surcharges, 'amount')) + $insurance['amount'];

        // VAT rate based on destination country
        $vatRates = [
            'TR' => 18, // Turkey
            'CD' => 16, // DRC
            'RW' => 18, // Rwanda
            'UG' => 18, // Uganda
            'KE' => 16, // Kenya
        ];

        $vatRate = $vatRates[$dest->country_code ?? 'XX'] ?? 0;

        // Calculate VAT
        $vatAmount = $taxableAmount * ($vatRate / 100);

        return [
            'vat_rate' => $vatRate,
            'amount' => round($vatAmount, 2),
            'description' => $vatRate > 0 ? "VAT ({$vatRate}%)" : 'No VAT applicable',
        ];
    }

    /**
     * Get service level surcharge
     */
    protected function getServiceLevelSurcharge(string $serviceLevel, RateCard $rateCard): float
    {
        $surcharges = [
            'economy' => 0,
            'standard' => 0,
            'express' => $rateCard->express_surcharge ?? 15.00,
            'priority' => $rateCard->priority_surcharge ?? 30.00,
            'urgent' => $rateCard->urgent_surcharge ?? 50.00,
        ];

        return $surcharges[$serviceLevel] ?? 0;
    }

    /**
     * Get estimated transit days
     */
    protected function getEstimatedTransitDays(RateCard $rateCard, Branch $origin, Branch $dest): array
    {
        $baseDays = $rateCard->transit_days ?? 5;

        // Adjust based on service level
        $serviceLevelDays = [
            'economy' => $baseDays + 2,
            'standard' => $baseDays,
            'express' => max(1, $baseDays - 2),
            'priority' => max(1, $baseDays - 3),
            'urgent' => 1,
        ];

        $serviceLevel = $rateCard->service_level ?? 'standard';
        $days = $serviceLevelDays[$serviceLevel] ?? $baseDays;

        return [
            'min' => $days,
            'max' => $days + 2,
            'business_days' => true,
        ];
    }

    /**
     * Get SLA details
     */
    protected function getSlaDetails(string $serviceLevel): array
    {
        $slaDefinitions = [
            'economy' => [
                'transit_days' => '7-10',
                'on_time_guarantee' => false,
                'tracking' => 'basic',
                'priority_handling' => false,
            ],
            'standard' => [
                'transit_days' => '5-7',
                'on_time_guarantee' => false,
                'tracking' => 'full',
                'priority_handling' => false,
            ],
            'express' => [
                'transit_days' => '2-4',
                'on_time_guarantee' => true,
                'tracking' => 'real-time',
                'priority_handling' => true,
            ],
            'priority' => [
                'transit_days' => '1-2',
                'on_time_guarantee' => true,
                'tracking' => 'real-time',
                'priority_handling' => true,
                'compensation_percent' => 50,
            ],
            'urgent' => [
                'transit_days' => '1',
                'on_time_guarantee' => true,
                'tracking' => 'real-time',
                'priority_handling' => true,
                'compensation_percent' => 100,
            ],
        ];

        return $slaDefinitions[$serviceLevel] ?? $slaDefinitions['standard'];
    }

    /**
     * Check if destination is remote area
     */
    protected function isRemoteArea(array $shipmentData): bool
    {
        // This would integrate with geolocation service
        return false;
    }

    /**
     * Check if peak season
     */
    protected function isPeakSeason(): bool
    {
        $month = now()->month;
        // Peak season: November-December (holiday season)
        return $month >= 11;
    }

    /**
     * Check if shipment is oversize
     */
    protected function isOversize(array $shipmentData): bool
    {
        $maxDimension = 150; // cm

        if (!empty($shipmentData['parcels'])) {
            foreach ($shipmentData['parcels'] as $parcel) {
                if (($parcel['length_cm'] ?? 0) > $maxDimension ||
                    ($parcel['width_cm'] ?? 0) > $maxDimension ||
                    ($parcel['height_cm'] ?? 0) > $maxDimension) {
                    return true;
                }
            }
        }

        return ($shipmentData['length'] ?? 0) > $maxDimension ||
               ($shipmentData['width'] ?? 0) > $maxDimension ||
               ($shipmentData['height'] ?? 0) > $maxDimension;
    }

    /**
     * Calculate default rate when no rate card available
     */
    protected function calculateDefaultRate(array $weights, array $shipmentData): array
    {
        $baseRatePerKg = 10.00;
        $chargeableWeight = $weights['chargeable_kg'];
        $baseAmount = max(15.00, $chargeableWeight * $baseRatePerKg);

        $insurance = $this->calculateInsurance($shipmentData);

        return [
            'success' => true,
            'currency' => 'USD',
            'weights' => $weights,
            'base_rate' => [
                'amount' => round($baseAmount, 2),
                'rate_per_kg' => $baseRatePerKg,
                'minimum_charge' => 15.00,
                'description' => 'Default shipping rate',
            ],
            'surcharges' => [],
            'insurance' => $insurance,
            'taxes' => ['vat_rate' => 0, 'amount' => 0, 'description' => 'No VAT applicable'],
            'subtotal' => round($baseAmount, 2),
            'total' => round($baseAmount + $insurance['amount'], 2),
            'service_level' => $shipmentData['service_level'] ?? 'standard',
            'estimated_transit_days' => ['min' => 5, 'max' => 7, 'business_days' => true],
            'sla' => $this->getSlaDetails($shipmentData['service_level'] ?? 'standard'),
            'rate_card_id' => null,
            'valid_until' => now()->addHours(24)->toIso8601String(),
            'note' => 'Using default pricing - no rate card configured for this route',
        ];
    }

    /**
     * Error response helper
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'total' => 0,
        ];
    }

    /**
     * Get rate comparison for multiple service levels
     */
    public function compareServiceLevels(array $shipmentData): array
    {
        $levels = ['economy', 'standard', 'express', 'priority'];
        $comparisons = [];

        foreach ($levels as $level) {
            $data = array_merge($shipmentData, ['service_level' => $level]);
            $rate = $this->calculateRate($data);

            if ($rate['success']) {
                $comparisons[] = [
                    'service_level' => $level,
                    'total' => $rate['total'],
                    'currency' => $rate['currency'],
                    'transit_days' => $rate['estimated_transit_days'],
                    'sla' => $rate['sla'],
                ];
            }
        }

        return $comparisons;
    }
}
