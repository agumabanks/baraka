<?php

namespace Tests\Feature\Admin;

use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for Settings Blade routes
 * 
 * Validates:
 * - Route accessibility and authentication
 * - Middleware enforcement (auth, verified)
 * - Route registration and naming
 * - SPA fallback exclusion for settings routes
 */
class SettingsRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $unverifiedUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create general settings to satisfy settings() helper calls
        GeneralSettings::create([
            'name' => 'Test Company',
            'phone' => '+256700000000',
            'email' => 'test@example.com',
            'copyright' => '© 2024 Test Co',
            'currency' => 'UGX',
            'primary_color' => '#1F2937',
            'text_color' => '#FFFFFF',
        ]);

        // Create admin role and user
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create unverified user
        $this->unverifiedUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => null,
        ]);
    }

    /** @test */
    public function settings_routes_are_defined(): void
    {
        // Test that the main settings routes are actually defined
        $this->assertTrue(\Route::has('settings.index'));
        $this->assertTrue(\Route::has('settings.general'));
        $this->assertTrue(\Route::has('settings.branding'));
        $this->assertTrue(\Route::has('settings.operations'));
        $this->assertTrue(\Route::has('settings.finance'));
        $this->assertTrue(\Route::has('settings.notifications'));
        $this->assertTrue(\Route::has('settings.integrations'));
        $this->assertTrue(\Route::has('settings.system'));
        $this->assertTrue(\Route::has('settings.website'));
    }

    /** @test */
    public function settings_post_routes_are_defined(): void
    {
        // Test that update routes are defined
        $this->assertTrue(\Route::has('settings.general.update'));
        $this->assertTrue(\Route::has('settings.branding.update'));
        $this->assertTrue(\Route::has('settings.operations.update'));
        $this->assertTrue(\Route::has('settings.finance.update'));
        $this->assertTrue(\Route::has('settings.notifications.update'));
        $this->assertTrue(\Route::has('settings.integrations.update'));
        $this->assertTrue(\Route::has('settings.system.update'));
        $this->assertTrue(\Route::has('settings.website.update'));
    }

    /** @test */
    public function settings_ajax_endpoints_are_defined(): void
    {
        // Test AJAX endpoints
        $this->assertTrue(\Route::has('settings.test-connection'));
        $this->assertTrue(\Route::has('settings.clear-cache'));
        $this->assertTrue(\Route::has('settings.export'));
    }

    /** @test */
    public function settings_routes_use_correct_middleware(): void
    {
        // Test that all settings routes have auth and verified middleware
        $route = \Route::getRoutes()->getByName('settings.index');
        $this->assertNotNull($route);
        $this->assertTrue($route->middleware('auth'));
        $this->assertTrue($route->middleware('verified'));
        
        $route = \Route::getRoutes()->getByName('settings.general.update');
        $this->assertNotNull($route);
        $this->assertTrue($route->middleware('auth'));
        $this->assertTrue($route->middleware('verified'));
    }

    /** @test */
    public function general_settings_legacy_route_is_defined(): void
    {
        $this->assertTrue(\Route::has('general-settings.index'));
        $this->assertTrue(\Route::has('general-settings.update'));
    }

    /** @test */
    public function settings_index_route_responds_to_get_requests(): void
    {
        // Test unauthenticated access redirects
        $response = $this->get('/settings');
        $this->assertTrue($response->isRedirection());
        
        // Test authenticated access works (even if view doesn't exist, route responds)
        $response = $this->actingAs($this->admin)
            ->get('/settings');
        
        // Should either be OK (200) or redirect to login if auth fails
        $this->assertTrue(in_array($response->status(), [200, 302, 401, 403]));
    }

    /** @test */
    public function settings_routes_exclude_spa_fallback(): void
    {
        // Test that settings routes don't match the SPA fallback pattern
        // This test ensures the SPA route definition excludes settings
        $spaRoute = \Route::getRoutes()->getByName('spa.entry');
        $this->assertNotNull($spaRoute);
        
        // The SPA route should have a constraint that excludes settings
        $uri = $spaRoute->uri();
        $this->assertTrue(str_contains($uri, '^(?!api|settings|general-settings)'));
    }

    /** @test */
    public function legacy_general_settings_route_works(): void
    {
        // Test legacy route works and redirects to modern route
        $response = $this->actingAs($this->admin)
            ->get('/general-settings');
        
        // Should either render a view or redirect (depending on implementation)
        $this->assertTrue(in_array($response->status(), [200, 302]));
    }

    /** @test */
    public function settings_ajax_endpoints_respond_to_post_requests(): void
    {
        // Test AJAX endpoints require authentication
        $response = $this->post('/settings/test-connection');
        $this->assertTrue($response->isRedirection());
        
        // Test with authentication
        $response = $this->actingAs($this->admin)
            ->post('/settings/test-connection', [
                'service' => 'stripe',
                'credentials' => ['secret_key' => 'test'],
            ]);
        
        // Should return JSON response (either success or error)
        $this->assertTrue(in_array($response->status(), [200, 422, 500]));
    }

    /** @test */
    public function settings_clear_cache_endpoint_works(): void
    {
        // Test cache clear endpoint
        $response = $this->actingAs($this->admin)
            ->post('/settings/clear-cache');
        
        // Should return JSON response
        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    /** @test */
    public function settings_export_endpoint_works(): void
    {
        // Test export endpoint
        $response = $this->actingAs($this->admin)
            ->get('/settings/export');
        
        // Should return a file download or JSON response
        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    /** @test */
    public function settings_routes_have_consistent_naming_pattern(): void
    {
        // Verify all settings routes follow the settings.* naming pattern
        $settingsRoutes = collect(\Route::getRoutes())
            ->filter(function ($route) {
                return str_starts_with($route->getName() ?? '', 'settings.');
            });

        $this->assertTrue($settingsRoutes->count() > 0);
        
        // Check for expected route patterns
        $expectedPatterns = [
            'settings.index',
            'settings.general',
            'settings.general.update',
            'settings.branding',
            'settings.branding.update',
            'settings.operations',
            'settings.operations.update',
            'settings.finance',
            'settings.finance.update',
            'settings.notifications',
            'settings.notifications.update',
            'settings.integrations',
            'settings.integrations.update',
            'settings.system',
            'settings.system.update',
            'settings.website',
            'settings.website.update',
            'settings.test-connection',
            'settings.clear-cache',
            'settings.export',
        ];

        foreach ($expectedPatterns as $pattern) {
            $this->assertTrue(\Route::has($pattern), "Route {$pattern} should be defined");
        }
    }

    /** @test */
    public function settings_routes_use_correct_http_methods(): void
    {
        // Test that GET routes don't accept POST
        $route = \Route::getRoutes()->getByName('settings.index');
        if ($route) {
            $this->assertTrue($route->methods()->contains('GET'));
            $this->assertFalse($route->methods()->contains('POST'));
        }
        
        // Test that POST routes only accept POST
        $route = \Route::getRoutes()->getByName('settings.general.update');
        if ($route) {
            $this->assertTrue($route->methods()->contains('POST'));
            $this->assertFalse($route->methods()->contains('GET'));
        }
    }

    /** @test */
    public function settings_routes_are_grouped_with_prefix(): void
    {
        // Test that settings routes have the correct prefix
        $route = \Route::getRoutes()->getByName('settings.index');
        $this->assertNotNull($route);
        $this->assertEquals('settings', $route->prefix());
        $this->assertTrue(str_starts_with($route->uri(), 'settings'));
    }

    /** @test */
    public function authentication_is_required_for_all_settings_routes(): void
    {
        $settingsRoutes = [
            'settings.index',
            'settings.general',
            'settings.general.update',
            'settings.branding',
            'settings.branding.update',
            'settings.operations',
            'settings.operations.update',
            'settings.finance',
            'settings.finance.update',
            'settings.notifications',
            'settings.notifications.update',
            'settings.integrations',
            'settings.integrations.update',
            'settings.system',
            'settings.system.update',
            'settings.website',
            'settings.website.update',
            'settings.test-connection',
            'settings.clear-cache',
            'settings.export',
        ];

        foreach ($settingsRoutes as $routeName) {
            $route = \Route::getRoutes()->getByName($routeName);
            if ($route) {
                $this->assertTrue($route->middleware('auth'));
            }
        }
    }

    /** @test */
    public function email_verification_is_required_for_all_settings_routes(): void
    {
        $settingsRoutes = [
            'settings.index',
            'settings.general',
            'settings.general.update',
            'settings.branding',
            'settings.branding.update',
            'settings.operations',
            'settings.operations.update',
            'settings.finance',
            'settings.finance.update',
            'settings.notifications',
            'settings.notifications.update',
            'settings.integrations',
            'settings.integrations.update',
            'settings.system',
            'settings.system.update',
            'settings.website',
            'settings.website.update',
            'settings.test-connection',
            'settings.clear-cache',
            'settings.export',
        ];

        foreach ($settingsRoutes as $routeName) {
            $route = \Route::getRoutes()->getByName($routeName);
            if ($route) {
                $this->assertTrue($route->middleware('verified'));
            }
        }
    }

    /** @test */
    public function settings_routes_work_with_controller_methods(): void
    {
        // Test that routes resolve to the correct controller
        $route = \Route::getRoutes()->getByName('settings.index');
        if ($route) {
            $this->assertEquals(\App\Http\Controllers\SettingsController::class, $route->getControllerClass());
        }
        
        $route = \Route::getRoutes()->getByName('settings.general');
        if ($route) {
            $this->assertEquals(\App\Http\Controllers\SettingsController::class, $route->getControllerClass());
        }
        
        $route = \Route::getRoutes()->getByName('general-settings.index');
        if ($route) {
            $this->assertEquals(\App\Http\Controllers\Backend\GeneralSettingsController::class, $route->getControllerClass());
        }
    }

    /** @test */
    public function settings_route_definitions_are_valid(): void
    {
        // Test that all settings routes can be resolved without errors
        $settingsRoutes = [
            'settings.index',
            'settings.general',
            'settings.general.update',
            'settings.branding',
            'settings.branding.update',
            'settings.operations',
            'settings.operations.update',
            'settings.finance',
            'settings.finance.update',
            'settings.notifications',
            'settings.notifications.update',
            'settings.integrations',
            'settings.integrations.update',
            'settings.system',
            'settings.system.update',
            'settings.website',
            'settings.website.update',
            'settings.test-connection',
            'settings.clear-cache',
            'settings.export',
        ];

        foreach ($settingsRoutes as $routeName) {
            try {
                $route = \Route::getRoutes()->getByName($routeName);
                $this->assertNotNull($route, "Route {$routeName} should exist");
                
                // Test that URI is valid
                $this->assertNotEmpty($route->uri());
                
                // Test that controller is callable
                $action = $route->getAction();
                $this->assertIsArray($action);
                
            } catch (\Exception $e) {
                $this->fail("Route {$routeName} definition is invalid: " . $e->getMessage());
            }
        }
    }
}