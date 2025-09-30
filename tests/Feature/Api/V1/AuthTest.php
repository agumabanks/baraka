<?php

use App\Enums\UserType;
use App\Models\Device;
use App\Models\User;

test('login with device_uuid creates token and binds device', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'user_type' => UserType::MERCHANT,
    ]);

    $deviceUuid = 'test-device-uuid-123';

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], [
        'device_uuid' => $deviceUuid,
        'platform' => 'ios',
        'push_token' => 'test-push-token',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ]);

    expect($response->json('data.token'))->toBeString();

    // Verify device was created and bound
    $device = Device::where('device_uuid', $deviceUuid)->first();
    expect($device)->not->toBeNull();
    expect($device->user_id)->toBe($user->id);
    expect($device->platform)->toBe('ios');
    expect($device->push_token)->toBe('test-push-token');
});

test('login with invalid credentials fails', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'invalid@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'The provided credentials are incorrect.',
        ]);
});

test('login without device_uuid header fails', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'user_type' => UserType::MERCHANT,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'device_uuid header required',
        ]);
});

test('login with existing device updates last_seen_at', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'user_type' => UserType::MERCHANT,
    ]);

    $deviceUuid = 'existing-device-uuid';
    $originalLastSeen = now()->subDays(1);

    Device::create([
        'user_id' => $user->id,
        'device_uuid' => $deviceUuid,
        'platform' => 'android',
        'last_seen_at' => $originalLastSeen,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], [
        'device_uuid' => $deviceUuid,
        'platform' => 'ios', // Different platform
        'push_token' => 'updated-push-token',
    ]);

    $response->assertStatus(200);

    $device = Device::where('device_uuid', $deviceUuid)->first();
    expect($device->last_seen_at)->not->toBe($originalLastSeen);
    expect($device->push_token)->toBe('updated-push-token');
});
