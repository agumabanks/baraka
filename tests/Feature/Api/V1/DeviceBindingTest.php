<?php

use App\Enums\UserType;
use App\Models\Device;
use App\Models\User;

test('middleware records device on authenticated calls', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'user_type' => UserType::MERCHANT,
    ]);

    $deviceUuid = 'middleware-test-device-uuid';

    // Login first to get token
    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], [
        'device_uuid' => $deviceUuid,
        'platform' => 'ios',
        'push_token' => 'test-push-token',
    ]);

    $token = $loginResponse->json('data.token');

    // Make an authenticated request with device headers
    $response = $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => $deviceUuid,
        'platform' => 'ios',
        'push_token' => 'test-push-token',
    ]);

    $response->assertStatus(200);

    // Verify device was recorded
    $device = Device::where('device_uuid', $deviceUuid)->first();
    expect($device)->not->toBeNull();
    expect($device->user_id)->toBe($user->id);
    expect($device->platform)->toBe('ios');
    expect($device->push_token)->toBe('test-push-token');
});

test('middleware requires device_uuid header for authenticated calls', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'user_type' => UserType::MERCHANT,
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], [
        'device_uuid' => 'some-device-uuid',
        'platform' => 'ios',
    ]);

    $token = $loginResponse->json('data.token');

    // Make authenticated request without device_uuid header
    $response = $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
        // Missing device_uuid header
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'device_uuid header required',
        ]);
});

test('middleware updates device last_seen_at on each call', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'user_type' => UserType::MERCHANT,
    ]);

    $deviceUuid = 'update-last-seen-device';

    // Create device with old timestamp
    $device = Device::create([
        'user_id' => $user->id,
        'device_uuid' => $deviceUuid,
        'platform' => 'android',
        'last_seen_at' => now()->subHours(2),
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], [
        'device_uuid' => $deviceUuid,
        'platform' => 'android',
    ]);

    $token = $loginResponse->json('data.token');

    // Make authenticated request
    $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => $deviceUuid,
    ]);

    // Refresh device and check last_seen_at was updated
    $device->refresh();
    expect($device->last_seen_at)->toBeGreaterThan(now()->subMinutes(1));
});

test('middleware handles device switching correctly', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'user_type' => UserType::MERCHANT,
    ]);

    $oldDeviceUuid = 'old-device-uuid';
    $newDeviceUuid = 'new-device-uuid';

    // Create old device
    Device::create([
        'user_id' => $user->id,
        'device_uuid' => $oldDeviceUuid,
        'platform' => 'ios',
        'last_seen_at' => now()->subDays(1),
    ]);

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], [
        'device_uuid' => $newDeviceUuid,
        'platform' => 'android',
        'push_token' => 'new-push-token',
    ]);

    $token = $loginResponse->json('data.token');

    // Make authenticated request with new device
    $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
        'device_uuid' => $newDeviceUuid,
    ]);

    // Verify new device was created
    $newDevice = Device::where('device_uuid', $newDeviceUuid)->first();
    expect($newDevice)->not->toBeNull();
    expect($newDevice->user_id)->toBe($user->id);
    expect($newDevice->platform)->toBe('android');

    // Old device should still exist but not updated
    $oldDevice = Device::where('device_uuid', $oldDeviceUuid)->first();
    expect($oldDevice)->not->toBeNull();
    expect($oldDevice->last_seen_at)->toBeLessThan(now()->subHours(1));
});
