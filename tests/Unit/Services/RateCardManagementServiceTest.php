<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RateCardManagementService;
use App\Services\WebhookManagementService;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Branch;
use App\Models\BranchWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Carbon\Carbon;

/**
 * Rate Card Management Service Test Suite
 * 
 * Tests all core functionality of the RateCardManagementService including:
 * - Base rate calculations
 * - Surcharge calculations
 * - Customer tier discounts
 * - Route-based pricing
 * - Volume-based pricing
 * - Bulk rate calculations
 * - API performance and caching
 */
class RateCardManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RateCardManagementService $service;
    protected WebhookManagementService $mockWebhookService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWebhookService = Mockery::mock(WebhookManagementService::class);
        $this->service = new RateCardManagementService($this->mockWebhookService);

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_calculates_shipping_rate_successfully()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'standard']);
        $origin = Branch::factory()->create(['name' => 'Nairobi Hub', 'location' => 'Nairobi, KE']);
        $destination = Branch::factory()->create(['name' => 'Mombasa Hub', 'location' => 'Mombasa, KE']);
        
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'origin_branch_id' => $origin->id,
            'destination_branch_id' => $destination->id,
            'total_weight_kg' => 5.0,
            'total_pieces' => 1
        ]);

        $options = ['service_level' => 'standard'];

        // Act
        $result = $this->service->calculateShippingRate($shipment, $options);

        // Assert
        $this->assertArrayHasKey('base_rate', $result);
        $this->assertArrayHasKey('surcharges', $result);
        $this->assertArrayHasKey('total_amount', $result);
        $this->assertArrayHasKey('delivery_estimate', $result);
        $this->assertArrayHasKey('breakdown', $result);
        
        $this->assertGreaterThan(0, $result['base_rate']);
        $this->assertIsArray($result['surcharges']);
        $this->assertGreaterThan(0, $result['total_amount']);
        $this->assertIsString($result['delivery_estimate']);
    }

    /** @test */
    public function it_applies_customer_tier_discounts_correctly()
    {
        // Arrange
        $tiers = ['bronze', 'silver', 'gold', 'platinum'];
        $expectedDiscounts = [0, 5, 10, 15]; // Percentage discounts for each tier

        foreach ($tiers as $index => $tier) {
            $customer = Customer::factory()->create(['customer_type' => $tier]);
            
            // Act
            $discount = $this->service->getCustomerDiscount($customer);
            
            // Assert
            $this->assertEquals($expectedDiscounts[$index], $discount['percentage']);
            $this->assertEquals($tier, $discount['tier']);
        }
    }

    /** @test */
    public function it_calculates_zone_based_rates_correctly()
    {
        // Arrange
        $nairobi = Branch::factory()->create(['location' => 'Nairobi']);
        $mombasa = Branch::factory()->create(['location' => 'Mombasa']);
        $kisumu = Branch::factory()->create(['location' => 'Kisumu']);

        // Act & Assert - Standard rates
        $nairobiToMombasa = $this->service->getZoneBasedRate($nairobi, $mombasa, 'standard');
        $nairobiToKisumu = $this->service->getZoneBasedRate($nairobi, $kisumu, 'standard');
        $mombasaToKisumu = $this->service->getZoneBasedRate($mombasa, $kisumu, 'standard');

        $this->assertGreaterThan(0, $nairobiToMombasa);
        $this->assertGreaterThan(0, $nairobiToKisumu);
        $this->assertGreaterThan(0, $mombasaToKisumu);

        // Express rates should be higher than standard
        $expressNairobiToMombasa = $this->service->getZoneBasedRate($nairobi, $mombasa, 'express');
        $this->assertGreaterThan($nairobiToMombasa, $expressNairobiToMombasa);
    }

    /** @test */
    public function it_calculates_surcharges_correctly()
    {
        // Arrange
        $shipment = Shipment::factory()->create([
            'total_weight_kg' => 25.0,
            'total_pieces' => 3,
            'special_handling' => 'fragile',
            'delivery_instructions' => 'Handle with care'
        ]);

        // Act
        $surcharges = $this->service->calculateSurcharges($shipment);

        // Assert
        $this->assertIsArray($surcharges);
        $this->assertArrayHasKey('weight_surcharge', $surcharges);
        $this->assertArrayHasKey('piece_surcharge', $surcharges);
        $this->assertArrayHasKey('special_handling', $surcharges);
        $this->assertArrayHasKey('fuel_surcharge', $surcharges);
        $this->assertArrayHasKey('total', $surcharges);

        // Verify calculations
        $this->assertGreaterThan(0, $surcharges['weight_surcharge']);
        $this->assertGreaterThan(0, $surcharges['piece_surcharge']);
        $this->assertGreaterThan(0, $surcharges['special_handling']);
        $this->assertEquals(
            $surcharges['weight_surcharge'] + $surcharges['piece_surcharge'] + 
            $surcharges['special_handling'] + $surcharges['fuel_surcharge'],
            $surcharges['total']
        );
    }

    /** @test */
    public function it_calculates_fuel_surcharge_correctly()
    {
        // Arrange
        $baseAmount = 100.0;
        Cache::put('current_fuel_index', 110.0, 3600); // 10% above base

        // Act
        $fuelSurcharge = $this->service->calculateFuelSurcharge($baseAmount);

        // Assert
        $expectedSurcharge = $baseAmount * 0.08; // 8% of base amount
        $this->assertEquals($expectedSurcharge, $fuelSurcharge, '', 0.01);
    }

    /** @test */
    public function it_calculates_taxes_correctly()
    {
        // Arrange
        $amount = 100.0;

        // Act
        $taxes = $this->service->calculateTaxes($amount);

        // Assert
        $this->assertIsArray($taxes);
        $this->assertArrayHasKey('vat', $taxes);
        $this->assertArrayHasKey('total', $taxes);
        
        $expectedVat = $amount * 0.16; // 16% VAT in Kenya
        $this->assertEquals($expectedVat, $taxes['vat'], '', 0.01);
        $this->assertEquals($amount + $expectedVat, $taxes['total'], '', 0.01);
    }

    /** @test */
    public function it_gets_customer_rate_card_correctly()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'silver']);

        // Act
        $rateCard = $this->service->getCustomerRateCard($customer);

        // Assert
        $this->assertArrayHasKey('tier_info', $rateCard);
        $this->assertArrayHasKey('base_rates', $rateCard);
        $this->assertArrayHasKey('discounts', $rateCard);
        $this->assertArrayHasKey('minimum_charges', $rateCard);
        $this->assertArrayHasKey('next_tier_requirements', $rateCard);

        $this->assertEquals('silver', $rateCard['tier_info']['tier']);
        $this->assertEquals(5, $rateCard['discounts']['percentage']);
    }

    /** @test */
    public function it_updates_customer_tier_correctly()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'bronze']);
        $newTier = 'gold';

        // Act
        $result = $this->service->updateCustomerTier($customer, $newTier);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals($newTier, $result['new_tier']);
        $this->assertArrayHasKey('benefits', $result);
        $this->assertArrayHasKey('next_tier', $result);

        // Verify customer was updated
        $this->assertEquals($newTier, $customer->fresh()->customer_type);
    }

    /** @test */
    public function it_calculates_bulk_rates_correctly()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'gold']);
        $shipments = collect([
            Shipment::factory()->create(['customer_id' => $customer->id, 'total_weight_kg' => 2.0]),
            Shipment::factory()->create(['customer_id' => $customer->id, 'total_weight_kg' => 3.0]),
            Shipment::factory()->create(['customer_id' => $customer->id, 'total_weight_kg' => 1.0])
        ]);

        // Act
        $bulkRates = $this->service->calculateBulkRates($shipments);

        // Assert
        $this->assertArrayHasKey('individual_rates', $bulkRates);
        $this->assertArrayHasKey('bulk_discount', $bulkRates);
        $this->assertArrayHasKey('total_savings', $bulkRates);
        $this->assertArrayHasKey('final_total', $bulkRates);
        $this->assertArrayHasKey('breakdown', $bulkRates);

        $this->assertCount(3, $bulkRates['individual_rates']);
        $this->assertGreaterThan(0, $bulkRates['bulk_discount']);
        $this->assertGreaterThanOrEqual(0, $bulkRates['total_savings']);
    }

    /** @test */
    public function it_performs_route_rate_analysis()
    {
        // Arrange
        $origin = Branch::factory()->create(['location' => 'Nairobi']);
        $destination = Branch::factory()->create(['location' => 'Mombasa']);
        $serviceLevels = ['standard', 'express', 'overnight'];

        // Act
        $analysis = $this->service->getRouteRateAnalysis($origin, $destination, $serviceLevels);

        // Assert
        $this->assertArrayHasKey('route_info', $analysis);
        $this->assertArrayHasKey('service_options', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
        $this->assertArrayHasKey('reliability_scores', $analysis);

        $this->assertEquals('Nairobi-Mombasa', $analysis['route_info']['route_name']);
        $this->assertCount(3, $analysis['service_options']);
        $this->assertContains('standard', $analysis['service_options']);
        $this->assertContains('express', $analysis['service_options']);
        $this->assertContains('overnight', $analysis['service_options']);
    }

    /** @test */
    public function it_handles_error_cases_gracefully()
    {
        // Arrange
        $invalidShipment = null;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculateShippingRate($invalidShipment);
    }

    /** @test */
    public function it_caches_calculation_results()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $shipment = Shipment::factory()->create(['customer_id' => $customer->id]);
        $options = ['service_level' => 'standard'];

        // First call should hit the service
        $result1 = $this->service->calculateShippingRate($shipment, $options);

        // Second call should use cache
        $result2 = $this->service->calculateShippingRate($shipment, $options);

        // Assert
        $this->assertEquals($result1['total_amount'], $result2['total_amount']);
        $this->assertEquals($result1['base_rate'], $result2['base_rate']);
    }

    /** @test */
    public function it_validates_service_levels()
    {
        // Arrange
        $validServiceLevels = ['standard', 'express', 'overnight', 'same_day'];
        $invalidServiceLevel = 'invalid_service';

        // Act & Assert - Valid service levels should not throw exception
        foreach ($validServiceLevels as $serviceLevel) {
            $origin = Branch::factory()->create();
            $destination = Branch::factory()->create();
            
            $this->assertGreaterThan(0, $this->service->getZoneBasedRate(
                $origin, $destination, $serviceLevel
            ));
        }

        // Invalid service level should still work (fallback to standard)
        $origin = Branch::factory()->create();
        $destination = Branch::factory()->create();
        
        $rate = $this->service->getZoneBasedRate($origin, $destination, $invalidServiceLevel);
        $this->assertGreaterThan(0, $rate);
    }

    /** @test */
    public function it_handles_weight_and_dimension_calculations()
    {
        // Arrange
        $shipment = Shipment::factory()->create([
            'total_weight_kg' => 2.0, // Actual weight
            'total_pieces' => 1,
            'total_volume_m3' => 0.05 // Large volume
        ]);

        // Act
        $surcharges = $this->service->calculateSurcharges($shipment);

        // Assert
        $this->assertGreaterThan(0, $surcharges['weight_surcharge']);
        $this->assertGreaterThan(0, $surcharges['piece_surcharge']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}