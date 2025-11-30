<?php

namespace Tests\Feature\Branch;

use App\Enums\ShipmentStatus;
use App\Models\Backend\Branch;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_creates_shipment_scoped_to_branch_and_sets_booked_timestamp(): void
    {
        $branch = Branch::factory()->create();
        $destBranch = Branch::factory()->create();
        $customer = User::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.shipments.store'), [
                'customer_id' => $customer->id,
                'dest_branch_id' => $destBranch->id,
                'service_level' => 'express',
                'payer_type' => 'sender',
                'parcels' => [
                    [
                        'weight_kg' => 1.2,
                        'length_cm' => 10,
                        'width_cm' => 10,
                        'height_cm' => 10,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $shipment = Shipment::first();
        $this->assertNotNull($shipment);
        $this->assertSame($branch->id, $shipment->origin_branch_id);
        $current = $shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status;
        $this->assertEquals(ShipmentStatus::BOOKED->value, $current);
        $this->assertNotNull($shipment->booked_at);
    }

    public function test_branch_user_cannot_view_other_branch_shipment(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branchA->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branchB->id,
            'dest_branch_id' => $branchB->id,
            'current_status' => ShipmentStatus::BOOKED->value,
            'status' => strtolower(ShipmentStatus::BOOKED->value),
        ]);

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $branchA->id])
            ->get(route('branch.shipments.show', $shipment))
            ->assertStatus(403);
    }

    public function test_status_transition_enforces_machine_and_sets_sla_timestamp(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'current_status' => ShipmentStatus::BOOKED->value,
            'status' => strtolower(ShipmentStatus::BOOKED->value),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.status'), [
                'shipment_id' => $shipment->id,
                'status' => ShipmentStatus::PICKUP_SCHEDULED->value,
            ]);

        $response->assertRedirect();

        $shipment->refresh();
        $current = $shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status;
        $this->assertEquals(ShipmentStatus::PICKUP_SCHEDULED->value, $current);
        $this->assertNotNull($shipment->pickup_scheduled_at);

        $this->assertDatabaseHas('shipment_transitions', [
            'shipment_id' => $shipment->id,
            'to_status' => ShipmentStatus::PICKUP_SCHEDULED->value,
        ]);
    }
}
