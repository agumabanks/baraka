<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarrierServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_carrier_and_service(): void
    {
        $user = User::factory()->create();
        $user->user_type = 'admin';
        $user->save();

        $this->actingAs($user)
            ->post(route('admin.carriers.store'), [
                'name' => 'DHL', 'code' => 'DHL', 'mode' => 'air',
            ])->assertStatus(302);

        $carrierId = \DB::table('carriers')->value('id');

        $this->actingAs($user)
            ->post(route('admin.carrier-services.store'), [
                'carrier_id' => $carrierId,
                'code' => 'EXP',
                'name' => 'Express',
                'requires_eawb' => 1,
            ])->assertStatus(302);

        $this->assertDatabaseHas('carrier_services', ['code' => 'EXP']);
    }
}
