<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ZoneLaneFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_zone_and_lane(): void
    {
        $user = User::factory()->create();
        $user->user_type = 'admin';
        $user->save();

        $this->actingAs($user)
            ->post(route('admin.zones.store'), [
                'code' => 'EU', 'name' => 'European Union', 'countries' => ['DE','FR']
            ])->assertStatus(302);

        $this->actingAs($user)
            ->post(route('admin.zones.store'), [
                'code' => 'UG', 'name' => 'Uganda', 'countries' => ['UG']
            ])->assertStatus(302);

        $zones = \DB::table('zones')->pluck('id','code');

        $this->actingAs($user)
            ->post(route('admin.lanes.store'), [
                'origin_zone_id' => $zones['EU'],
                'dest_zone_id' => $zones['UG'],
                'mode' => 'air',
                'std_transit_days' => 4,
                'dim_divisor' => 6000,
                'eawb_required' => 1,
            ])->assertStatus(302);

        $this->assertDatabaseHas('lanes', ['std_transit_days' => 4, 'dim_divisor' => 6000]);
    }
}

