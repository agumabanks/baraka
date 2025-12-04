<?php

namespace App\Services;

use App\Models\CustomerContract;
use App\Models\CustomerContractItem;
use App\Models\RateCard;
use App\Models\RouteCapability;
use App\Models\ServiceConstraint;
use App\Models\SurchargeRule;
use App\Models\Tariff;
use App\Support\SystemSettings;
use Illuminate\Support\Carbon;

class RatingService
{
    protected array $warnings = [];
    protected ?string $rateTableVersion = null;

    public function dimWeightKg(int $volumeCm3, int $dimFactor): float
    {
        return round($volumeCm3 / max(1, $dimFactor), 3);
    }

    public function calculateChargeableWeight(float $actualWeight, ?float $length, ?float $width, ?float $height): array
    {
        $dimFactor = config('pos.volumetric_divisor', 5000);
        $volumetricWeight = 0;

        if ($length && $width && $height) {
            $volumeCm3 = $length * $width * $height;
            $volumetricWeight = $this->dimWeightKg((int) $volumeCm3, $dimFactor);
        }

        $chargeableWeight = max($actualWeight, $volumetricWeight);

        return [
            'actual_weight' => round($actualWeight, 3),
            'volumetric_weight' => round($volumetricWeight, 3),
            'chargeable_weight' => round($chargeableWeight, 3),
            'dim_factor' => $dimFactor,
        ];
    }

