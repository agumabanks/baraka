<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->user = User::factory()->create([
            'primary_branch_id' => $this->branch->id,
        ]);
    }

    /** @test */
    public function it_displays_shipments_dashboard()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('branch.shipments'));

        $response->assertStatus(200);
        $response->assertViewIs('branch.shipments');
        $response->assertViewHas(['branch', 'shipments', 'stats']);
    }

    /** @test */
    public function it_filters_inbound_shipments()
    {
        $customer = Customer::factory()->create();
        
        // Create inbound shipment
        Shipment::factory()->create([
            'dest_branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
        ]);

        // Create outbound shipment
        Shipment::factory()->create([
            'origin_branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('branch.shipments', ['direction' => 'inbound']));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('shipments')->total());
    }

    /** @test */
    public function it_filters_outbound_shipments()
    {
        $customer = Customer::factory()->create();
        
        Shipment::factory()->create([
            'origin_branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('branch.shipments', ['direction' => 'outbound']));

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->viewData('shipments')->total());
    }

    /** @test */
    public function it_calculates_statistics_correctly()
    {
        $customer = Customer::factory()->create();
        
        // Create shipments with different statuses
        Shipment::factory()->create([
            'origin_branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'current_status' => 'IN_TRANSIT',
        ]);

        Shipment::factory()->create([
            'origin_branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'current_status' => 'DELIVERED',
            'delivered_at' => now(),
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('branch.shipments'));

        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('in_transit', $stats);
        $this->assertArrayHasKey('delivered_today', $stats);
        $this->assertEquals(2, $stats['total']);
    }

    /** @test */
    public function it_enforces_branch_isolation()
    {
        $otherBranch = Branch::factory()->create();
        $customer = Customer::factory()->create();
        
        Shipment::factory()->create([
            'origin_branch_id' => $otherBranch->id,
            'dest_branch_id' => $otherBranch->id,
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('branch.shipments'));

        // Should not see other branch's shipments
        $this->assertEquals(0, $response->viewData('shipments')->total());
    }
}
