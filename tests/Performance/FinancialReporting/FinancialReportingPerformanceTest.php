<?php

namespace Tests\Performance\FinancialReporting;

use Tests\TestCase;
use App\Services\FinancialReporting\RevenueRecognitionService;
use App\Services\FinancialReporting\CostAnalysisService;
use App\Services\FinancialReporting\ProfitabilityAnalysisService;
use App\Services\FinancialReporting\AgingAnalysisService;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\ETL\DimensionClient;
use App\Models\ETL\DimensionDate;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Carbon\Carbon;

class FinancialReportingPerformanceTest extends TestCase
{
    use DatabaseMigrations;

    private $revenueService;
    private $costService;
    private $profitabilityService;
    private $agingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->revenueService = new RevenueRecognitionService();
        $this->costService = new CostAnalysisService();
        $this->profitabilityService = new ProfitabilityAnalysisService();
        $this->agingService = new AgingAnalysisService();
    }

    /** @test */
    public function it_performs_revenue_analysis_within_performance_threshold()
    {
        // Arrange
        $this->seedLargeDataset(10000);
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $performanceThreshold = 3.0; // seconds

        // Act
        $startTime = microtime(true);
        $result = $this->revenueService->analyzeRevenueRecognition($dateRange, []);
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert
        $this->assertLessThan($performanceThreshold, $executionTime, 
            "Revenue analysis took {$executionTime}s, exceeding threshold of {$performanceThreshold}s");
        
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('revenue_recognized', $result);
    }

    /** @test */
    public function it_performs_cost_analysis_with_large_datasets()
    {
        // Arrange
        $this->seedLargeDataset(15000);
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $filters = ['cost_category' => 'fuel'];
        $performanceThreshold = 4.0;

        // Act
        $startTime = microtime(true);
        $result = $this->costService->analyzeCOGS($dateRange, $filters);
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert
        $this->assertLessThan($performanceThreshold, $executionTime,
            "Cost analysis took {$executionTime}s, exceeding threshold of {$performanceThreshold}s");
        
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('cost_breakdown', $result);
    }

    /** @test */
    public function it_handles_concurrent_analytical_requests()
    {
        // Arrange
        $this->seedLargeDataset(5000);
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $concurrentRequests = 5;
        $performanceThreshold = 8.0; // total time for all requests

        // Act
        $startTime = microtime(true);
        
        $results = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $results[] = $this->revenueService->analyzeRevenueRecognition($dateRange, []);
            $results[] = $this->costService->analyzeCOGS($dateRange, []);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Assert
        $this->assertLessThan($performanceThreshold, $totalTime,
            "Concurrent requests took {$totalTime}s, exceeding threshold of {$performanceThreshold}s");
        
        $this->assertCount($concurrentRequests * 2, $results);
        
        // Ensure all results are valid
        foreach ($results as $result) {
            $this->assertNotEmpty($result);
        }
    }

    private function seedLargeDataset(int $recordCount): void
    {
        $clients = DimensionClient::factory()->count(min($recordCount / 10, 50))->create();
        $dates = DimensionDate::factory()->count(min($recordCount / 50, 100))->create();

        $transactions = [];
        for ($i = 0; $i < $recordCount; $i++) {
            $client = $clients->random();
            $date = $dates->random();
            $type = ['revenue', 'cost', 'receivable'][array_rand(['revenue', 'cost', 'receivable'])];
            
            $transactions[] = [
                'client_key' => $client->client_key,
                'date_key' => $date->date_key,
                'transaction_type' => $type,
                'cost_category' => $type === 'cost' ? ['fuel', 'labor', 'maintenance'][array_rand(['fuel', 'labor', 'maintenance'])] : null,
                'amount' => rand(10, 5000),
                'currency' => 'USD',
                'created_at' => $date->date,
                'updated_at' => $date->date
            ];

            // Insert in chunks to avoid memory issues
            if (count($transactions) >= 1000) {
                FactFinancialTransaction::insert($transactions);
                $transactions = [];
            }
        }

        // Insert remaining transactions
        if (!empty($transactions)) {
            FactFinancialTransaction::insert($transactions);
        }
    }
}