    public function quote(array $params): array
    {
        $this->warnings = [];
        $this->rateTableVersion = null;

        $serviceLevel = $params['service_level'] ?? 'standard';
        $originBranchId = $params['origin_branch_id'] ?? null;
        $destBranchId = $params['destination_branch_id'] ?? null;
        $weight = (float) ($params['weight'] ?? 0);
        $length = isset($params['length']) ? (float) $params['length'] : null;
        $width = isset($params['width']) ? (float) $params['width'] : null;
        $height = isset($params['height']) ? (float) $params['height'] : null;
        $declaredValue = (float) ($params['declared_value'] ?? 0);
        $codAmount = (float) ($params['cod_amount'] ?? 0);
        $insuranceType = $params['insurance_type'] ?? 'none';
        $customerId = $params['customer_id'] ?? null;
        $zone = $params['zone'] ?? null;

        // Build route-specific zone from branch codes (e.g., "IST-KSS")
        $originBranch = null;
        $destBranch = null;
        if ($originBranchId && $destBranchId) {
            $originBranch = \App\Models\Backend\Branch::find($originBranchId);
            $destBranch = \App\Models\Backend\Branch::find($destBranchId);
            if ($originBranch && $destBranch && !$zone) {
                $zone = "{$originBranch->code}-{$destBranch->code}";
            }
        }

        // Calculate chargeable weight
        $weightData = $this->calculateChargeableWeight($weight, $length, $width, $height);
        $chargeableWeight = $weightData['chargeable_weight'];

        // Validate route capability
        if ($originBranchId && $destBranchId) {
            $capability = RouteCapability::forRoute($originBranchId, $destBranchId)
                ->where('service_level', $serviceLevel)
                ->active()
                ->first();

            if (!$capability) {
                $this->warnings[] = "Service level '{$serviceLevel}' not available for this route.";
            } elseif ($capability->max_weight && $chargeableWeight > $capability->max_weight) {
                $this->warnings[] = "Weight exceeds maximum ({$capability->max_weight} kg) for this route/service.";
            }
            
            // Check COD availability
            if ($codAmount > 0 && $capability && !$capability->cod_allowed) {
                $this->warnings[] = "COD is not available for this route.";
            }
        }

        // Validate service constraints
        $constraintResult = ServiceConstraint::validateWeight($serviceLevel, $chargeableWeight, $originBranchId, $destBranchId);
        if (!$constraintResult['valid']) {
            $this->warnings[] = $constraintResult['error'];
        }

        // Get pricing - check for customer contract first
        $pricing = $this->getPricing($serviceLevel, $chargeableWeight, $zone, $customerId);

        // Calculate components
        $baseRate = $pricing['base_rate'];
        $weightCharge = $pricing['per_kg_rate'] * $chargeableWeight;
        
        // Apply weight band adjustments per POS hardening plan
        $weightBandAdjustment = $this->getWeightBandAdjustment($chargeableWeight);
        $weightCharge = $weightCharge * (1 + $weightBandAdjustment);
        
        $fuelSurcharge = ($baseRate + $weightCharge) * ($pricing['fuel_surcharge_percent'] / 100);
        
        // Apply surcharge rules
        $surchargeResult = $this->priceWithSurcharges($baseRate + $weightCharge, $chargeableWeight);
        $surchargesTotal = $surchargeResult['total'] - ($baseRate + $weightCharge);

        // Insurance
        $insuranceFee = $this->calculateInsurance($insuranceType, $declaredValue);

        // COD fee
        $codFee = $this->calculateCodFee($codAmount);

        // Subtotal before tax
        $subtotal = $baseRate + $weightCharge + $fuelSurcharge + $surchargesTotal + $insuranceFee + $codFee;

        // Apply customer discount if applicable
        $discount = 0;
        if ($pricing['discount_percent'] > 0) {
            $discount = round($subtotal * ($pricing['discount_percent'] / 100), 2);
            $subtotal -= $discount;
        }

        // Tax
        $taxRate = SystemSettings::get('vat_rate', 18);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        // Total
        $total = $subtotal + $taxAmount;

        // Minimum charge
        $minCharge = SystemSettings::minCharge(); // Uses finance.min_charge
        if ($total < $minCharge) {
            $this->warnings[] = "Minimum charge of " . SystemSettings::formatCurrency($minCharge) . " applied.";
            $total = $minCharge;
            $taxAmount = round($total / (1 + $taxRate / 100) * ($taxRate / 100), 2);
        }

        return [
            'base_freight' => round($baseRate, 2),
            'weight_charge' => round($weightCharge, 2),
            'fuel_surcharge' => round($fuelSurcharge, 2),
            'surcharges_total' => round($surchargesTotal, 2),
            'surcharges_applied' => $surchargeResult['applied'],
            'insurance_fee' => round($insuranceFee, 2),
            'cod_fee' => round($codFee, 2),
            'discount' => round($discount, 2),
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate,
            'tax' => round($taxAmount, 2),
            'total' => round($total, 2),
            'currency' => SystemSettings::get('currency', 'UGX'),
            'rate_table_version' => $this->rateTableVersion ?? 'default-1.0',
            'weight_data' => $weightData,
            'warnings' => $this->warnings,
        ];
    }

