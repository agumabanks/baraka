<?php

use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Shipment;
use App\Models\User;

test('admin can view metrics dashboard', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    // Create test data
    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    // Create shipments in various statuses
    Shipment::factory()->count(5)->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::CREATED,
    ]);

    Shipment::factory()->count(3)->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::IN_TRANSIT,
    ]);

    Shipment::factory()->count(2)->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::DELIVERED,
    ]);

    Shipment::factory()->count(1)->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::RETURN_TO_SENDER,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'admin-metrics-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson('/api/v1/admin/metrics', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'admin-metrics-device',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'total_shipments',
                'shipments_by_status',
                'total_customers',
                'active_shipments',
                'delivered_today',
                // Add other expected metrics
            ],
        ]);

    $metrics = $response->json('data');

    // Verify total shipments count
    expect($metrics['total_shipments'])->toBe(11);

    // Verify shipments by status
    expect($metrics['shipments_by_status'][ShipmentStatus::CREATED->value])->toBe(5);
    expect($metrics['shipments_by_status'][ShipmentStatus::IN_TRANSIT->value])->toBe(3);
    expect($metrics['shipments_by_status'][ShipmentStatus::DELIVERED->value])->toBe(2);
    expect($metrics['shipments_by_status'][ShipmentStatus::RETURN_TO_SENDER->value])->toBe(1);

    // Verify customer count
    expect($metrics['total_customers'])->toBe(1); // Only one merchant created
});

test('metrics include date range filtering', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    // Create shipments with different dates
    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'created_at' => now()->subDays(10),
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'created_at' => now()->subDays(2),
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'created_at' => now(),
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'metrics-date-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson('/api/v1/admin/metrics?date_from='.now()->subDays(5)->format('Y-m-d'), [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'metrics-date-device',
    ]);

    $response->assertStatus(200);

    $metrics = $response->json('data');
    // Should include shipments from last 5 days (2 shipments)
    expect($metrics['total_shipments'])->toBe(2);
});

test('metrics include delivery performance', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    // Create delivered shipments with different delivery times
    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::DELIVERED,
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(1), // Delivered in 1 day
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::DELIVERED,
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(1), // Delivered in 4 days
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'current_status' => ShipmentStatus::IN_TRANSIT,
        'created_at' => now()->subDays(1),
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'performance-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson('/api/v1/admin/metrics', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'performance-device',
    ]);

    $response->assertStatus(200);

    $metrics = $response->json('data');

    // Verify delivery metrics
    expect($metrics['delivered_shipments'])->toBe(2);
    expect($metrics['in_transit_shipments'])->toBe(1);
    expect($metrics['delivery_rate'])->toBe(66.67); // 2 out of 3

    // Average delivery time calculation would depend on implementation
    if (isset($metrics['average_delivery_time_days'])) {
        expect($metrics['average_delivery_time_days'])->toBeGreaterThan(0);
    }
});

test('metrics include revenue data', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    // Create shipments with different price amounts
    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'price_amount' => 50.00,
        'current_status' => ShipmentStatus::DELIVERED,
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'price_amount' => 75.50,
        'current_status' => ShipmentStatus::DELIVERED,
    ]);

    Shipment::factory()->create([
        'customer_id' => $merchant->id,
        'price_amount' => 100.00,
        'current_status' => ShipmentStatus::CREATED, // Not delivered yet
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'revenue-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson('/api/v1/admin/metrics', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'revenue-device',
    ]);

    $response->assertStatus(200);

    $metrics = $response->json('data');

    // Verify revenue metrics
    expect($metrics['total_revenue'])->toBe(125.50); // 50 + 75.50
    expect($metrics['pending_revenue'])->toBe(100.00); // Not delivered yet
    expect($metrics['collected_revenue'])->toBe(125.50);
});

test('non-admin cannot access metrics', function () {
    $merchant = User::factory()->create([
        'user_type' => UserType::MERCHANT,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $merchant->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'non-admin-metrics-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson('/api/v1/admin/metrics', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'non-admin-metrics-device',
    ]);

    $response->assertStatus(403);
});

test('metrics handle empty data gracefully', function () {
    $admin = User::factory()->create([
        'user_type' => UserType::ADMIN,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ], [
        'device_uuid' => 'empty-metrics-device',
    ]);

    $token = $loginResponse->json('data.token');

    $response = $this->getJson('/api/v1/admin/metrics', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => 'empty-metrics-device',
    ]);

    $response->assertStatus(200);

    $metrics = $response->json('data');

    // Verify default values for empty data
    expect($metrics['total_shipments'])->toBe(0);
    expect($metrics['total_customers'])->toBe(0);
    expect($metrics['total_revenue'])->toBe(0.0);
});
