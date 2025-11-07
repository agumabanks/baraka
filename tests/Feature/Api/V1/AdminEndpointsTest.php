<?php

use App\Enums\UserType;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $adminCookie;

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

        // Login as admin
        $response = $this->postJson('/dashboard/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->adminCookie = $response->headers->getCookies()[0]->getValue();
    }

    public function test_list_customers_as_admin()
    {
        // Create some customers
        User::factory()->count(3)->create(['user_type' => UserType::MERCHANT]);

        $response = $this->getJson('/api/v1/admin/customers', [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customers' => [
                        '*' => ['id', 'name', 'email', 'user_type', 'created_at'],
                    ],
                ],
            ]);

        $this->assertCount(4, $response->json('data.customers')); // 3 seeded + existing client from setUp

        $customerIds = array_column($response->json('data.customers'), 'id');
        $this->assertNotContains($this->admin->id, $customerIds);
    }

    public function test_show_customer_details_as_admin()
    {
        $response = $this->getJson('/api/v1/admin/customers/' . $this->customer->id, [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customer' => ['id', 'name', 'email', 'user_type', 'created_at'],
                ],
            ]);

        $this->assertEquals($this->customer->id, $response->json('data.customer.id'));
    }

    public function test_update_customer_as_admin()
    {
        $response = $this->patchJson('/api/v1/admin/customers/' . $this->customer->id, [
            'name' => 'Updated Customer Name',
            'status' => 'inactive',
        ], [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
            'Idempotency-Key' => 'update-customer-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated',
            ]);

        $this->customer->refresh();
        $this->assertEquals('Updated Customer Name', $this->customer->name);
        $this->assertEquals('inactive', $this->customer->status);
    }

    public function test_list_shipments_as_admin()
    {
        // Create some shipments
        Shipment::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/shipments', [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
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

    public function test_show_shipment_details_as_admin()
    {
        $shipment = Shipment::factory()->create();

        $response = $this->getJson('/api/v1/admin/shipments/' . $shipment->id, [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
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

    public function test_update_shipment_status_as_admin()
    {
        $shipment = Shipment::factory()->create(['current_status' => 'created']);

        $response = $this->patchJson('/api/v1/admin/shipments/' . $shipment->id . '/status', [
            'status' => 'out_for_delivery',
            'reason' => 'Admin status update',
        ], [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
            'Idempotency-Key' => 'update-status-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Shipment status updated',
            ]);

        $shipment->refresh();
        $this->assertEquals('out_for_delivery', $shipment->current_status);
    }

    public function test_get_metrics_as_admin()
    {
        // Create some test data
        Shipment::factory()->count(5)->create(['current_status' => 'delivered']);
        Shipment::factory()->count(2)->create(['current_status' => 'pending']);

        $response = $this->getJson('/api/v1/admin/metrics', [
            'Cookie' => 'laravel_session=' . $this->adminCookie,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'metrics' => [
                        'total_shipments',
                        'pending_shipments',
                        'delivered_shipments',
                        'total_revenue',
                    ],
                ],
            ]);

        $metrics = $response->json('data.metrics');
        $this->assertEquals(7, $metrics['total_shipments']);
        $this->assertEquals(5, $metrics['delivered_shipments']);
        $this->assertEquals(2, $metrics['pending_shipments']);
    }

    public function test_non_admin_cannot_access_admin_endpoints()
    {
        $customer = User::factory()->create(['user_type' => UserType::MERCHANT]);

        // Login as customer
        $customerResponse = $this->postJson('/dashboard/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $customerCookie = $customerResponse->headers->getCookies()[0]->getValue();

        $response = $this->getJson('/api/v1/admin/customers', [
            'Cookie' => 'laravel_session=' . $customerCookie,
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_endpoints()
    {
        $response = $this->getJson('/api/v1/admin/customers');

        $response->assertStatus(401);
    }
}
