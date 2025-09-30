<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Factories\Backend\HubFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinehaulLegsTest extends TestCase
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
        $this->actingAs($user)->get(route('admin.linehaul-legs.index'))->assertStatus(200);
    }

    public function test_create_persists_leg(): void
    {
        $user = $this->makeAdminUser();
        $hubA = HubFactory::new()->create();
        $hubB = HubFactory::new()->create();
        $cust = User::factory()->create();
        // create minimal shipment
        $shipmentId = \DB::table('shipments')->insertGetId([
            'customer_id' => $cust->id,
            'origin_branch_id' => $hubA->id,
            'dest_branch_id' => $hubB->id,
            'service_level' => 'STANDARD',
            'incoterm' => 'DAP',
            'price_amount' => 10.00,
            'currency' => 'USD',
            'current_status' => 'CREATED',
            'created_by' => $user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $payload = [
            'shipment_id' => $shipmentId,
            'mode' => 'AIR',
            'carrier' => 'KQ',
            'awb' => '123-45678901',
            'status' => 'PLANNED',
        ];
        $res = $this->actingAs($user)->post(route('admin.linehaul-legs.store'), $payload);
        $res->assertRedirect(route('admin.linehaul-legs.index'));
        $this->assertDatabaseHas('transport_legs', ['shipment_id' => $shipmentId, 'carrier' => 'KQ']);
    }

    public function test_policy_blocks_unauthorized(): void
    {
        $unauth = User::factory()->create();
        $this->actingAs($unauth)->get(route('admin.linehaul-legs.index'))->assertForbidden();
    }
}
