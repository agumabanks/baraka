<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserType;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceBindingTest extends TestCase
{
    use RefreshDatabase;

    private function createMerchant(): User
    {
        return User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::MERCHANT,
        ]);
    }

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

    public function test_middleware_records_device_on_authenticated_calls(): void
    {
        $user = $this->createMerchant();
        $deviceUuid = 'middleware-test-device-uuid';

        $token = $this->authenticate($user, $deviceUuid, [
            'platform' => 'ios',
            'push_token' => 'test-push-token',
        ]);

        $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => $deviceUuid,
            'platform' => 'ios',
            'push_token' => 'test-push-token',
        ])->assertStatus(200);

        $device = Device::where('device_uuid', $deviceUuid)->first();
        $this->assertNotNull($device);
        $this->assertSame($user->id, $device->user_id);
        $this->assertSame('ios', $device->platform);
        $this->assertSame('test-push-token', $device->push_token);
    }

    public function test_middleware_requires_device_uuid_header_for_authenticated_calls(): void
    {
        $user = $this->createMerchant();
        $token = $this->authenticate($user, 'some-device-uuid', [
            'platform' => 'ios',
        ]);

        $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(400)
            ->assertJson([
                'error' => 'device_uuid header required',
            ]);
    }

    public function test_middleware_updates_device_last_seen_at_on_each_call(): void
    {
        $user = $this->createMerchant();
        $deviceUuid = 'update-last-seen-device';

        $device = Device::create([
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'platform' => 'android',
            'last_seen_at' => now()->subHours(2),
        ]);

        $token = $this->authenticate($user, $deviceUuid, [
            'platform' => 'android',
        ]);

        $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => $deviceUuid,
        ])->assertStatus(200);

        $device->refresh();
        $this->assertTrue($device->last_seen_at->greaterThan(now()->subMinutes(1)));
    }

    public function test_middleware_handles_device_switching_correctly(): void
    {
        $user = $this->createMerchant();
        $oldDeviceUuid = 'old-device-uuid';
        $newDeviceUuid = 'new-device-uuid';

        Device::create([
            'user_id' => $user->id,
            'device_uuid' => $oldDeviceUuid,
            'platform' => 'ios',
            'last_seen_at' => now()->subDays(1),
        ]);

        $token = $this->authenticate($user, $newDeviceUuid, [
            'platform' => 'android',
            'push_token' => 'new-push-token',
        ]);

        $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => $newDeviceUuid,
        ])->assertStatus(200);

        $newDevice = Device::where('device_uuid', $newDeviceUuid)->first();
        $this->assertNotNull($newDevice);
        $this->assertSame($user->id, $newDevice->user_id);
        $this->assertSame('android', $newDevice->platform);

        $oldDevice = Device::where('device_uuid', $oldDeviceUuid)->first();
        $this->assertNotNull($oldDevice);
        $this->assertTrue($oldDevice->last_seen_at->lessThan(now()->subHours(1)));
    }
}
