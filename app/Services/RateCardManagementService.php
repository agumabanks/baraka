<?php

namespace App\Services;

use App\Models\Backend\Branch;
use App\Models\Shipment;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RateCardManagementService
{
    /**
     * Calculate shipping rate for a shipment
     */
    public function calculateShippingRate(Shipment $shipment, array $options = []): array
    {
        $originBranch = $shipment->originBranch;
        $destBranch = $shipment->destBranch;
        $customer = $shipment->customer;
        $serviceLevel = $shipment->service_level ?? 'standard';

        // Get base rate from zone pricing
        $baseRate = $this->getZoneBasedRate($originBranch, $destBranch, $serviceLevel);

        // Apply customer-specific pricing
        $customerDiscount = $this->getCustomerDiscount($customer);
        $discountedRate = $this->applyCustomerDiscount($baseRate, $customerDiscount);

        // Apply weight and dimension surcharges
        $surcharges = $this->calculateSurcharges($shipment);
        $totalRate = $discountedRate + $surcharges['total'];

        // Apply fuel surcharge
        $fuelSurcharge = $this->calculateFuelSurcharge($totalRate);
        $finalRate = $totalRate + $fuelSurcharge;

        // Apply taxes
        $taxes = $this->calculateTaxes($finalRate);
        $grandTotal = $finalRate + $taxes['total'];

        return [
            'base_rate' => $baseRate,
            'customer_discount' => $customerDiscount,
            'discounted_rate' => $discountedRate,
            'surcharges' => $surcharges,
            'fuel_surcharge' => $fuelSurcharge,
            'subtotal' => $finalRate,
            'taxes' => $taxes,
            'grand_total' => $grandTotal,
            'currency' => 'USD',
            'breakdown' => [
                'zone_rate' => $baseRate,
                'customer_discount_amount' => $baseRate - $discountedRate,
                'weight_surcharge' => $surcharges['weight'] ?? 0,
                'dimension_surcharge' => $surcharges['dimension'] ?? 0,
                'special_handling' => $surcharges['special_handling'] ?? 0,
                'fuel_surcharge_amount' => $fuelSurcharge,
                'tax_amount' => $taxes['total'],
            ],
            'applied_rules' => [
                'zone' => $this->getZoneName($originBranch, $destBranch),
                'service_level' => $serviceLevel,
                'customer_tier' => $customer->pricing_tier ?? 'standard',
                'fuel_index' => $this->getCurrentFuelIndex(),
            ],
        ];
    }

    /**
     * Get zone-based rate between two branches
     */
    private function getZoneBasedRate(Branch $origin, Branch $destination, string $serviceLevel): float
    {
        // Calculate distance-based pricing
        $distance = $origin->distanceTo($destination);

        // Base rate per km
        $baseRatePerKm = match($serviceLevel) {
            'express' => 2.50,
            'priority' => 1.75,
            'standard' => 1.25,
            default => 1.25,
        };

        // Minimum charge
        $minimumCharge = match($serviceLevel) {
            'express' => 25.00,
            'priority' => 15.00,
            'standard' => 10.00,
            default => 10.00,
        };

        $calculatedRate = $distance * $baseRatePerKm;

        // Apply zone multipliers
        $zoneMultiplier = $this->getZoneMultiplier($origin, $destination);
        $calculatedRate *= $zoneMultiplier;

        return max($calculatedRate, $minimumCharge);
    }

    /**
     * Get zone multiplier based on branch relationship
     */
    private function getZoneMultiplier(Branch $origin, Branch $destination): float
    {
        // Same branch
        if ($origin->id === $destination->id) {
            return 0.8; // 20% discount for local deliveries
        }

        // HUB involved
        if ($origin->is_hub || $destination->is_hub) {
            return 1.0; // Standard rate for HUB operations
        }

        // Regional branches
        if ($origin->type === 'REGIONAL' && $destination->type === 'REGIONAL') {
            return 1.2; // 20% premium for inter-regional
        }

        // Local to regional or vice versa
        if (($origin->type === 'LOCAL' && $destination->type === 'REGIONAL') ||
            ($origin->type === 'REGIONAL' && $destination->type === 'LOCAL')) {
            return 1.1; // 10% premium
        }

        return 1.0; // Standard rate
    }

    /**
     * Get zone name for display
     */
    private function getZoneName(Branch $origin, Branch $destination): string
    {
        if ($origin->id === $destination->id) {
            return 'Local';
        }

        if ($origin->is_hub || $destination->is_hub) {
            return 'HUB Network';
        }

        if ($origin->type === 'REGIONAL' && $destination->type === 'REGIONAL') {
            return 'Inter-Regional';
        }

        return 'Regional';
    }

    /**
     * Get customer discount based on their pricing tier
     */
    private function getCustomerDiscount(Customer $customer): array
    {
        $tier = $customer->pricing_tier ?? 'standard';

        return match($tier) {
            'platinum' => ['percentage' => 15.0, 'type' => 'volume'],
            'gold' => ['percentage' => 10.0, 'type' => 'volume'],
            'silver' => ['percentage' => 5.0, 'type' => 'volume'],
            'standard' => ['percentage' => 0.0, 'type' => 'standard'],
            default => ['percentage' => 0.0, 'type' => 'standard'],
        };
    }

    /**
     * Apply customer discount to base rate
     */
    private function applyCustomerDiscount(float $baseRate, array $discount): float
    {
        if ($discount['percentage'] > 0) {
            return $baseRate * (1 - $discount['percentage'] / 100);
        }

        return $baseRate;
    }

    /**
     * Calculate surcharges for shipment
     */
    private function calculateSurcharges(Shipment $shipment): array
    {
        $surcharges = [
            'weight' => 0.0,
            'dimension' => 0.0,
            'special_handling' => 0.0,
            'total' => 0.0,
        ];

        // Weight surcharge (over 10kg)
        $weight = $shipment->total_weight ?? 0;
        if ($weight > 10) {
            $surcharges['weight'] = ($weight - 10) * 0.50; // $0.50 per kg over 10kg
        }

        // Dimension surcharge (oversized)
        $dimensions = $shipment->metadata['dimensions'] ?? [];
        if (isset($dimensions['length']) && isset($dimensions['width']) && isset($dimensions['height'])) {
            $volume = $dimensions['length'] * $dimensions['width'] * $dimensions['height'] / 1000000; // Convert to cubic meters
            if ($volume > 0.1) { // Over 0.1 cubic meters
                $surcharges['dimension'] = ($volume - 0.1) * 50; // $50 per additional 0.1 cubic meters
            }
        }

        // Special handling surcharges
        if ($shipment->service_level === 'express') {
            $surcharges['special_handling'] += 5.00; // Express handling fee
        }

        if (isset($shipment->metadata['special_handling'])) {
            foreach ($shipment->metadata['special_handling'] as $handling) {
                $surcharges['special_handling'] += match($handling) {
                    'fragile' => 3.00,
                    'hazardous' => 10.00,
                    'refrigerated' => 8.00,
                    'oversized' => 15.00,
                    default => 0.00,
                };
            }
        }

        $surcharges['total'] = array_sum(array_slice($surcharges, 0, -1));

        return $surcharges;
    }

    /**
     * Calculate fuel surcharge
     */
    private function calculateFuelSurcharge(float $baseAmount): float
    {
        $fuelIndex = $this->getCurrentFuelIndex();
        $fuelSurchargeRate = 0.08; // 8% fuel surcharge

        return $baseAmount * $fuelSurchargeRate * ($fuelIndex / 100);
    }

    /**
     * Get current fuel index (simulated)
     */
    private function getCurrentFuelIndex(): float
    {
        // In a real implementation, this would fetch from an external API
        // For now, return a simulated value
        return 120.0; // 120% of base fuel index
    }

    /**
     * Calculate taxes
     */
    private function calculateTaxes(float $amount): array
    {
        $taxRate = 0.08; // 8% tax rate
        $taxAmount = $amount * $taxRate;

        return [
            'rate' => $taxRate * 100,
            'total' => $taxAmount,
            'breakdown' => [
                'sales_tax' => $taxAmount,
            ],
        ];
    }

    /**
     * Get rate card for a customer
     */
    public function getCustomerRateCard(Customer $customer): array
    {
        $tiers = ['standard', 'silver', 'gold', 'platinum'];
        $currentTier = $customer->pricing_tier ?? 'standard';

        $rateCard = [];

        foreach ($tiers as $tier) {
            $rateCard[$tier] = [
                'base_rates' => $this->getTierBaseRates($tier),
                'discount_percentage' => $this->getTierDiscount($tier),
                'minimum_charge' => $this->getTierMinimumCharge($tier),
                'fuel_surcharge_included' => true,
            ];
        }

        return [
            'customer_id' => $customer->id,
            'current_tier' => $currentTier,
            'rate_card' => $rateCard,
            'next_tier_requirements' => $this->getNextTierRequirements($customer),
            'effective_date' => now()->toDateString(),
        ];
    }

    /**
     * Get base rates for a pricing tier
     */
    private function getTierBaseRates(string $tier): array
    {
        $multiplier = match($tier) {
            'platinum' => 0.85,
            'gold' => 0.90,
            'silver' => 0.95,
            'standard' => 1.0,
            default => 1.0,
        };

        return [
            'local_delivery' => 10.00 * $multiplier,
            'regional_delivery' => 15.00 * $multiplier,
            'inter_regional' => 25.00 * $multiplier,
            'express_local' => 20.00 * $multiplier,
            'express_regional' => 35.00 * $multiplier,
            'express_inter_regional' => 50.00 * $multiplier,
        ];
    }

    /**
     * Get discount percentage for a tier
     */
    private function getTierDiscount(string $tier): float
    {
        return match($tier) {
            'platinum' => 15.0,
            'gold' => 10.0,
            'silver' => 5.0,
            'standard' => 0.0,
            default => 0.0,
        };
    }

    /**
     * Get minimum charge for a tier
     */
    private function getTierMinimumCharge(string $tier): float
    {
        return match($tier) {
            'platinum' => 8.00,
            'gold' => 9.00,
            'silver' => 9.50,
            'standard' => 10.00,
            default => 10.00,
        };
    }

    /**
     * Get requirements for next tier
     */
    private function getNextTierRequirements(Customer $customer): array
    {
        $currentTier = $customer->pricing_tier ?? 'standard';
        $monthlyVolume = $customer->monthly_shipment_volume ?? 0;
        $monthlyRevenue = $customer->monthly_revenue ?? 0;

        $requirements = [
            'silver' => [
                'monthly_volume' => 50,
                'monthly_revenue' => 1000,
                'current_volume' => $monthlyVolume,
                'current_revenue' => $monthlyRevenue,
            ],
            'gold' => [
                'monthly_volume' => 200,
                'monthly_revenue' => 5000,
                'current_volume' => $monthlyVolume,
                'current_revenue' => $monthlyRevenue,
            ],
            'platinum' => [
                'monthly_volume' => 500,
                'monthly_revenue' => 15000,
                'current_volume' => $monthlyVolume,
                'current_revenue' => $monthlyRevenue,
            ],
        ];

        return $requirements[$currentTier] ?? [];
    }

    /**
     * Update customer pricing tier
     */
    public function updateCustomerTier(Customer $customer, string $newTier): array
    {
        $validTiers = ['standard', 'silver', 'gold', 'platinum'];

        if (!in_array($newTier, $validTiers)) {
            return [
                'success' => false,
                'message' => 'Invalid pricing tier',
            ];
        }

        $oldTier = $customer->pricing_tier ?? 'standard';

        DB::beginTransaction();
        try {
            $customer->update([
                'pricing_tier' => $newTier,
                'tier_updated_at' => now(),
            ]);

            // Log the tier change
            activity()
                ->performedOn($customer)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_tier' => $oldTier,
                    'new_tier' => $newTier,
                    'changed_by' => auth()->user()->name,
                ])
                ->log("Customer pricing tier changed from {$oldTier} to {$newTier}");

            DB::commit();

            return [
                'success' => true,
                'message' => 'Customer pricing tier updated successfully',
                'old_tier' => $oldTier,
                'new_tier' => $newTier,
                'effective_date' => now()->toDateString(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer tier update failed', [
                'customer_id' => $customer->id,
                'old_tier' => $oldTier,
                'new_tier' => $newTier,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update customer pricing tier: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get bulk pricing for multiple shipments
     */
    public function calculateBulkRates(Collection $shipments): array
    {
        $results = [];

        foreach ($shipments as $shipment) {
            $results[] = [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'rate_calculation' => $this->calculateShippingRate($shipment),
            ];
        }

        $totalValue = collect($results)->sum('rate_calculation.grand_total');
        $averageRate = $results ? $totalValue / count($results) : 0;

        return [
            'shipments_count' => count($results),
            'total_value' => $totalValue,
            'average_rate' => $averageRate,
            'currency' => 'USD',
            'calculations' => $results,
        ];
    }

    /**
     * Get rate analysis for route optimization
     */
    public function getRouteRateAnalysis(Branch $origin, Branch $destination, array $serviceLevels = []): array
    {
        $serviceLevels = $serviceLevels ?: ['standard', 'priority', 'express'];
        $analysis = [];

        foreach ($serviceLevels as $serviceLevel) {
            // Create a mock shipment for rate calculation
            $mockShipment = new Shipment([
                'origin_branch_id' => $origin->id,
                'dest_branch_id' => $destination->id,
                'service_level' => $serviceLevel,
                'total_weight' => 5, // 5kg average
            ]);

            $mockShipment->originBranch = $origin;
            $mockShipment->destBranch = $destination;

            $rate = $this->calculateShippingRate($mockShipment);

            $analysis[$serviceLevel] = [
                'base_rate' => $rate['base_rate'],
                'grand_total' => $rate['grand_total'],
                'estimated_delivery_time' => $this->getEstimatedDeliveryTime($origin, $destination, $serviceLevel),
                'reliability_score' => $this->getRouteReliabilityScore($origin, $destination),
            ];
        }

        return [
            'origin_branch' => $origin->name,
            'destination_branch' => $destination->name,
            'distance_km' => $origin->distanceTo($destination),
            'service_level_analysis' => $analysis,
            'recommended_service' => $this->getRecommendedServiceLevel($analysis),
        ];
    }

    /**
     * Get estimated delivery time
     */
    private function getEstimatedDeliveryTime(Branch $origin, Branch $destination, string $serviceLevel): string
    {
        $distance = $origin->distanceTo($destination);
        $speed = match($serviceLevel) {
            'express' => 80, // km/h
            'priority' => 60,
            'standard' => 40,
            default => 40,
        };

        $hours = $distance / $speed;

        if ($hours < 24) {
            return round($hours) . ' hours';
        }

        return round($hours / 24, 1) . ' days';
    }

    /**
     * Get route reliability score
     */
    private function getRouteReliabilityScore(Branch $origin, Branch $destination): float
    {
        // Simplified reliability calculation
        // In a real implementation, this would consider historical data
        $distance = $origin->distanceTo($destination);

        if ($distance < 50) {
            return 95.0; // High reliability for short distances
        } elseif ($distance < 200) {
            return 90.0; // Good reliability for medium distances
        } else {
            return 85.0; // Lower reliability for long distances
        }
    }

    /**
     * Get recommended service level
     */
    private function getRecommendedServiceLevel(array $analysis): string
    {
        // Recommend based on cost-effectiveness and speed
        $standard = $analysis['standard'] ?? null;
        $priority = $analysis['priority'] ?? null;
        $express = $analysis['express'] ?? null;

        if (!$standard) {
            return 'standard';
        }

        // If express is not much more expensive than priority, recommend express
        if ($express && $priority && ($express['grand_total'] / $priority['grand_total']) < 1.3) {
            return 'express';
        }

        // If priority offers good value, recommend it
        if ($priority && ($priority['grand_total'] / $standard['grand_total']) < 1.5) {
            return 'priority';
        }

        return 'standard';
    }
}