<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_blocks_misroute(): void
    {
        $home = Branch::factory()->create();
        $other = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $home->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $other->id,
            'dest_branch_id' => $other->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $home->id])
            ->post(route('branch.operations.scan'), [
                'tracking_number' => $shipment->tracking_number,
            ])
            ->assertSessionHas('error', 'MISRouted: belongs to another branch');
    }
}
