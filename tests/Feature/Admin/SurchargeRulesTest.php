<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurchargeRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdminUser(): User
    {
        $roleId = \DB::table('roles')->insertGetId(['name' => 'Admin', 'slug' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
        $user = User::factory()->create();
        \DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);

        return $user->fresh();
    }

    public function test_index_visible_for_authorized(): void
    {
        $user = $this->makeAdminUser();
        $this->actingAs($user)->get(route('admin.surcharges.index'))->assertStatus(200);
    }

    public function test_create_persists_rule(): void
    {
        $user = $this->makeAdminUser();
        $payload = [
            'code' => 'FUEL10',
            'name' => 'Fuel 10%',
            'trigger' => 'fuel',
            'rate_type' => 'percent',
            'amount' => 10,
            'currency' => null,
            'active_from' => now()->format('Y-m-d'),
            'active' => 1,
        ];
        $res = $this->actingAs($user)->post(route('admin.surcharges.store'), $payload);
        $res->assertRedirect(route('admin.surcharges.index'));
        $this->assertDatabaseHas('surcharge_rules', ['code' => 'FUEL10']);
    }

    public function test_policy_blocks_unauthorized(): void
    {
        $unauth = User::factory()->create();
        $this->actingAs($unauth)->get(route('admin.surcharges.index'))->assertForbidden();
    }
}
