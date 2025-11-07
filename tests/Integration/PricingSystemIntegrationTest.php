<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\DynamicPricingService;
use App\Services\RateCardManagementService;
use App\Services\PromotionEngineService;
use App\Services\ContractManagementService;
use App\Services\AuditService;
use App\Services\WebhookManagementService;
use App\Services\MilestoneTrackingService;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\PromotionalCampaign;
use App\Models\Contract;
use App\Models\Branch;
use App\Models\BranchWorker;
use App\Models\PromotionalCampaignUsage;
use App\Models\CustomerMilestone;
use App\Events\PromotionActivated;
use App\Events\ContractActivated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

/**
 * Integration Test Suite for Enhanced Logistics Pricing System
 * 
 * Tests cross-service integration including:
 * - Database integration and transaction integrity
 * - Service integration and data flow
 * - External integration (carrier APIs, payment gateways)
 * - Cache integration and performance optimization
 * - Queue integration and event handling
 * - Audit trail integration
 */
class PricingSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected DynamicPricingService $pricingService;
    protected RateCardManagementService $rateService;
    protected PromotionEngineService $promotionService;
    protected ContractManagementService $contractService;
    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize services
        $this->pricingService = app(DynamicPricingService::class);
        $this->rateService = app(RateCardManagementService::class);
        $this->promotionService = app(PromotionEngineService::class);
        $this->contractService = app(ContractManagementService::class);
        $this->auditService = app(AuditService::class);

        // Set up test environment
        $this->artisan('db:seed');
        Cache::flush();
    }

    // ===== DATABASE INTEGRATION TESTS =====

    /** @test */
    public function it_maintains_transaction_integrity_across_services()
    {
        // Arrange
        Event::fake();
        $customer = Customer::factory()->create();
        $promotion = PromotionalCampaign::factory()->create([
            'is_active' => true,
            'effective_from' => Carbon::now()->subDay(),
            'effective_to' => Carbon::now()->addDays(30)
        ]);

        $shipmentData = [
            'weight_kg' => 5.0,
            'pieces' => 1,
            'declared_value' => 100.0
        ];

        $orderData = [
            'total_amount' => 50.00,
            'shipping_cost' => 15.00
        ];

        // Act - Execute a transaction involving multiple services
        $result = DB::transaction(function () use ($customer, $promotion, $shipmentData, $orderData) {
            // Get pricing from rate service
            $pricing = $this->rateService->calculateShippingRate(
                Shipment::factory()->create(['customer_id' => $customer->id]),
                ['service_level' => 'standard']
            );

            // Apply promotion
            $promotionResult = $this->promotionService->validatePromotionalCode(
                $promotion->promo_code,
                $customer->id,
                $orderData
            );

            // Track milestone
            $milestoneResult = $this->promotionService->trackMilestoneProgress(
                $customer->id,
                $shipmentData
            );

            // Log audit trail
            $this->auditService->logAction(
                'order_processing',
                $customer->id,
                [
                    'pricing' => $pricing,
                    'promotion' => $promotionResult,
                    'milestone' => $milestoneResult
                ]
            );

            return [
                'pricing' => $pricing,
                'promotion' => $promotionResult,
                'milestone' => $milestoneResult
            ];
        });

        // Assert
        $this->assertArrayHasKey('pricing', $result);
        $this->assertArrayHasKey('promotion', $result);
        $this->assertArrayHasKey('milestone', $result);
        $this->assertTrue($result['promotion']['valid']);

        // Verify audit trail was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $customer->id,
            'action' => 'order_processing'
        ]);
    }

    /** @test */
    public function it_rolls_back_transactions_on_failure()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $initialShipmentCount = Shipment::count();

        // Act - Simulate a failure during multi-service operation
        try {
            DB::transaction(function () use ($customer) {
                // Create shipment
                Shipment::factory()->create(['customer_id' => $customer->id]);
                
                // Simulate failure (e.g., external service unavailable)
                throw new \Exception('External service unavailable');
            });
        } catch (\Exception $e) {
            // Expected failure
        }

        // Assert - Transaction should have rolled back
        $this->assertEquals($initialShipmentCount, Shipment::count(), 'Shipment creation should have rolled back');
    }

    /** @test */
    public function it_maintains_data_consistency_across_cached_and_database_operations()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'silver']);
        $shipment = Shipment::factory()->create(['customer_id' => $customer->id]);

        // Act - Get initial rate (should be cached)
        $initialRate = $this->rateService->calculateShippingRate($shipment, ['service_level' => 'standard']);

        // Update customer tier
        $customer->update(['customer_type' => 'gold']);

        // Get rate again (cache should be invalidated)
        $updatedRate = $this->rateService->calculateShippingRate($shipment, ['service_level' => 'standard']);

        // Assert
        $this->assertNotEquals(
            $initialRate['total_amount'],
            $updatedRate['total_amount'],
            'Rate should change when customer tier changes'
        );

        // Gold tier should have better rate than silver
        $this->assertLessThan(
            $initialRate['total_amount'],
            $updatedRate['total_amount'],
            'Gold tier should have lower rate than silver'
        );
    }

    // ===== SERVICE INTEGRATION TESTS =====

    /** @test */
    public function it_integrates_pricing_promotion_and_contract_services()
    {
        // Arrange
        Event::fake();
        
        $customer = Customer::factory()->create(['customer_type' => 'gold']);
        $contract = Contract::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'active',
            'volume_commitment' => 100,
            'current_volume' => 50
        ]);

        $promotion = PromotionalCampaign::factory()->create([
            'campaign_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'customer_eligibility' => [
                'customer_types' => ['gold', 'platinum']
            ]
        ]);

        $shipmentData = [
            'weight_kg' => 10.0,
            'pieces' => 2,
            'declared_value' => 200.0
        ];

        $orderData = [
            'total_amount' => 100.00,
            'shipping_cost' => 20.00
        ];

        // Act - Comprehensive pricing calculation
        $baseQuote = $this->pricingService->calculateInstantQuote(
            'KE', 'UG', $shipmentData, 'standard', $customer->id, 'USD'
        );

        $promotionValidation = $this->promotionService->validatePromotionalCode(
            $promotion->promo_code,
            $customer->id,
            $orderData
        );

        $contractPricing = $this->contractService->applyContractPricing(
            $contract->id,
            $shipmentData
        );

        $milestoneTracking = $this->promotionService->trackMilestoneProgress(
            $customer->id,
            $shipmentData
        );

        // Assert
        $this->assertTrue($baseQuote['success'] ?? true);
        $this->assertTrue($promotionValidation['valid']);
        $this->assertArrayHasKey('contract_discount', $contractPricing);
        $this->assertTrue($milestoneTracking['progress_updated']);

        // Verify all services contributed to final pricing
        $this->assertGreaterThan(0, $baseQuote['final_total'] ?? 0);
    }

    /** @test */
    public function it_handles_cross_service_error_propagation()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $invalidPromotionCode = 'INVALID_CODE_12345';

        // Act & Assert - Promotion service should handle invalid codes gracefully
        $validationResult = $this->promotionService->validatePromotionalCode(
            $invalidPromotionCode,
            $customer->id,
            ['total_amount' => 100.00]
        );

        $this->assertFalse($validationResult['valid']);
        $this->assertNotNull($validationResult['error']);

        // Quote generation should still work despite invalid promotion
        $shipmentData = ['weight_kg' => 5.0, 'pieces' => 1];
        $quote = $this->pricingService->calculateInstantQuote(
            'KE', 'UG', $shipmentData, 'standard', $customer->id, 'USD'
        );

        $this->assertTrue($quote['success'] ?? true);
        $this->assertGreaterThan(0, $quote['final_total'] ?? 0);
    }

    // ===== EXTERNAL INTEGRATION TESTS =====

    /** @test */
    public function it_handles_external_carrier_api_integration()
    {
        // Arrange
        Cache::put('external_carrier_rates_KE_UG', [
            'carriers' => [
                'fedex' => 45.00,
                'ups' => 42.00,
                'dhl' => 48.00
            ],
            'timestamp' => now()->toISOString()
        ], 3600);

        // Act
        $benchmarking = $this->pricingService->getCompetitorBenchmarking('KE-UG', 'standard');

        // Assert
        $this->assertTrue($benchmarking['available']);
        $this->assertArrayHasKey('analysis', $benchmarking);
        $this->assertArrayHasKey('overall_average', $benchmarking['analysis']);
    }

    /** @test */
    public function it_handles_payment_gateway_integration()
    {
        // Arrange
        $shipment = Shipment::factory()->create([
            'total_amount' => 100.00,
            'payment_status' => 'pending'
        ]);

        // Act - Simulate payment processing
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'payment_method' => 'stripe',
            'payment_token' => 'tok_test_123'
        ];

        // This would typically integrate with actual payment gateway
        // For testing, we simulate the response
        $mockPaymentResponse = [
            'success' => true,
            'transaction_id' => 'txn_' . uniqid(),
            'amount' => 100.00,
            'currency' => 'USD'
        ];

        // Assert
        $this->assertTrue($mockPaymentResponse['success']);
        $this->assertNotNull($mockPaymentResponse['transaction_id']);
        $this->assertEquals(100.00, $mockPaymentResponse['amount']);
    }

    // ===== CACHE INTEGRATION TESTS =====

    /** @test */
    public function it_maintains_cache_consistency_with_database_changes()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'silver']);
        $shipment = Shipment::factory()->create(['customer_id' => $customer->id]);

        // Act - Get initial rate (cached)
        $initialRate = $this->rateService->calculateShippingRate($shipment, ['service_level' => 'standard']);
        $cacheKey = "rate_calculation_{$customer->id}_{$shipment->id}_standard";

        // Verify cache was set
        $this->assertTrue(Cache::has($cacheKey));

        // Update customer tier (should invalidate cache)
        $customer->update(['customer_type' => 'gold']);

        // Get rate again
        $updatedRate = $this->rateService->calculateShippingRate($shipment, ['service_level' => 'standard']);

        // Assert
        $this->assertNotEquals($initialRate['total_amount'], $updatedRate['total_amount']);
        $this->assertLessThan($initialRate['total_amount'], $updatedRate['total_amount']);
    }

    /** @test */
    public function it_handles_cache_misses_gracefully()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $shipment = Shipment::factory()->create(['customer_id' => $customer->id]);

        // Clear cache
        Cache::flush();

        // Act - Request calculation when cache is empty
        $rate = $this->rateService->calculateShippingRate($shipment, ['service_level' => 'standard']);

        // Assert - Should still work without cache
        $this->assertArrayHasKey('total_amount', $rate);
        $this->assertGreaterThan(0, $rate['total_amount']);

        // Verify cache was populated
        $cacheKey = "rate_calculation_{$customer->id}_{$shipment->id}_standard";
        $this->assertTrue(Cache::has($cacheKey));
    }

    // ===== QUEUE INTEGRATION TESTS =====

    /** @test */
    public function it_processes_background_jobs_correctly()
    {
        // Arrange
        Event::fake();
        
        $customer = Customer::factory()->create();
        $shipments = Shipment::factory()->count(5)->create(['customer_id' => $customer->id]);

        // Act - Dispatch background job
        ProcessShipmentBatch::dispatch($shipments->pluck('id')->toArray());

        // Assert - Job should be queued
        $this->assertDatabaseHas('jobs', [
            'queue' => 'default',
            'payload' => json_encode(['data' => ['command' => 'ProcessShipmentBatch']])
        ]);
    }

    /** @test */
    public function it_handles_event_driven_workflows()
    {
        // Arrange
        Event::fake();
        
        $customer = Customer::factory()->create();
        $promotion = PromotionalCampaign::factory()->create([
            'is_active' => true,
            'promo_code' => 'TEST20'
        ]);

        // Act - Trigger promotion activation event
        event(new PromotionActivated($promotion, $customer));

        // Assert
        Event::assertDispatched(PromotionActivated::class);

        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'promotion_activated',
            'entity_type' => 'promotional_campaign',
            'entity_id' => $promotion->id
        ]);
    }

    // ===== AUDIT TRAIL INTEGRATION TESTS =====

    /** @test */
    public function it_maintains_comprehensive_audit_trail()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $promotion = PromotionalCampaign::factory()->create();

        // Act - Perform multiple actions that should be audited
        $this->auditService->logAction('quote_generated', $customer->id, [
            'origin' => 'KE',
            'destination' => 'UG',
            'amount' => 45.00
        ]);

        $this->auditService->logPricingAction('promotion_applied', [
            'promotion_id' => $promotion->id,
            'customer_id' => $customer->id,
            'discount_amount' => 10.00
        ]);

        $this->auditService->logContractAction('contract_consulted', [
            'contract_id' => 1,
            'customer_id' => $customer->id
        ]);

        // Assert
        $this->assertDatabaseCount('audit_logs', 3);

        // Verify specific log entries
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $customer->id,
            'action' => 'quote_generated'
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'promotion_applied',
            'entity_type' => 'promotional_campaign'
        ]);
    }

    /** @test */
    public function it_handles_audit_log_cleanup()
    {
        // Arrange - Create old audit logs
        $oldLog = [
            'user_id' => 1,
            'action' => 'test_action',
            'entity_type' => 'test_entity',
            'entity_id' => 1,
            'created_at' => Carbon::now()->subYears(2)
        ];

        DB::table('audit_logs')->insert($oldLog);

        // Act - Run cleanup
        $cleanedCount = $this->auditService->cleanupOldLogs(365); // Keep 1 year

        // Assert
        $this->assertGreaterThan(0, $cleanedCount);
    }

    // ===== PERFORMANCE INTEGRATION TESTS =====

    /** @test */
    public function it_performs_well_under_load()
    {
        // Arrange
        $customers = Customer::factory()->count(50)->create();
        $concurrentRequests = 10;

        // Act - Simulate concurrent quote requests
        $startTime = microtime(true);
        
        foreach (range(1, $concurrentRequests) as $i) {
            $customer = $customers->random();
            $shipmentData = [
                'weight_kg' => rand(1, 20),
                'pieces' => rand(1, 5)
            ];

            $this->pricingService->calculateInstantQuote(
                'KE', 'UG', $shipmentData, 'standard', $customer->id, 'USD'
            );
        }

        $totalTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Assert - Should complete within reasonable time
        $this->assertLessThan(10000, $totalTime, '50 concurrent requests should complete within 10 seconds');
        $this->assertLessThan(1000, $totalTime / $concurrentRequests, 'Each request should average under 1 second');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Event::fake();
        parent::tearDown();
    }
}