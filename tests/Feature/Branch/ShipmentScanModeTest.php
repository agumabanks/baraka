<?php

namespace Tests\Feature\Branch;

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Models\Backend\Branch;
use App\Models\ScanEvent;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentScanModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unload_scan_blocks_other_branch(): void
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
                'mode' => 'unload',
            ])
            ->assertSessionHas('error', 'MISRouted: belongs to another branch');
    }

    public function test_route_scan_accepts(): void
    {
        $home = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $home->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $home->id,
            'dest_branch_id' => $home->id,
            'current_status' => ShipmentStatus::CUSTOMS_CLEARED->value,
            'status' => strtolower(ShipmentStatus::CUSTOMS_CLEARED->value),
        ]);

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $home->id])
            ->post(route('branch.operations.scan'), [
                'tracking_number' => $shipment->tracking_number,
                'mode' => 'route',
            ])
            ->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals(ShipmentStatus::OUT_FOR_DELIVERY->value, $shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status);
        $this->assertDatabaseHas('scan_events', [
            'shipment_id' => $shipment->id,
            'type' => ScanType::OUT_FOR_DELIVERY->value,
        ]);
    }

    public function test_delivery_scan_updates_status_and_records_event(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'current_status' => ShipmentStatus::OUT_FOR_DELIVERY->value,
            'status' => strtolower(ShipmentStatus::OUT_FOR_DELIVERY->value),
        ]);

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.scan'), [
                'tracking_number' => $shipment->tracking_number,
                'mode' => 'delivery',
            ])
            ->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals(ShipmentStatus::DELIVERED->value, $shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status);
        $this->assertInstanceOf(ScanEvent::class, ScanEvent::where('shipment_id', $shipment->id)->first());
    }

    public function test_returns_scan_for_delivered_allows_reverse_flow(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'current_status' => ShipmentStatus::DELIVERED->value,
            'status' => strtolower(ShipmentStatus::DELIVERED->value),
        ]);

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.scan'), [
                'tracking_number' => $shipment->tracking_number,
                'mode' => 'returns',
            ])
            ->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals(ShipmentStatus::RETURN_INITIATED->value, $shipment->current_status instanceof ShipmentStatus ? $shipment->current_status->value : $shipment->current_status);
    }
}
