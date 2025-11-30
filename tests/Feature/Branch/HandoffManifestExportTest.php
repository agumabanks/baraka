<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\BranchHandoff;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandoffManifestExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_can_export_batch_manifest_csv(): void
    {
        $origin = Branch::factory()->create();
        $dest = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $origin->id,
            'permissions' => ['branch_manage'],
        ]);

        $shipmentA = Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
        ]);
        $shipmentB = Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
        ]);

        foreach ([$shipmentA, $shipmentB] as $shipment) {
            BranchHandoff::create([
                'shipment_id' => $shipment->id,
                'origin_branch_id' => $origin->id,
                'dest_branch_id' => $dest->id,
                'requested_by' => $user->id,
                'status' => 'APPROVED',
                'expected_hand_off_at' => now()->addHour(),
            ]);
        }

        $response = $this->actingAs($user)
            ->withSession(['current_branch_id' => $origin->id])
            ->get(route('branch.operations.handoff.manifest.batch', [
                'format' => 'csv',
                'status' => 'APPROVED',
                'direction' => 'outbound',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $response->assertSee($shipmentA->tracking_number);
        $response->assertSee($shipmentB->tracking_number);
    }

    public function test_branch_can_export_single_manifest_pdf(): void
    {
        $origin = Branch::factory()->create();
        $dest = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $dest->id,
            'permissions' => ['branch_manage'],
        ]);

        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
        ]);

        $handoff = BranchHandoff::create([
            'shipment_id' => $shipment->id,
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
            'requested_by' => $user->id,
            'status' => 'APPROVED',
            'expected_hand_off_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_branch_id' => $dest->id])
            ->get(route('branch.operations.handoff.manifest', [$handoff, 'format' => 'pdf']));

        $response->assertOk();
        $response->assertSee((string) $handoff->id);
    }
}
