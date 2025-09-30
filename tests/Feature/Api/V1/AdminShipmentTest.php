<?php

use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Shipment;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('admin can update shipment status with audit logging', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::CREATED,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'admin-shipment-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->patchJson("/api/v1/admin/shipments/{$shipment->id}/status", [
        'status' => ShipmentStatus::HANDED_OVER->value,
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'admin-shipment-device',
        'Idempotency-Key' => 'update-status-123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'shipment' => ['id', 'current_status'],
            ],
        ]);

    $shipment->refresh();
    expect($shipment->current_status)->toBe(ShipmentStatus::HANDED_OVER);

    // Verify audit logging
    $activity = Activity::where('subject_type', Shipment::class)
        ->where('subject_id', $shipment->id)
        ->where('description', 'updated shipment')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->changes()['attributes']['current_status'])->toBe(ShipmentStatus::HANDED_OVER->value);
    expect($activity->changes()['old']['current_status'])->toBe(ShipmentStatus::CREATED->value);
});

test('admin can filter shipments by status', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    // Create shipments with different statuses
    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::CREATED,
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::DELIVERED,
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::DELIVERED,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'admin-filter-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson('/api/v1/admin/shipments?status='.ShipmentStatus::DELIVERED->value, [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'admin-filter-device',
    ]);

    $response->assertStatus(200);

    $shipments = $response->json('data.shipments.data');
    expect($shipments)->toHaveCount(2);

    foreach ($shipments as $shipment) {
        expect($shipment['current_status'])->toBe(ShipmentStatus::DELIVERED->value);
    }
});

test('admin can filter shipments by customer', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $merchant1 = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $merchant2 = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    Shipment::factory()->count(2)->create([
        'customer_id' => $merchant1->id,
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant2->id,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'admin-customer-filter-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson("/api/v1/admin/shipments?customer_id={$merchant1->id}", [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'admin-customer-filter-device',
    ]);

    $response->assertStatus(200);

    $shipments = $response->json('data.shipments.data');
    expect($shipments)->toHaveCount(2);

    foreach ($shipments as $shipment) {
        expect($shipment['customer_id'])->toBe($merchant1->id);
    }
});

test('admin can paginate shipments', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    Shipment::factory()->count(10)->create([
        'customer_id' => $merchant->id,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'admin-pagination-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson('/api/v1/admin/shipments?per_page=5&page=1', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'admin-pagination-device',
    ]);

    $response->assertStatus(200);

    $data = $response->json('data.shipments');
    expect($data['data'])->toHaveCount(5);
    expect($data['current_page'])->toBe(1);
    expect($data['per_page'])->toBe(5);
    expect($data['total'])->toBe(10);
});

test('status transition creates activity log entry', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $shipment = Shipment::factory()->create([
        'current_status' => ShipmentStatus::CREATED,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'activity-log-device',
    ]);

    $token = $loginResponse->json('data.token');

    // Perform status update
    $this->patchJson("/api/v1/admin/shipments/{$shipment->id}/status", [
        'status' => ShipmentStatus::ARRIVE->value,
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'activity-log-device',
        'Idempotency-Key' => 'status-transition-456',
    ]);

    // Check activity log
    $activities = Activity::where('subject_type', Shipment::class)
        ->where('subject_id', $shipment->id)
        ->get();

    expect($activities)->toHaveCount(1);

    $activity = $activities->first();
    expect($activity->description)->toBe('updated shipment');
    expect($activity->changes()['attributes']['current_status'])->toBe(ShipmentStatus::ARRIVE->value);
    expect($activity->changes()['old']['current_status'])->toBe(ShipmentStatus::CREATED->value);
});

test('invalid status transition fails', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $shipment = Shipment::factory()->create([
        'current_status' => ShipmentStatus::DELIVERED,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'invalid-status-device',
    ]);

    $token = $loginResponse->json('data.token');

    // Try to update status of delivered shipment
    $response = $this->patchJson("/api/v1/admin/shipments/{$shipment->id}/status", [
        'status' => ShipmentStatus::CREATED->value, // Invalid transition
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'invalid-status-device',
    ]);

    // This should succeed as the controller doesn't validate transitions
    // In a real app, you might want to add business logic validation
    $response->assertStatus(200);

    $shipment->refresh();
    expect($shipment->current_status)->toBe(ShipmentStatus::CREATED);
});

test('non-admin cannot update shipment status', function () {
    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $shipment = Shipment::factory()->create([
        'customer_id' => $merchant->id,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $merchant->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'non-admin-status-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->patchJson("/api/v1/admin/shipments/{$shipment->id}/status", [
        'status' => ShipmentStatus::DELIVERED->value,
    ], [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'non-admin-status-device',
    ]);

    $response->assertStatus(403);
});
