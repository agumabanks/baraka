<?php

namespace Tests\Unit\Services\FinancialReporting;

use Tests\TestCase;
use App\Services\FinancialReporting\ProfitabilityAnalysisService;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\ETL\FactShipment;
use App\Models\ETL\DimensionClient;
use App\Models\ETL\DimensionDate;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProfitabilityAnalysisServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected $profitabilityAnalysisService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profitabilityAnalysisService = new ProfitabilityAnalysisService();
        $this->seedTestData();
    }

    /** @test */
    public function it_analyzes_profitability_correctly()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $filters = ['include_optimization' => true];

        // Act
        $result = $this->profitabilityAnalysisService->analyzeProfitability($dateRange, $filters);

        // Assert
        $this->assertArrayHasKey('profitability_summary', $result);
        $this->assertArrayHasKey('profitability_details', $result);
        $this->assertArrayHasKey('optimization_recommendations', $result);
        $this->assertArrayHasKey('trend_analysis', $result);
        
        $this->assertIsNumeric($result['profitability_summary']['gross_profit']);
        $this->assertIsNumeric($result['profitability_summary']['net_profit']);
        $this->assertIsArray($result['optimization_recommendations']);
    }

    /** @test */
    public function it_calculates_gross_margin_correctly()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $segment = 'customer';

        // Act
        $marginAnalysis = $this->profitabilityAnalysisService->calculateGrossMargin($dateRange, $segment);

        // Assert
        $this->assertArrayHasKey('margin_analysis', $marginAnalysis);
        $this->assertArrayHasKey('historical_trends', $marginAnalysis);
        $this->assertArrayHasKey('forecasting', $marginAnalysis);
        $this->assertArrayHasKey('competitive_benchmarking', $marginAnalysis);
        
        $this->assertIsNumeric($marginAnalysis['margin_analysis']['average_margin']);
        $this->assertGreaterThanOrEqual(0, $marginAnalysis['margin_analysis']['average_margin']);
        $this->assertLessThanOrEqual(100, $marginAnalysis['margin_analysis']['average_margin']);
    }

    /** @test */
    public function it_performs_customer_profitability_analysis()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act
        $customerProfitability = $this->profitabilityAnalysisService->analyzeCustomerProfitability($dateRange);

        // Assert
        $this->assertArrayHasKey('customer_profitability', $customerProfitability);
        $this->assertArrayHasKey('profitability_ranking', $customerProfitability);
        $this->assertArrayHasKey('loss_making_customers', $customerProfitability);
        $this->assertArrayHasKey('high_value_customers', $customerProfitability);
        
        $this->assertIsArray($customerProfitability['customer_profitability']);
    }

    /** @test */
    public function it_identifies_profitability_trends()
    {
        // Arrange
        $dateRange = [
            'start' => '20230101',
            'end' => '20240131'
        ];
        $period = 'monthly';

        // Act
        $trendAnalysis = $this->profitabilityAnalysisService->identifyProfitabilityTrends($dateRange, $period);

        // Assert
        $this->assertArrayHasKey('trend_direction', $trendAnalysis);
        $this->assertArrayHasKey('trend_strength', $trendAnalysis);
        $this->assertArrayHasKey('seasonal_patterns', $trendAnalysis);
        $this->assertArrayHasKey('forecast', $trendAnalysis);
        
        $this->assertContains($trendAnalysis['trend_direction'], ['increasing', 'decreasing', 'stable']);
        $this->assertIsNumeric($trendAnalysis['trend_strength']);
    }

    /** @test */
    public function it_calculates_roi_correctly()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $investmentTypes = ['marketing', 'technology', 'infrastructure'];

        // Act
        $roiAnalysis = $this->profitabilityAnalysisService->calculateROI($dateRange, $investmentTypes);

        // Assert
        $this->assertArrayHasKey('roi_by_investment_type', $roiAnalysis);
        $this->assertArrayHasKey('overall_roi', $roiAnalysis);
        $this->assertArrayHasKey('payback_period', $roiAnalysis);
        
        foreach ($investmentTypes as $type) {
            $this->assertArrayHasKey($type, $roiAnalysis['roi_by_investment_type']);
            $this->assertIsNumeric($roiAnalysis['roi_by_investment_type'][$type]);
        }
    }

    /** @test */
    public function it_performs_scenario_analysis()
    {
        // Arrange
        $baseDateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $scenarios = [
            ['name' => 'optimistic', 'revenue_increase' => 10, 'cost_reduction' => 5],
            ['name' => 'pessimistic', 'revenue_increase' => -5, 'cost_reduction' => 0]
        ];

        // Act
        $scenarioResults = $this->profitabilityAnalysisService->performScenarioAnalysis($baseDateRange, $scenarios);

        // Assert
        $this->assertArrayHasKey('base_case', $scenarioResults);
        $this->assertArrayHasKey('scenarios', $scenarioResults);
        
        foreach ($scenarios as $scenario) {
            $this->assertArrayHasKey($scenario['name'], $scenarioResults['scenarios']);
        }
    }

    /** @test */
    public function it_handles_profitability_cache_correctly()
    {
        // Arrange
        $dateRange = ['start' => '20240101', 'end' => '20240131'];
        $cacheKey = 'profitability_' . md5(json_encode($dateRange));

        // Act - First call should cache the result
        $result1 = $this->profitabilityAnalysisService->analyzeProfitability($dateRange, []);
        $result2 = $this->profitabilityAnalysisService->analyzeProfitability($dateRange, []);

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_validates_profitability_calculations()
    {
        // Arrange
        $dateRange = ['start' => '20240101', 'end' => '20240131'];

        // Act
        $validationResult = $this->profitabilityAnalysisService->validateCalculations($dateRange);

        // Assert
        $this->assertArrayHasKey('is_valid', $validationResult);
        $this->assertArrayHasKey('errors', $validationResult);
        $this->assertArrayHasKey('warnings', $validationResult);
        
        $this->assertIsBool($validationResult['is_valid']);
        $this->assertIsArray($validationResult['errors']);
        $this->assertIsArray($validationResult['warnings']);
    }

    private function seedTestData(): void
    {
        // Create test clients
        $clients = DimensionClient::factory()->count(5)->create();

        // Create test dates
        $dates = DimensionDate::factory()->count(30)->create();

        // Create revenue transactions
        foreach ($clients as $client) {
            foreach ($dates as $date) {
                FactFinancialTransaction::factory()->create([
                    'client_key' => $client->client_key,
                    'date_key' => $date->date_key,
                    'transaction_type' => 'revenue',
                    'amount' => rand(1000, 5000),
                    'currency' => 'USD'
                ]);

                // Add corresponding cost transactions
                FactFinancialTransaction::factory()->count(2)->create([
                    'client_key' => $client->client_key,
                    'date_key' => $date->date_key,
                    'transaction_type' => 'cost',
                    'cost_category' => ['fuel', 'labor'][array_rand(['fuel', 'labor'])],
                    'amount' => rand(200, 1000),
                    'currency' => 'USD'
                ]);
            }
        }
    }
}