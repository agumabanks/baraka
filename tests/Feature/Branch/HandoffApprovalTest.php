<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\BranchHandoff;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandoffApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_handoff_request_and_approval(): void
    {
        $origin = Branch::factory()->create();
        $dest = Branch::factory()->create();
        $requester = User::factory()->create([
            'primary_branch_id' => $origin->id,
            'permissions' => ['branch_manage'],
        ]);
        $approver = User::factory()->create([
            'primary_branch_id' => $dest->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
        ]);

        $this->actingAs($requester)
            ->withSession(['current_branch_id' => $origin->id])
            ->post(route('branch.operations.handoff.request'), [
                'shipment_id' => $shipment->id,
                'dest_branch_id' => $dest->id,
            ])
            ->assertRedirect();

        $handoff = BranchHandoff::first();
        $this->assertNotNull($handoff);
        $this->assertEquals('PENDING', $handoff->status);

        $this->actingAs($approver)
            ->withSession(['current_branch_id' => $dest->id])
            ->post(route('branch.operations.handoff.approve', $handoff))
            ->assertRedirect();

        $handoff->refresh();
        $this->assertEquals('APPROVED', $handoff->status);
        $this->assertEquals($approver->id, $handoff->approved_by);
    }
}
