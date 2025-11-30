<?php

namespace Tests\Unit;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettingsService();

        // Seed a test setting
        DB::table('system_settings')->insert([
            'key' => 'test_setting',
            'value' => '100',
            'type' => 'integer',
            'category' => 'general',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function it_can_get_system_setting()
    {
        $value = $this->service->get('test_setting');

        $this->assertEquals(100, $value);
        $this->assertIsInt($value);
    }

    /** @test */
    public function it_can_set_system_setting()
    {
        $this->service->set('new_setting', 'test_value');

        $value = $this->service->get('new_setting');
        $this->assertEquals('test_value', $value);
    }

    /** @test */
    public function it_casts_values_correctly()
    {
        // Integer
        $this->service->set('int_setting', 123);
        $this->assertIsInt($this->service->get('int_setting'));

        // Boolean
        $this->service->set('bool_setting', true);
        $this->assertIsBool($this->service->get('bool_setting'));

        // Array/JSON
        $this->service->set('json_setting', ['key' => 'value']);
        $this->assertIsArray($this->service->get('json_setting'));
    }

    /** @test */
    public function it_can_set_branch_override()
    {
        $branchId = 1;
        $userId = 1;

        $this->service->setBranchOverride($branchId, 'test_setting', 200, $userId);

        $value = $this->service->get('test_setting', null, $branchId);
        $this->assertEquals(200, $value);
    }

    /** @test */
    public function it_returns_branch_override_over_system_default()
    {
        $branchId = 1;
        $userId = 1;

        // System default is 100
        $systemValue = $this->service->get('test_setting');
        $this->assertEquals(100, $systemValue);

        // Set branch override to 200
        $this->service->setBranchOverride($branchId, 'test_setting', 200, $userId);

        // Branch should see 200
        $branchValue = $this->service->get('test_setting', null, $branchId);
        $this->assertEquals(200, $branchValue);

        // Other branch should still see system default
        $otherBranchValue = $this->service->get('test_setting', null, 2);
        $this->assertEquals(100, $otherBranchValue);
    }

    /** @test */
    public function it_prevents_override_of_non_public_settings()
    {
        // Create non-public setting
        DB::table('system_settings')->insert([
            'key' => 'locked_setting',
            'value' => 'secret',
            'type' => 'string',
            'category' => 'general',
            'is_public' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("cannot be overridden");

        $this->service->setBranchOverride(1, 'locked_setting', 'hacked', 1);
    }

    /** @test */
    public function it_can_remove_branch_override()
    {
        $branchId = 1;
        
        $this->service->setBranchOverride($branchId, 'test_setting', 200, 1);
        $this->assertEquals(200, $this->service->get('test_setting', null, $branchId));

        $this->service->removeBranchOverride($branchId, 'test_setting');
        $this->assertEquals(100, $this->service->get('test_setting', null, $branchId));
    }
}
