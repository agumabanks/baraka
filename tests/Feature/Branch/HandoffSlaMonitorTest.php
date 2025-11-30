<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\BranchHandoff;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HandoffSlaMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_handoffs_raise_critical_alerts_for_origin_and_dest(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 12:00:00'));

        $origin = Branch::factory()->create();
        $dest = Branch::factory()->create();

        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
        ]);

        BranchHandoff::create([
            'shipment_id' => $shipment->id,
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $dest->id,
            'status' => 'APPROVED',
            'expected_hand_off_at' => Carbon::now()->subMinutes(30),
        ]);

        Artisan::call('handoff:sla-monitor');

        $this->assertDatabaseHas('branch_alerts', [
            'alert_type' => 'HANDOFF_OVERDUE',
            'branch_id' => $origin->id,
            'severity' => 'CRITICAL',
        ]);

        $this->assertDatabaseHas('branch_alerts', [
            'alert_type' => 'HANDOFF_OVERDUE',
            'branch_id' => $dest->id,
            'severity' => 'CRITICAL',
        ]);
    }
}
