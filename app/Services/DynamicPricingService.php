<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Zone;
use App\Models\FuelIndex;
use App\Models\ServiceLevelDefinition;
use App\Models\SurchargeRule;
use App\Models\CompetitorPrice;
use App\Models\RateCard;
use App\Models\PricingRule;
use App\Models\Quotation;
use App\Enums\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Dynamic Rate Calculation Service
 * 
 * Handles real-time rate calculations with instant quote generation,
 * origin/destination zone pricing, dimensional weight automation,
 * service level multipliers, variable fuel surcharge indexation,
 * customs clearance fees, multi-currency support, and tax calculations.
 */
class DynamicPricingService
{
    // Service Level Multipliers
    public const SERVICE_LEVEL_MULTIPLIERS = [
        'express' => 1.5,
        'priority' => 1.25,
        'standard' => 1.0,
        'economy' => 0.8,
    ];

    // Dimensional Weight Factors
    public const DIMENSIONAL_WEIGHT_FACTORS = [
        'domestic' => 5000, // Standard divisor
        'international' => 4000, // More conservative for international
        'express' => 6000, // Higher divisor for express services
    ];

    // Fuel Surcharge Thresholds
    public const FUEL_SURCHARGE_THRESHOLD = 100.0; // Base index
    public const FUEL_SURCHARGE_FACTOR = 0.08; // 8% factor

    // Currency Exchange Rate TTL (in minutes)
    private const CACHE_TTL_EXCHANGE_RATES = 30;
    private const CACHE_TTL_RATE_CALCULATION = 15;
    private const CACHE_TTL_COMPETITOR_DATA = 60;

    // Customer Tier Discounts
    public const CUSTOMER_TIER_DISCOUNTS = [
        'platinum' => 15.0,
        'gold' => 10.0,
        'silver' => 5.0,
        'standard' => 0.0,
    ];

    public function __construct(
        private RateCardManagementService $rateCardService,
        private WebhookManagementService $webhookService
    ) {}

    /**
     * Calculate instant quote for shipment
     */
    public function calculateInstantQuote(
        string $origin,
        string $destination,
        array $shipmentData,
        string $serviceLevel = 'standard',
        ?int $customerId = null,
        string $currency = 'USD'
    ): array {
        $startTime = microtime(true);
        
        try {
            // Generate cache key for this calculation
            $cacheKey = $this->generateCacheKey($origin, $destination, $shipmentData, $serviceLevel, $customerId, $currency);
            
            // Check cache first
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult) {
                return $cachedResult;
            }

            // Get customer if provided
            $customer = $customerId ? Customer::find($customerId) : null;
            
            // Calculate base rate using existing rate card service
            $baseCalculation = $this->rateCardService->calculateShippingRate($this->createMockShipment($origin, $destination, $shipmentData, $serviceLevel));
            
            // Apply dynamic pricing adjustments
            $quote = $this->applyDynamicPricing($baseCalculation, $shipmentData, $serviceLevel, $customer);
            
            // Add dimensional weight calculations
            $quote = $this->applyDimensionalWeight($quote, $shipmentData);
            
            // Apply service level multipliers
            $quote = $this->applyServiceLevelMultiplier($quote, $serviceLevel);
            
            // Calculate fuel surcharge
            $quote = $this->getFuelSurcharge($quote, $origin, $destination, $serviceLevel);
            
            // Apply customer tier discounts
            $quote = $this->applyVolumeDiscounts($quote, $customer, $shipmentData);
            
            // Calculate taxes
            $quote = $this->calculateTaxes($quote, $destination, $currency);
            
            // Add customs clearance fees if international
            $quote = $this->addCustomsClearanceFees($quote, $origin, $destination, $shipmentData);
            
            // Convert to requested currency
            $quote = $this->convertCurrency($quote, $currency);
            
            // Add competitive benchmarking
            $quote = $this->addCompetitorBenchmarking($quote, $origin, $destination, $serviceLevel);
            
            // Calculate final totals
            $quote = $this->calculateFinalTotals($quote);
            
            // Add processing time and metadata
            $quote['processing_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            $quote['calculated_at'] = now()->toISOString();
            $quote['quote_id'] = $this->generateQuoteId();
            $quote['valid_until'] = now()->addHours(24)->toISOString();
            
            // Cache the result
            Cache::put($cacheKey, $quote, now()->addMinutes(self::CACHE_TTL_RATE_CALCULATION));
            
            // Log quote generation
            $this->logQuoteGeneration($quote, $customerId);
            
            return $quote;
            
        } catch (\Exception $e) {
            Log::error('Instant quote calculation failed', [
                'error' => $e->getMessage(),
                'origin' => $origin,
                'destination' => $destination,
                'service_level' => $serviceLevel,
                'customer_id' => $customerId,
                'shipment_data' => $shipmentData
            ]);
            
            throw new \Exception('Failed to calculate instant quote: ' . $e->getMessage());
        }
    }

