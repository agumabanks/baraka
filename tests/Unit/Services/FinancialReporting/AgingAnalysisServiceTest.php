<?php

namespace Tests\Unit\Services\FinancialReporting;

use Tests\TestCase;
use App\Services\FinancialReporting\AgingAnalysisService;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\ETL\FactShipment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgingAnalysisServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected $agingAnalysisService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agingAnalysisService = new AgingAnalysisService();
        $this->seedTestData();
    }

    /** @test */
    public function it_performs_aging_analysis_correctly()
    {
        // Arrange
        $filters = [
            'date_range' => [
                'start' => '20240101',
                'end' => '20240131'
            ]
        ];
        $agingBuckets = [
            ['min_days' => 0, 'max_days' => 30],
            ['min_days' => 31, 'max_days' => 60],
            ['min_days' => 61, 'max_days' => 90],
            ['min_days' => 91, 'max_days' => null]
        ];

        // Act
        $agingResult = $this->agingAnalysisService->performAgingAnalysis($filters, $agingBuckets);

        // Assert
        $this->assertArrayHasKey('aging_summary', $agingResult);
        $this->assertArrayHasKey('aging_details', $agingResult);
        $this->assertArrayHasKey('risk_assessment', $agingResult);
        $this->assertArrayHasKey('trend_analysis', $agingResult);
        
        $this->assertIsArray($agingResult['aging_summary']);
        $this->assertIsArray($agingResult['aging_details']);
    }

    /** @test */
    public function it_identifies_overdue_accounts()
    {
        // Arrange
        $filters = [
            'date_range' => ['start' => '20240101', 'end' => '20240131'],
            'overdue_only' => true
        ];

        // Act
        $overdueAccounts = $this->agingAnalysisService->identifyOverdueAccounts($filters);

        // Assert
        $this->assertArrayHasKey('overdue_summary', $overdueAccounts);
        $this->assertArrayHasKey('overdue_details', $overdueAccounts);
        $this->assertArrayHasKey('risk_analysis', $overdueAccounts);
        $this->assertArrayHasKey('collection_recommendations', $overdueAccounts);
        
        $this->assertIsArray($overdueAccounts['overdue_summary']);
    }

    /** @test */
    public function it_calculates_bad_debt_provision()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $provisionRates = [
            'current' => 0.01,
            '30_days' => 0.05,
            '60_days' => 0.20,
            '90_plus_days' => 0.50
        ];

        // Act
        $badDebtProvision = $this->agingAnalysisService->calculateBadDebtProvision($dateRange, $provisionRates);

        // Assert
        $this->assertArrayHasKey('total_provision', $badDebtProvision);
        $this->assertArrayHasKey('provision_by_aging_bucket', $badDebtProvision);
        $this->assertArrayHasKey('impact_on_financial_statements', $badDebtProvision);
        
        $this->assertIsNumeric($badDebtProvision['total_provision']);
        $this->assertGreaterThanOrEqual(0, $badDebtProvision['total_provision']);
    }

    /** @test */
    public function it_performs_collection_effectiveness_analysis()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act
        $effectiveness = $this->agingAnalysisService->analyzeCollectionEffectiveness($dateRange);

        // Assert
        $this->assertArrayHasKey('collection_rate', $effectiveness);
        $this->assertArrayHasKey('dso_calculation', $effectiveness);
        $this->assertArrayHasKey('collection_trends', $effectiveness);
        $this->assertArrayHasKey('improvement_opportunities', $effectiveness);
        
        $this->assertIsNumeric($effectiveness['collection_rate']);
        $this->assertGreaterThanOrEqual(0, $effectiveness['collection_rate']);
        $this->assertLessThanOrEqual(100, $effectiveness['collection_rate']);
    }

    /** @test */
    public function it_tracks_payment_patterns()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $customerSegments = ['enterprise', 'smb', 'individual'];

        // Act
        $paymentPatterns = $this->agingAnalysisService->trackPaymentPatterns($dateRange, $customerSegments);

        // Assert
        $this->assertArrayHasKey('payment_behavior', $paymentPatterns);
        $this->assertArrayHasKey('seasonal_trends', $paymentPatterns);
        $this->assertArrayHasKey('predictive_analysis', $paymentPatterns);
        
        foreach ($customerSegments as $segment) {
            $this->assertArrayHasKey($segment, $paymentPatterns['payment_behavior']);
        }
    }

    /** @test */
    public function it_validates_aging_data_accuracy()
    {
        // Act
        $validation = $this->agingAnalysisService->validateAgingData();

        // Assert
        $this->assertArrayHasKey('is_accurate', $validation);
        $this->assertArrayHasKey('data_issues', $validation);
        $this->assertArrayHasKey('reconciliation_status', $validation);
        
        $this->assertIsBool($validation['is_accurate']);
        $this->assertIsArray($validation['data_issues']);
    }

    /** @test */
    public function it_handles_aging_bucket_configuration()
    {
        // Arrange
        $customBuckets = [
            ['min_days' => 0, 'max_days' => 15],
            ['min_days' => 16, 'max_days' => 45],
            ['min_days' => 46, 'max_days' => 75],
            ['min_days' => 76, 'max_days' => 105],
            ['min_days' => 106, 'max_days' => null]
        ];

        // Act
        $bucketAnalysis = $this->agingAnalysisService->analyzeWithCustomBuckets($customBuckets);

        // Assert
        $this->assertArrayHasKey('custom_analysis', $bucketAnalysis);
        $this->assertArrayHasKey('comparison_with_standard', $bucketAnalysis);
        
        $this->assertEquals(5, count($bucketAnalysis['custom_analysis']));
    }

    private function seedTestData(): void
    {
        // Create test transactions with various aging periods
        $baseDate = Carbon::now();
        
        // Current (0-30 days)
        FactFinancialTransaction::factory()->create([
            'transaction_type' => 'receivable',
            'amount' => 1000.00,
            'currency' => 'USD',
            'created_at' => $baseDate->copy()->subDays(10)
        ]);

        // 31-60 days
        FactFinancialTransaction::factory()->create([
            'transaction_type' => 'receivable',
            'amount' => 1500.00,
            'currency' => 'USD',
            'created_at' => $baseDate->copy()->subDays(45)
        ]);

        // 61-90 days
        FactFinancialTransaction::factory()->create([
            'transaction_type' => 'receivable',
            'amount' => 800.00,
            'currency' => 'USD',
            'created_at' => $baseDate->copy()->subDays(75)
        ]);

        // 90+ days (overdue)
        FactFinancialTransaction::factory()->create([
            'transaction_type' => 'receivable',
            'amount' => 2000.00,
            'currency' => 'USD',
            'created_at' => $baseDate->copy()->subDays(120)
        ]);

        // Add some paid transactions
        FactFinancialTransaction::factory()->count(5)->create([
            'transaction_type' => 'payment',
            'amount' => -500.00,
            'currency' => 'USD',
            'created_at' => $baseDate->copy()->subDays(20)
        ]);
    }
}