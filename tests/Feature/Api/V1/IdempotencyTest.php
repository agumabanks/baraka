<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\ScanEvent;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function createMerchant(): User
    {
        return User::factory()->create([
            'user_type' => UserType::MERCHANT,
            'password' => bcrypt('password'),
        ]);
    }

    private function authenticate(User $user, string $deviceUuid): string
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ], [
            'device_uuid' => $deviceUuid,
        ])->assertStatus(200);

        $token = $response->json('data.token');
        $this->assertNotNull($token, 'Authentication token was not returned');

        return $token;
    }

    public function test_idempotent_shipment_events_return_cached_response(): void
    {
        $user = $this->createMerchant();
        $shipment = Shipment::factory()->create([
            'customer_id' => $user->id,
            'current_status' => ShipmentStatus::LINEHAUL_DEPARTED,
        ]);

        $token = $this->authenticate($user, 'idempotency-test-device');

        $idempotencyKey = 'test-event-key-123';
        $eventData = [
            'type' => ScanType::DESTINATION_ARRIVAL->value,
            'location' => 'Destination hub',
            'notes' => 'Arrived at destination',
        ];

        $firstResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/events", $eventData, [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'idempotency-test-device',
            'Idempotency-Key' => $idempotencyKey,
        ])->assertStatus(201);

        $firstEventId = $firstResponse->json('data.event.id');
        $this->assertNotNull($firstEventId);

        $secondResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/events", $eventData, [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'idempotency-test-device',
            'Idempotency-Key' => $idempotencyKey,
        ])->assertStatus(201);

        $secondEventId = $secondResponse->json('data.event.id');
        $this->assertSame($firstEventId, $secondEventId);

        $eventCount = ScanEvent::where('shipment_id', $shipment->id)
            ->where('type', ScanType::DESTINATION_ARRIVAL)
            ->count();

        $this->assertSame(1, $eventCount);
    }

    public function test_idempotent_pod_returns_cached_response(): void
    {
        $user = $this->createMerchant();
        $shipment = Shipment::factory()->create([
            'customer_id' => $user->id,
            'current_status' => ShipmentStatus::OUT_FOR_DELIVERY,
        ]);

        $token = $this->authenticate($user, 'pod-idempotency-device');

        $idempotencyKey = 'test-pod-key-456';
        $podData = [
            'otp' => '123456',
            'location' => 'Recipient address',
            'notes' => 'Delivered successfully',
        ];

        $firstResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/pod", $podData, [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'pod-idempotency-device',
            'Idempotency-Key' => $idempotencyKey,
        ])->assertStatus(201);

        $firstEventId = $firstResponse->json('data.event.id');
        $this->assertNotNull($firstEventId);

        $secondResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/pod", $podData, [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'pod-idempotency-device',
            'Idempotency-Key' => $idempotencyKey,
        ])->assertStatus(201);

        $secondEventId = $secondResponse->json('data.event.id');
        $this->assertSame($firstEventId, $secondEventId);

        $shipment->refresh();
        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->current_status);

        $podEventCount = ScanEvent::where('shipment_id', $shipment->id)
            ->where('type', ScanType::DELIVERY_CONFIRMED)
            ->where('notes', 'like', 'POD:%')
            ->count();

        $this->assertSame(1, $podEventCount);
    }

    public function test_idempotency_returns_conflict_when_body_mismatch(): void
    {
        $user = $this->createMerchant();
        $shipment = Shipment::factory()->create([
            'customer_id' => $user->id,
            'current_status' => ShipmentStatus::LINEHAUL_DEPARTED,
        ]);

        $token = $this->authenticate($user, 'mismatch-test-device');

        $idempotencyKey = 'mismatch-key-789';

        $this->postJson("/api/v1/shipments/{$shipment->id}/events", [
            'type' => ScanType::LINEHAUL_DEPARTED->value,
            'location' => 'Origin hub',
            'notes' => 'Departed',
        ], [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'mismatch-test-device',
            'Idempotency-Key' => $idempotencyKey,
        ])->assertStatus(201);

        $this->postJson("/api/v1/shipments/{$shipment->id}/events", [
            'type' => ScanType::LINEHAUL_DEPARTED->value,
            'location' => 'Different location',
            'notes' => 'Different notes',
        ], [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'mismatch-test-device',
            'Idempotency-Key' => $idempotencyKey,
        ])->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Idempotency key conflict',
            ]);
    }

    public function test_idempotency_key_is_required_for_write_operations(): void
    {
        $user = $this->createMerchant();
        $shipment = Shipment::factory()->create([
            'customer_id' => $user->id,
        ]);

        $token = $this->authenticate($user, 'no-key-device');

        $this->postJson("/api/v1/shipments/{$shipment->id}/events", [
            'type' => ScanType::ORIGIN_ARRIVAL->value,
            'location' => 'Test location',
        ], [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'no-key-device',
        ])->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }
}
