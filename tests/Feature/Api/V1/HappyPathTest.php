<?php

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('complete shipment flow from quote to delivery', function () {
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

    // 1. Login
    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'merchant@test.com',
        'password' => 'password',
    ], [
        'device_uuid' => $deviceUuid,
        'platform' => 'ios',
    ]);

    $loginResponse->assertStatus(200);
    $token = $loginResponse->json('data.token');

    // 2. Create quote
    $quoteResponse = $this->postJson('/api/v1/quotes', [
        'origin_address' => '123 Origin St',
        'destination_address' => '456 Dest St',
        'weight_kg' => 5.0,
        'service_type' => 'express',
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => $deviceUuid,
    ]);

    $quoteResponse->assertStatus(201);
    $quoteId = $quoteResponse->json('data.quote.id');

    // 3. Create shipment
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
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => $deviceUuid,
        'Idempotency-Key' => 'shipment-create-123',
    ]);

    $shipmentResponse->assertStatus(201);
    $shipmentId = $shipmentResponse->json('data.shipment.id');

    // 4. Get label
    $labelResponse = $this->getJson("/api/v1/shipments/{$shipmentId}/label", [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => $deviceUuid,
    ]);

    $labelResponse->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['label_url'],
        ]);

    // 5. Schedule pickup
    $pickupResponse = $this->postJson('/api/v1/pickups', [
        'shipment_id' => $shipmentId,
        'pickup_date' => now()->addDay()->format('Y-m-d'),
        'pickup_time' => '10:00',
        'notes' => 'Please call before pickup',
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => $deviceUuid,
        'Idempotency-Key' => 'pickup-schedule-123',
    ]);

    $pickupResponse->assertStatus(201);

    // 6. Assign driver (admin action - need admin token)
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'user_type' => UserType::ADMIN,
    ]);

    $adminLoginResponse = $this->postJson('/api/v1/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ], [
        'device_uuid' => 'admin-device-uuid',
        'platform' => 'web',
    ]);

    $adminToken = $adminLoginResponse->json('data.token');

    $assignResponse = $this->postJson("/api/v1/shipments/{$shipmentId}/assign", [
        'driver_id' => $driver->id,
    ], [
        'Authorization' => 'Bearer '.$adminToken,
        'device_uuid' => 'admin-device-uuid',
        'Idempotency-Key' => 'assign-driver-123',
    ]);

    $assignResponse->assertStatus(200);

    // 7. Post out_for_delivery event
    $eventResponse = $this->postJson("/api/v1/shipments/{$shipmentId}/events", [
        'type' => ScanType::OUT_FOR_DELIVERY->value,
        'location' => 'Driver location',
        'notes' => 'Out for delivery',
    ], [
        'Authorization' => 'Bearer '.$adminToken,
        'device_uuid' => 'admin-device-uuid',
        'Idempotency-Key' => 'out-for-delivery-123',
    ]);

    $eventResponse->assertStatus(201);

    // Verify shipment status updated
    $shipment = Shipment::find($shipmentId);
    expect($shipment->current_status)->toBe(ShipmentStatus::OUT_FOR_DELIVERY);

    // 8. POD with OTP/photo
    $podResponse = $this->postJson("/api/v1/shipments/{$shipmentId}/pod", [
        'otp' => '123456',
        'location' => 'Recipient address',
        'notes' => 'Delivered successfully',
        'recipient_signature' => 'base64signature', // Mock signature
    ], [
        'Authorization' => 'Bearer '.$adminToken,
        'device_uuid' => 'admin-device-uuid',
        'Idempotency-Key' => 'pod-submit-123',
    ]);

    $podResponse->assertStatus(201);

    // Verify shipment status updated to delivered
    $shipment->refresh();
    expect($shipment->current_status)->toBe(ShipmentStatus::DELIVERED);

    // 9. Delivered event (should already be handled by POD)
    // This might be redundant, but let's test it
    $deliveredEventResponse = $this->postJson("/api/v1/shipments/{$shipmentId}/events", [
        'type' => ScanType::DELIVERED->value,
        'location' => 'Recipient address',
        'notes' => 'Delivered',
    ], [
        'Authorization' => 'Bearer '.$adminToken,
        'device_uuid' => 'admin-device-uuid',
        'Idempotency-Key' => 'delivered-event-123',
    ]);

    $deliveredEventResponse->assertStatus(201);

    // 10. Mock webhook enqueued (verify job was dispatched)
    Queue::assertPushed(\App\Jobs\SendWebhookNotification::class, 1);
});

test('shipment flow fails without authentication', function () {
    $response = $this->postJson('/api/v1/quotes', [
        'origin_address' => '123 Origin St',
        'destination_address' => '456 Dest St',
        'weight_kg' => 5.0,
    ]);

    $response->assertStatus(401);
});

test('shipment creation fails with invalid data', function () {
    $user = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'test-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->postJson('/api/v1/shipments', [
        // Missing required fields
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'test-device',
    ]);

    $response->assertStatus(422);
});
