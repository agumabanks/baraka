<?php

namespace Tests\Integration\FinancialReporting;

use Tests\TestCase;
use App\Services\FinancialReporting\RevenueRecognitionService;
use App\Services\FinancialReporting\CostAnalysisService;
use App\Services\FinancialReporting\ProfitabilityAnalysisService;
use App\Services\FinancialReporting\AgingAnalysisService;
use App\Services\FinancialReporting\PaymentProcessingService;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\ETL\FactShipment;
use App\Models\ETL\DimensionClient;
use App\Models\ETL\DimensionDate;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportingIntegrationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedComprehensiveTestData();
    }

    /** @test */
    public function it_integrates_revenue_and_cost_analysis_correctly()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act
        $revenueService = new RevenueRecognitionService();
        $costService = new CostAnalysisService();

        $revenueAnalysis = $revenueService->analyzeRevenueRecognition($dateRange, []);
        $costAnalysis = $costService->analyzeCOGS($dateRange, []);

        // Assert
        $this->assertNotEmpty($revenueAnalysis);
        $this->assertNotEmpty($costAnalysis);
        
        $this->assertArrayHasKey('revenue_recognized', $revenueAnalysis);
        $this->assertArrayHasKey('cost_breakdown', $costAnalysis);
        
        // Integration assertion - both should have data for the same time period
        $this->assertGreaterThan(0, $revenueAnalysis['revenue_recognized']['total'] ?? 0);
        $this->assertGreaterThan(0, $costAnalysis['cost_breakdown']['total_cost'] ?? 0);
    }

    /** @test */
    public function it_provides_consistent_profitability_data_across_services()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act
        $revenueService = new RevenueRecognitionService();
        $costService = new CostAnalysisService();
        $profitabilityService = new ProfitabilityAnalysisService();

        $revenue = $revenueService->analyzeRevenueRecognition($dateRange, []);
        $costs = $costService->analyzeCOGS($dateRange, []);
        $profitability = $profitabilityService->analyzeProfitability($dateRange, []);

        // Assert integration consistency
        $calculatedProfit = ($revenue['revenue_recognized']['total'] ?? 0) - ($costs['cost_breakdown']['total_cost'] ?? 0);
        $reportedProfit = $profitability['profitability_summary']['net_profit'] ?? 0;

        // Allow for minor differences due to rounding, but they should be close
        $difference = abs($calculatedProfit - $reportedProfit);
        $this->assertLessThan(0.01, $difference, 'Profitability data is inconsistent between services');
    }

    /** @test */
    public function it_synchronizes_aging_data_with_profitability_analysis()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $agingBuckets = [
            ['min_days' => 0, 'max_days' => 30],
            ['min_days' => 31, 'max_days' => 60],
            ['min_days' => 61, 'max_days' => 90],
            ['min_days' => 91, 'max_days' => null]
        ];

        // Act
        $agingService = new AgingAnalysisService();
        $profitabilityService = new ProfitabilityAnalysisService();

        $agingAnalysis = $agingService->performAgingAnalysis(['date_range' => $dateRange], $agingBuckets);
        $profitability = $profitabilityService->analyzeProfitability($dateRange, []);

        // Assert integration
        $this->assertNotEmpty($agingAnalysis);
        $this->assertNotEmpty($profitability);
        
        // Both should account for the same receivables
        $totalReceivables = collect($agingAnalysis['aging_summary'] ?? [])->sum('amount');
        $this->assertGreaterThan(0, $totalReceivables);
        
        // Profitability should reflect the collection status
        $this->assertArrayHasKey('risk_factors', $profitability);
    }

    /** @test */
    public function it_validates_data_integrity_across_financial_services()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act & Assert
        $services = [
            new RevenueRecognitionService(),
            new CostAnalysisService(),
            new ProfitabilityAnalysisService(),
            new AgingAnalysisService(),
            new PaymentProcessingService()
        ];

        foreach ($services as $service) {
            $result = $service->validateDataIntegrity($dateRange);
            
            $this->assertArrayHasKey('is_valid', $result);
            $this->assertArrayHasKey('issues', $result);
            
            // Log any data integrity issues for debugging
            if (!$result['is_valid']) {
                $this->fail("Data integrity issue in " . get_class($service) . ": " . implode(', ', $result['issues']));
            }
        }
    }

    /** @test */
    public function it_handles_large_dataset_performance_across_services()
    {
        // Arrange - seed with large dataset
        $this->seedLargeDataset(5000);
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act - measure performance across all services
        $startTime = microtime(true);
        
        $revenueService = new RevenueRecognitionService();
        $costService = new CostAnalysisService();
        $profitabilityService = new ProfitabilityAnalysisService();

        $revenue = $revenueService->analyzeRevenueRecognition($dateRange, []);
        $costs = $costService->analyzeCOGS($dateRange, []);
        $profitability = $profitabilityService->analyzeProfitability($dateRange, []);
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Assert performance
        $this->assertLessThan(10, $totalTime, 'Financial reporting services took too long to process large dataset');
        
        // Ensure all results are valid
        $this->assertNotEmpty($revenue);
        $this->assertNotEmpty($costs);
        $this->assertNotEmpty($profitability);
    }

    /** @test */
    public function it_maintains_transaction_consistency_during_concurrent_access()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Simulate concurrent access with multiple sequential calls
        $results = [];
        
        for ($i = 0; $i < 10; $i++) {
            $revenueService = new RevenueRecognitionService();
            $costService = new CostAnalysisService();
            
            $revenue = $revenueService->analyzeRevenueRecognition($dateRange, []);
            $costs = $costService->analyzeCOGS($dateRange, []);
            
            $results[] = [
                'revenue' => $revenue['revenue_recognized']['total'] ?? 0,
                'costs' => $costs['cost_breakdown']['total_cost'] ?? 0
            ];
        }

        // Assert consistency across concurrent requests
        $expectedRevenue = $results[0]['revenue'];
        $expectedCosts = $results[0]['costs'];
        
        foreach ($results as $result) {
            $this->assertEquals($expectedRevenue, $result['revenue'], 'Revenue results inconsistent under concurrent access');
            $this->assertEquals($expectedCosts, $result['costs'], 'Cost results inconsistent under concurrent access');
        }
    }

    /** @test */
    public function it_provides_comprehensive_financial_dashboard_data()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act
        $services = [
            'revenue' => new RevenueRecognitionService(),
            'costs' => new CostAnalysisService(),
            'profitability' => new ProfitabilityAnalysisService(),
            'aging' => new AgingAnalysisService(),
            'payments' => new PaymentProcessingService()
        ];

        $dashboardData = [];
        foreach ($services as $name => $service) {
            switch ($name) {
                case 'revenue':
                    $dashboardData['revenue'] = $service->analyzeRevenueRecognition($dateRange, []);
                    break;
                case 'costs':
                    $dashboardData['costs'] = $service->analyzeCOGS($dateRange, []);
                    break;
                case 'profitability':
                    $dashboardData['profitability'] = $service->analyzeProfitability($dateRange, []);
                    break;
                case 'aging':
                    $dashboardData['aging'] = $service->performAgingAnalysis(['date_range' => $dateRange], []);
                    break;
                case 'payments':
                    $dashboardData['payments'] = $service->analyzePaymentProcessing(['date_range' => $dateRange]);
                    break;
            }
        }

        // Assert comprehensive dashboard
        $this->assertArrayHasKey('revenue', $dashboardData);
        $this->assertArrayHasKey('costs', $dashboardData);
        $this->assertArrayHasKey('profitability', $dashboardData);
        $this->assertArrayHasKey('aging', $dashboardData);
        $this->assertArrayHasKey('payments', $dashboardData);

        // Validate data structure for dashboard
        foreach ($dashboardData as $key => $data) {
            $this->assertNotEmpty($data, "Dashboard data for {$key} is empty");
        }
    }

    private function seedComprehensiveTestData(): void
    {
        // Create diverse test data
        $clients = DimensionClient::factory()->count(10)->create();
        $dates = DimensionDate::factory()->count(60)->create();

        foreach ($clients as $client) {
            foreach ($dates as $date) {
                // Revenue transactions
                FactFinancialTransaction::factory()->create([
                    'client_key' => $client->client_key,
                    'date_key' => $date->date_key,
                    'transaction_type' => 'revenue',
                    'amount' => rand(500, 3000),
                    'currency' => 'USD',
                    'created_at' => $date->date
                ]);

                // Cost transactions
                FactFinancialTransaction::factory()->count(2)->create([
                    'client_key' => $client->client_key,
                    'date_key' => $date->date_key,
                    'transaction_type' => 'cost',
                    'cost_category' => ['fuel', 'labor', 'maintenance'][array_rand(['fuel', 'labor', 'maintenance'])],
                    'amount' => rand(100, 800),
                    'currency' => 'USD',
                    'created_at' => $date->date
                ]);

                // Receivables
                if (rand(1, 3) === 1) { // 33% chance
                    FactFinancialTransaction::factory()->create([
                        'client_key' => $client->client_key,
                        'date_key' => $date->date_key,
                        'transaction_type' => 'receivable',
                        'amount' => rand(200, 1500),
                        'currency' => 'USD',
                        'created_at' => $date->date
                    ]);
                }
            }
        }
    }

    private function seedLargeDataset(int $recordCount): void
    {
        $clients = DimensionClient::factory()->count(20)->create();
        $dates = DimensionDate::factory()->count(90)->create();

        $transactions = [];
        for ($i = 0; $i < $recordCount; $i++) {
            $transactions[] = [
                'client_key' => $clients->random()->client_key,
                'date_key' => $dates->random()->date_key,
                'transaction_type' => ['revenue', 'cost', 'receivable'][array_rand(['revenue', 'cost', 'receivable'])],
                'cost_category' => in_array($type = ['revenue', 'cost', 'receivable'][array_rand(['revenue', 'cost', 'receivable'])], ['cost']) ? ['fuel', 'labor', 'maintenance'][array_rand(['fuel', 'labor', 'maintenance'])] : null,
                'amount' => rand(50, 2000),
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('fact_financial_transactions')->insert($transactions);
    }
}