    protected function getPricing(string $serviceLevel, float $weight, ?string $zone, ?int $customerId): array
    {
        // Check for customer contract first
        if ($customerId) {
            $contract = CustomerContract::findActiveForCustomer($customerId);
            if ($contract) {
                $contractItem = $contract->items()
                    ->active()
                    ->forService($serviceLevel)
                    ->forWeight($weight)
                    ->when($zone, fn($q) => $q->where(fn($q2) => $q2->whereNull('zone')->orWhere('zone', $zone)))
                    ->first();

                if ($contractItem) {
                    $this->rateTableVersion = "contract-{$contract->id}-{$contract->updated_at->format('Ymd')}";
                    return [
                        'base_rate' => $contractItem->base_rate ?? 0,
                        'per_kg_rate' => $contractItem->per_kg_rate ?? 0,
                        'fuel_surcharge_percent' => 0,
                        'discount_percent' => $contractItem->discount_percent ?? $contract->discount_percent ?? 0,
                    ];
                }

                // If no specific item, use contract discount with public tariff
                $tariff = Tariff::findApplicable($serviceLevel, $weight, $zone);
                if ($tariff) {
                    $this->rateTableVersion = "tariff-{$tariff->id}-{$tariff->version}";
                    return [
                        'base_rate' => $tariff->base_rate,
                        'per_kg_rate' => $tariff->per_kg_rate,
                        'fuel_surcharge_percent' => $tariff->fuel_surcharge_percent,
                        'discount_percent' => $contract->discount_percent ?? 0,
                    ];
                }
            }
        }

        // Check public tariffs
        $tariff = Tariff::findApplicable($serviceLevel, $weight, $zone);
        if ($tariff) {
            $this->rateTableVersion = "tariff-{$tariff->id}-{$tariff->version}";
            return [
                'base_rate' => $tariff->base_rate,
                'per_kg_rate' => $tariff->per_kg_rate,
                'fuel_surcharge_percent' => $tariff->fuel_surcharge_percent,
                'discount_percent' => 0,
            ];
        }

        // Fall back to rate cards (legacy)
        $rateCard = RateCard::where('is_active', true)->first();
        if ($rateCard) {
            $this->rateTableVersion = "ratecard-{$rateCard->id}";
            $baseRate = SystemSettings::get('base_rate_per_kg', 5000);
            return [
                'base_rate' => $baseRate,
                'per_kg_rate' => $baseRate,
                'fuel_surcharge_percent' => $rateCard->fuel_surcharge_percent ?? 0,
                'discount_percent' => 0,
            ];
        }

        // Final fallback to system settings
        $this->rateTableVersion = 'default-1.0';
        return [
            'base_rate' => SystemSettings::get('base_rate_per_kg', 5000),
            'per_kg_rate' => SystemSettings::get('base_rate_per_kg', 5000),
            'fuel_surcharge_percent' => SystemSettings::get('fuel_surcharge', 8),
            'discount_percent' => 0,
        ];
    }

    protected function calculateInsurance(string $type, float $declaredValue): float
    {
        if ($type === 'none' || $declaredValue <= 0) {
            return 0;
        }

        $rates = [
            'basic' => 1.0,
            'full' => 2.0,
            'premium' => 3.0,
        ];

        $rate = $rates[$type] ?? SystemSettings::get('insurance_rate', 1.5);
        return round($declaredValue * ($rate / 100), 2);
    }

    protected function calculateCodFee(float $codAmount): float
    {
        if ($codAmount <= 0) {
            return 0;
        }

        $codFeePercent = SystemSettings::get('cod_fee_percent', 2);
        $minCodFee = SystemSettings::get('min_cod_fee', 1000);

        $fee = $codAmount * ($codFeePercent / 100);
        return max($fee, $minCodFee);
    }

    public function priceWithSurcharges(float $base, float $billableWeight, $date = null): array
    {
        $date = $date ? Carbon::parse($date) : now();
        $applied = [];
        $total = $base;

        $rules = SurchargeRule::query()
            ->where('active', true)
            ->whereDate('active_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('active_to')->orWhereDate('active_to', '>=', $date);
            })->get();

        foreach ($rules as $rule) {
            $amount = $rule->rate_type === 'percent'
                ? round($base * ($rule->amount / 100), 2)
                : (float) $rule->amount;
            $total += $amount;
            $applied[] = [
                'code' => $rule->code,
                'name' => $rule->name,
                'amount' => $amount,
            ];
        }

        return [
            'base' => $base,
            'billable_weight' => $billableWeight,
            'total' => round($total, 2),
            'applied' => $applied,
        ];
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getRateTableVersion(): ?string
    {
        return $this->rateTableVersion;
    }

    /**
     * Get weight band adjustment factor from SystemSettings
     * Configurable by admin at Settings > Pricing > Weight Bands
     */
    protected function getWeightBandAdjustment(float $weight): float
    {
        return SystemSettings::weightBandAdjustment($weight);
    }
}
