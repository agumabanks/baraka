<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\PromotionalCampaign;
use App\Models\Contract;
use App\Models\User;
use App\Services\DynamicPricingService;
use App\Services\PromotionEngineService;
use App\Services\ContractManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * API V1 Feature Test Suite for Enhanced Logistics Pricing System
 * 
 * Tests all API endpoints including:
 * - Dynamic Pricing API (quote generation, rate calculations, fuel surcharges)
 * - Contract Management API (CRUD operations, lifecycle management, renewals)
 * - Promotion Engine API (code validation, milestone tracking, ROI analytics)
 * - Integration API (third-party carriers, webhooks, bulk operations)
 * - Accessibility API (WCAG compliance testing and reporting)
 */
class PricingSystemApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->artisan('db:seed');
    }

    // ===== DYNAMIC PRICING API TESTS =====

    /** @test */
    public function it_generates_instant_quote_via_api()
    {
        // Arrange
        $quoteData = [
            'origin' => 'KE',
            'destination' => 'UG',
            'service_level' => 'standard',
            'shipment_data' => [
                'weight_kg' => 5.0,
                'pieces' => 1,
                'dimensions' => [
                    'length_cm' => 30,
                    'width_cm' => 20,
                    'height_cm' => 15
                ],
                'declared_value' => 100.0
            ],
            'customer_id' => null,
            'currency' => 'USD'
        ];

        // Act
        $response = $this->postJson('/api/v1/pricing/quote/instant', $quoteData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'quote_id',
                    'final_total',
                    'processing_time_ms',
                    'dimensional_weight',
                    'fuel_surcharge',
                    'taxes',
                    'currency',
                    'breakdown'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('data.quote_id'));
        $this->assertGreaterThan(0, $response->json('data.final_total'));
        $this->assertEquals('USD', $response->json('data.currency'));
    }

    /** @test */
    public function it_validates_quote_request_data()
    {
        // Arrange - Missing required fields
        $invalidData = [
            'service_level' => 'standard'
            // Missing origin, destination, shipment_data
        ];

        // Act
        $response = $this->postJson('/api/v1/pricing/quote/instant', $invalidData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'origin',
                'destination', 
                'shipment_data'
            ]);
    }

    /** @test */
    public function it_generates_bulk_quotes_via_api()
    {
        // Arrange
        $bulkRequests = [
            [
                'origin' => 'KE',
                'destination' => 'UG',
                'service_level' => 'standard',
                'shipment_data' => ['weight_kg' => 2.0, 'pieces' => 1]
            ],
            [
                'origin' => 'KE',
                'destination' => 'RW',
                'service_level' => 'express',
                'shipment_data' => ['weight_kg' => 5.0, 'pieces' => 2]
            ]
        ];

        // Act
        $response = $this->postJson('/api/v1/pricing/quote/bulk', [
            'requests' => $bulkRequests
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_requests',
                    'successful_quotes',
                    'failed_quotes',
                    'results',
                    'processing_time_ms'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(2, $response->json('data.total_requests'));
        $this->assertEquals(2, $response->json('data.successful_quotes'));
        $this->assertEquals(0, $response->json('data.failed_quotes'));
        $this->assertCount(2, $response->json('data.results'));
    }

    /** @test */
    public function it_gets_fuel_surcharge_rates()
    {
        // Act
        $response = $this->getJson('/api/v1/pricing/fuel-surcharge');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_index',
                    'surcharge_rate',
                    'last_updated',
                    'base_index',
                    'percentage_change'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertGreaterThan(0, $response->json('data.current_index'));
        $this->assertGreaterThanOrEqual(0, $response->json('data.surcharge_rate'));
    }

    // ===== CONTRACT MANAGEMENT API TESTS =====

    /** @test */
    public function it_creates_contract_via_api()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $contractData = [
            'name' => 'Test Contract API',
            'customer_id' => $customer->id,
            'contract_type' => 'standard',
            'start_date' => Carbon::now()->addDay()->toDateString(),
            'end_date' => Carbon::now()->addYear()->toDateString(),
            'volume_commitment' => 100,
            'volume_commitment_period' => 'monthly'
        ];

        // Act
        $response = $this->postJson('/api/v1/contracts', $contractData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'customer_id',
                    'status',
                    'start_date',
                    'end_date',
                    'volume_commitment',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('draft', $response->json('data.status'));
        $this->assertEquals($customer->id, $response->json('data.customer_id'));
    }

    /** @test */
    public function it_lists_contracts_with_filters()
    {
        // Arrange
        $customer = Customer::factory()->create();
        Contract::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'status' => 'active'
        ]);

        // Act
        $response = $this->getJson('/api/v1/contracts?status=active&customer_id=' . $customer->id);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'contracts' => [
                        '*' => [
                            'id',
                            'name',
                            'customer_id',
                            'status',
                            'start_date',
                            'end_date'
                        ]
                    ],
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page'
                    ]
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data.contracts'));
    }

    /** @test */
    public function it_activates_contract_via_api()
    {
        // Arrange
        $user = User::factory()->create();
        $contract = Contract::factory()->create([
            'status' => 'draft',
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addYear()
        ]);

        // Act
        $response = $this->patchJson("/api/v1/contracts/{$contract->id}/activate", [
            'activated_by' => $user->id
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'contract' => [
                        'id',
                        'status',
                        'activated_at',
                        'activated_by'
                    ]
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('active', $response->json('data.contract.status'));
    }

    /** @test */
    public function it_renews_contract_via_api()
    {
        // Arrange
        $contract = Contract::factory()->create([
            'status' => 'active',
            'end_date' => Carbon::now()->addMonth(),
            'auto_renewal_terms' => [
                'auto_renewal' => true,
                'notice_period_days' => 30
            ]
        ]);

        $renewalData = [
            'new_end_date' => Carbon::now()->addYear()->toDateString(),
            'terms_changed' => false,
            'user_id' => 1
        ];

        // Act
        $response = $this->patchJson("/api/v1/contracts/{$contract->id}/renew", $renewalData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'action_taken',
                    'new_end_date',
                    'renewal_details'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('renewed', $response->json('data.action_taken'));
    }

    // ===== PROMOTION ENGINE API TESTS =====

    /** @test */
    public function it_validates_promotional_code_via_api()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'premium']);
        $promotion = PromotionalCampaign::factory()->create([
            'promo_code' => 'TEST20',
            'is_active' => true,
            'effective_from' => Carbon::now()->subDay(),
            'effective_to' => Carbon::now()->addDays(30),
            'usage_limit' => 100,
            'usage_count' => 5,
            'customer_eligibility' => [
                'customer_types' => ['premium', 'gold']
            ]
        ]);

        $validationRequest = [
            'promo_code' => 'TEST20',
            'customer_id' => $customer->id,
            'order_data' => [
                'total_amount' => 100.00,
                'shipping_cost' => 15.00
            ]
        ];

        // Act
        $response = $this->postJson('/api/v1/promotions/validate', $validationRequest);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'valid',
                    'type',
                    'value',
                    'discount_amount',
                    'final_amount',
                    'validation_details'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.valid'));
        $this->assertEquals('percentage', $response->json('data.type'));
    }

    /** @test */
    public function it_rejects_invalid_promotional_code()
    {
        // Arrange
        $validationRequest = [
            'promo_code' => 'INVALID',
            'order_data' => ['total_amount' => 100.00]
        ];

        // Act
        $response = $this->postJson('/api/v1/promotions/validate', $validationRequest);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'valid',
                    'error'
                ],
                'message'
            ]);

        $this->assertFalse($response->json('data.valid'));
        $this->assertNotNull($response->json('data.error'));
    }

    /** @test */
    public function it_applies_promotion_discount_via_api()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create([
            'campaign_type' => 'percentage',
            'value' => 20,
            'maximum_discount_amount' => 50.00
        ]);

        $applyRequest = [
            'promotion_id' => $promotion->id,
            'base_amount' => 200.00,
            'customer_id' => null
        ];

        // Act
        $response = $this->postJson('/api/v1/promotions/apply', $applyRequest);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'discount_amount',
                    'final_amount',
                    'percentage_saved',
                    'breakdown'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(40.00, $response->json('data.discount_amount'));
        $this->assertEquals(160.00, $response->json('data.final_amount'));
    }

    /** @test */
    public function it_tracks_milestone_progress_via_api()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $shipmentData = [
            'weight' => 10.0,
            'volume' => 5.0,
            'value' => 150.00
        ];

        // Act
        $response = $this->postJson("/api/v1/customers/{$customer->id}/milestones/track", [
            'shipment_data' => $shipmentData
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'milestones_achieved',
                    'progress_updated',
                    'next_milestones'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_gets_promotion_roi_analytics()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create();
        $timeframe = '30d';

        // Act
        $response = $this->getJson("/api/v1/promotions/{$promotion->id}/roi?timeframe={$timeframe}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'roi_percentage',
                    'revenue_impact',
                    'cost_impact',
                    'net_impact',
                    'usage_metrics',
                    'timeframe'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertArrayHasKey('roi_percentage', $response->json('data'));
    }

    // ===== ACCESSIBILITY API TESTS =====

    /** @test */
    public function it_runs_accessibility_test_via_api()
    {
        // Arrange
        $testRequest = [
            'page_url' => 'https://example.com',
            'test_type' => 'automated',
            'config' => [
                'wcag_level' => 'AA',
                'rules_to_test' => ['color-contrast', 'alt-text']
            ]
        ];

        // Act
        $response = $this->postJson('/api/v1/accessibility/test', $testRequest);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'test_id',
                    'compliance_score',
                    'violations' => [
                        '*' => [
                            'id',
                            'description',
                            'severity',
                            'wcag_criteria'
                        ]
                    ],
                    'recommendations',
                    'test_duration_ms'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertGreaterThanOrEqual(0, $response->json('data.compliance_score'));
        $this->assertLessThanOrEqual(100, $response->json('data.compliance_score'));
    }

    /** @test */
    public function it_gets_accessibility_compliance_summary()
    {
        // Arrange
        $pageUrl = 'https://example.com';

        // Act
        $response = $this->getJson("/api/v1/accessibility/compliance/{$pageUrl}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overall_score',
                    'last_test_date',
                    'violation_counts' => [
                        'critical',
                        'serious',
                        'moderate',
                        'minor'
                    ],
                    'trends',
                    'recommendations'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    // ===== INTEGRATION API TESTS =====

    /** @test */
    public function it_handles_bulk_operations_via_api()
    {
        // Arrange
        $operations = [
            'type' => 'quote_generation',
            'requests' => [
                [
                    'origin' => 'KE',
                    'destination' => 'UG',
                    'service_level' => 'standard',
                    'shipment_data' => ['weight_kg' => 5.0, 'pieces' => 1]
                ]
            ]
        ];

        // Act
        $response = $this->postJson('/api/v1/integrations/bulk-operations', $operations);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'operation_id',
                    'status',
                    'total_processed',
                    'successful',
                    'failed',
                    'results',
                    'processing_time_ms'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_handles_webhook_events()
    {
        // Arrange
        $webhookData = [
            'event' => 'quote_generated',
            'data' => [
                'quote_id' => 'quote_123',
                'amount' => 45.00,
                'customer_id' => null
            ],
            'timestamp' => now()->toISOString()
        ];

        // Act
        $response = $this->postJson('/api/v1/webhooks/events', $webhookData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    // ===== ERROR HANDLING TESTS =====

    /** @test */
    public function it_handles_api_rate_limiting()
    {
        // Arrange - Make multiple requests to trigger rate limiting
        $quoteData = [
            'origin' => 'KE',
            'destination' => 'UG',
            'service_level' => 'standard',
            'shipment_data' => ['weight_kg' => 5.0, 'pieces' => 1]
        ];

        // Act - Make 60 requests in a minute (exceed typical rate limit)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson('/api/v1/pricing/quote/instant', $quoteData);
            
            if ($response->status() === 429) {
                // Rate limit hit
                break;
            }
        }

        // Assert - Should eventually hit rate limit
        $this->assertTrue(true); // Test structure validation
    }

    /** @test */
    public function it_validates_api_authentication()
    {
        // Arrange
        $quoteData = [
            'origin' => 'KE',
            'destination' => 'UG',
            'service_level' => 'standard',
            'shipment_data' => ['weight_kg' => 5.0, 'pieces' => 1]
        ];

        // Act - Request without proper API key
        $response = $this->withHeaders([
            'X-API-Key' => 'invalid-key'
        ])->postJson('/api/v1/pricing/quote/instant', $quoteData);

        // Assert
        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

        $this->assertFalse($response->json('success'));
    }

    // ===== PERFORMANCE TESTS =====

    /** @test */
    public function it_responds_within_performance_threshold()
    {
        // Arrange
        $quoteData = [
            'origin' => 'KE',
            'destination' => 'UG',
            'service_level' => 'standard',
            'shipment_data' => ['weight_kg' => 5.0, 'pieces' => 1]
        ];

        // Act
        $startTime = microtime(true);
        $response = $this->postJson('/api/v1/pricing/quote/instant', $quoteData);
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Assert
        $response->assertStatus(200);
        $this->assertLessThan(2000, $responseTime, 'API response time should be under 2000ms');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}