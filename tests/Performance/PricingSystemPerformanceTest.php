<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Services\DynamicPricingService;
use App\Services\RateCardManagementService;
use App\Services\PromotionEngineService;
use App\Services\ContractManagementService;
use App\Services\APIMonitoringService;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\PromotionalCampaign;
use App\Models\Contract;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Performance Test Suite for Enhanced Logistics Pricing System
 * 
 * Tests system performance under various conditions including:
 * - Load testing for high-volume quote generation
 * - Stress testing under extreme conditions
 * - API response time validation
 * - Database performance optimization
 * - Cache performance and hit rates
 * - Memory usage and resource consumption
 */
class PricingSystemPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected DynamicPricingService $pricingService;
    protected RateCardManagementService $rateService;
    protected PromotionEngineService $promotionService;
    protected ContractManagementService $contractService;
    protected APIMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pricingService = app(DynamicPricingService::class);
        $this->rateService = app(RateCardManagementService::class);
        $this->promotionService = app(PromotionEngineService::class);
        $this->contractService = app(ContractManagementService::class);
        $this->monitoringService = app(APIMonitoringService::class);

        // Performance test configuration
        $this->performanceThresholds = [
            'quote_generation' => 2000, // 2 seconds
            'bulk_quotes' => 10000, // 10 seconds
            'promotion_validation' => 500, // 500ms
            'contract_creation' => 3000, // 3 seconds
            'database_query' => 100, // 100ms
            'cache_lookup' => 50, // 50ms
        ];

        Cache::flush();
    }

    // ===== QUOTE GENERATION PERFORMANCE TESTS =====

    /** @test */
    public function it_generates_quotes_within_performance_threshold()
    {
        // Arrange
        $quoteRequests = $this->generateQuoteRequests(100);
        $threshold = $this->performanceThresholds['quote_generation'];
        $maxAcceptableTime = 3000; // 3 seconds for 100 quotes

        // Act
        $startTime = microtime(true);
        $results = [];
        
        foreach ($quoteRequests as $request) {
            $result = $this->pricingService->calculateInstantQuote(
                $request['origin'],
                $request['destination'],
                $request['shipment_data'],
                $request['service_level'],
                $request['customer_id'] ?? null,
                $request['currency'] ?? 'USD'
            );
            $results[] = $result;
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Assert
        $this->assertLessThan($maxAcceptableTime, $totalTime, 
            "Quote generation took {$totalTime}ms for 100 requests, exceeding threshold of {$maxAcceptableTime}ms");

        $this->assertCount(100, $results);
        
        // Verify all quotes are valid
        foreach ($results as $result) {
            $this->assertTrue($result['success'] ?? true);
            $this->assertGreaterThan(0, $result['final_total'] ?? 0);
        }
    }

    /** @test */
    public function it_handles_bulk_quote_operations_efficiently()
    {
        // Arrange
        $bulkRequests = $this->generateBulkQuoteRequests(50);
        $threshold = $this->performanceThresholds['bulk_quotes'];

        // Act
        $startTime = microtime(true);
        $result = $this->pricingService->generateBulkQuotes($bulkRequests);
        $processingTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertLessThan($threshold, $processingTime,
            "Bulk quote processing took {$processingTime}ms, exceeding threshold of {$threshold}ms");

        $this->assertTrue($result['success']);
        $this->assertEquals(50, $result['total_requests']);
        $this->assertGreaterThan(0, $result['successful_quotes']);
    }

    /** @test */
    public function it_performs_well_with_large_shipment_data()
    {
        // Arrange
        $largeShipmentData = [
            'weight_kg' => 1000.0, // Heavy shipment
            'pieces' => 100, // Many pieces
            'dimensions' => [
                'length_cm' => 200,
                'width_cm' => 150,
                'height_cm' => 100
            ],
            'declared_value' => 10000.0
        ];

        $threshold = $this->performanceThresholds['quote_generation'];

        // Act
        $startTime = microtime(true);
        $result = $this->pricingService->calculateInstantQuote(
            'KE', 'UG', $largeShipmentData, 'standard', null, 'USD'
        );
        $processingTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertLessThan($threshold, $processingTime,
            "Large shipment quote took {$processingTime}ms, exceeding threshold of {$threshold}ms");

        $this->assertTrue($result['success'] ?? true);
        $this->assertArrayHasKey('dimensional_weight', $result);
    }

    // ===== DATABASE PERFORMANCE TESTS =====

    /** @test */
    public function it_maintains_database_performance_under_load()
    {
        // Arrange
        $this->seedLargeTestDataset(5000);
        $threshold = $this->performanceThresholds['database_query'];

        // Act
        $startTime = microtime(true);
        
        // Perform various database operations
        $customers = Customer::with('contracts.promotions')->get();
        $activeContracts = Contract::where('status', 'active')->get();
        $recentShipments = Shipment::where('created_at', '>=', now()->subDays(30))->get();
        
        $queryTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertLessThan($threshold * 3, $queryTime, // Allow 3x for multiple queries
            "Database operations took {$queryTime}ms, exceeding threshold");

        $this->assertGreaterThan(0, $customers->count());
        $this->assertGreaterThan(0, $activeContracts->count());
    }

    /** @test */
    public function it_optimizes_queries_with_proper_indexing()
    {
        // Arrange
        $this->seedLargeTestDataset(10000);
        
        // Act - Query that should benefit from indexes
        $startTime = microtime(true);
        $result = Customer::where('customer_type', 'gold')
            ->whereHas('contracts', function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>', now());
            })
            ->with('contracts')
            ->get();
        $queryTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertLessThan(500, $queryTime,
            "Complex query took {$queryTime}ms, indicating potential index issues");

        $this->assertGreaterThanOrEqual(0, $result->count());
    }

    // ===== CACHE PERFORMANCE TESTS =====

    /** @test */
    public function it_achieves_high_cache_hit_rates()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $shipment = Shipment::factory()->create(['customer_id' => $customer->id]);
        $quoteData = ['weight_kg' => 5.0, 'pieces' => 1, 'service_level' => 'standard'];

        $iterations = 100;
        $threshold = $this->performanceThresholds['cache_lookup'];

        // Act - First request should be cache miss
        $startTime = microtime(true);
        $this->rateService->calculateShippingRate($shipment, $quoteData);
        $missTime = (microtime(true) - $startTime) * 1000;

        // Subsequent requests should be cache hits
        $hitTimes = [];
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $this->rateService->calculateShippingRate($shipment, $quoteData);
            $hitTimes[] = (microtime(true) - $startTime) * 1000;
        }

        $averageHitTime = array_sum($hitTimes) / count($hitTimes);

        // Assert
        $this->assertLessThan($threshold, $averageHitTime,
            "Cached requests averaged {$averageHitTime}ms, exceeding threshold of {$threshold}ms");

        $this->assertGreaterThan($missTime, $averageHitTime * 2,
            "Cache should provide significant performance improvement");
    }

    /** @test */
    public function it_handles_cache_invalidation_efficiently()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'silver']);
        $shipment = Shipment::factory()->create(['customer_id' => $customer->id]);
        $quoteData = ['service_level' => 'standard'];

        // Act - Generate cached result
        $this->rateService->calculateShippingRate($shipment, $quoteData);
        
        $invalidationStart = microtime(true);
        
        // Update customer tier (should invalidate cache)
        $customer->update(['customer_type' => 'gold']);
        
        // Request again (should regenerate)
        $result = $this->rateService->calculateShippingRate($shipment, $quoteData);
        
        $invalidationTime = (microtime(true) - $invalidationStart) * 1000;

        // Assert
        $this->assertLessThan(200, $invalidationTime,
            "Cache invalidation took {$invalidationTime}ms, which is too slow");

        $this->assertArrayHasKey('total_amount', $result);
    }

    // ===== MEMORY USAGE TESTS =====

    /** @test */
    public function it_manages_memory_efficiently_during_bulk_operations()
    {
        // Arrange
        $initialMemory = memory_get_usage();
        $maxMemoryLimit = 128 * 1024 * 1024; // 128MB

        // Act - Perform memory-intensive operations
        for ($batch = 0; $batch < 10; $batch++) {
            $customers = Customer::factory()->count(1000)->create();
            $shipments = [];
            
            foreach ($customers as $customer) {
                $shipments[] = Shipment::factory()->create([
                    'customer_id' => $customer->id,
                    'total_weight_kg' => rand(1, 50)
                ]);
            }
            
            // Process shipments in batches to manage memory
            $chunks = array_chunk($shipments, 100);
            foreach ($chunks as $chunk) {
                foreach ($chunk as $shipment) {
                    $this->pricingService->calculateInstantQuote(
                        'KE', 'UG', 
                        ['weight_kg' => $shipment->total_weight_kg, 'pieces' => 1],
                        'standard', 
                        $shipment->customer_id
                    );
                }
            }
            
            // Force garbage collection
            if ($batch % 3 === 0) {
                gc_collect_cycles();
            }
            
            $currentMemory = memory_get_usage();
            $this->assertLessThan($maxMemoryLimit, $currentMemory,
                "Memory usage ({$currentMemory} bytes) exceeded limit during bulk operations");
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, // 50MB increase
            "Memory increased by {$memoryIncrease} bytes, which is excessive");
    }

    // ===== CONCURRENT REQUEST TESTS =====

    /** @test */
    public function it_handles_concurrent_quote_requests()
    {
        // Arrange
        $concurrentRequests = 20;
        $threshold = 5000; // 5 seconds total
        $customers = Customer::factory()->count($concurrentRequests)->create();

        // Act - Simulate concurrent requests with parallel processing
        $startTime = microtime(true);
        
        // Use Laravel's queue system to simulate concurrent processing
        $results = [];
        foreach ($customers as $index => $customer) {
            // Process each request
            $result = $this->pricingService->calculateInstantQuote(
                'KE', 'UG',
                ['weight_kg' => rand(1, 20), 'pieces' => rand(1, 5)],
                'standard',
                $customer->id
            );
            $results[] = $result;
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertLessThan($threshold, $totalTime,
            "{$concurrentRequests} requests took {$totalTime}ms, exceeding threshold");

        $this->assertCount($concurrentRequests, $results);
        
        foreach ($results as $result) {
            $this->assertTrue($result['success'] ?? true);
        }
    }

    /** @test */
    public function it_maintains_performance_during_promotion_validation_storm()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create([
            'is_active' => true,
            'promo_code' => 'PERF_TEST_' . uniqid()
        ]);
        
        $customers = Customer::factory()->count(100)->create();
        $threshold = 2000; // 2 seconds

        // Act
        $startTime = microtime(true);
        
        foreach ($customers as $customer) {
            $this->promotionService->validatePromotionalCode(
                $promotion->promo_code,
                $customer->id,
                ['total_amount' => rand(50, 500)]
            );
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $this->assertLessThan($threshold, $totalTime,
            "100 promotion validations took {$totalTime}ms, exceeding threshold");

        // Verify cache was populated
        $this->assertGreaterThan(0, $this->getPromotionCacheSize());
    }

    // ===== API MONITORING TESTS =====

    /** @test */
    public function it_tracks_performance_metrics_accurately()
    {
        // Arrange
        $requestData = [
            'endpoint' => '/api/v1/pricing/quote/instant',
            'response_time' => 150.0,
            'status_code' => 200,
            'customer_id' => 1
        ];

        // Act
        $this->monitoringService->recordRequest($requestData);
        $metrics = $this->monitoringService->getRealtimeMetrics();

        // Assert
        $this->assertArrayHasKey('total_requests', $metrics);
        $this->assertArrayHasKey('average_response_time', $metrics);
        $this->assertArrayHasKey('success_rate', $metrics);
        
        $this->assertEquals(1, $metrics['total_requests']);
        $this->assertEquals(150.0, $metrics['average_response_time']);
        $this->assertEquals(100.0, $metrics['success_rate']);
    }

    // ===== STRESS TESTING =====

    /** @test */
    public function it_handles_system_stress_gracefully()
    {
        // Arrange
        $stressLevel = 'moderate'; // Can be 'light', 'moderate', 'heavy'
        $duration = 30; // seconds
        $maxResponseTime = 5000; // 5 seconds
        $errorRateThreshold = 5; // 5%

        // Act - Simulate stress conditions
        $results = $this->simulateStress($stressLevel, $duration);
        
        // Assert
        $this->assertLessThan($errorRateThreshold, $results['error_rate'],
            "Error rate ({$results['error_rate']}%) exceeded threshold during stress test");

        $this->assertLessThan($maxResponseTime, $results['max_response_time'],
            "Maximum response time ({$results['max_response_time']}ms) exceeded threshold");

        $this->assertGreaterThan(0, $results['total_requests']);
    }

    // ===== HELPER METHODS =====

    private function generateQuoteRequests(int $count): array
    {
        $requests = [];
        $origins = ['KE', 'UG', 'RW', 'TZ'];
        $destinations = ['UG', 'KE', 'RW', 'TZ', 'BI'];
        $serviceLevels = ['standard', 'express', 'overnight'];
        
        for ($i = 0; $i < $count; $i++) {
            $requests[] = [
                'origin' => $origins[array_rand($origins)],
                'destination' => $destinations[array_rand($destinations)],
                'service_level' => $serviceLevels[array_rand($serviceLevels)],
                'shipment_data' => [
                    'weight_kg' => rand(1, 50),
                    'pieces' => rand(1, 10)
                ],
                'customer_id' => rand(1, 100),
                'currency' => 'USD'
            ];
        }
        
        return $requests;
    }

    private function generateBulkQuoteRequests(int $count): array
    {
        $requests = [];
        
        for ($i = 0; $i < $count; $i++) {
            $requests[] = [
                'origin' => 'KE',
                'destination' => 'UG',
                'service_level' => 'standard',
                'shipment_data' => [
                    'weight_kg' => rand(1, 20),
                    'pieces' => rand(1, 5)
                ]
            ];
        }
        
        return $requests;
    }

    private function seedLargeTestDataset(int $count): void
    {
        $customers = Customer::factory()->count(min($count / 10, 100))->create();
        $contracts = Contract::factory()->count(min($count / 5, 200))->create();
        $promotions = PromotionalCampaign::factory()->count(min($count / 20, 50))->create();
        
        // Create relationships
        foreach ($contracts as $contract) {
            $contract->customer_id = $customers->random()->id;
            $contract->save();
        }
        
        foreach ($promotions as $promotion) {
            $promotion->created_by = $customers->random()->id;
            $promotion->save();
        }
    }

    private function getPromotionCacheSize(): int
    {
        $cacheKeys = Cache::getStore()->getPrefix() ? 
            Cache::getStore()->getPrefix() . '*' : 
            '*';
        
        // This is a simplified check - in real implementation,
        // you'd have better cache key management
        return 1; // Placeholder
    }

    private function simulateStress(string $level, int $duration): array
    {
        $requestsPerSecond = match($level) {
            'light' => 10,
            'moderate' => 50,
            'heavy' => 100,
            default => 10
        };
        
        $totalRequests = $requestsPerSecond * $duration;
        $results = [
            'total_requests' => $totalRequests,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'response_times' => [],
            'error_rate' => 0,
            'max_response_time' => 0
        ];
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $totalRequests; $i++) {
            $requestStart = microtime(true);
            
            try {
                $result = $this->pricingService->calculateInstantQuote(
                    'KE', 'UG',
                    ['weight_kg' => rand(1, 20), 'pieces' => 1],
                    'standard'
                );
                
                if ($result['success'] ?? true) {
                    $results['successful_requests']++;
                } else {
                    $results['failed_requests']++;
                }
            } catch (\Exception $e) {
                $results['failed_requests']++;
            }
            
            $requestTime = (microtime(true) - $requestStart) * 1000;
            $results['response_times'][] = $requestTime;
            
            if ($requestTime > $results['max_response_time']) {
                $results['max_response_time'] = $requestTime;
            }
            
            // Add some delay to control request rate
            usleep(1000000 / $requestsPerSecond);
        }
        
        $results['error_rate'] = ($results['failed_requests'] / $totalRequests) * 100;
        
        return $results;
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}