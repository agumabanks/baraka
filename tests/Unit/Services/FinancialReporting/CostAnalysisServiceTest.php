<?php

namespace Tests\Unit\Services\FinancialReporting;

use Tests\TestCase;
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

class CostAnalysisServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected $costAnalysisService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->costAnalysisService = new CostAnalysisService();
        $this->seedTestData();
    }

    /** @test */
    public function it_analyzes_cogs_correctly()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $filters = ['cost_category' => 'fuel'];

        // Act
        $result = $this->costAnalysisService->analyzeCOGS($dateRange, $filters);

        // Assert
        $this->assertArrayHasKey('cost_breakdown', $result);
        $this->assertArrayHasKey('variance_analysis', $result);
        $this->assertArrayHasKey('cost_trends', $result);
        $this->assertArrayHasKey('forecasting', $result);
        
        $this->assertIsArray($result['cost_breakdown']);
        $this->assertIsArray($result['variance_analysis']);
        $this->assertGreaterThan(0, count($result['cost_breakdown']));
    }

    /** @test */
    public function it_performs_cost_variance_analysis()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $dimension = 'route';

        // Act
        $result = $this->costAnalysisService->performVarianceAnalysis($dateRange, $dimension);

        // Assert
        $this->assertArrayHasKey('variance_summary', $result);
        $this->assertArrayHasKey('variance_details', $result);
        $this->assertArrayHasKey('budget_comparison', $result);
        $this->assertArrayHasKey('root_cause_analysis', $result);
        
        $this->assertIsNumeric($result['variance_summary']['total_variance']);
        $this->assertIsArray($result['variance_details']);
    }

    /** @test */
    public function it_identifies_cost_optimization_opportunities()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act
        $optimizationOpportunities = $this->costAnalysisService->identifyOptimizationOpportunities($dateRange);

        // Assert
        $this->assertIsArray($optimizationOpportunities);
        $this->assertArrayHasKey('high_impact_opportunities', $optimizationOpportunities);
        $this->assertArrayHasKey('medium_impact_opportunities', $optimizationOpportunities);
        $this->assertArrayHasKey('low_impact_opportunities', $optimizationOpportunities);
        
        foreach ($optimizationOpportunities as $category => $opportunities) {
            $this->assertIsArray($opportunities);
        }
    }

    /** @test */
    public function it_handles_large_cost_datasets_efficiently()
    {
        // Arrange - seed with large dataset
        $this->seedLargeDataset(10000);
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act
        $startTime = microtime(true);
        $result = $this->costAnalysisService->analyzeCOGS($dateRange, []);
        $endTime = microtime(true);

        // Assert
        $this->assertArrayHasKey('cost_breakdown', $result);
        
        // Performance assertion - should complete within 5 seconds
        $this->assertLessThan(5, $endTime - $startTime, 'Large dataset processing took too long');
    }

    /** @test */
    public function it_validates_cost_data_integrity()
    {
        // Act
        $integrityCheck = $this->costAnalysisService->validateDataIntegrity();

        // Assert
        $this->assertArrayHasKey('is_valid', $integrityCheck);
        $this->assertArrayHasKey('issues', $integrityCheck);
        $this->assertArrayHasKey('severity', $integrityCheck);
        
        $this->assertIsBool($integrityCheck['is_valid']);
        $this->assertIsArray($integrityCheck['issues']);
    }

    private function seedTestData(): void
    {
        // Create test dimensions
        $client = DimensionClient::factory()->create([
            'client_key' => 'TEST_CLIENT_001',
            'client_name' => 'Test Client',
            'client_type' => 'enterprise'
        ]);

        $date = DimensionDate::factory()->create([
            'date_key' => '20240115',
            'date' => '2024-01-15',
            'year' => 2024,
            'month' => 1,
            'day' => 15
        ]);

        // Create test financial transactions
        FactFinancialTransaction::factory()->count(50)->create([
            'client_key' => $client->client_key,
            'date_key' => $date->date_key,
            'transaction_type' => 'cost',
            'cost_category' => 'fuel',
            'amount' => 150.00,
            'currency' => 'USD'
        ]);

        FactFinancialTransaction::factory()->count(30)->create([
            'client_key' => $client->client_key,
            'date_key' => $date->date_key,
            'transaction_type' => 'cost',
            'cost_category' => 'labor',
            'amount' => 250.00,
            'currency' => 'USD'
        ]);
    }

    private function seedLargeDataset(int $recordCount): void
    {
        $clients = DimensionClient::factory()->count(10)->create();
        $dates = DimensionDate::factory()->count(30)->create();

        $transactions = [];
        for ($i = 0; $i < $recordCount; $i++) {
            $transactions[] = [
                'client_key' => $clients->random()->client_key,
                'date_key' => $dates->random()->date_key,
                'transaction_type' => 'cost',
                'cost_category' => ['fuel', 'labor', 'maintenance'][array_rand(['fuel', 'labor', 'maintenance'])],
                'amount' => rand(100, 1000),
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('fact_financial_transactions')->insert($transactions);
    }
}