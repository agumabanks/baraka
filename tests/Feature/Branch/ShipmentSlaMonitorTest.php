<?php

namespace Tests\Feature\Branch;

use App\Enums\ShipmentStatus;
use App\Models\Backend\Branch;
use App\Models\BranchAlert;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ShipmentSlaMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipment_overdue_generates_critical_alerts_for_origin_and_dest(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $origin = Branch::factory()->create();
        $dest = Branch::factory()->create();

        Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
            'expected_delivery_date' => Carbon::now()->subHour(),
            'current_status' => ShipmentStatus::OUT_FOR_DELIVERY->value,
        ]);

        Artisan::call('shipment:sla-monitor', ['--window' => 24]);

        $this->assertDatabaseHas('branch_alerts', [
            'alert_type' => 'SHIPMENT_OVERDUE',
            'severity' => 'CRITICAL',
            'branch_id' => $origin->id,
        ]);

        $this->assertDatabaseHas('branch_alerts', [
            'alert_type' => 'SHIPMENT_OVERDUE',
            'severity' => 'CRITICAL',
            'branch_id' => $dest->id,
        ]);
    }

    public function test_shipment_approaching_generates_warning_alert(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $origin = Branch::factory()->create();
        $dest = Branch::factory()->create();

        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
            'expected_delivery_date' => Carbon::now()->addHours(6),
            'current_status' => ShipmentStatus::AT_DESTINATION_HUB->value,
        ]);

        Artisan::call('shipment:sla-monitor', ['--window' => 12]);

        $this->assertDatabaseHas('branch_alerts', [
            'alert_type' => 'SHIPMENT_SLA',
            'severity' => 'WARNING',
            'branch_id' => $dest->id,
            'context->shipment_id' => $shipment->id,
        ]);
    }
}
