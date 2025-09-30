<?php

namespace Tests\Feature;

use App\Models\Backend\Hub;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_ops_can_create_quotation()
    {
        $hub = Hub::factory()->create();
        $user = User::factory()->create(['hub_id' => $hub->id]);
        // Attach a role if roles system is required; fall back to user_type
        $user->user_type = 'branch_ops';
        $user->save();

        $customerId = \DB::table('customers')->insertGetId([
            'name' => 'ACME', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $payload = [
            'customer_id' => $customerId,
            'destination_country' => 'KE',
            'service_type' => 'EXPRESS',
            'pieces' => 1,
            'weight_kg' => 2.5,
            'volume_cm3' => 30000,
            'dim_factor' => 5000,
            'base_charge' => 10.00,
            'currency' => 'USD',
        ];

        $res = $this->actingAs($user)->post(route('admin.quotations.store'), $payload);
        $res->assertStatus(302);
        $this->assertDatabaseCount('quotations', 1);
    }
}
