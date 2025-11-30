<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_limit_flag_and_branch_scope(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);

        $client = Client::create([
            'primary_branch_id' => $branch->id,
            'business_name' => 'Test Co',
            'status' => 'active',
            'credit_limit' => 50,
            'contacts' => [['name' => 'Ops', 'email' => 'ops@test.co']],
            'addresses' => [['type' => 'billing', 'line1' => '123 Main']],
        ]);

        $invoicePayload = [
            'invoice_number' => 'INV-1',
            'customer_id' => $client->id,
            'branch_id' => $branch->id,
            'subtotal' => 60,
            'tax_amount' => 0,
            'total_amount' => 60,
            'currency' => 'USD',
            'status' => 'PENDING',
            'due_date' => now()->addDays(7),
        ];

        if (Schema::hasColumn('invoices', 'merchant_id')) {
            if (Schema::hasTable('merchants')) {
                $invoicePayload['merchant_id'] = DB::table('merchants')->insertGetId([
                    'business_name' => 'Client Merchant',
                    'current_balance' => 0,
                    'opening_balance' => 0,
                    'wallet_balance' => 0,
                    'vat' => 0,
                    'payment_period' => 2,
                    'return_charges' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $invoicePayload['merchant_id'] = 1;
            }
        }

        Invoice::create($invoicePayload);

        $this->assertTrue($client->fresh()->is_over_limit);

        $response = $this->actingAs($user)
            ->withSession(['current_branch_id' => $branch->id])
            ->get(route('branch.clients'));

        $response->assertStatus(200);
        $response->assertSee('Test Co');
    }
}
