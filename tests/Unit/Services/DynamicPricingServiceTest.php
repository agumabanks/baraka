<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DynamicPricingService;
use App\Services\RateCardManagementService;
use App\Services\WebhookManagementService;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\CompetitorPrice;
use App\Models\FuelIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for DynamicPricingService
 */
class DynamicPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DynamicPricingService $pricingService;
    protected RateCardManagementService $mockRateCardService;
    protected WebhookManagementService $mockWebhookService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->mockRateCardService = Mockery::mock(RateCardManagementService::class);
        $this->mockWebhookService = Mockery::mock(WebhookManagementService::class);
        
        $this->pricingService = new DynamicPricingService(
            $this->mockRateCardService,
            $this->mockWebhookService
        );

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_calculates_instant_quote_successfully()
    {
        // Arrange
        $origin = 'US';
        $destination = 'CA';
        $shipmentData = [
            'weight_kg' => 5.0,
            'pieces' => 1,
            'dimensions' => [
                'length_cm' => 30,
                'width_cm' => 20,
                'height_cm' => 15
            ],
            'declared_value' => 100.0
        ];
        $serviceLevel = 'standard';
        $customerId = null;
        $currency = 'USD';

        // Mock the rate card service
        $this->mockRateCardService->shouldReceive('calculateShippingRate')
            ->once()
            ->andReturn([
                'base_rate' => 25.00,
                'grand_total' => 35.00,
                'surcharges' => ['total' => 10.00]
            ]);

        // Act
        $quote = $this->pricingService->calculateInstantQuote(
            $origin,
            $destination,
            $shipmentData,
            $serviceLevel,
            $customerId,
            $currency
        );

        // Assert
        $this->assertArrayHasKey('quote_id', $quote);
        $this->assertArrayHasKey('final_total', $quote);
        $this->assertArrayHasKey('processing_time_ms', $quote);
        $this->assertArrayHasKey('dimensional_weight', $quote);
        $this->assertArrayHasKey('fuel_surcharge', $quote);
        $this->assertArrayHasKey('taxes', $quote);
        $this->assertArrayHasKey('currency', $quote);
        $this->assertEquals('USD', $quote['currency']);
        $this->assertIsString($quote['quote_id']);
        $this->assertGreaterThan(0, $quote['final_total']);
    }

    /** @test */
    public function it_applies_dimensional_weight_calculations()
    {
        // Arrange
        $quote = ['base_amount' => 50.0];
        $dimensions = [
            'weight_kg' => 2.0,
            'length_cm' => 50,
            'width_cm' => 40,
            'height_cm' => 30
        ];

        // Volume = 50 * 40 * 30 / 1,000,000 = 0.06 cubic meters
        // Dimensional weight = 0.06 * 1000 * 5000 = 300 kg
        // Chargeable weight = max(2, 300) = 300 kg
        // Surcharge = (300 - 2) * 2.5 = 745

        // Act
        $result = $this->pricingService->applyDimensionalWeight($quote, $dimensions);

        // Assert
        $this->assertTrue($result['dimensional_weight']['applicable']);
        $this->assertEquals(2.0, $result['dimensional_weight']['actual_weight']);
        $this->assertEquals(300, $result['dimensional_weight']['dimensional_weight']);
        $this->assertEquals(298, $result['dimensional_weight']['difference']);
        $this->assertEquals(745, $result['dimensional_weight']['surcharge']);
        $this->assertEquals(795.0, $result['base_amount']);
    }

    /** @test */
    public function it_does_not_apply_dimensional_weight_when_not_needed()
    {
        // Arrange
        $quote = ['base_amount' => 25.0];
        $dimensions = [
            'weight_kg' => 5.0,
            'length_cm' => 20,
            'width_cm' => 15,
            'height_cm' => 10
        ];

        // Volume = 20 * 15 * 10 / 1,000,000 = 0.003 cubic meters
        // Dimensional weight = 0.003 * 1000 * 5000 = 15 kg
        // Chargeable weight = max(5, 15) = 15 kg
        // Surcharge = (15 - 5) * 2.5 = 25

        // Act
        $result = $this->pricingService->applyDimensionalWeight($quote, $dimensions);

        // Assert
        $this->assertTrue($result['dimensional_weight']['applicable']);
        $this->assertGreaterThan($quote['base_amount'], $result['base_amount']);
    }

    /** @test */
    public function it_calculates_fuel_surcharge_correctly()
    {
        // Arrange
        Cache::put('current_fuel_index', 110.0, 3600); // 10% above base
        $quote = ['base_amount' => 100.0, 'service_level' => 'standard'];
        $origin = 'US';
        $destination = 'CA';
        $serviceLevel = 'standard';

        // Expected surcharge: 100 * ((110-100)/100) * 0.08 = 0.8

        // Act
        $result = $this->pricingService->getFuelSurcharge($quote, $origin, $destination, $serviceLevel);

        // Assert
        $this->assertTrue($result['fuel_surcharge']['applicable']);
        $this->assertEquals(0.08, $result['fuel_surcharge']['rate']);
        $this->assertEquals(0.8, $result['fuel_surcharge']['amount']);
        $this->assertEquals(100.8, $result['base_amount']);
    }

    /** @test */
    public function it_calculates_taxes_for_different_jurisdictions()
    {
        // Arrange
        $quote = ['base_amount' => 100.0];
        $jurisdiction = 'US';
        $currency = 'USD';

        // Act
        $result = $this->pricingService->calculateTaxes($quote, $jurisdiction, $currency);

        // Assert
        $this->assertTrue($result['taxes']['applicable']);
        $this->assertArrayHasKey('breakdown', $result['taxes']);
        $this->assertGreaterThan(0, $result['taxes']['total']);
        $this->assertEquals('US', $result['taxes']['jurisdiction']);
        $this->assertGreaterThan($quote['base_amount'], $result['base_amount']);
    }

    /** @test */
    public function it_applies_volume_discounts_for_tier_customers()
    {
        // Arrange
        $quote = ['base_amount' => 100.0];
        $customer = Customer::factory()->create([
            'customer_type' => 'platinum',
            'total_shipments' => 600
        ]);
        $shipmentData = ['weight_kg' => 5.0, 'pieces' => 10];

        // Expected: 15% tier discount + volume discount
        // Expected discount amount: 100 * (15 + 5) / 100 = 20

        // Act
        $result = $this->pricingService->applyVolumeDiscounts($quote, $customer, $shipmentData);

        // Assert
        $this->assertTrue($result['volume_discount']['applicable']);
        $this->assertEquals('platinum', $result['volume_discount']['customer_tier']);
        $this->assertGreaterThan(0, $result['volume_discount']['total_discount_rate']);
        $this->assertEquals(20.0, $result['volume_discount']['amount']);
        $this->assertEquals(80.0, $result['base_amount']);
    }

    /** @test */
    public function it_validates_quote_data_correctly()
    {
        // Arrange
        $validQuote = [
            'origin' => 'US',
            'destination' => 'CA',
            'service_level' => 'standard',
            'shipment_data' => [
                'weight_kg' => 5.0,
                'dimensions' => [
                    'length_cm' => 30,
                    'width_cm' => 20,
                    'height_cm' => 15
                ]
            ],
            'total_amount' => 50.0,
            'processing_time_ms' => 1000,
            'dimensional_weight' => [
                'applicable' => false,
                'difference' => 0
            ]
        ];

        $invalidQuote = [
            'origin' => '',
            'service_level' => 'standard',
            'total_amount' => -5.0
        ];

        // Act
        $validResult = $this->pricingService->validateQuote($validQuote);
        $invalidResult = $this->pricingService->validateQuote($invalidQuote);

        // Assert
        $this->assertTrue($validResult['valid']);
        $this->assertEmpty($validResult['errors']);
        
        $this->assertFalse($invalidResult['valid']);
        $this->assertNotEmpty($invalidResult['errors']);
    }

    /** @test */
    public function it_generates_bulk_quotes_successfully()
    {
        // Arrange
        $shipmentRequests = [
            [
                'origin' => 'US',
                'destination' => 'CA',
                'service_level' => 'standard',
                'shipment_data' => ['weight_kg' => 5.0, 'pieces' => 1]
            ],
            [
                'origin' => 'US',
                'destination' => 'MX',
                'service_level' => 'express',
                'shipment_data' => ['weight_kg' => 10.0, 'pieces' => 2]
            ]
        ];

        // Mock the rate card service for both calls
        $this->mockRateCardService->shouldReceive('calculateShippingRate')
            ->twice()
            ->andReturn(
                ['base_rate' => 25.00, 'grand_total' => 35.00, 'surcharges' => ['total' => 10.00]],
                ['base_rate' => 50.00, 'grand_total' => 70.00, 'surcharges' => ['total' => 20.00]]
            );

        // Act
        $results = $this->pricingService->generateBulkQuotes($shipmentRequests);

        // Assert
        $this->assertArrayHasKey('total_requests', $results);
        $this->assertArrayHasKey('successful_quotes', $results);
        $this->assertArrayHasKey('failed_quotes', $results);
        $this->assertArrayHasKey('results', $results);
        $this->assertEquals(2, $results['total_requests']);
        $this->assertEquals(2, $results['successful_quotes']);
        $this->assertEquals(0, $results['failed_quotes']);
        $this->assertCount(2, $results['results']);
    }

    /** @test */
    public function it_handles_competitor_benchmarking_correctly()
    {
        // Arrange
        CompetitorPrice::factory()->create([
            'carrier_name' => 'FedEx',
            'origin_country' => 'US',
            'destination_country' => 'CA',
            'service_level' => 'standard',
            'price' => 45.00,
            'collected_at' => now()->subDays(5)
        ]);

        CompetitorPrice::factory()->create([
            'carrier_name' => 'UPS',
            'origin_country' => 'US',
            'destination_country' => 'CA',
            'service_level' => 'standard',
            'price' => 42.00,
            'collected_at' => now()->subDays(3)
        ]);

        $route = 'US-CA';
        $serviceLevel = 'standard';

        // Act
        $benchmarking = $this->pricingService->getCompetitorBenchmarking($route, $serviceLevel);

        // Assert
        $this->assertTrue($benchmarking['available']);
        $this->assertArrayHasKey('analysis', $benchmarking);
        $this->assertArrayHasKey('overall_average', $benchmarking['analysis']);
        $this->assertEquals(2, $benchmarking['data_points']);
    }

    /** @test */
    public function it_handles_currency_conversion()
    {
        // Test currency conversion logic by directly testing the protected method
        $quote = ['base_amount' => 100.0, 'currency' => 'USD'];
        $EURExchangeRate = 0.85;

        // Mock the getExchangeRate method
        $reflection = new \ReflectionClass($this->pricingService);
        $method = $reflection->getMethod('getExchangeRate');
        $method->setAccessible(true);
        
        // Mock Cache::get for exchange rate
        Cache::put('exchange_rate_EUR', $EURExchangeRate, 1800);
        
        $convertedQuote = $this->invokeMethod($this->pricingService, 'convertCurrency', [$quote, 'EUR']);

        $this->assertEquals('EUR', $convertedQuote['currency']);
        $this->assertArrayHasKey('currency_conversion', $convertedQuote);
        $this->assertEquals(0.85, $convertedQuote['currency_conversion']['exchange_rate']);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        // Arrange
        $this->mockRateCardService->shouldReceive('calculateShippingRate')
            ->once()
            ->andThrow(new \Exception('Rate calculation failed'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to calculate instant quote');

        $this->pricingService->calculateInstantQuote(
            'US',
            'CA',
            ['weight_kg' => 5.0],
            'standard'
        );
    }

    /** @test */
    public function it_caches_quote_calculations()
    {
        // Arrange
        $origin = 'US';
        $destination = 'CA';
        $shipmentData = ['weight_kg' => 5.0, 'pieces' => 1];
        $serviceLevel = 'standard';
        $customerId = null;
        $currency = 'USD';

        $this->mockRateCardService->shouldReceive('calculateShippingRate')
            ->once()
            ->andReturn([
                'base_rate' => 25.00,
                'grand_total' => 35.00,
                'surcharges' => ['total' => 10.00]
            ]);

        // First call should hit the service
        $quote1 = $this->pricingService->calculateInstantQuote(
            $origin, $destination, $shipmentData, $serviceLevel, $customerId, $currency
        );

        // Second call should use cache
        $quote2 = $this->pricingService->calculateInstantQuote(
            $origin, $destination, $shipmentData, $serviceLevel, $customerId, $currency
        );

        // Assert
        $this->assertEquals($quote1['quote_id'], $quote2['quote_id']);
        $this->assertEquals($quote1['final_total'], $quote2['final_total']);
    }

    // Helper method to invoke private/protected methods
    private function invokeMethod($object, string $method, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}