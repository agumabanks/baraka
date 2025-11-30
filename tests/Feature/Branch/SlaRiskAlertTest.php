<?php

namespace Tests\Feature\Branch;

use App\Enums\ShipmentStatus;
use App\Models\Backend\Branch;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaRiskAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_sla_filter_returns_at_risk_shipments(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);

        $riskShipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'expected_delivery_date' => now()->addHours(4),
            'current_status' => ShipmentStatus::OUT_FOR_DELIVERY->value,
            'status' => strtolower(ShipmentStatus::OUT_FOR_DELIVERY->value),
        ]);

        $safeShipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'expected_delivery_date' => now()->addDays(3),
            'current_status' => ShipmentStatus::BOOKED->value,
            'status' => strtolower(ShipmentStatus::BOOKED->value),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('branch.operations', ['sla_risk' => 1]));

        $shipments = $response->viewData('shipments');
        $this->assertTrue($shipments->contains(fn ($s) => $s->id === $riskShipment->id));
        $this->assertFalse($shipments->contains(fn ($s) => $s->id === $safeShipment->id));
    }

    public function test_can_raise_and_resolve_sla_alert(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
            'expected_delivery_date' => now()->addHours(2),
            'current_status' => ShipmentStatus::OUT_FOR_DELIVERY->value,
            'status' => strtolower(ShipmentStatus::OUT_FOR_DELIVERY->value),
        ]);

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.alerts.raise'), [
                'shipment_id' => $shipment->id,
                'severity' => 'critical',
                'message' => 'At risk of SLA breach',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('branch_alerts', [
            'branch_id' => $branch->id,
            'alert_type' => 'SLA_RISK',
            'status' => 'OPEN',
        ]);

        $alertId = \App\Models\BranchAlert::first()->id;

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.alerts.resolve', $alertId))
            ->assertRedirect();

        $this->assertDatabaseHas('branch_alerts', [
            'id' => $alertId,
            'status' => 'RESOLVED',
        ]);
    }
}
