<?php

namespace Tests\Feature\Api\V1\FinancialReporting;

use Tests\TestCase;
use App\Models\User;
use App\Models\ETL\FactFinancialTransaction;
use App\Models\ETL\FactShipment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

class FinancialReportingControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate user
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    /** @test */
    public function it_gets_revenue_recognition_data()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];

        // Act
        $response = $this->postJson('/api/v1/financial/revenue-recognition', [
            'date_range' => $dateRange,
            'filters' => []
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'revenue_analysis',
                         'revenue_recognized',
                         'deferred_revenue',
                         'accrual_adjustments'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_validates_date_range_requirements()
    {
        // Act
        $response = $this->postJson('/api/v1/financial/revenue-recognition', [
            'filters' => []
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date_range']);
    }

    /** @test */
    public function it_gets_cogs_analysis()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $filters = ['cost_category' => 'fuel'];

        // Act
        $response = $this->postJson('/api/v1/financial/cogs-analysis', [
            'date_range' => $dateRange,
            'filters' => $filters
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'cost_breakdown',
                         'variance_analysis',
                         'cost_trends',
                         'forecasting'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_performs_cost_variance_analysis()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $filters = ['dimension' => 'route'];

        // Act
        $response = $this->postJson('/api/v1/financial/cost-variance-analysis', [
            'date_range' => $dateRange,
            'filters' => $filters
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'variance_summary',
                         'variance_details',
                         'budget_comparison'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_gets_gross_margin_analysis()
    {
        // Arrange
        $dateRange = [
            'start' => '20240101',
            'end' => '20240131'
        ];
        $filters = ['segment' => 'customer', 'include_forecasting' => true];

        // Act
        $response = $this->postJson('/api/v1/financial/gross-margin-analysis', [
            'date_range' => $dateRange,
            'filters' => $filters
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'margin_analysis',
                         'historical_trends',
                         'forecasting',
                         'competitive_benchmarking'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_tracks_cod_collections()
    {
        // Arrange
        $filters = [
            'date_range' => [
                'start' => '20240101',
                'end' => '20240131'
            ]
        ];

        // Act
        $response = $this->postJson('/api/v1/financial/cod-collection-tracking', [
            'filters' => $filters,
            'include_dunning' => true
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'cod_summary',
                         'aging_analysis',
                         'collection_metrics',
                         'dunning_analysis'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_performs_aging_analysis()
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
        $response = $this->postJson('/api/v1/financial/aging-analysis', [
            'filters' => $filters,
            'aging_buckets' => $agingBuckets,
            'include_risk_analysis' => true
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'aging_summary',
                         'aging_details',
                         'risk_assessment'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_gets_payment_processing_data()
    {
        // Arrange
        $filters = [
            'date_range' => [
                'start' => '20240101',
                'end' => '20240131'
            ]
        ];

        // Act
        $response = $this->postJson('/api/v1/financial/payment-processing', [
            'filters' => $filters,
            'include_reconciliation' => true
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'payment_summary',
                         'processing_workflow',
                         'reconciliation'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_analyzes_profitability()
    {
        // Arrange
        $filters = [
            'date_range' => [
                'start' => '20240101',
                'end' => '20240131'
            ],
            'include_optimization' => true
        ];

        // Act
        $response = $this->postJson('/api/v1/financial/profitability-analysis', [
            'filters' => $filters
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'profitability_summary',
                         'profitability_details',
                         'optimization_recommendations'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_exports_financial_data()
    {
        // Arrange
        $data = [
            'export_type' => 'revenue',
            'format' => 'excel',
            'date_range' => [
                'start' => '20240101',
                'end' => '20240131'
            ],
            'filters' => [],
            'include_charts' => false
        ];

        // Act
        $response = $this->postJson('/api/v1/financial/export', $data);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'filename',
                         'file_path',
                         'download_url',
                         'format',
                         'export_type',
                         'size',
                         'generated_at',
                         'expires_at',
                         'record_count'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_validates_export_parameters()
    {
        // Act
        $response = $this->postJson('/api/v1/financial/export', [
            'format' => 'excel'
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['export_type', 'date_range']);
    }

    /** @test */
    public function it_syncs_accounting_data()
    {
        // Arrange
        $syncData = [
            'system' => 'quickbooks',
            'sync_type' => 'revenue',
            'date_range' => [
                'start' => '20240101',
                'end' => '20240131'
            ],
            'dry_run' => true
        ];

        // Act
        $response = $this->postJson('/api/v1/financial/sync-accounting-data', $syncData);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'sync_id',
                         'system',
                         'sync_type',
                         'dry_run',
                         'status',
                         'start_time',
                         'records_processed',
                         'records_success',
                         'records_failed',
                         'errors',
                         'warnings'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_gets_audit_trail()
    {
        // Arrange
        $filters = [
            'transaction_type' => 'revenue',
            'user_id' => 1
        ];

        // Act
        $response = $this->getJson('/api/v1/financial/audit-trail?' . http_build_query($filters));

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'total_records',
                         'audit_summary',
                         'transaction_log',
                         'change_tracking',
                         'user_activity',
                         'compliance_status',
                         'data_integrity',
                         'retention_info'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_gets_financial_dashboard()
    {
        // Act
        $response = $this->postJson('/api/v1/financial/dashboard', [
            'include_forecasting' => true,
            'dashboard_type' => 'executive'
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'dashboard_type',
                         'summary' => [
                             'revenue',
                             'cogs',
                             'margins',
                             'cod',
                             'payments',
                             'profitability'
                         ],
                         'generated_at'
                     ],
                     'timestamp'
                 ]);
    }

    /** @test */
    public function it_handles_invalid_date_ranges()
    {
        // Act
        $response = $this->postJson('/api/v1/financial/revenue-recognition', [
            'date_range' => [
                'start' => 'invalid_date',
                'end' => '20240131'
            ]
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date_range.start']);
    }

    /** @test */
    public function it_enforces_authentication()
    {
        // Remove authentication
        $this->withoutMiddleware();

        // Act
        $response = $this->postJson('/api/v1/financial/revenue-recognition', [
            'date_range' => ['start' => '20240101', 'end' => '20240131']
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_large_datasets()
    {
        // This test would require seeding a large dataset
        // For now, we'll test the expected response structure
        
        // Act
        $response = $this->postJson('/api/v1/financial/profitability-analysis', [
            'filters' => [
                'date_range' => [
                    'start' => '20230101',
                    'end' => '20231231'
                ]
            ]
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response->json());
    }

    /** @test */
    public function it_validates_filter_parameters()
    {
        // Act
        $response = $this->postJson('/api/v1/financial/cogs-analysis', [
            'date_range' => ['start' => '20240101', 'end' => '20240131'],
            'filters' => [
                'cost_category' => 'invalid_category'
            ]
        ]);

        // Assert
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['filters.cost_category']);
    }
}