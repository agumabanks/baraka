<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentManifestExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_can_export_shipment_manifest_csv(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);

        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'current_status' => 'BOOKED',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('branch.operations.manifest.shipments', [
                'ids' => $shipment->id,
                'format' => 'csv',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $response->assertSee($shipment->tracking_number);
    }
}
