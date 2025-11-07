<?php

namespace Tests\Unit\CustomerIntelligence;

use Tests\TestCase;
use App\Services\CustomerIntelligence\CustomerChurnPredictionService;
use App\Services\CustomerIntelligence\CustomerSentimentAnalysisService;
use App\Services\CustomerIntelligence\CustomerSegmentationService;
use App\Services\CustomerIntelligence\CustomerValueAnalysisService;
use App\Services\CustomerIntelligence\ClientActivityMonitoringService;
use App\Services\CustomerIntelligence\DormantAccountDetectionService;
use App\Services\CustomerIntelligence\CustomerSatisfactionService;
use App\Services\CustomerIntelligence\AutomatedAlertSystemService;
use App\Services\CustomerIntelligence\DataPrivacyService;
use App\Models\ETL\FactCustomerChurnMetrics;
use App\Models\ETL\DimensionClient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Comprehensive test suite for Customer Intelligence Platform
 * Tests all major services and functionalities
 */
class CustomerIntelligenceTestSuite extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    /** @test */
    public function it_can_predict_customer_churn_risk()
    {
        $service = new CustomerChurnPredictionService();
        $clientKey = 1;

        $result = $service->predictCustomerChurnRisk($clientKey, 90);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('churn_probability', $result);
        $this->assertArrayHasKey('churn_risk_level', $result);
        $this->assertArrayHasKey('primary_risk_factors', $result);
        $this->assertArrayHasKey('recommended_interventions', $result);
        
        $this->assertGreaterThanOrEqual(0, $result['churn_probability']);
        $this->assertLessThanOrEqual(1, $result['churn_probability']);
        $this->assertContains($result['churn_risk_level'], ['low', 'medium', 'high', 'critical']);
    }

    /** @test */
    public function it_can_analyze_customer_sentiment()
    {
        $service = new CustomerSentimentAnalysisService();
        $clientKey = 1;
        $days = 30;

        $result = $service->analyzeCustomerSentiment($clientKey, $days);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment_score', $result);
        $this->assertArrayHasKey('nps_score', $result);
        $this->assertArrayHasKey('nps_category', $result);
        $this->assertArrayHasKey('sentiment_trends', $result);
        $this->assertArrayHasKey('confidence_level', $result);
        
        $this->assertGreaterThanOrEqual(-1, $result['sentiment_score']);
        $this->assertLessThanOrEqual(1, $result['sentiment_score']);
        $this->assertContains($result['nps_category'], ['promoter', 'passive', 'detractor']);
    }

    /** @test */
    public function it_can_segment_customers()
    {
        $service = new CustomerSegmentationService();
        
        $result = $service->performCustomerSegmentation();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('segments', $result);
        $this->assertArrayHasKey('segment_distribution', $result);
        $this->assertArrayHasKey('total_customers_analyzed', $result);
        
        $segments = $result['segments'];
        $this->assertNotEmpty($segments);
        
        foreach ($segments as $segment) {
            $this->assertArrayHasKey('segment_name', $segment);
            $this->assertArrayHasKey('customer_count', $segment);
            $this->assertArrayHasKey('segment_characteristics', $segment);
            $this->assertGreaterThan(0, $segment['customer_count']);
        }
    }

    /** @test */
    public function it_can_analyze_customer_value()
    {
        $service = new CustomerValueAnalysisService();
        $clientKey = 1;

        $result = $service->analyzeCustomerValue($clientKey, 90);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value_metrics', $result);
        $this->assertArrayHasKey('trending_analysis', $result);
        $this->assertArrayHasKey('value_insights', $result);
        $this->assertArrayHasKey('value_recommendations', $result);
        
        $valueMetrics = $result['value_metrics'];
        $this->assertArrayHasKey('total_customer_value', $valueMetrics);
        $this->assertArrayHasKey('average_shipment_value', $valueMetrics);
        $this->assertArrayHasKey('value_trend', $valueMetrics);
        
        $this->assertIsNumeric($valueMetrics['total_customer_value']);
        $this->assertIsNumeric($valueMetrics['average_shipment_value']);
    }

    /** @test */
    public function it_can_monitor_client_activity()
    {
        $service = new ClientActivityMonitoringService();
        $clientKey = 1;
        $analysisPeriod = 90;

        $result = $service->monitorCustomerActivity($clientKey, $analysisPeriod);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('shipment_frequency_analysis', $result);
        $this->assertArrayHasKey('engagement_scoring', $result);
        $this->assertArrayHasKey('activity_pattern_recognition', $result);
        $this->assertArrayHasKey('behavioral_trend_analysis', $result);
        $this->assertArrayHasKey('activity_health_score', $result);
        
        $this->assertGreaterThanOrEqual(0, $result['activity_health_score']);
        $this->assertLessThanOrEqual(1, $result['activity_health_score']);
    }

    /** @test */
    public function it_can_detect_dormant_accounts()
    {
        $service = new DormantAccountDetectionService();
        $criteria = ['dormancy_threshold_days' => 90];

        $result = $service->detectDormantAccounts($criteria);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('detection_criteria', $result);
        $this->assertArrayHasKey('dormant_customers', $result);
        $this->assertArrayHasKey('reactivation_campaigns', $result);
        $this->assertArrayHasKey('dormant_metrics', $result);
        
        $dormantCustomers = $result['dormant_customers'];
        if (!empty($dormantCustomers)) {
            foreach ($dormantCustomers as $customer) {
                $this->assertArrayHasKey('client_key', $customer);
                $this->assertArrayHasKey('days_inactive', $customer);
                $this->assertArrayHasKey('reactivation_score', $customer);
                $this->assertGreaterThanOrEqual(0, $customer['reactivation_score']);
                $this->assertLessThanOrEqual(1, $customer['reactivation_score']);
            }
        }
    }

    /** @test */
    public function it_can_calculate_customer_satisfaction()
    {
        $service = new CustomerSatisfactionService();
        $clientKey = 1;
        $analysisPeriod = 90;

        $result = $service->calculateCustomerSatisfactionMetrics($clientKey, $analysisPeriod);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('multi_dimensional_scoring', $result);
        $this->assertArrayHasKey('issue_categorization', $result);
        $this->assertArrayHasKey('satisfaction_trend_analysis', $result);
        $this->assertArrayHasKey('root_cause_analysis', $result);
        $this->assertArrayHasKey('satisfaction_health_score', $result);
        
        $multiDimensionalScoring = $result['multi_dimensional_scoring'];
        $this->assertArrayHasKey('overall_satisfaction_score', $multiDimensionalScoring);
        $this->assertArrayHasKey('nps_score', $multiDimensionalScoring);
        
        $this->assertGreaterThanOrEqual(0, $multiDimensionalScoring['overall_satisfaction_score']);
        $this->assertLessThanOrEqual(5, $multiDimensionalScoring['overall_satisfaction_score']);
    }

    /** @test */
    public function it_can_execute_automated_alerts()
    {
        $service = new AutomatedAlertSystemService();

        $result = $service->executeAlertMonitoring();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('monitoring_execution', $result);
        $this->assertArrayHasKey('alert_summary', $result);
        $this->assertArrayHasKey('escalation_status', $result);
        $this->assertArrayHasKey('alert_trends', $result);
        
        $monitoringExecution = $result['monitoring_execution'];
        $this->assertArrayHasKey('alerts_generated', $monitoringExecution);
        $this->assertArrayHasKey('alerts_processed', $monitoringExecution);
        $this->assertArrayHasKey('notifications_sent', $monitoringExecution);
        
        $this->assertIsInt($monitoringExecution['alerts_generated']);
        $this->assertIsInt($monitoringExecution['alerts_processed']);
        $this->assertIsInt($monitoringExecution['notifications_sent']);
    }

    /** @test */
    public function it_can_implement_gdpr_compliance()
    {
        $service = new DataPrivacyService();

        $result = $service->implementGDPRCompliance();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('gdpr_compliance_status', $result);
        $this->assertArrayHasKey('compliance_results', $result);
        $this->assertArrayHasKey('implementation_date', $result);
        
        $this->assertEquals('implemented', $result['gdpr_compliance_status']);
        
        $complianceResults = $result['compliance_results'];
        $this->assertArrayHasKey('data_subject_rights', $complianceResults);
        $this->assertArrayHasKey('retention_policies', $complianceResults);
        $this->assertArrayHasKey('anonymization', $complianceResults);
        $this->assertArrayHasKey('consent_management', $complianceResults);
    }

    private function setupTestData()
    {
        // Create test dimension client
        DimensionClient::create([
            'client_key' => 1,
            'client_name' => 'Test Customer',
            'email' => 'test@example.com',
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(100)
        ]);

        // Create test churn metrics
        FactCustomerChurnMetrics::create([
            'churn_key' => 'test_churn_1',
            'client_key' => 1,
            'churn_date_key' => Carbon::now()->format('Ymd'),
            'churn_probability' => 0.3,
            'risk_factors' => json_encode(['low_activity', 'recent_complaints']),
            'model_version' => '1.0'
        ]);
    }
}
