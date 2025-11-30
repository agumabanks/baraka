<?php

namespace Tests\Feature\Api;

use App\Models\Backend\Branch;
use App\Models\Shipment;
use App\Models\TrackerEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TrackingHooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_event_ingest_and_tracking_response(): void
    {
        $branch = Branch::factory()->create();
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $branch->id,
            'dest_branch_id' => $branch->id,
        ]);

        $payload = [
            'shipment_public_token' => $shipment->public_token,
            'branch_id' => $branch->id,
            'tracker_id' => 'TK-123',
            'temperature_c' => 12.5,
            'battery_percent' => 85,
            'latitude' => 1.23,
            'longitude' => 2.34,
        ];

        $this->postJson(route('tracking.hooks.ingest'), $payload)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('tracker_events', [
            'shipment_id' => $shipment->id,
            'tracker_id' => 'TK-123',
        ]);

        $response = $this->getJson(route('tracking.show', $shipment->public_token))
            ->assertStatus(200)
            ->json('data.shipment.tracker_events');

        $this->assertNotEmpty($response);
        $this->assertEquals('TK-123', $response[0]['tracker_id']);
    }

    public function test_signed_public_tracking_is_branch_scoped(): void
    {
        $origin = Branch::factory()->create();
        $other = Branch::factory()->create();
        $shipment = Shipment::factory()->create([
            'origin_branch_id' => $origin->id,
            'dest_branch_id' => $origin->id,
        ]);

        $signed = URL::signedRoute('public.track', [
            'token' => $shipment->public_token,
            'branch' => $origin->id,
        ]);

        $this->getJson($signed)->assertStatus(200);

        $badSigned = URL::signedRoute('public.track', [
            'token' => $shipment->public_token,
            'branch' => $other->id,
        ]);

        $this->getJson($badSigned)->assertStatus(403);
    }
}