    /**
     * Apply dimensional weight calculations
     */
    public function applyDimensionalWeight(array $quote, array $dimensions): array
    {
        $actualWeight = $dimensions['weight_kg'] ?? 0;
        $length = $dimensions['length_cm'] ?? 0;
        $width = $dimensions['width_cm'] ?? 0;
        $height = $dimensions['height_cm'] ?? 0;
        
        if (!$length || !$width || !$height) {
            $quote['dimensional_weight'] = [
                'applicable' => false,
                'chargeable_weight' => $actualWeight,
                'difference' => 0,
                'surcharge' => 0
            ];
            return $quote;
        }
        
        // Calculate dimensional weight
        $volume = ($length * $width * $height) / 1000000; // Convert to cubic meters
        $serviceType = $quote['service_level'] ?? 'standard';
        $divisor = self::DIMENSIONAL_WEIGHT_FACTORS[$serviceType] ?? self::DIMENSIONAL_WEIGHT_FACTORS['domestic'];
        
        $dimensionalWeight = ceil($volume * 1000 * $divisor);
        
        // Use the greater of actual or dimensional weight
        $chargeableWeight = max($actualWeight, $dimensionalWeight);
        $weightDifference = $chargeableWeight - $actualWeight;
        
        $surcharge = 0;
        if ($weightDifference > 0) {
            // Apply dimensional weight surcharge
            $surcharge = $weightDifference * 2.5; // $2.50 per kg difference
        }
        
        $quote['dimensional_weight'] = [
            'applicable' => $weightDifference > 0,
            'actual_weight' => $actualWeight,
            'dimensional_weight' => $dimensionalWeight,
            'volume_m3' => round($volume, 4),
            'chargeable_weight' => $chargeableWeight,
            'difference' => $weightDifference,
            'surcharge' => $surcharge,
            'divisor_used' => $divisor
        ];
        
        $quote['base_amount'] += $surcharge;
        
        return $quote;
    }

    /**
     * Get fuel surcharge based on route and service level
     */
    public function getFuelSurcharge(array $quote, string $origin, string $destination, string $serviceLevel): array
    {
        // Get current fuel index
        $fuelIndex = $this->getCurrentFuelIndex();
        $baseIndex = self::FUEL_SURCHARGE_THRESHOLD;
        
        if ($fuelIndex <= $baseIndex) {
            $quote['fuel_surcharge'] = [
                'applicable' => false,
                'rate' => 0,
                'amount' => 0,
                'current_index' => $fuelIndex,
                'base_index' => $baseIndex
            ];
            return $quote;
        }
        
        // Calculate surcharge rate
        $surchargeRate = (($fuelIndex - $baseIndex) / $baseIndex) * self::FUEL_SURCHARGE_FACTOR;
        
        // Apply service level adjustment
        $serviceMultiplier = 1.0;
        if ($serviceLevel === 'express') {
            $serviceMultiplier = 1.1; // Higher fuel cost for express
        } elseif ($serviceLevel === 'economy') {
            $serviceMultiplier = 0.9; // Lower fuel cost for economy
        }
        
        $surchargeRate *= $serviceMultiplier;
        
        $baseAmount = $quote['base_amount'];
        $surchargeAmount = $baseAmount * $surchargeRate;
        
        $quote['fuel_surcharge'] = [
            'applicable' => true,
            'rate' => $surchargeRate,
            'amount' => $surchargeAmount,
            'current_index' => $fuelIndex,
            'base_index' => $baseIndex,
            'service_multiplier' => $serviceMultiplier
        ];
        
        $quote['base_amount'] += $surchargeAmount;
        
        return $quote;
    }

