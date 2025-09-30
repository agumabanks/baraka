<?php

use App\Enums\UserType;
use App\Models\Device;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::MERCHANT,
        ]);

        $deviceUuid = 'test-device-uuid';
        Device::create([
            'user_id' => $this->user->id,
            'device_uuid' => $deviceUuid,
            'platform' => 'ios',
        ]);

        // Login to get token
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ], [
            'device_uuid' => $deviceUuid,
        ]);

        $this->token = $loginResponse->json('data.token');
    }

    public function test_list_user_shipments()
    {
        // Create some shipments for the user
        Shipment::factory()->count(3)->create([
            'customer_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/shipments', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'shipments' => [
                        '*' => ['id', 'tracking_number', 'current_status', 'created_at'],
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data.shipments'));
    }

    public function test_create_shipment()
    {
        $shipmentData = [
            'origin_branch_id' => 1,
            'dest_branch_id' => 2,
            'service_level' => 'standard',
            'incoterm' => 'DDP',
            'price_amount' => 50.00,
            'currency' => 'USD',
            'metadata' => ['test' => true],
        ];

        $response = $this->postJson('/api/v1/shipments', $shipmentData, [
            'Authorization' => 'Bearer ' . $this->token,
            'Idempotency-Key' => 'test-key-123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'shipment' => ['id', 'tracking_number', 'current_status', 'created_at'],
                ],
            ]);

        $this->assertDatabaseHas('shipments', [
            'customer_id' => $this->user->id,
            'origin_branch_id' => 1,
            'dest_branch_id' => 2,
            'service_level' => 'standard',
            'price_amount' => 50.00,
        ]);
    }

    public function test_create_shipment_with_invalid_data_fails()
    {
        $shipmentData = [
            'origin_branch_id' => 999, // Non-existent branch
            'dest_branch_id' => 2,
            'service_level' => 'invalid',
            'incoterm' => 'DDP',
            'price_amount' => -10, // Negative price
            'currency' => 'INVALID',
        ];

        $response = $this->postJson('/api/v1/shipments', $shipmentData, [
            'Authorization' => 'Bearer ' . $this->token,
            'Idempotency-Key' => 'test-key-123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'origin_branch_id',
                'service_level',
                'price_amount',
                'currency',
            ]);
    }

    public function test_show_shipment_details()
    {
        $shipment = Shipment::factory()->create([
            'customer_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/shipments/' . $shipment->id, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'shipment' => ['id', 'tracking_number', 'current_status', 'created_at'],
                ],
            ]);

        $this->assertEquals($shipment->id, $response->json('data.shipment.id'));
    }

    public function test_show_other_user_shipment_fails()
    {
        $otherUser = User::factory()->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/shipments/' . $shipment->id, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(403);
    }

    public function test_cancel_shipment()
    {
        $shipment = Shipment::factory()->create([
            'customer_id' => $this->user->id,
            'current_status' => 'created',
        ]);

        $response = $this->postJson('/api/v1/shipments/' . $shipment->id . '/cancel', [
            'reason' => 'Customer requested cancellation',
        ], [
            'Authorization' => 'Bearer ' . $this->token,
            'Idempotency-Key' => 'cancel-key-123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Shipment cancelled',
            ]);

        $shipment->refresh();
        $this->assertEquals('cancelled', $shipment->current_status);
        $this->assertEquals('Customer requested cancellation', $shipment->metadata['cancel_reason']);
    }

    public function test_cancel_already_delivered_shipment_fails()
    {
        $shipment = Shipment::factory()->create([
            'customer_id' => $this->user->id,
            'current_status' => 'delivered',
        ]);

        $response = $this->postJson('/api/v1/shipments/' . $shipment->id . '/cancel', [
            'reason' => 'Customer requested cancellation',
        ], [
            'Authorization' => 'Bearer ' . $this->token,
            'Idempotency-Key' => 'cancel-key-123',
        ]);

        $response->assertStatus(403);
    }

    public function test_cancel_other_user_shipment_fails()
    {
        $otherUser = User::factory()->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $otherUser->id,
            'current_status' => 'created',
        ]);

        $response = $this->postJson('/api/v1/shipments/' . $shipment->id . '/cancel', [
            'reason' => 'Customer requested cancellation',
        ], [
            'Authorization' => 'Bearer ' . $this->token,
            'Idempotency-Key' => 'cancel-key-123',
        ]);

        $response->assertStatus(403);
    }

    public function test_idempotent_shipment_creation_returns_same_response()
    {
        $shipmentData = [
            'origin_branch_id' => 1,
            'dest_branch_id' => 2,
            'service_level' => 'standard',
            'incoterm' => 'DDP',
            'price_amount' => 50.00,
            'currency' => 'USD',
        ];

        $idempotencyKey = 'test-idempotent-key-123';

        // First request
        $response1 = $this->postJson('/api/v1/shipments', $shipmentData, [
            'Authorization' => 'Bearer ' . $this->token,
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(201);
        $shipmentId1 = $response1->json('data.shipment.id');

        // Second request with same key
        $response2 = $this->postJson('/api/v1/shipments', $shipmentData, [
            'Authorization' => 'Bearer ' . $this->token,
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(201);
        $shipmentId2 = $response2->json('data.shipment.id');

        // Should return the same shipment
        $this->assertEquals($shipmentId1, $shipmentId2);

        // Should only have one shipment in database
        $this->assertEquals(1, Shipment::count());
    }
}
