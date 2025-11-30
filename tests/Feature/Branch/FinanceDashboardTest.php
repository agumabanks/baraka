<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_aging_buckets_and_export_branch_scope(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
        ]);

        $payload = [
            'invoice_number' => 'INV-TEST',
            'shipment_id' => $shipment->id,
            'customer_id' => $shipment->customer_id,
            'branch_id' => $branch->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'currency' => 'USD',
            'status' => 'PENDING',
            'due_date' => now()->subDays(10),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'merchant_id')) {
            if (\Illuminate\Support\Facades\Schema::hasTable('merchants')) {
                $merchantId = \Illuminate\Support\Facades\DB::table('merchants')->insertGetId([
                    'business_name' => 'Test Merchant',
                    'current_balance' => 0,
                    'opening_balance' => 0,
                    'wallet_balance' => 0,
                    'vat' => 0,
                    'payment_period' => 2,
                    'return_charges' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $payload['merchant_id'] = $merchantId;
            } else {
                $payload['merchant_id'] = 1;
            }
        }

        Invoice::create($payload);

        $response = $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('branch.finance'));

        $response->assertStatus(200);
        $response->assertSee('Aging');

        $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('branch.finance.export', ['type' => 'invoices']))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
