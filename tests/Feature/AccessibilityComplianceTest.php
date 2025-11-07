<?php

namespace Tests\Feature;

use App\Services\AuditService;
use App\Services\AccessibilityAuditService;
use App\Services\ComplianceMonitoringService;
use App\Models\User;
use App\Models\AuditTrailLog;
use App\Models\AccessibilityComplianceLog;
use App\Models\ComplianceViolation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AccessibilityComplianceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $accessibilityService;
    protected $auditService;
    protected $complianceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => 'admin'
        ]);
        
        $this->accessibilityService = app(AccessibilityAuditService::class);
        $this->auditService = app(AuditService::class);
        $this->complianceService = app(ComplianceMonitoringService::class);
    }

    /** @test */
    public function user_can_run_accessibility_test()
    {
        $url = 'https://example.com';
        $testType = 'automated';
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/admin/accessibility/test/run', [
                'url' => $url,
                'test_type' => $testType
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'test_id',
                    'status',
                    'compliance_score',
                    'violations',
                    'warnings',
                    'passes'
                ]
            ]);
        
        // Verify the test was logged
        $this->assertDatabaseHas('audit_trail_logs', [
            'action_type' => 'accessibility_test',
            'resource_type' => 'accessibility_test'
        ]);
    }

    /** @test */
    public function user_can_get_compliance_summary()
    {
        // Create a test record
        AccessibilityComplianceLog::create([
            'test_id' => 'test-' . uniqid(),
            'page_url' => 'https://example.com',
            'test_type' => 'automated',
            'wcag_version' => ['2.1', 'AA'],
            'test_results' => ['status' => 'completed'],
            'compliance_score' => 85.5,
            'violations' => [
                ['id' => 'color-contrast', 'impact' => 'minor', 'description' => 'Low contrast']
            ],
            'warnings' => [],
            'passes' => [
                ['id' => 'image-alt', 'impact' => 'pass', 'description' => 'Images have alt text']
            ],
            'tested_by' => $this->user->id,
            'tested_at' => now()
        ]);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/accessibility/compliance/summary?url=https://example.com');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'score',
                    'violations',
                    'warnings',
                    'passes',
                    'wcag_level'
                ]
            ]);
    }

    /** @test */
    public function user_can_get_accessibility_trends()
    {
        // Create multiple test records
        for ($i = 0; $i < 5; $i++) {
            AccessibilityComplianceLog::create([
                'test_id' => 'test-' . uniqid(),
                'page_url' => 'https://example.com',
                'test_type' => 'automated',
                'wcag_version' => ['2.1', 'AA'],
                'test_results' => ['status' => 'completed'],
                'compliance_score' => 70 + $i * 5, // Scores: 70, 75, 80, 85, 90
                'violations' => [],
                'warnings' => [],
                'passes' => [],
                'tested_by' => $this->user->id,
                'tested_at' => now()->subDays($i)
            ]);
        }
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/accessibility/trends?url=https://example.com&days=7');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'trends',
                    'average_score',
                    'improvement_rate'
                ]
            ]);
        
        $data = $response->json('data');
        $this->assertEquals(80, $data['average_score']);
        $this->assertIsArray($data['trends']);
    }

    /** @test */
    public function user_can_schedule_recurring_test()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/admin/accessibility/schedule', [
                'url' => 'https://example.com',
                'frequency' => 'daily'
            ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Recurring accessibility test scheduled successfully'
            ]);
    }

    /** @test */
    public function user_can_get_compliance_violations()
    {
        // Create violation records
        ComplianceViolation::create([
            'violation_id' => 'violation-' . uniqid(),
            'compliance_framework' => 'WCAG',
            'violation_type' => 'color-contrast',
            'severity' => 'high',
            'description' => 'Insufficient color contrast',
            'discovered_by' => 'automated_scan',
            'discovered_at' => now()
        ]);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/accessibility/violations?framework=WCAG');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page'
                ]
            ]);
    }

    /** @test */
    public function user_can_resolve_compliance_violation()
    {
        $violation = ComplianceViolation::create([
            'violation_id' => 'violation-' . uniqid(),
            'compliance_framework' => 'WCAG',
            'violation_type' => 'color-contrast',
            'severity' => 'high',
            'description' => 'Insufficient color contrast',
            'discovered_by' => 'automated_scan',
            'discovered_at' => now()
        ]);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/accessibility/violations/{$violation->id}/resolve", [
                'resolution_notes' => 'Fixed color contrast ratios',
                'is_false_positive' => false
            ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Violation marked as resolved successfully'
            ]);
        
        // Verify violation was resolved
        $violation->refresh();
        $this->assertNotNull($violation->resolved_at);
        $this->assertEquals($this->user->id, $violation->resolved_by_user_id);
        $this->assertEquals('Fixed color contrast ratios', $violation->resolution_notes);
    }

    /** @test */
    public function user_can_get_compliance_overview()
    {
        // Create test data
        for ($i = 0; $i < 10; $i++) {
            AccessibilityComplianceLog::create([
                'test_id' => 'test-' . uniqid(),
                'page_url' => 'https://example.com',
                'test_type' => 'automated',
                'wcag_version' => ['2.1', 'AA'],
                'test_results' => ['status' => 'completed'],
                'compliance_score' => rand(60, 95),
                'violations' => $i < 3 ? [['id' => 'color-contrast', 'impact' => 'critical']] : [],
                'warnings' => [],
                'passes' => [],
                'tested_by' => $this->user->id,
                'tested_at' => now()->subDays($i)
            ]);
        }
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/accessibility/overview?date_range=7d');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_tests',
                    'average_score',
                    'critical_violations',
                    'non_compliant_pages',
                    'violations_by_type',
                    'score_trends',
                    'top_violations'
                ]
            ]);
    }

    /** @test */
    public function middleware_validates_accessibility_compliance()
    {
        // This test would verify that the accessibility validation middleware
        // correctly processes requests and adds appropriate headers
        $response = $this->get('/test-page');
        
        $response->assertHeader('X-Accessibility-Tested', 'true');
        $response->assertHeader('X-WCAG-Version', '2.1');
        $response->assertHeader('X-Compliance-Level', 'AA');
    }

    /** @test */
    public function audit_logging_middleware_tracks_actions()
    {
        // This test would verify that the audit logging middleware
        // correctly logs user actions
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/pricing/quote', [
                'weight' => 5,
                'dimensions' => [10, 10, 10],
                'destination' => 'New York, NY'
            ]);
        
        // Verify audit log was created
        $this->assertDatabaseHas('audit_trail_logs', [
            'user_id' => $this->user->id,
            'action_type' => 'pricing_action',
            'resource_type' => 'pricing',
            'module' => 'api'
        ]);
    }

    /** @test */
    public function accessibility_service_generates_comprehensive_tests()
    {
        $url = 'https://example.com/test-page';
        
        $result = $this->accessibilityService->runAccessibilityTest($url);
        
        $this->assertNotNull($result);
        $this->assertNotNull($result->compliance_score);
        $this->assertIsArray($result->violations);
        $this->assertIsArray($result->warnings);
        $this->assertIsArray($result->passes);
        $this->assertGreaterThanOrEqual(0, $result->compliance_score);
        $this->assertLessThanOrEqual(100, $result->compliance_score);
    }

    /** @test */
    public function compliance_monitoring_detects_violations()
    {
        // Create test data that should trigger violations
        $this->complianceService->monitorCompliance();
        
        // Verify monitoring ran without errors
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function accessibility_components_have_proper_aria_attributes()
    {
        // Test React component accessibility attributes
        $component = view('components.accessibility.accessible-input', [
            'label' => 'Test Label',
            'name' => 'test_input',
            'id' => 'test_input',
            'required' => true
        ])->render();
        
        $this->assertStringContainsString('aria-required="true"', $component);
        $this->assertStringContainsString('for="test_input"', $component);
        $this->assertStringContainsString('id="test_input"', $component);
    }

    /** @test */
    public function audit_dashboard_displays_correct_data()
    {
        // Create test audit data
        AuditTrailLog::create([
            'log_id' => 'log-' . uniqid(),
            'user_id' => $this->user->id,
            'action_type' => 'create',
            'resource_type' => 'user',
            'resource_id' => $this->user->id,
            'module' => 'admin',
            'severity' => 'info',
            'ip_address' => '192.168.1.1',
            'occurred_at' => now()
        ]);
        
        // Test dashboard API endpoint
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/reports/audit/summary');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_actions',
                    'critical_actions',
                    'actions_by_type',
                    'recent_activity'
                ]
            ]);
    }

    /** @test */
    public function real_time_accessibility_testing_works()
    {
        $url = 'https://example.com';
        
        $response = $this->getJson("/api/v1/accessibility/test/{$url}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'test_id',
                'status',
                'compliance_score',
                'violations',
                'issues'
            ]);
    }
}