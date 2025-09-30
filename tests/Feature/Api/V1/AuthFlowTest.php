<?php

use App\Enums\UserType;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::MERCHANT,
        ]);
    }

    public function test_login_with_device_uuid_creates_token_and_binds_device()
    {
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
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);

        $this->assertIsString($response->json('data.token'));

        // Verify device was created and bound
        $device = Device::where('device_uuid', $deviceUuid)->first();
        $this->assertNotNull($device);
        $this->assertEquals($this->user->id, $device->user_id);
        $this->assertEquals('ios', $device->platform);
        $this->assertEquals('test-push-token', $device->push_token);
    }

    public function test_login_with_invalid_credentials_fails()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ], [
            'device_uuid' => 'test-device-uuid',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    public function test_login_without_device_uuid_header_fails()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'device_uuid header required',
            ]);
    }

    public function test_login_with_existing_device_updates_last_seen_at()
    {
        $deviceUuid = 'existing-device-uuid';
        $originalLastSeen = now()->subDays(1);

        Device::create([
            'user_id' => $this->user->id,
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
        $this->assertNotEquals($originalLastSeen, $device->last_seen_at);
        $this->assertEquals('updated-push-token', $device->push_token);
    }

    public function test_logout_revokes_token()
    {
        $deviceUuid = 'test-device-uuid';
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ], [
            'device_uuid' => $deviceUuid,
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->postJson('/api/v1/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
        ]);
    }

    public function test_get_authenticated_user_profile()
    {
        $deviceUuid = 'test-device-uuid';
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ], [
            'device_uuid' => $deviceUuid,
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'mobile', 'user_type'],
                ],
            ]);
    }

    public function test_update_authenticated_user_profile()
    {
        $deviceUuid = 'test-device-uuid';
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ], [
            'device_uuid' => $deviceUuid,
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->patchJson('/api/v1/me', [
            'name' => 'Updated Name',
            'mobile' => '+1234567890',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated',
            ]);

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('+1234567890', $this->user->mobile);
    }
}
