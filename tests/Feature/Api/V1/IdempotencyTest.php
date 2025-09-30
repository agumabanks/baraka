<?php

use App\Enums\ScanType;
use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

test('idempotent shipment events return cached response', function () {
    $user = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $user->id,
        'current_status' => ShipmentStatus::IN_TRANSIT,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'idempotency-test-device',
    ]);

    $token = $loginResponse->json('data.token');

    $idempotencyKey = 'test-event-key-123';
    $eventData = [
        'type' => ScanType::ARRIVE_DEST->value,
        'location' => 'Destination hub',
        'notes' => 'Arrived at destination',
    ];

    // First request
    $firstResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/events", $eventData, [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'idempotency-test-device',
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $firstResponse->assertStatus(201);
    $firstEventId = $firstResponse->json('data.event.id');

    // Second request with same key and data
    $secondResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/events", $eventData, [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'idempotency-test-device',
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $secondResponse->assertStatus(201);
    $secondEventId = $secondResponse->json('data.event.id');

    // Should return same event ID (cached response)
    expect($firstEventId)->toBe($secondEventId);

    // Verify only one event was created in database
    $eventCount = \App\Models\ScanEvent::where('shipment_id', $shipment->id)
        ->where('type', ScanType::ARRIVE_DEST)
        ->count();
    expect($eventCount)->toBe(1);
});

test('idempotent POD returns cached response', function () {
    $user = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $user->id,
        'current_status' => ShipmentStatus::OUT_FOR_DELIVERY,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'pod-idempotency-device',
    ]);

    $token = $loginResponse->json('data.token');

    $idempotencyKey = 'test-pod-key-456';
    $podData = [
        'otp' => '123456',
        'location' => 'Recipient address',
        'notes' => 'Delivered successfully',
    ];

    // First POD request
    $firstResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/pod", $podData, [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'pod-idempotency-device',
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $firstResponse->assertStatus(201);
    $firstEventId = $firstResponse->json('data.event.id');

    // Second POD request with same key and data
    $secondResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/pod", $podData, [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'pod-idempotency-device',
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $secondResponse->assertStatus(201);
    $secondEventId = $secondResponse->json('data.event.id');

    // Should return same event ID (cached response)
    expect($firstEventId)->toBe($secondEventId);

    // Verify shipment status was only updated once
    $shipment->refresh();
    expect($shipment->current_status)->toBe(ShipmentStatus::DELIVERED);

    // Verify only one POD event was created
    $podEventCount = \App\Models\ScanEvent::where('shipment_id', $shipment->id)
        ->where('type', ScanType::DELIVERED)
        ->where('notes', 'like', 'POD:%')
        ->count();
    expect($podEventCount)->toBe(1);
});

test('idempotency returns 409 when body mismatch', function () {
    $user = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $user->id,
        'current_status' => ShipmentStatus::IN_TRANSIT,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'mismatch-test-device',
    ]);

    $token = $loginResponse->json('data.token');

    $idempotencyKey = 'mismatch-key-789';

    // First request
    $this->postJson("/api/v1/shipments/{$shipment->id}/events", [
        'type' => ScanType::DEPART->value,
        'location' => 'Origin hub',
        'notes' => 'Departed',
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'mismatch-test-device',
        'Idempotency-Key' => $idempotencyKey,
    ])->assertStatus(201);

    // Second request with different body
    $response = $this->postJson("/api/v1/shipments/{$shipment->id}/events", [
        'type' => ScanType::DEPART->value,
        'location' => 'Different location',
        'notes' => 'Different notes',
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'mismatch-test-device',
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Idempotency key conflict',
        ]);
});

test('idempotency key required for write operations', function () {
    $user = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $user->id,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'no-key-device',
    ]);

    $token = $loginResponse->json('data.token');

    // Request without Idempotency-Key header
    $response = $this->postJson("/api/v1/shipments/{$shipment->id}/events", [
        'type' => ScanType::ARRIVE->value,
        'location' => 'Test location',
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'no-key-device',
        // Missing Idempotency-Key
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Idempotency-Key header required',
        ]);
});

test('idempotency cache expires after 30 minutes', function () {
    $user = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $user->id,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'expiry-test-device',
    ]);

    $token = $loginResponse->json('data.token');

    $idempotencyKey = 'expiry-test-key';

    // First request
    $this->postJson("/api/v1/shipments/{$shipment->id}/events", [
        'type' => ScanType::LOAD->value,
        'location' => 'Loading dock',
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'expiry-test-device',
        'Idempotency-Key' => $idempotencyKey,
    ])->assertStatus(201);

    // Simulate cache expiry by manually deleting from Redis
    $userId = $user->id;
    $path = "/api/v1/shipments/{$shipment->id}/events";
    $body = json_encode([
        'type' => ScanType::LOAD->value,
        'location' => 'Loading dock',
    ]);
    $key = hash('sha256', $idempotencyKey.$body.$path.$userId);
    Redis::del($key);

    // Second request should create new event (cache expired)
    $secondResponse = $this->postJson("/api/v1/shipments/{$shipment->id}/events", [
        'type' => ScanType::LOAD->value,
        'location' => 'Loading dock',
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'expiry-test-device',
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $secondResponse->assertStatus(201);

    // Verify two events were created (cache expired)
    $eventCount = \App\Models\ScanEvent::where('shipment_id', $shipment->id)
        ->where('type', ScanType::LOAD)
        ->count();
    expect($eventCount)->toBe(2);
});
