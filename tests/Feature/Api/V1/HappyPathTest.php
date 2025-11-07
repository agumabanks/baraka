<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Jobs\SendWebhookNotification;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HappyPathTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(User $user, string $deviceUuid, array $headers = []): string
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ], array_merge([
            'device_uuid' => $deviceUuid,
        ], $headers))->assertStatus(200);

        $token = $response->json('data.token');
        $this->assertNotNull($token, 'Authentication token was not returned');

        return $token;
    }

    public function test_complete_shipment_flow_from_quote_to_delivery(): void
    {
        Queue::fake();

        $merchant = User::factory()->create([
            'email' => 'merchant@test.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::MERCHANT,
        ]);

        $driver = User::factory()->create([
            'email' => 'driver@test.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::DELIVERYMAN,
        ]);

        $deviceUuid = 'happy-path-device-uuid';

        $merchantToken = $this->authenticate($merchant, $deviceUuid, [
            'platform' => 'ios',
        ]);

        $quoteResponse = $this->postJson('/api/v1/quotes', [
            'origin_address' => '123 Origin St',
            'destination_address' => '456 Dest St',
            'weight_kg' => 5.0,
            'service_type' => 'express',
        ], [
            'Authorization' => 'Bearer ' . $merchantToken,
            'device_uuid' => $deviceUuid,
        ])->assertStatus(201);

        $quoteId = $quoteResponse->json('data.quote.id');
        $this->assertNotNull($quoteId);

        $shipmentResponse = $this->postJson('/api/v1/shipments', [
            'quote_id' => $quoteId,
            'origin_address' => '123 Origin St',
            'destination_address' => '456 Dest St',
            'recipient_name' => 'John Doe',
            'recipient_phone' => '+1234567890',
            'parcels' => [
                [
                    'description' => 'Test package',
                    'weight_kg' => 5.0,
                    'length_cm' => 30,
                    'width_cm' => 20,
                    'height_cm' => 10,
                ],
            ],
        ], [
            'Authorization' => 'Bearer ' . $merchantToken,
            'device_uuid' => $deviceUuid,
            'Idempotency-Key' => 'shipment-create-123',
        ])->assertStatus(201);

        $shipmentId = $shipmentResponse->json('data.shipment.id');
        $this->assertNotNull($shipmentId);

        $this->getJson("/api/v1/shipments/{$shipmentId}/label", [
            'Authorization' => 'Bearer ' . $merchantToken,
            'device_uuid' => $deviceUuid,
        ])->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['label_url'],
            ]);

        $this->postJson('/api/v1/pickups', [
            'shipment_id' => $shipmentId,
            'pickup_date' => now()->addDay()->format('Y-m-d'),
            'pickup_time' => '10:00',
            'notes' => 'Please call before pickup',
        ], [
            'Authorization' => 'Bearer ' . $merchantToken,
            'device_uuid' => $deviceUuid,
            'Idempotency-Key' => 'pickup-schedule-123',
        ])->assertStatus(201);

        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::ADMIN,
        ]);

        $adminToken = $this->authenticate($admin, 'admin-device-uuid', [
            'platform' => 'web',
        ]);

        $this->postJson("/api/v1/shipments/{$shipmentId}/assign", [
            'driver_id' => $driver->id,
        ], [
            'Authorization' => 'Bearer ' . $adminToken,
            'device_uuid' => 'admin-device-uuid',
            'Idempotency-Key' => 'assign-driver-123',
        ])->assertStatus(200);

        $this->postJson("/api/v1/shipments/{$shipmentId}/events", [
            'type' => ScanType::OUT_FOR_DELIVERY->value,
            'location' => 'Driver location',
            'notes' => 'Out for delivery',
        ], [
            'Authorization' => 'Bearer ' . $adminToken,
            'device_uuid' => 'admin-device-uuid',
            'Idempotency-Key' => 'out-for-delivery-123',
        ])->assertStatus(201);

        $shipment = Shipment::find($shipmentId);
        $this->assertNotNull($shipment);
        $this->assertSame(ShipmentStatus::OUT_FOR_DELIVERY, $shipment->current_status);

        $this->postJson("/api/v1/shipments/{$shipmentId}/pod", [
            'otp' => '123456',
            'location' => 'Recipient address',
            'notes' => 'Delivered successfully',
            'recipient_signature' => 'base64signature',
        ], [
            'Authorization' => 'Bearer ' . $adminToken,
            'device_uuid' => 'admin-device-uuid',
            'Idempotency-Key' => 'pod-submit-123',
        ])->assertStatus(201);

        $shipment->refresh();
        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->current_status);

        $this->postJson("/api/v1/shipments/{$shipmentId}/events", [
            'type' => ScanType::DELIVERY_CONFIRMED->value,
            'location' => 'Recipient address',
            'notes' => 'Delivered',
        ], [
            'Authorization' => 'Bearer ' . $adminToken,
            'device_uuid' => 'admin-device-uuid',
            'Idempotency-Key' => 'delivered-event-123',
        ])->assertStatus(201);

        Queue::assertPushed(SendWebhookNotification::class, 1);
    }

    public function test_shipment_flow_fails_without_authentication(): void
    {
        $this->postJson('/api/v1/quotes', [
            'origin_address' => '123 Origin St',
            'destination_address' => '456 Dest St',
            'weight_kg' => 5.0,
        ])->assertStatus(401);
    }

    public function test_shipment_creation_fails_with_invalid_data(): void
    {
        $user = User::factory()->create([
            'user_type' => UserType::MERCHANT,
            'password' => bcrypt('password'),
        ]);

        $token = $this->authenticate($user, 'test-device');

        $this->postJson('/api/v1/shipments', [
            // Missing required fields intentionally
        ], [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'test-device',
        ])->assertStatus(422);
    }
}
