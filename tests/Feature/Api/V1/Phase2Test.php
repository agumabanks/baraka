<?php

use App\Models\User;
use App\Models\Shipment;
use App\Models\Task;
use App\Models\PodProof;
use App\Models\Quotation;
use App\Models\PickupRequest;
use App\Models\DeliveryMan;
use App\Enums\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2Test extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $driver;
    protected $adminCookie;
    protected $customerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::ADMIN,
        ]);

        $this->customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::MERCHANT,
        ]);

        $this->driver = User::factory()->create([
            'email' => 'driver@example.com',
            'password' => bcrypt('password'),
            'user_type' => UserType::DELIVERYMAN,
        ]);

        // Login as admin
        $adminResponse = $this->postJson('/dashboard/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->adminCookie = $adminResponse->headers->getCookies()[0]->getValue();

        // Login as customer
        $customerResponse = $this->postJson('/api/v1/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ], [
            'device_uuid' => 'customer-device',
        ]);
        $this->customerToken = $customerResponse->json('data.token');
    }

    public function test_create_quotation()
    {
        $quotationData = [
            'origin_branch_id' => 1,
            'destination_country' => 'US',
            'service_type' => 'standard',
            'pieces' => 2,
            'weight_kg' => 5.5,
            'volume_cm3' => 10000,
            'currency' => 'USD',
        ];

        $response = $this->postJson('/api/v1/quotes', $quotationData, [
            'Authorization' => 'Bearer ' . $this->customerToken,
            'Idempotency-Key' => 'quote-key-123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'quote' => ['id', 'total_amount', 'currency', 'valid_until'],
                ],
            ]);

        $this->assertDatabaseHas('quotations', [
            'customer_id' => $this->customer->id,
            'service_type' => 'standard',
            'total_amount' => 70.0, // 5.5kg * $10 + 15% fuel surcharge
        ]);
    }

    public function test_create_pickup_request()
    {
        $pickupData = [
            'pickup_date' => now()->addDays(1)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'contact_person' => 'John Doe',
            'contact_phone' => '+1234567890',
            'address' => '123 Main St, City, Country',
            'instructions' => 'Handle with care',
        ];

        $response = $this->postJson('/api/v1/pickups', $pickupData, [
            'Authorization' => 'Bearer ' . $this->customerToken,
            'Idempotency-Key' => 'pickup-key-123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'pickup_request' => ['id', 'pickup_date', 'contact_person', 'status'],
                ],
            ]);

        $this->assertDatabaseHas('pickup_requests', [
            'merchant_id' => $this->customer->merchant->id,
            'contact_person' => 'John Doe',
            'status' => 'pending',
        ]);
    }

    public function test_assign_driver_to_shipment()
    {
        $shipment = Shipment::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $driver = DeliveryMan::factory()->create([
            'user_id' => $this->driver->id,
        ]);

        $assignData = [
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'priority' => 'high',
            'scheduled_at' => now()->addHours(2)->toISOString(),
        ];

        $response = $this->postJson('/api/v1/dispatch/assign', $assignData, [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
            'Idempotency-Key' => 'assign-key-123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Driver assigned successfully',
            ]);

        $shipment->refresh();
        $this->assertEquals($driver->id, $shipment->driver_id);

        $this->assertDatabaseHas('tasks', [
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'type' => 'delivery',
            'status' => 'assigned',
        ]);
    }

    public function test_driver_location_tracking()
    {
        $driver = DeliveryMan::factory()->create([
            'user_id' => $this->driver->id,
        ]);

        $locationData = [
            'locations' => [
                [
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                    'timestamp' => now()->toISOString(),
                    'accuracy' => 10.0,
                    'speed' => 15.5,
                    'heading' => 90.0,
                ],
                [
                    'latitude' => 40.7130,
                    'longitude' => -74.0058,
                    'timestamp' => now()->addMinutes(1)->toISOString(),
                    'accuracy' => 8.0,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/driver/locations', $locationData, [
            'Authorization' => 'Bearer ' . $this->customerToken, // This should fail for customer
        ]);

        $response->assertStatus(403); // Customer shouldn't be able to post driver locations

        // Login as driver
        $driverResponse = $this->postJson('/api/v1/login', [
            'email' => 'driver@example.com',
            'password' => 'password',
        ], [
            'device_uuid' => 'driver-device',
        ]);

        $driverToken = $driverResponse->json('data.token');

        $response = $this->postJson('/api/v1/driver/locations', $locationData, [
            'Authorization' => 'Bearer ' . $driverToken,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Locations stored',
            ]);

        $this->assertDatabaseHas('driver_locations', [
            'driver_id' => $driver->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);
    }

    public function test_shipment_event_creation_and_broadcasting()
    {
        $shipment = Shipment::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $eventData = [
            'sscc' => 'SSCC123456789',
            'type' => 'out_for_delivery',
            'branch_id' => 1,
            'location' => [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
            ],
            'note' => 'Package is out for delivery',
        ];

        $response = $this->postJson('/api/v1/shipments/' . $shipment->id . '/events', $eventData, [
            'Authorization' => 'Bearer ' . $this->customerToken,
            'Idempotency-Key' => 'event-key-123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'event' => ['id', 'type', 'occurred_at'],
                ],
            ]);

        $this->assertDatabaseHas('scan_events', [
            'sscc' => 'SSCC123456789',
            'type' => 'out_for_delivery',
        ]);

        // Check if shipment status was updated
        $shipment->refresh();
        $this->assertEquals('out_for_delivery', $shipment->current_status);
    }

    public function test_pod_submission_and_verification()
    {
        $shipment = Shipment::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $driver = DeliveryMan::factory()->create([
            'user_id' => $this->driver->id,
        ]);

        $task = Task::factory()->create([
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'type' => 'delivery',
        ]);

        // Login as driver
        $driverResponse = $this->postJson('/api/v1/login', [
            'email' => 'driver@example.com',
            'password' => 'password',
        ], [
            'device_uuid' => 'driver-device',
        ]);

        $driverToken = $driverResponse->json('data.token');

        // Submit POD (this would normally include file uploads)
        $podData = [
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'photo' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            'notes' => 'Delivered successfully',
        ];

        $response = $this->postJson('/api/v1/tasks/' . $task->id . '/pod', $podData, [
            'Authorization' => 'Bearer ' . $driverToken,
            'Idempotency-Key' => 'pod-key-123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'pod' => ['id', 'shipment_id', 'otp_code'],
                ],
            ]);

        $pod = PodProof::first();
        $this->assertNotNull($pod->otp_code);
        $this->assertEquals(6, strlen($pod->otp_code));

        // Verify POD with OTP
        $response = $this->postJson('/api/v1/pod/' . $pod->id . '/verify', [
            'otp' => $pod->otp_code,
        ], [
            'Authorization' => 'Bearer ' . $this->customerToken,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'POD verified successfully',
            ]);

        $pod->refresh();
        $this->assertNotNull($pod->verified_at);
        $this->assertEquals('delivered', $shipment->fresh()->current_status);
    }

    public function test_admin_dispatch_optimization()
    {
        $response = $this->postJson('/api/v1/dispatch/optimize', [
            'hub_id' => 1,
            'date' => now()->addDays(1)->format('Y-m-d'),
        ], [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
            'Idempotency-Key' => 'optimize-key-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'job_id',
                ],
            ]);

        $this->assertStringStartsWith('opt-', $response->json('data.job_id'));
    }

    public function test_get_available_drivers()
    {
        DeliveryMan::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/dispatch/drivers', [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'drivers' => [
                        '*' => ['id', 'name', 'current_tasks', 'is_available'],
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data.drivers'));
    }

    public function test_get_unassigned_shipments()
    {
        Shipment::factory()->count(5)->create([
            'driver_id' => null,
        ]);

        $response = $this->getJson('/api/v1/dispatch/unassigned', [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'shipments' => [
                        '*' => ['id', 'tracking_number', 'current_status'],
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('data.shipments'));
    }
}