<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PromotionEngineService;
use App\Services\PromotionAnalyticsService;
use App\Services\MilestoneTrackingService;
use App\Services\NotificationService;
use App\Models\PromotionalCampaign;
use App\Models\Customer;
use App\Models\CustomerMilestone;
use App\Events\PromotionActivated;
use App\Events\PromotionExpired;
use App\Events\RoiThresholdBreached;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

/**
 * Promotion Engine Service Test Suite
 * 
 * Tests all core functionality of the PromotionEngineService including:
 * - Promotional code validation and application
 * - Milestone tracking and achievement
 * - Anti-stacking rules enforcement
 * - ROI calculations
 * - Expiry enforcement
 * - Notification integration
 */
class PromotionEngineServiceTest extends TestCase
{
    use DatabaseMigrations;

    private PromotionEngineService $service;
    private PromotionAnalyticsService $analyticsService;
    private MilestoneTrackingService $milestoneService;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsService = \Mockery::mock(PromotionAnalyticsService::class);
        $this->milestoneService = \Mockery::mock(MilestoneTrackingService::class);
        $this->notificationService = \Mockery::mock(NotificationService::class);
        
        $this->service = new PromotionEngineService(
            $this->analyticsService,
            $this->milestoneService,
            $this->notificationService
        );
    }

    /** @test */
    public function it_validates_valid_promotional_code()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'premium']);
        $promotion = PromotionalCampaign::factory()->create([
            'promo_code' => 'TEST20',
            'campaign_type' => 'percentage',
            'value' => 20,
            'is_active' => true,
            'effective_from' => now()->subDays(1),
            'effective_to' => now()->addDays(30),
            'usage_limit' => 100,
            'usage_count' => 5,
            'customer_eligibility' => [
                'customer_types' => ['premium', 'standard']
            ]
        ]);

        $orderData = [
            'total_amount' => 100.00,
            'shipping_cost' => 15.00
        ];

        // Act
        $result = $this->service->validatePromotionalCode(
            'TEST20', 
            $customer->id, 
            $orderData
        );

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEquals('percentage', $result['type']);
        $this->assertEquals(20, $result['value']);
    }

    /** @test */
    public function it_rejects_expired_promotional_code()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create([
            'promo_code' => 'EXPIRED',
            'effective_from' => now()->subDays(30),
            'effective_to' => now()->subDays(1),
            'is_active' => true
        ]);

        // Act
        $result = $this->service->validatePromotionalCode(
            'EXPIRED', 
            null, 
            ['total_amount' => 100.00]
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Promotion has expired', $result['error']);
    }

    /** @test */
    public function it_rejects_inactive_promotional_code()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create([
            'promo_code' => 'INACTIVE',
            'is_active' => false
        ]);

        // Act
        $result = $this->service->validatePromotionalCode(
            'INACTIVE', 
            null, 
            ['total_amount' => 100.00]
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Promotion is not active', $result['error']);
    }

    /** @test */
    public function it_enforces_usage_limits()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create([
            'promo_code' => 'LIMITED',
            'usage_limit' => 10,
            'usage_count' => 10
        ]);

        // Act
        $result = $this->service->validatePromotionalCode(
            'LIMITED', 
            null, 
            ['total_amount' => 100.00]
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Promotion usage limit has been reached', $result['error']);
    }

    /** @test */
    public function it_validates_customer_eligibility()
    {
        // Arrange
        $customer = Customer::factory()->create(['customer_type' => 'basic']);
        $promotion = PromotionalCampaign::factory()->create([
            'promo_code' => 'PREMIUMONLY',
            'customer_eligibility' => [
                'customer_types' => ['premium', 'gold']
            ]
        ]);

        // Act
        $result = $this->service->validatePromotionalCode(
            'PREMIUMONLY', 
            $customer->id, 
            ['total_amount' => 100.00]
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Customer not eligible for this promotion', $result['error']);
    }

    /** @test */
    public function it_applies_percentage_discount_correctly()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create([
            'campaign_type' => 'percentage',
            'value' => 15,
            'maximum_discount_amount' => 50.00
        ]);

        $orderData = [
            'total_amount' => 200.00
        ];

        // Act
        $result = $this->service->applyPromotionalDiscount(
            'percentage',
            200.00,
            null,
            ['percentage' => 15, 'max_discount' => 50.00]
        );

        // Assert
        $this->assertEquals('percentage', $result['type']);
        $this->assertEquals(30.00, $result['discount_amount']); // 15% of 200
        $this->assertEquals(170.00, $result['final_amount']);
        $this->assertEquals(15.0, $result['percentage_saved']);
    }

    /** @test */
    public function it_enforces_maximum_discount_cap()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create([
            'campaign_type' => 'percentage',
            'value' => 30,
            'maximum_discount_amount' => 50.00
        ]);

        $orderData = [
            'total_amount' => 300.00
        ];

        // Act
        $result = $this->service->applyPromotionalDiscount(
            'percentage',
            300.00,
            null,
            ['percentage' => 30, 'max_discount' => 50.00]
        );

        // Assert
        $this->assertEquals(50.00, $result['discount_amount']); // Capped at max
        $this->assertEquals(250.00, $result['final_amount']);
    }

    /** @test */
    public function it_applies_fixed_amount_discount()
    {
        // Arrange
        $orderAmount = 100.00;
        
        // Act
        $result = $this->service->applyPromotionalDiscount(
            'fixed_amount',
            $orderAmount,
            null,
            ['fixed_amount' => 25.00]
        );

        // Assert
        $this->assertEquals('fixed_amount', $result['type']);
        $this->assertEquals(25.00, $result['discount_amount']);
        $this->assertEquals(75.00, $result['final_amount']);
    }

    /** @test */
    public function it_handles_free_shipping_discount()
    {
        // Arrange
        $orderAmount = 150.00;
        
        // Act
        $result = $this->service->applyPromotionalDiscount(
            'free_shipping',
            $orderAmount,
            null,
            ['shipping_cost' => 15.00]
        );

        // Assert
        $this->assertEquals('free_shipping', $result['type']);
        $this->assertEquals(15.00, $result['discount_amount']);
        $this->assertEquals(135.00, $result['final_amount']);
    }

    /** @test */
    public function it_enforces_anti_stacking_rules()
    {
        // Arrange
        $customerId = 1;
        $newPromotion = PromotionalCampaign::factory()->create([
            'stacking_allowed' => false
        ]);

        $existingPromotions = collect([
            (object) ['stacking_allowed' => true], // Allow stacking
            (object) ['stacking_allowed' => false] // Doesn't allow stacking
        ]);

        $orderData = ['total_amount' => 100.00];

        // Act
        $result = $this->service->checkAntiStackingRules(
            $customerId,
            $newPromotion,
            $orderData
        );

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Promotion stacking not allowed due to existing discounts', $result['error']);
    }

    /** @test */
    public function it_allows_stacking_when_permitted()
    {
        // Arrange
        $customerId = 1;
        $newPromotion = PromotionalCampaign::factory()->create([
            'stacking_allowed' => true
        ]);

        $existingPromotions = collect([
            (object) ['stacking_allowed' => true] // All allow stacking
        ]);

        $orderData = ['total_amount' => 100.00];

        // Act
        $result = $this->service->checkAntiStackingRules(
            $customerId,
            $newPromotion,
            $orderData
        );

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEquals('Promotion stacking is allowed', $result['message']);
    }

    /** @test */
    public function it_tracks_milestone_progress()
    {
        // Arrange
        $customer = Customer::factory()->create();
        
        $shipmentData = [
            'weight' => 10.0,
            'volume' => 5.0,
            'value' => 150.00
        ];

        $this->milestoneService->shouldReceive('trackMilestoneProgress')
            ->once()
            ->with($customer->id, $shipmentData)
            ->andReturn([
                'milestones_achieved' => [],
                'progress_updated' => true
            ]);

        // Act
        $result = $this->service->trackMilestoneProgress($customer->id, $shipmentData);

        // Assert
        $this->assertTrue($result['progress_updated']);
    }

    /** @test */
    public function it_calculates_promotion_roi()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create();
        $timeframe = '30d';

        $this->analyticsService->shouldReceive('calculatePromotionROI')
            ->once()
            ->with($promotion->id, $timeframe, true)
            ->andReturn([
                'roi_percentage' => 145.50,
                'revenue_impact' => 2500.00,
                'cost_impact' => 1750.00,
                'net_impact' => 750.00
            ]);

        // Act
        $result = $this->service->calculatePromotionROI($promotion->id, $timeframe);

        // Assert
        $this->assertEquals(145.50, $result['roi_percentage']);
        $this->assertEquals(2500.00, $result['revenue_impact']);
    }

    /** @test */
    public function it_generates_promotion_codes()
    {
        // Arrange
        $template = [
            'type' => 'random',
            'length' => 8,
            'prefix' => 'SAVE'
        ];

        $constraints = [
            'max_attempts' => 5,
            'unique_only' => true
        ];

        // Act
        $code = $this->service->generatePromotionCode($template, $constraints);

        // Assert
        $this->assertStringStartsWith('SAVE', $code);
        $this->assertEquals(8, strlen($code));
        
        // Verify uniqueness
        $existingCodes = PromotionalCampaign::pluck('promo_code')->toArray();
        $this->assertNotContains($code, $existingCodes);
    }

    /** @test */
    public function it_notifies_milestone_achievement()
    {
        // Arrange
        Event::fake();
        $customer = Customer::factory()->create();
        $milestone = CustomerMilestone::factory()->create([
            'customer_id' => $customer->id,
            'milestone_type' => 'shipment_count',
            'milestone_value' => 100
        ]);

        $this->notificationService->shouldReceive('sendCustomerPromotionAlert')
            ->once()
            ->with(
                $customer->id,
                \Mockery::type('object'), // Promotion object
                'milestone_celebration',
                \Mockery::type('array')
            );

        // Act
        $this->service->notifyMilestoneAchievement($customer->id, $milestone);

        // Assert
        Event::assertDispatched(MilestoneAchieved::class);
    }

    /** @test */
    public function it_enforces_promotion_expiry()
    {
        // Arrange
        Event::fake();
        
        $expiredPromotion = PromotionalCampaign::factory()->create([
            'promo_code' => 'EXPIRED',
            'effective_to' => now()->subDays(1),
            'is_active' => true
        ]);

        $activePromotion = PromotionalCampaign::factory()->create([
            'promo_code' => 'ACTIVE',
            'effective_to' => now()->addDays(30),
            'is_active' => true
        ]);

        // Act
        $result = $this->service->enforcePromotionExpiry();

        // Assert
        $this->assertTrue($result['expired_promotions_updated'] > 0);
        
        $expiredPromotion->refresh();
        $this->assertFalse($expiredPromotion->is_active);
        
        Event::assertDispatched(PromotionExpired::class);
    }

    /** @test */
    public function it_optimizes_promotion_strategy()
    {
        // Arrange
        $customerSegment = 'premium';
        
        $this->analyticsService->shouldReceive('getSegmentPerformance')
            ->once()
            ->with('customer_type')
            ->andReturn([
                'premium' => [
                    'avg_roi' => 180.0,
                    'conversion_rate' => 0.15,
                    'customer_lifetime_value' => 2500.0
                ]
            ]);

        $this->analyticsService->shouldReceive('runABTest')
            ->once()
            ->andReturn([
                'test_id' => 'test_123',
                'status' => 'active',
                'estimated_completion' => now()->addDays(14)->toISOString()
            ]);

        // Act
        $result = $this->service->optimizePromotionStrategy($customerSegment);

        // Assert
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('ab_test', $result);
        $this->assertEquals('premium', $result['segment_analyzed']);
    }

    /** @test */
    public function it_handles_discount_calculation_priorities()
    {
        // Arrange
        $baseAmount = 100.00;
        $discounts = [
            ['type' => 'contract', 'amount' => 10.00, 'priority' => 1],
            ['type' => 'promotion', 'amount' => 15.00, 'priority' => 2],
            ['type' => 'loyalty', 'amount' => 5.00, 'priority' => 3]
        ];

        // Act
        $result = $this->service->calculateFinalDiscount($baseAmount, $discounts);

        // Assert
        $this->assertEquals(30.00, $result['total_discount']);
        $this->assertEquals(70.00, $result['final_amount']);
        $this->assertEquals(30.0, $result['discount_percentage']);
    }

    /** @test */
    public function it_handles_cascade_discount_scenarios()
    {
        // Arrange
        $baseAmount = 200.00;
        
        // Scenario: Contract discount (highest priority) then percentage promotion
        $discounts = [
            ['type' => 'contract', 'amount' => 20.00, 'priority' => 1], // Fixed amount
            ['type' => 'promotion', 'percentage' => 10.0, 'priority' => 2] // Percentage on remaining
        ];

        // Act
        $result = $this->service->calculateCascadeDiscounts($baseAmount, $discounts);

        // Assert
        // After $20 contract discount: $180
        // After 10% promotion on $180: $18
        $this->assertEquals(38.00, $result['total_discount']);
        $this->assertEquals(162.00, $result['final_amount']);
        $this->assertEquals(19.0, $result['overall_discount_percentage']);
    }

    /** @test */
    public function it_validates_discount_application_context()
    {
        // Arrange
        $context = [
            'order_type' => 'standard',
            'customer_tier' => 'premium',
            'order_value' => 500.00,
            'shipping_method' => 'express'
        ];

        $promotion = PromotionalCampaign::factory()->create([
            'customer_eligibility' => [
                'customer_types' => ['premium'],
                'minimum_order_value' => 100.00,
                'applicable_services' => ['express', 'standard']
            ]
        ]);

        // Act
        $result = $this->service->validateDiscountContext($promotion, $context);

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['violations']);
    }

    /** @test */
    public function it_handles_bulk_promotion_operations()
    {
        // Arrange
        $promotionIds = [1, 2, 3];
        $operation = 'activate';

        // Act
        $result = $this->service->performBulkOperation($promotionIds, $operation);

        // Assert
        $this->assertEquals(3, $result['total_processed']);
        $this->assertTrue($result['results'][0]['success']);
    }

    /** @test */
    public function it_generates_promotion_effectiveness_report()
    {
        // Arrange
        $promotion = PromotionalCampaign::factory()->create();
        $timeframe = '30d';

        $this->analyticsService->shouldReceive('getDashboardData')
            ->once()
            ->with(30)
            ->andReturn([
                'total_promotions' => 5,
                'active_promotions' => 3,
                'total_revenue_impact' => 15000.00,
                'average_roi' => 125.5,
                'top_performing_campaigns' => []
            ]);

        // Act
        $result = $this->service->generateEffectivenessReport($timeframe);

        // Assert
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('trends', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }
}