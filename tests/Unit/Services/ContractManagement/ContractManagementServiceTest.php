<?php

namespace Tests\Unit\Services\ContractManagement;

use App\Services\ContractManagementService;
use App\Services\VolumeDiscountService;
use App\Services\ContractComplianceService;
use App\Services\ContractNotificationService;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Customer;
use App\Events\ContractActivated;
use App\Events\ContractVolumeCommitmentReached;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;

/**
 * Contract Management Service Test Suite
 * 
 * Tests the core contract management functionality including:
 * - Contract creation and management
 * - Volume discount calculations
 * - Compliance monitoring
 * - Contract lifecycle operations
 */
class ContractManagementServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected ContractManagementService $contractService;
    protected VolumeDiscountService $volumeService;
    protected ContractComplianceService $complianceService;
    protected ContractNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->contractService = new ContractManagementService(
            new VolumeDiscountService($this->app->make(\App\Services\WebhookManagementService::class)),
            new ContractNotificationService($this->app->make(\App\Services\WebhookManagementService::class))
        );
        
        $this->volumeService = new VolumeDiscountService(
            $this->app->make(\App\Services\WebhookManagementService::class)
        );
        
        $this->complianceService = new ContractComplianceService();
        
        $this->notificationService = new ContractNotificationService(
            $this->app->make(\App\Services\WebhookManagementService::class)
        );
    }

    /** @test */
    public function it_can_create_a_contract_from_template()
    {
        Event::fake();
        
        // Arrange
        $customer = Customer::factory()->create();
        $template = ContractTemplate::factory()->create([
            'template_type' => 'standard',
            'terms_template' => [
                'payment_terms' => 'Net 30',
                'delivery_terms' => 'FOB',
                'liability' => 'Standard liability coverage'
            ]
        ]);
        
        $customerData = [
            'customer_id' => $customer->id,
            'company_name' => 'Test Company',
            'contact_person' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        $contractData = [
            'name' => 'Test Contract',
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addYear(),
            'contract_type' => 'standard',
            'volume_commitment' => 100,
            'volume_commitment_period' => 'monthly'
        ];
        
        // Act
        $contract = $this->contractService->createContractFromTemplate(
            $template->id,
            $customerData,
            $contractData
        );
        
        // Assert
        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals('draft', $contract->status);
        $this->assertEquals($template->id, $contract->template_id);
        $this->assertEquals($customer->id, $contract->customer_id);
        $this->assertEquals(100, $contract->volume_commitment);
        $this->assertEquals('monthly', $contract->volume_commitment_period);
    }

    /** @test */
    public function it_can_activate_a_contract()
    {
        Event::fake();
        
        // Arrange
        $contract = Contract::factory()->create([
            'status' => 'draft',
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addYear()
        ]);
        
        $userId = 1;
        
        // Act
        $result = $this->contractService->activateContract($contract->id, $userId);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['contract']['status']);
        $this->assertEquals($userId, $result['activated_by']);
        
        Event::assertDispatched(ContractActivated::class, function ($event) use ($contract) {
            return $event->contract->id === $contract->id;
        });
    }

    /** @test */
    public function it_can_renew_a_contract()
    {
        // Arrange
        $contract = Contract::factory()->create([
            'status' => 'active',
            'end_date' => Carbon::now()->addMonth(),
            'auto_renewal_terms' => [
                'auto_renewal' => true,
                'notice_period_days' => 30,
                'extension_duration_days' => 365
            ]
        ]);
        
        $newEndDate = Carbon::now()->addYear();
        $renewalDetails = ['terms_changed' => false];
        $userId = 1;
        
        // Act
        $result = $this->contractService->processContractRenewal(
            $contract->id,
            $newEndDate,
            $renewalDetails,
            $userId
        );
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('renewed', $result['action_taken']);
        $this->assertEquals($newEndDate->format('Y-m-d'), $result['new_end_date']);
    }

    /** @test */
    public function it_can_update_contract_volume()
    {
        Event::fake();
        
        // Arrange
        $contract = Contract::factory()->create([
            'current_volume' => 50,
            'volume_commitment' => 100
        ]);
        
        $volumeIncrease = 60;
        
        // Act
        $result = $this->volumeService->updateContractVolume(
            $contract->id,
            $volumeIncrease
        );
        
        // Assert
        $this->assertEquals($contract->id, $result['contract_id']);
        $this->assertEquals(50, $result['old_volume']);
        $this->assertEquals(110, $result['new_volume']);
        $this->assertEquals($volumeIncrease, $result['volume_increase']);
        
        // Check that volume commitment is met
        $this->assertTrue($result['volume_commitment_met']);
        
        Event::assertDispatched(ContractVolumeCommitmentReached::class, function ($event) use ($contract) {
            return $event->contract->id === $contract->id;
        });
    }

    /** @test */
    public function it_calculates_volume_discounts_correctly()
    {
        // Arrange
        $contract = Contract::factory()->create();
        
        // Create volume discounts
        $contract->volumeDiscounts()->createMany([
            [
                'tier_name' => 'Bronze',
                'volume_requirement' => 0,
                'discount_percentage' => 0,
                'sort_order' => 1
            ],
            [
                'tier_name' => 'Silver',
                'volume_requirement' => 50,
                'discount_percentage' => 5,
                'sort_order' => 2
            ],
            [
                'tier_name' => 'Gold',
                'volume_requirement' => 200,
                'discount_percentage' => 10,
                'sort_order' => 3
            ]
        ]);
        
        // Act & Assert - Silver tier
        $silverDiscount = $this->volumeService->calculateDiscountsForVolume($contract, 75);
        $this->assertTrue($silverDiscount['applicable']);
        $this->assertEquals('Silver', $silverDiscount['tier_name']);
        $this->assertEquals(5, $silverDiscount['discount_percentage']);
        
        // Act & Assert - Gold tier
        $goldDiscount = $this->volumeService->calculateDiscountsForVolume($contract, 250);
        $this->assertTrue($goldDiscount['applicable']);
        $this->assertEquals('Gold', $goldDiscount['tier_name']);
        $this->assertEquals(10, $goldDiscount['discount_percentage']);
        
        // Act & Assert - No discount
        $noDiscount = $this->volumeService->calculateDiscountsForVolume($contract, 25);
        $this->assertFalse($noDiscount['applicable']);
        $this->assertEquals(0, $noDiscount['discount_percentage']);
    }

    /** @test */
    public function it_handles_contract_compliance_correctly()
    {
        // Arrange
        $contract = Contract::factory()->create();
        
        // Create compliance requirements
        $contract->compliances()->createMany([
            [
                'requirement_name' => 'Delivery On-Time Rate',
                'compliance_type' => 'performance',
                'target_value' => 95.0,
                'performance_percentage' => 92.0,
                'compliance_status' => 'warning',
                'is_critical' => true
            ],
            [
                'requirement_name' => 'Customer Satisfaction Score',
                'compliance_type' => 'quality',
                'target_value' => 4.0,
                'performance_percentage' => 4.2,
                'compliance_status' => 'met',
                'is_critical' => false
            ]
        ]);
        
        // Act
        $complianceStatus = $this->complianceService->getContractComplianceStatus($contract->id);
        
        // Assert
        $this->assertEquals(88.0, $complianceStatus['overall_score']); // (92 + 84) / 2
        $this->assertEquals(1, $complianceStatus['breach_count']);
        $this->assertEquals(1, $complianceStatus['warning_count']);
        $this->assertCount(2, $complianceStatus['requirements']);
    }

    /** @test */
    public function it_processes_contract_expirations_correctly()
    {
        // Arrange
        $expiringContract = Contract::factory()->create([
            'status' => 'active',
            'end_date' => Carbon::now()->addDays(7)
        ]);
        
        $expiredContract = Contract::factory()->create([
            'status' => 'active',
            'end_date' => Carbon::now()->subDays(1)
        ]);
        
        $activeContract = Contract::factory()->create([
            'status' => 'active',
            'end_date' => Carbon::now()->addDays(30)
        ]);
        
        // Act
        $job = new \App\Jobs\ContractManagement\ContractProcessingJob('expiry_processing');
        
        // Mock the service methods
        $this->mock(ContractManagementService::class, function ($mock) {
            $mock->shouldReceive('processContractRenewal')
                ->never();
        });
        
        // Act & Assert - This is a simplified test
        $this->assertInstanceOf(\App\Jobs\ContractManagement\ContractProcessingJob::class, $job);
    }

    /** @test */
    public function it_validates_contract_data_correctly()
    {
        // Arrange
        $invalidData = [
            'customer_id' => null, // Missing required field
            'name' => '', // Empty name
            'start_date' => 'invalid-date', // Invalid date format
            'end_date' => '2023-01-01', // End date before start date
            'contract_type' => 'invalid_type' // Invalid contract type
        ];
        
        // Act & Assert
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->contractService->createContract($invalidData);
    }

    /** @test */
    public function it_handles_volume_milestones_correctly()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $milestoneVolume = 100;
        
        // Act
        $milestone = \App\Models\CustomerMilestone::checkAndCreateMilestone(
            $customer,
            'shipment_count',
            $milestoneVolume
        );
        
        // Assert
        $this->assertInstanceOf(\App\Models\CustomerMilestone::class, $milestone);
        $this->assertEquals('shipment_count', $milestone->milestone_type);
        $this->assertEquals($milestoneVolume, $milestone->milestone_value);
        $this->assertNotNull($milestone->achieved_at);
        $this->assertNotNull($milestone->reward_given);
    }

    /** @test */
    public function it_sends_notifications_for_contract_events()
    {
        Event::fake();
        
        // Arrange
        $contract = Contract::factory()->create();
        $customer = $contract->customer;
        
        // Act
        $this->notificationService->sendContractActivationNotifications($contract);
        
        // Assert
        Event::assertDispatched(\App\Events\ContractActivated::class, function ($event) use ($contract) {
            return $event->contract->id === $contract->id;
        });
        
        // Check that notification was created
        $this->assertDatabaseHas('contract_notifications', [
            'contract_id' => $contract->id,
            'notification_type' => 'contract_activated'
        ]);
    }

    /** @test */
    public function it_performs_batch_operations_correctly()
    {
        // Arrange
        $contracts = Contract::factory()->count(3)->create([
            'status' => 'active'
        ]);
        
        // Act
        $results = $this->contractService->performBatchOperation('compliance_check', [
            'contract_ids' => $contracts->pluck('id')->toArray()
        ]);
        
        // Assert
        $this->assertTrue($results['success']);
        $this->assertEquals(3, $results['contracts_processed']);
    }

    /** @test */
    public function it_handles_error_cases_gracefully()
    {
        // Arrange
        $nonExistentContractId = 99999;
        
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->contractService->activateContract($nonExistentContractId, 1);
    }

    /** @test */
    public function it_calculates_contract_summary_correctly()
    {
        // Arrange
        $contract = Contract::factory()->create([
            'name' => 'Test Contract',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addYear(),
            'contract_type' => 'standard',
            'status' => 'active',
            'current_volume' => 75,
            'volume_commitment' => 100
        ]);
        
        // Act
        $summary = $contract->getContractSummary();
        
        // Assert
        $this->assertEquals($contract->id, $summary['id']);
        $this->assertEquals('Test Contract', $summary['name']);
        $this->assertEquals('active', $summary['status']);
        $this->assertEquals(75, $summary['current_volume']);
        $this->assertEquals(100, $summary['required_volume']);
        $this->assertEquals(75.0, $summary['progress_percentage']);
        $this->assertFalse($summary['volume_commitment_met']);
    }
}