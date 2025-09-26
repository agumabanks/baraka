<?php

namespace Tests\Feature;

use App\Models\Quotation;
use App\Models\User;
use App\Models\Backend\Hub;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PolicyBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_ops_cannot_view_other_branch_record()
    {
        $hubA = Hub::factory()->create();
        $hubB = Hub::factory()->create();
        $userA = User::factory()->create(['hub_id'=>$hubA->id]);
        $userB = User::factory()->create(['hub_id'=>$hubB->id]);

        $cust = \DB::table('customers')->insertGetId(['name'=>'X','created_at'=>now(),'updated_at'=>now()]);
        $quoteA = Quotation::create([
            'customer_id'=>$cust,
            'origin_branch_id'=>$hubA->id,
            'destination_country'=>'KE',
            'service_type'=>'EXP',
            'pieces'=>1,
            'weight_kg'=>1,
            'base_charge'=>0,
            'total_amount'=>0,
            'currency'=>'USD',
        ]);

        $this->assertFalse((new \App\Policies\BranchScopedPolicy())->view($userB, $quoteA));
        $this->assertTrue((new \App\Policies\BranchScopedPolicy())->view($userA, $quoteA));
    }
}

