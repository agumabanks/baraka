<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserType;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_device_uuid_creates_token_and_binds_device(): void
    {
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
        ])->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);

        $token = $response->json('data.token');
        $this->assertIsString($token);

        $device = Device::where('device_uuid', $deviceUuid)->first();
        $this->assertNotNull($device);
        $this->assertSame($user->id, $device->user_id);
        $this->assertSame('ios', $device->platform);
        $this->assertSame('test-push-token', $device->push_token);
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    public function test_login_without_device_uuid_header_fails(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::MERCHANT,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertStatus(400)
            ->assertJson([
                'error' => 'device_uuid header required',
            ]);
    }

    public function test_login_with_existing_device_updates_last_seen_at(): void
    {
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

        $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ], [
            'device_uuid' => $deviceUuid,
            'platform' => 'ios',
            'push_token' => 'updated-push-token',
        ])->assertStatus(200);

        $device = Device::where('device_uuid', $deviceUuid)->first();
        $this->assertNotNull($device);
        $this->assertNotEquals($originalLastSeen->toDateTimeString(), optional($device->last_seen_at)->toDateTimeString());
        $this->assertSame('updated-push-token', $device->push_token);
    }
}