    /**
     * Calculate taxes based on jurisdiction
     */
    public function calculateTaxes(array $quote, string $jurisdiction, string $currency): array
    {
        $taxRules = $this->getTaxRules($jurisdiction);
        $subtotal = $quote['base_amount'];
        
        $taxBreakdown = [];
        $totalTax = 0;
        
        foreach ($taxRules as $taxType => $rule) {
            $rate = $rule['rate'] ?? 0;
            $amount = $subtotal * ($rate / 100);
            
            $taxBreakdown[$taxType] = [
                'name' => $rule['name'] ?? $taxType,
                'rate' => $rate,
                'amount' => $amount,
                'jurisdiction' => $jurisdiction
            ];
            
            $totalTax += $amount;
        }
        
        $quote['taxes'] = [
            'applicable' => $totalTax > 0,
            'breakdown' => $taxBreakdown,
            'total' => $totalTax,
            'jurisdiction' => $jurisdiction
        ];
        
        $quote['base_amount'] += $totalTax;
        
        return $quote;
    }

    /**
     * Get competitor benchmarking data
     */
    public function getCompetitorBenchmarking(string $route, string $serviceLevel): array
    {
        $cacheKey = "competitor_benchmarking_{$route}_{$serviceLevel}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_COMPETITOR_DATA), function() use ($route, $serviceLevel) {
            [$origin, $destination] = explode('-', $route);
            
            $competitorData = CompetitorPrice::byRoute($origin, $destination)
                ->byServiceLevel($serviceLevel)
                ->recent(30)
                ->get();
                
            if ($competitorData->isEmpty()) {
                return [
                    'available' => false,
                    'message' => 'No competitor data available for this route'
                ];
            }
            
            $analysis = CompetitorPrice::getRouteAverages($origin, $destination, $serviceLevel, 30);
            
            return [
                'available' => true,
                'analysis' => $analysis,
                'data_points' => $competitorData->count(),
                'last_updated' => $competitorData->max('collected_at')?->toISOString()
            ];
        });
    }

    /**
     * Apply volume discounts based on customer tier and shipment volume
     */
    public function applyVolumeDiscounts(array $quote, ?Customer $customer, array $shipmentData): array
    {
        if (!$customer) {
            $quote['volume_discount'] = [
                'applicable' => false,
                'discount_rate' => 0,
                'amount' => 0,
                'customer_tier' => 'standard'
            ];
            return $quote;
        }
        
        $customerTier = $customer->customer_type ?? 'standard';
        $tierDiscount = self::CUSTOMER_TIER_DISCOUNTS[$customerTier] ?? 0;
        
        // Check for volume-based discounts
        $volume = $this->calculateShipmentVolume($shipmentData);
        $volumeDiscount = $this->calculateVolumeBasedDiscount($customer, $volume);
        
        $totalDiscountRate = $tierDiscount + $volumeDiscount;
        $discountAmount = $quote['base_amount'] * ($totalDiscountRate / 100);
        
        $quote['volume_discount'] = [
            'applicable' => $totalDiscountRate > 0,
            'customer_tier' => $customerTier,
            'tier_discount_rate' => $tierDiscount,
            'volume_discount_rate' => $volumeDiscount,
            'total_discount_rate' => $totalDiscountRate,
            'amount' => $discountAmount,
            'shipment_volume' => $volume
        ];
        
        $quote['base_amount'] -= $discountAmount;
        
        return $quote;
    }

    /**
     * Validate quote data
     */
    public function validateQuote(array $quoteData): array
    {
        $errors = [];
        $warnings = [];
        
        // Required fields validation
        $requiredFields = ['origin', 'destination', 'service_level', 'shipment_data'];
        foreach ($requiredFields as $field) {
            if (!isset($quoteData[$field]) || empty($quoteData[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }
        
        // Shipment data validation
        if (isset($quoteData['shipment_data'])) {
            $shipmentData = $quoteData['shipment_data'];
            
            if (!isset($shipmentData['weight_kg']) || $shipmentData['weight_kg'] <= 0) {
                $errors[] = "Shipment weight must be greater than 0";
            }
            
            if (isset($shipmentData['dimensions'])) {
                $dimensions = $shipmentData['dimensions'];
                $requiredDimensions = ['length_cm', 'width_cm', 'height_cm'];
                
                foreach ($requiredDimensions as $dimension) {
                    if (!isset($dimensions[$dimension]) || $dimensions[$dimension] <= 0) {
                        $errors[] = "Dimension '{$dimension}' must be greater than 0";
                    }
                }
            }
        }
        
        // Business rule validations
        if (isset($quoteData['total_amount']) && $quoteData['total_amount'] <= 0) {
            $errors[] = "Quote total amount must be greater than 0";
        }
        
        // Warning checks
        if (isset($quoteData['processing_time_ms']) && $quoteData['processing_time_ms'] > 2000) {
            $warnings[] = "Quote calculation took longer than 2 seconds";
        }
        
        if (isset($quoteData['dimensional_weight']) && $quoteData['dimensional_weight']['applicable']) {
            $percentageIncrease = ($quoteData['dimensional_weight']['difference'] / $quoteData['dimensional_weight']['actual_weight']) * 100;
            if ($percentageIncrease > 50) {
                $warnings[] = "Dimensional weight surcharge exceeds 50% of actual weight";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'validation_performed_at' => now()->toISOString()
        ];
    }

    /**
     * Generate bulk quotes for optimization scenarios
     */
    public function generateBulkQuotes(array $shipmentRequests): array
    {
        $results = [];
        $startTime = microtime(true);
        
        foreach ($shipmentRequests as $index => $request) {
            try {
                $quote = $this->calculateInstantQuote(
                    $request['origin'],
                    $request['destination'],
                    $request['shipment_data'],
                    $request['service_level'] ?? 'standard',
                    $request['customer_id'] ?? null,
                    $request['currency'] ?? 'USD'
                );
                
                $results[] = [
                    'request_index' => $index,
                    'success' => true,
                    'quote' => $quote
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'request_index' => $index,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'total_requests' => count($shipmentRequests),
            'successful_quotes' => count(array_filter($results, fn($r) => $r['success'])),
            'failed_quotes' => count(array_filter($results, fn($r) => !$r['success'])),
            'total_processing_time_ms' => $processingTime,
            'average_time_per_quote_ms' => $processingTime / count($shipmentRequests),
            'results' => $results,
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Get current fuel index (cached)
     */
    private function getCurrentFuelIndex(): float
    {
        return Cache::remember('current_fuel_index', now()->addHours(1), function() {
            $fuelIndex = FuelIndex::getLatestIndex('eia');
            return $fuelIndex ? $fuelIndex->index_value : self::FUEL_SURCHARGE_THRESHOLD;
        });
    }

    /**
     * Get tax rules for jurisdiction
     */
    private function getTaxRules(string $jurisdiction): array
    {
        // Simplified tax rules - in production, this would come from a database
        return match($jurisdiction) {
            'US' => [
                'sales_tax' => ['name' => 'Sales Tax', 'rate' => 8.0],
                'fuel_tax' => ['name' => 'Fuel Tax', 'rate' => 0.2]
            ],
            'CA' => [
                'gst' => ['name' => 'GST', 'rate' => 5.0],
                'pst' => ['name' => 'PST', 'rate' => 7.0]
            ],
            'EU' => [
                'vat' => ['name' => 'VAT', 'rate' => 20.0]
            ],
            default => [
                'sales_tax' => ['name' => 'Sales Tax', 'rate' => 0.0]
            ]
        };
    }

    /**
     * Apply dynamic pricing adjustments
     */
    private function applyDynamicPricing(array $baseCalculation, array $shipmentData, string $serviceLevel, ?Customer $customer): array
    {
        $quote = [
            'base_amount' => $baseCalculation['grand_total'] ?? 0,
            'service_level' => $serviceLevel,
            'breakdown' => [
                'base_rate' => $baseCalculation['base_rate'] ?? 0,
                'customer_discount' => $baseCalculation['customer_discount'] ?? 0,
                'surcharges' => $baseCalculation['surcharges'] ?? ['total' => 0]
            ]
        ];
        
        // Apply time-based pricing (peak hours, seasonal adjustments)
        $timeBasedAdjustment = $this->calculateTimeBasedAdjustment();
        if ($timeBasedAdjustment !== 0) {
            $quote['time_based_adjustment'] = $timeBasedAdjustment;
            $quote['base_amount'] *= (1 + $timeBasedAdjustment);
        }
        
        return $quote;
    }

    /**
     * Calculate time-based pricing adjustments
     */
    private function calculateTimeBasedAdjustment(): float
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;
        
        // Peak hours adjustment
        if (in_array($hour, [7, 8, 9, 17, 18, 19])) {
            return 0.05; // 5% peak hour surcharge
        }
        
        // Weekend adjustment
        if (in_array($dayOfWeek, [0, 6])) {
            return -0.10; // 10% weekend discount
        }
        
        return 0; // No adjustment
    }

    /**
     * Apply service level multiplier
     */
    private function applyServiceLevelMultiplier(array $quote, string $serviceLevel): array
    {
        $multiplier = self::SERVICE_LEVEL_MULTIPLIERS[$serviceLevel] ?? 1.0;
        
        $quote['service_level_multiplier'] = [
            'service_level' => $serviceLevel,
            'multiplier' => $multiplier,
            'amount' => $quote['base_amount'] * ($multiplier - 1)
        ];
        
        $quote['base_amount'] *= $multiplier;
        
        return $quote;
    }

    /**
     * Calculate shipment volume
     */
    private function calculateShipmentVolume(array $shipmentData): float
    {
        $weight = $shipmentData['weight_kg'] ?? 0;
        $pieces = $shipmentData['pieces'] ?? 1;
        
        return $weight * $pieces;
    }

    /**
     * Calculate volume-based discount
     */
    private function calculateVolumeBasedDiscount(Customer $customer, float $volume): float
    {
        $monthlyVolume = $customer->total_shipments ?? 0;
        
        if ($monthlyVolume >= 1000) {
            return 5.0; // 5% discount for high volume
        } elseif ($monthlyVolume >= 500) {
            return 3.0; // 3% discount for medium volume
        } elseif ($monthlyVolume >= 100) {
            return 1.0; // 1% discount for low volume
        }
        
        return 0.0;
    }

    /**
     * Add customs clearance fees
     */
    private function addCustomsClearanceFees(array $quote, string $origin, string $destination, array $shipmentData): array
    {
        $isInternational = $this->isInternationalRoute($origin, $destination);
        
        if (!$isInternational) {
            $quote['customs_clearance'] = [
                'applicable' => false,
                'fees' => 0
            ];
            return $quote;
        }
        
        $customsFee = $this->calculateCustomsClearanceFee($shipmentData);
        
        $quote['customs_clearance'] = [
            'applicable' => true,
            'fees' => $customsFee,
            'route_type' => 'international'
        ];
        
        $quote['base_amount'] += $customsFee;
        
        return $quote;
    }

    /**
     * Calculate customs clearance fee
     */
    private function calculateCustomsClearanceFee(array $shipmentData): float
    {
        $baseFee = 25.00; // Base customs fee
        $value = $shipmentData['declared_value'] ?? 0;
        
        // Percentage of declared value (minimum $25)
        $percentageFee = $value * 0.02; // 2% of declared value
        
        return max($baseFee, $percentageFee);
    }

    /**
     * Convert currency amounts
     */
    private function convertCurrency(array $quote, string $targetCurrency): array
    {
        if ($targetCurrency === 'USD') {
            return $quote; // USD is base currency
        }
        
        $exchangeRate = $this->getExchangeRate($targetCurrency);
        $originalCurrency = 'USD';
        
        $convertedQuote = $quote;
        
        // Convert all monetary amounts
        $monetaryFields = ['base_amount'];
        
        foreach ($monetaryFields as $field) {
            if (isset($convertedQuote[$field])) {
                $convertedQuote[$field] *= $exchangeRate;
            }
        }
        
        // Convert nested monetary fields
        if (isset($convertedQuote['breakdown'])) {
            foreach ($convertedQuote['breakdown'] as $key => $value) {
                if (is_numeric($value)) {
                    $convertedQuote['breakdown'][$key] *= $exchangeRate;
                }
            }
        }
        
        $convertedQuote['currency_conversion'] = [
            'from_currency' => $originalCurrency,
            'to_currency' => $targetCurrency,
            'exchange_rate' => $exchangeRate,
            'converted_at' => now()->toISOString()
        ];
        
        $convertedQuote['currency'] = $targetCurrency;
        
        return $convertedQuote;
    }

    /**
     * Get exchange rate for currency conversion
     */
    private function getExchangeRate(string $currency): float
    {
        $cacheKey = "exchange_rate_{$currency}";
        
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_EXCHANGE_RATES), function() use ($currency) {
            // In production, this would call a real currency API
            return match($currency) {
                'EUR' => 0.85,
                'GBP' => 0.75,
                'CAD' => 1.25,
                'JPY' => 110.0,
                'AUD' => 1.35,
                default => 1.0
            };
        });
    }

    /**
     * Add competitor benchmarking to quote
     */
    private function addCompetitorBenchmarking(array $quote, string $origin, string $destination, string $serviceLevel): array
    {
        $route = "{$origin}-{$destination}";
        $benchmarking = $this->getCompetitorBenchmarking($route, $serviceLevel);
        
        $quote['competitor_benchmarking'] = $benchmarking;
        
        if ($benchmarking['available'] && isset($benchmarking['analysis']['overall_average'])) {
            $ourPrice = $quote['base_amount'];
            $competitorAverage = $benchmarking['analysis']['overall_average'];
            $pricePosition = $this->calculatePricePosition($ourPrice, $competitorAverage);
            
            $quote['price_positioning'] = $pricePosition;
        }
        
        return $quote;
    }

    /**
     * Calculate price position relative to competition
     */
    private function calculatePricePosition(float $ourPrice, float $competitorAverage): array
    {
        $percentageDiff = (($ourPrice - $competitorAverage) / $competitorAverage) * 100;
        
        return [
            'our_price' => $ourPrice,
            'competitor_average' => $competitorAverage,
            'percentage_difference' => round($percentageDiff, 2),
            'position' => $percentageDiff < -5 ? 'competitive_advantage' : 
                         ($percentageDiff > 5 ? 'premium_pricing' : 'market_rate'),
            'description' => $this->getPricePositionDescription($percentageDiff)
        ];
    }

    /**
     * Get price position description
     */
    private function getPricePositionDescription(float $percentageDiff): string
    {
        return match(true) {
            $percentageDiff < -10 => 'Significantly below market rate',
            $percentageDiff < -5 => 'Below market rate - competitive advantage',
            $percentageDiff < 5 => 'At market rate',
            $percentageDiff < 10 => 'Above market rate - premium positioning',
            default => 'Significantly above market rate'
        };
    }

    /**
     * Calculate final totals and summary
     */
    private function calculateFinalTotals(array $quote): array
    {
        $subtotal = $quote['base_amount'];
        
        $quote['final_total'] = $subtotal;
        $quote['currency'] = $quote['currency'] ?? 'USD';
        $quote['quote_summary'] = [
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'currency' => $quote['currency'],
            'breakdown_available' => true
        ];
        
        return $quote;
    }

    /**
     * Check if route is international
     */
    private function isInternationalRoute(string $origin, string $destination): bool
    {
        // Simplified logic - in production, this would check against a country database
        $domesticCountries = ['US', 'CA'];
        
        return !in_array($origin, $domesticCountries) || !in_array($destination, $domesticCountries);
    }

    /**
     * Create mock shipment for rate calculations
     */
    private function createMockShipment(string $origin, string $destination, array $shipmentData, string $serviceLevel): Shipment
    {
        $shipment = new Shipment([
            'service_level' => $serviceLevel,
            'total_weight' => $shipmentData['weight_kg'] ?? 0,
            'metadata' => $shipmentData
        ]);
        
        // Mock branches would be set in a real implementation
        $shipment->originBranch = new \App\Models\Backend\Branch(['name' => $origin]);
        $shipment->destBranch = new \App\Models\Backend\Branch(['name' => $destination]);
        
        return $shipment;
    }

    /**
     * Generate cache key for quote calculation
     */
    private function generateCacheKey(string $origin, string $destination, array $shipmentData, string $serviceLevel, ?int $customerId, string $currency): string
    {
        $keyData = [
            $origin, $destination, $serviceLevel, $customerId, $currency,
            md5(serialize($shipmentData))
        ];
        
        return 'quote_' . md5(implode('|', $keyData));
    }

    /**
     * Generate unique quote ID
     */
    private function generateQuoteId(): string
    {
        return 'Q' . now()->format('Ymd') . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Log quote generation
     */
    private function logQuoteGeneration(array $quote, ?int $customerId): void
    {
        Log::info('Quote generated', [
            'quote_id' => $quote['quote_id'],
            'customer_id' => $customerId,
            'total_amount' => $quote['final_total'],
            'service_level' => $quote['service_level'],
            'processing_time_ms' => $quote['processing_time_ms'],
            'currency' => $quote['currency']
        ]);
    }
}