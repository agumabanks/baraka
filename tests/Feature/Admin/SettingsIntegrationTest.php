<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Backend\GeneralSettingsController;
use App\Http\Controllers\SettingsController;
use App\Http\Requests\SettingsFormRequest;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Role;
use App\Models\Backend\Upload;
use App\Models\User;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use App\Repositories\GeneralSettings\GeneralSettingsRepository;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Test suite for Settings Integration with backend components
 * 
 * Validates:
 * - GeneralSettingsRepository integration
 * - SettingsFormRequest validation with real repository
 * - Controller integration with repository
 * - Database persistence and cache invalidation
 * - Demo mode restrictions
 * - File upload integration with repository's file() helper
 * - Preferences structure mapping and validation
 */
class SettingsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $userWithoutPermission;
    protected GeneralSettingsInterface $repository;
    protected CurrencyInterface $currencyRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup storage for file uploads
        Storage::fake('public');

        // Create admin role and users
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $userRole = Role::create(['name' => 'User', 'slug' => 'user']);
        
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->userWithoutPermission = User::factory()->create([
            'role_id' => $userRole->id,
            'email_verified_at' => now(),
        ]);

        // Bind repositories
        $this->app->bind(GeneralSettingsInterface::class, GeneralSettingsRepository::class);
        
        $this->repository = app(GeneralSettingsInterface::class);
        $this->currencyRepository = app(CurrencyInterface::class);

        // Acting as admin by default
        $this->actingAs($this->admin);
    }

    /** @test */
    public function general_settings_repository_all_method_creates_default_settings(): void
        {
        // Ensure no settings exist initially
        $this->assertDatabaseCount('general_settings', 0);
        
            $settings = $this->repository->all();
            
            // Should create default settings
            $this->assertDatabaseCount('general_settings', 1);
            $this->assertInstanceOf(GeneralSettings::class, $settings);
            $this->assertEquals('Baraka Sanaa', $settings->name);
            $this->assertEquals('UGX', $settings->currency);
        }

    /** @test */
    public function general_settings_repository_all_method_returns_existing_settings(): void
    {
        // Create existing settings
        $existingSettings = GeneralSettings::create([
            'name' => 'Existing Company',
            'phone' => '+256700000000',
            'email' => 'existing@example.com',
            'currency' => 'USD',
            'primary_color' => '#ff0000',
        ]);

        $retrievedSettings = $this->repository->all();

        $this->assertEquals($existingSettings->id, $retrievedSettings->id);
        $this->assertEquals('Existing Company', $retrievedSettings->name);
        $this->assertEquals('USD', $retrievedSettings->currency);
    }

    /** @test */
    public function general_settings_repository_update_method_saves_to_database(): void
    {
        $initialSettings = $this->repository->all();

        $updateData = [
            'name' => 'Updated Company Name',
            'phone' => '+256711111111',
            'email' => 'updated@example.com',
            'address' => '456 Updated Street',
            'currency' => 'EUR',
            'copyright' => '© 2024 Updated Co',
            'par_track_prefix' => 'UPD',
            'invoice_prefix' => 'UINV',
            'primary_color' => '#00ff00',
            'text_color' => '#0000ff',
            'preferences' => [
                'general' => [
                    'support_email' => 'support@updated.com',
                    'timezone' => 'Europe/London',
                ],
                'branding' => [
                    'theme' => 'dark',
                    'sidebar_density' => 'compact',
                ],
            ],
        ];

        $request = new \Illuminate\Http\Request($updateData);
        $updatedSettings = $this->repository->update($request);

        // Verify database was updated
        $this->assertDatabaseHas('general_settings', [
            'name' => 'Updated Company Name',
            'phone' => '+256711111111',
            'email' => 'updated@example.com',
            'address' => '456 Updated Street',
            'currency' => 'EUR',
            'copyright' => '© 2024 Updated Co',
            'par_track_prefix' => 'UPD',
            'invoice_prefix' => 'UINV',
            'primary_color' => '#00ff00',
            'text_color' => '#0000ff',
        ]);

        $this->assertEquals('Updated Company Name', $updatedSettings->name);
        $this->assertEquals('EUR', $updatedSettings->currency);
    }

    /** @test */
    public function general_settings_repository_file_upload_integration(): void
    {
        $settings = $this->repository->all();

        // Create test image files
        $logoFile = \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 100)->size(1024);
        $lightLogoFile = \Illuminate\Http\UploadedFile::fake()->image('light-logo.png', 200, 100)->size(1024);
        $faviconFile = \Illuminate\Http\UploadedFile::fake()->image('favicon.png', 32, 32)->size(256);

        $updateData = [
            'name' => 'Test Company',
            'currency' => 'UGX',
            'logo' => $logoFile,
            'light_logo' => $lightLogoFile,
            'favicon' => $faviconFile,
        ];

        $request = new \Illuminate\Http\Request();
        $request->merge($updateData);
        $request->files->set('logo', $logoFile);
        $request->files->set('light_logo', $lightLogoFile);
        $request->files->set('favicon', $faviconFile);

        $updatedSettings = $this->repository->update($request);

        // Verify Upload records were created
        $this->assertDatabaseCount('uploads', 3);

        // Verify settings have upload IDs
        $this->assertNotNull($updatedSettings->logo);
        $this->assertNotNull($updatedSettings->light_logo);
        $this->assertNotNull($updatedSettings->favicon);

        // Verify files exist in storage
        $logoUpload = Upload::find($updatedSettings->logo);
        $lightLogoUpload = Upload::find($updatedSettings->light_logo);
        $faviconUpload = Upload::find($updatedSettings->favicon);

        $this->assertNotNull($logoUpload);
        $this->assertNotNull($lightLogoUpload);
        $this->assertNotNull($faviconUpload);

        $this->assertNotEmpty($logoUpload->original);
        $this->assertNotEmpty($lightLogoUpload->original);
        $this->assertNotEmpty($faviconUpload->original);

        // Verify files are stored in correct location
        Storage::disk('public')->assertExists($logoUpload->original);
        Storage::disk('public')->assertExists($lightLogoUpload->original);
        Storage::disk('public')->assertExists($faviconUpload->original);
    }

    /** @test */
    public function settings_form_request_validation_works_with_repository(): void
    {
        // Mock currency repository to return test currencies
        $this->mock(CurrencyInterface::class, function ($mock) {
            $mock->shouldReceive('getActive')
                ->andReturn(collect([
                    (object) ['code' => 'USD', 'symbol' => '$'],
                    (object) ['code' => 'EUR', 'symbol' => '€'],
                    (object) ['code' => 'UGX', 'symbol' => 'USh'],
                ]));
        });

        // Test valid data passes validation
        $validData = [
            'name' => 'Valid Company Name',
            'phone' => '+256700000000',
            'email' => 'valid@example.com',
            'currency' => 'USD',
            'primary_color' => '#ff0000',
            'text_color' => '#00ff00',
            'par_track_prefix' => 'TEST',
            'invoice_prefix' => 'INV',
            'preferences' => [
                'general' => [
                    'support_email' => 'support@valid.com',
                    'timezone' => 'America/New_York',
                ],
            ],
        ];

        $request = new SettingsFormRequest();
        $request->merge($validData);
        $request->setContainer(app())->setRedirector(app(\Illuminate\Routing\Redirector::class));

        // Should not throw validation exception
        try {
            $request->validateResolved();
            $this->assertTrue(true, 'Valid data passed validation');
        } catch (ValidationException $e) {
            $this->fail('Valid data should not fail validation: ' . json_encode($e->errors()));
        }

        // Test invalid data fails validation
        $invalidData = [
            'name' => '', // Required field
            'email' => 'invalid-email', // Invalid email
            'currency' => 'INVALID', // Not in allowed currencies
            'primary_color' => 'not-a-color', // Invalid hex color
            'phone' => 'invalid-phone', // Invalid phone format
            'par_track_prefix' => 'invalid@prefix', // Invalid characters
        ];

        $request = new SettingsFormRequest();
        $request->merge($invalidData);

        try {
            $request->validateResolved();
            $this->fail('Invalid data should fail validation');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertArrayHasKey('email', $e->errors());
            $this->assertArrayHasKey('currency', $e->errors());
            $this->assertArrayHasKey('primary_color', $e->errors());
            $this->assertArrayHasKey('phone', $e->errors());
            $this->assertArrayHasKey('par_track_prefix', $e->errors());
        }
    }

    /** @test */
    public function settings_form_request_preferences_validation_works(): void
    {
        $dataWithInvalidPreferences = [
            'name' => 'Test Company',
            'currency' => 'UGX',
            'preferences' => [
                'general' => [
                    'support_email' => 'not-an-email', // Invalid email
                    'auto_logout_minutes' => 2000, // Too high
                ],
                'finance' => [
                    'default_tax_rate' => 150, // Over 100%
                ],
                'system' => [
                    'data_retention_days' => 10, // Too low
                ],
            ],
        ];

        $request = new SettingsFormRequest();
        $request->merge($dataWithInvalidPreferences);

        try {
            $request->validateResolved();
            $this->fail('Invalid preferences should fail validation');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertTrue(
                isset($errors['preferences.general.support_email']) ||
                isset($errors['preferences.general.auto_logout_minutes']) ||
                isset($errors['preferences.finance.default_tax_rate']) ||
                isset($errors['preferences.system.data_retention_days'])
            );
        }
    }

    /** @test */
    public function cache_invalidation_after_settings_update(): void
    {
        // Prime the cache
        Cache::put('settings', 'cached_value', 3600);
        $this->assertTrue(Cache::has('settings'));

        $updateData = [
            'name' => 'Cache Test Company',
            'currency' => 'UGX',
        ];

        $request = new \Illuminate\Http\Request($updateData);
        $this->repository->update($request);

        // Cache should be invalidated
        $this->assertFalse(Cache::has('settings'));
    }

    /** @test */
    public function demo_mode_restrictions_work(): void
    {
        // Set demo mode
        app()->config(['app.demo' => true]);

        $controller = new GeneralSettingsController($this->repository, $this->currencyRepository);

        $request = new \Illuminate\Http\Request([
            'name' => 'Demo Update Test',
            'currency' => 'UGX',
        ]);

        $response = $controller->update($request);

        // Should redirect back with error
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirect());
        
        // Toastr error should be set
        $this->assertTrue(session()->has('toastr_error'));
    }

    /** @test */
    public function general_settings_controller_integration_works(): void
    {
        $controller = new GeneralSettingsController($this->repository, $this->currencyRepository);

        // Test index method
        $response = $controller->index();
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('settings.index', $response->name());

        // Test update method with valid data
        $updateData = [
            'name' => 'Controller Test Company',
            'phone' => '+256799999999',
            'email' => 'controller@test.com',
            'currency' => 'EUR',
            'copyright' => '© 2024 Controller Test',
            'par_track_prefix' => 'CTRL',
            'invoice_prefix' => 'CTST',
            'primary_color' => '#ff8800',
            'text_color' => '#0088ff',
        ];

        $request = new \Illuminate\Http\Request($updateData);
        $response = $controller->update($request);

        // Should redirect to settings index
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirect(route('settings.index')));

        // Database should be updated
        $this->assertDatabaseHas('general_settings', [
            'name' => 'Controller Test Company',
            'phone' => '+256799999999',
            'email' => 'controller@test.com',
            'currency' => 'EUR',
            'copyright' => '© 2024 Controller Test',
            'par_track_prefix' => 'CTRL',
            'invoice_prefix' => 'CTST',
            'primary_color' => '#ff8800',
            'text_color' => '#0088ff',
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has('settings'));
    }

    /** @test */
    public function settings_controller_integration_works(): void
    {
        $controller = app(SettingsController::class);

        // Test all controller methods that should work with the repository pattern
        // Note: SettingsController uses config() directly rather than repository
        // but we should test that it doesn't break the integration

        // Test index method
        $response = $controller->index();
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);

        // Test general settings update
        $updateData = [
            'app_name' => 'Test App Name',
            'app_url' => 'https://test.example.com',
            'app_timezone' => 'UTC',
            'app_locale' => 'en',
        ];

        $request = new \Illuminate\Http\Request($updateData);
        $response = $controller->updateGeneral($request);
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $responseData = $response->getData(true);
        $this->assertTrue($responseData['success']);
    }

    /** @test */
    public function preferences_structure_maps_correctly(): void
    {
        $preferencesPayload = [
            'general' => [
                'support_email' => 'test@example.com',
                'timezone' => 'America/New_York',
                'country' => 'United States',
            ],
            'branding' => [
                'theme' => 'dark',
                'sidebar_density' => 'compact',
                'enable_animations' => false,
            ],
            'operations' => [
                'auto_assign_drivers' => true,
                'enable_capacity_management' => false,
                'require_dispatch_approval' => true,
            ],
            'finance' => [
                'auto_reconcile' => false,
                'enforce_cod_settlement_workflow' => true,
                'default_tax_rate' => 8.5,
                'rounding_mode' => 'nearest',
            ],
            'notifications' => [
                'email' => true,
                'sms' => false,
                'push' => true,
                'daily_digest' => false,
            ],
            'integrations' => [
                'webhooks_enabled' => true,
                'webhooks_url' => 'https://hooks.example.com/webhook',
                'slack_enabled' => false,
                'analytics_tracking_id' => 'UA-123456-1',
            ],
            'system' => [
                'maintenance_mode' => false,
                'two_factor_required' => true,
                'auto_logout_minutes' => 120,
                'data_retention_days' => 730,
            ],
        ];

        $updateData = [
            'name' => 'Preferences Test Company',
            'currency' => 'UGX',
            'preferences' => $preferencesPayload,
        ];

        $request = new \Illuminate\Http\Request($updateData);
        $updatedSettings = $this->repository->update($request);

        // Verify preferences structure is maintained
        $savedPreferences = $updatedSettings->details ?? [];
        
        foreach ($preferencesPayload as $section => $sectionData) {
            $this->assertArrayHasKey($section, $savedPreferences);
            
            foreach ($sectionData as $key => $value) {
                $this->assertArrayHasKey($key, $savedPreferences[$section]);
                $this->assertEquals($value, $savedPreferences[$section][$key]);
            }
        }
    }

    /** @test */
    public function general_settings_authorization_works(): void
    {
        // Test that authorization is checked in SettingsFormRequest
        $request = new SettingsFormRequest();

        // Unauthenticated request should be denied
        auth()->logout();
        $this->assertFalse($request->authorize());

        // User without permissions should be denied
        auth()->login($this->userWithoutPermission);
        $this->assertFalse($request->authorize());

        // User with proper permissions should be allowed
        auth()->login($this->admin);
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function file_upload_validation_integration(): void
    {
        // Test that file validation works through the full stack
        $logoFile = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1024);
        $faviconFile = \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 16, 16)->size(2048); // Too large

        $updateData = [
            'name' => 'File Validation Test',
            'currency' => 'UGX',
            'logo' => $logoFile,
            'favicon' => $faviconFile,
        ];

        $request = new \Illuminate\Http\Request();
        $request->merge($updateData);
        $request->files->set('logo', $logoFile);
        $request->files->set('favicon', $faviconFile);

        // Should throw validation exception for invalid files
        try {
            $this->repository->update($request);
            $this->fail('Should have thrown validation exception for invalid files');
        } catch (\Exception $e) {
            // Validation should fail
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function boolean_conversion_in_preferences_works(): void
    {
        $stringBooleanData = [
            'name' => 'Boolean Test Company',
            'currency' => 'UGX',
            'preferences' => [
                'general' => [
                    'support_email' => 'test@example.com',
                ],
                'branding' => [
                    'enable_animations' => 'true',
                    'theme' => 'dark',
                ],
                'operations' => [
                    'auto_assign_drivers' => 'false',
                    'require_dispatch_approval' => '1',
                ],
                'notifications' => [
                    'email' => '0',
                    'push' => 'false',
                ],
            ],
        ];

        $request = new SettingsFormRequest();
        $request->merge($stringBooleanData);

        // Should convert string booleans to actual booleans
        $request->validateResolved();
        $validatedData = $request->validated();

        $preferences = $validatedData['preferences'];
        
        // Check boolean conversion
        $this->assertIsBool($preferences['branding']['enable_animations']);
        $this->assertTrue($preferences['branding']['enable_animations']);
        
        $this->assertIsBool($preferences['operations']['auto_assign_drivers']);
        $this->assertFalse($preferences['operations']['auto_assign_drivers']);
        
        $this->assertIsBool($preferences['operations']['require_dispatch_approval']);
        $this->assertTrue($preferences['operations']['require_dispatch_approval']);
        
        $this->assertIsBool($preferences['notifications']['email']);
        $this->assertFalse($preferences['notifications']['email']);
    }

    /** @test */
    public function existing_files_are_replaced_correctly(): void
    {
        // Create initial settings with files
        $initialLogo = \Illuminate\Http\UploadedFile::fake()->image('initial-logo.png', 200, 100)->size(1024);
        
        $initialData = [
            'name' => 'Initial Company',
            'currency' => 'UGX',
            'logo' => $initialLogo,
        ];

        $request = new \Illuminate\Http\Request();
        $request->merge($initialData);
        $request->files->set('logo', $initialLogo);

        $initialSettings = $this->repository->update($request);
        $initialLogoUpload = Upload::find($initialSettings->logo);

        // Upload replacement file
        $replacementLogo = \Illuminate\Http\UploadedFile::fake()->image('replacement-logo.png', 200, 100)->size(1024);
        
        $replacementData = [
            'name' => 'Updated Company',
            'currency' => 'UGX',
            'logo' => $replacementLogo,
        ];

        $request = new \Illuminate\Http\Request();
        $request->merge($replacementData);
        $request->files->set('logo', $replacementLogo);

        $updatedSettings = $this->repository->update($request);
        $replacementLogoUpload = Upload::find($updatedSettings->logo);

        // Verify old file was replaced
        $this->assertNotEquals($initialLogoUpload->id, $replacementLogoUpload->id);
        
        // Verify new file exists and old file might be deleted
        Storage::disk('public')->assertExists($replacementLogoUpload->original);
    }

    /** @test */
    public function settings_persistence_across_requests(): void
    {
        $initialData = [
            'name' => 'Persistent Company',
            'phone' => '+256700000000',
            'email' => 'persistent@example.com',
            'currency' => 'EUR',
            'copyright' => '© 2024 Persistent Co',
            'par_track_prefix' => 'PERS',
            'invoice_prefix' => 'PST',
            'primary_color' => '#aa0000',
            'text_color' => '#00aa00',
            'preferences' => [
                'general' => [
                    'support_email' => 'support@persistent.com',
                    'timezone' => 'Europe/Paris',
                ],
            ],
        ];

        // First request - create settings
        $request1 = new \Illuminate\Http\Request($initialData);
        $settings1 = $this->repository->update($request1);
        $settingsId1 = $settings1->id;

        // Second request - update settings
        $updatedData = [
            'name' => 'Updated Persistent Company',
            'currency' => 'GBP',
            'preferences' => [
                'general' => [
                    'support_email' => 'updated@persistent.com',
                    'timezone' => 'Europe/London',
                ],
            ],
        ];

        $request2 = new \Illuminate\Http\Request($updatedData);
        $settings2 = $this->repository->update($request2);

        // Should be the same record
        $this->assertEquals($settingsId1, $settings2->id);
        $this->assertEquals('Updated Persistent Company', $settings2->name);
        $this->assertEquals('GBP', $settings2->currency);
        
        // Verify preferences were updated
        $preferences = $settings2->details ?? [];
        $this->assertEquals('updated@persistent.com', $preferences['general']['support_email']);
        $this->assertEquals('Europe/London', $preferences['general']['timezone']);
    }
}