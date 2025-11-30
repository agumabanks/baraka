<?php

namespace Tests\Feature\Branch;

use App\Enums\ShipmentStatus;
use App\Models\Backend\Branch;
use App\Models\ChargeLine;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceAutoInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_invoice_on_delivery(): void
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

        ChargeLine::create([
            'shipment_id' => $shipment->id,
            'charge_type' => 'base',
            'description' => 'Base charge',
            'amount' => 100,
            'currency' => 'USD',
        ]);

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.operations.status'), [
                'shipment_id' => $shipment->id,
                'status' => ShipmentStatus::DELIVERED->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('invoices', 1);
    }
}
