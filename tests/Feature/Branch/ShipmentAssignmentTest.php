<?php

namespace Tests\Feature\Branch;

use App\Enums\ShipmentStatus;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\BranchAlert;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function createWorker(Branch $branch): BranchWorker
    {
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);

        return BranchWorker::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 1,
            'role' => 'courier',
        ]);
    }

    public function test_manual_assignment_advances_lifecycle(): void
    {
        $branch = Branch::factory()->create();
        $worker = $this->createWorker($branch);
        $actor = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'current_status' => ShipmentStatus::BOOKED->value,
            'status' => strtolower(ShipmentStatus::BOOKED->value),
        ]);

        $this->actingAs($actor)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.assign'), [
                'shipment_id' => $shipment->id,
                'worker_id' => $worker->id,
            ])
            ->assertRedirect();

        $shipment->refresh();
        $this->assertEquals($worker->id, $shipment->assigned_worker_id);
        $current = $shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status;
        $this->assertEquals(ShipmentStatus::PICKUP_SCHEDULED->value, $current);
    }

    public function test_auto_assignment_picks_worker_and_sets_pickup_scheduled(): void
    {
        $branch = Branch::factory()->create();
        $worker = $this->createWorker($branch);
        $actor = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'current_status' => ShipmentStatus::BOOKED->value,
            'status' => strtolower(ShipmentStatus::BOOKED->value),
        ]);

        $this->actingAs($actor)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.assign'), [
                'shipment_id' => $shipment->id,
                'auto' => true,
            ])
            ->assertRedirect();

        $shipment->refresh();
        $this->assertEquals($worker->id, $shipment->assigned_worker_id);
        $current = $shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status;
        $this->assertEquals(ShipmentStatus::PICKUP_SCHEDULED->value, $current);
    }

    public function test_hold_and_reroute_are_branch_scoped(): void
    {
        $origin = Branch::factory()->create();
        $dest = Branch::factory()->create();
        $newDest = Branch::factory()->create();
        $actor = User::factory()->create([
            'primary_branch_id' => $origin->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
            'current_status' => ShipmentStatus::BOOKED->value,
            'status' => strtolower(ShipmentStatus::BOOKED->value),
        ]);

        $this->actingAs($actor)
            ->withSession(['current_branch_id' => $origin->id])
            ->post(route('branch.operations.hold'), [
                'shipment_id' => $shipment->id,
                'reason' => 'Awaiting paperwork',
            ])
            ->assertRedirect();

        $shipment->refresh();
        $this->assertNotNull($shipment->held_at);
        $this->assertEquals('Awaiting paperwork', $shipment->hold_reason);

        $this->actingAs($actor)
            ->withSession(['current_branch_id' => $origin->id])
            ->post(route('branch.operations.reroute'), [
                'shipment_id' => $shipment->id,
                'dest_branch_id' => $newDest->id,
                'reason' => 'Capacity issue',
            ])
            ->assertRedirect();

        $shipment->refresh();
        $this->assertEquals($newDest->id, $shipment->dest_branch_id);
        $this->assertEquals($dest->id, $shipment->rerouted_from_branch_id);
        $this->assertEquals($actor->id, $shipment->rerouted_by);
    }

    public function test_assignment_blocked_when_branch_capacity_is_zero_during_maintenance(): void
    {
        $branch = Branch::factory()->create();
        $worker = $this->createWorker($branch);
        $actor = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'current_status' => ShipmentStatus::BOOKED->value,
            'status' => strtolower(ShipmentStatus::BOOKED->value),
        ]);

        BranchAlert::create([
            'branch_id' => $branch->id,
            'alert_type' => 'MAINTENANCE',
            'severity' => 'high',
            'status' => 'OPEN',
            'title' => 'Capacity down',
            'message' => 'No capacity',
            'context' => [
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addHour(),
                'capacity_factor' => 0,
            ],
            'triggered_at' => now(),
        ]);

        $this->actingAs($actor)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.assign'), [
                'shipment_id' => $shipment->id,
                'worker_id' => $worker->id,
            ])
            ->assertSessionHas('error');

        $shipment->refresh();
        $this->assertNull($shipment->assigned_worker_id);
    }
}
