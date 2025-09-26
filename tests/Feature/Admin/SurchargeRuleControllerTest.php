<?php

namespace Tests\Feature\Admin;

use App\Models\Backend\Role;
use App\Models\SurchargeRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurchargeRuleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Satisfy settings() calls in layout
        \App\Models\Backend\GeneralSettings::create([
            'company_name' => 'Test Co',
            'copyright' => '© Test',
        ]);
    }

    private function makeAdmin(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_index_policy_enforced(): void
    {
        $this->get(route('admin.surcharges.index'))->assertRedirect();
        $this->actingAs(User::factory()->create())
            ->get(route('admin.surcharges.index'))
            ->assertForbidden();

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.surcharges.index'))
            ->assertOk();
    }

    public function test_create_store_validation_and_persist(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->post(route('admin.surcharges.store'), [])->assertSessionHasErrors(['code','name','trigger','rate_type','amount','active_from']);

        $payload = [
            'code' => 'FUEL-001',
            'name' => 'Fuel Surcharge',
            'trigger' => 'fuel',
            'rate_type' => 'percent',
            'amount' => 12.5,
            'currency' => 'USD',
            'active_from' => now()->toDateString(),
            'active_to' => null,
            'active' => 1,
        ];
        $this->post(route('admin.surcharges.store'), $payload)
            ->assertRedirect(route('admin.surcharges.index'));

        $this->assertDatabaseHas('surcharge_rules', [
            'code' => 'FUEL-001',
            'name' => 'Fuel Surcharge',
            'trigger' => 'fuel',
            'rate_type' => 'percent',
        ]);
    }

    public function test_update_and_delete_enforced_by_policy(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $rule = SurchargeRule::create([
            'code' => 'RA-001',
            'name' => 'Remote Area',
            'trigger' => 'remote_area',
            'rate_type' => 'flat',
            'amount' => 5,
            'currency' => 'USD',
            'active_from' => now()->toDateString(),
            'active' => true,
        ]);

        $this->put(route('admin.surcharges.update',$rule), [
            'name' => 'Remote Area Fee',
            'trigger' => 'remote_area',
            'rate_type' => 'flat',
            'amount' => 7,
            'currency' => 'USD',
            'active_from' => now()->toDateString(),
            'active' => true,
        ])->assertRedirect(route('admin.surcharges.index'));

        $this->assertDatabaseHas('surcharge_rules', ['code' => 'RA-001', 'name' => 'Remote Area Fee', 'amount' => 7]);
        // Admin cannot delete per policy (only hq_admin); expect 403
        $this->delete(route('admin.surcharges.destroy', $rule))
            ->assertForbidden();

        // Elevate to HQ Admin and delete
        $hqRole = Role::create(['name' => 'HQ Admin', 'slug' => 'hq_admin']);
        $hqUser = User::factory()->create(['role_id' => $hqRole->id]);
        $this->actingAs($hqUser)
            ->delete(route('admin.surcharges.destroy', $rule))
            ->assertRedirect();
        $this->assertSoftDeleted('surcharge_rules', ['id' => $rule->id]);
    }
}
