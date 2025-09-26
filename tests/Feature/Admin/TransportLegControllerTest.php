<?php

namespace Tests\Feature\Admin;

use App\Models\Backend\Role;
use App\Models\Backend\Hub;
use App\Models\Shipment;
use App\Models\TransportLeg;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportLegControllerTest extends TestCase
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
        $user = User::factory()->create(['role_id' => $role->id]);
        return $user;
    }

    private function seedShipment(): Shipment
    {
        $creator = User::factory()->create();
        $customer = User::factory()->create();
        $origin = Hub::factory()->create();
        $dest = Hub::factory()->create();
        return Shipment::create([
            'customer_id' => $customer->id,
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
            'service_level' => 'STANDARD',
            'incoterm' => 'DAP',
            'price_amount' => 10.00,
            'currency' => 'EUR',
            'created_by' => $creator->id,
        ]);
    }

    public function test_index_requires_auth_and_policy(): void
    {
        $this->get(route('admin.linehaul-legs.index'))->assertRedirect();

        $user = User::factory()->create(); // no role -> denied by policy
        $this->actingAs($user)
            ->get(route('admin.linehaul-legs.index'))
            ->assertForbidden();

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.linehaul-legs.index'))
            ->assertOk();
    }

    public function test_store_validates_and_creates(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        // Missing required -> validation fails
        $this->post(route('admin.linehaul-legs.store'), [])->assertSessionHasErrors(['shipment_id','mode','status']);

        $shipment = $this->seedShipment();

        $res = $this->post(route('admin.linehaul-legs.store'), [
            'shipment_id' => $shipment->id,
            'mode' => 'AIR',
            'status' => 'PLANNED',
            'carrier' => 'KQ',
            'awb' => '123-12345678',
        ]);
        $res->assertRedirect(route('admin.linehaul-legs.index'));
        $this->assertDatabaseHas('transport_legs', [
            'shipment_id' => $shipment->id,
            'mode' => 'AIR',
            'carrier' => 'KQ',
            'status' => 'PLANNED',
        ]);
    }

    public function test_update_enforced_by_policy(): void
    {
        $admin = $this->makeAdmin();
        $shipment = $this->seedShipment();
        $leg = TransportLeg::create([
            'shipment_id' => $shipment->id,
            'mode' => 'AIR',
            'status' => 'PLANNED',
        ]);

        // Unauthorized user
        $user = User::factory()->create();
        $this->actingAs($user)
            ->put(route('admin.linehaul-legs.update', $leg), ['status' => 'ARRIVED'])
            ->assertForbidden();

        // Admin can update
        $this->actingAs($admin)
            ->put(route('admin.linehaul-legs.update', $leg), ['status' => 'ARRIVED'])
            ->assertRedirect(route('admin.linehaul-legs.index'));

        $this->assertDatabaseHas('transport_legs', ['id' => $leg->id, 'status' => 'ARRIVED']);
    }
}
