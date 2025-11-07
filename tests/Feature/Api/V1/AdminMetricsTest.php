<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_metrics_dashboard(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'password' => bcrypt('password'),
        ]);

        $merchant = User::factory()->create([
            'user_type' => UserType::MERCHANT,
        ]);

        Shipment::factory()->count(5)->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::BOOKED,
        ]);

        Shipment::factory()->count(3)->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::LINEHAUL_DEPARTED,
        ]);

        Shipment::factory()->count(2)->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::DELIVERED,
        ]);

        Shipment::factory()->count(1)->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::RETURN_INITIATED,
        ]);

        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $admin->email,
            'password' => 'password',
        ], [
            'device_uuid' => 'admin-metrics-device',
        ])->assertStatus(200);

        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token, 'Authentication token was not returned');

        $response = $this->getJson('/api/v1/admin/metrics', [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'admin-metrics-device',
        ])->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_shipments',
                    'shipments_by_status',
                    'total_customers',
                    'active_shipments',
                    'delivered_today',
                ],
            ]);

        $metrics = $response->json('data');

        $this->assertSame(11, $metrics['total_shipments']);
        $this->assertSame(5, $metrics['shipments_by_status'][ShipmentStatus::BOOKED->value] ?? null);
        $this->assertSame(3, $metrics['shipments_by_status'][ShipmentStatus::LINEHAUL_DEPARTED->value] ?? null);
        $this->assertSame(2, $metrics['shipments_by_status'][ShipmentStatus::DELIVERED->value] ?? null);
        $this->assertSame(1, $metrics['shipments_by_status'][ShipmentStatus::RETURN_INITIATED->value] ?? null);
        $this->assertSame(1, $metrics['total_customers']);
    }

    public function test_metrics_include_date_range_filtering(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'password' => bcrypt('password'),
        ]);

        $merchant = User::factory()->create([
            'user_type' => UserType::MERCHANT,
        ]);

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
        ])->assertStatus(200);

        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token, 'Authentication token was not returned');

        $response = $this->getJson('/api/v1/admin/metrics?date_from=' . now()->subDays(5)->format('Y-m-d'), [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'metrics-date-device',
        ])->assertStatus(200);

        $metrics = $response->json('data');
        $this->assertSame(2, $metrics['total_shipments']);
    }

    public function test_metrics_include_delivery_performance(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'password' => bcrypt('password'),
        ]);

        $merchant = User::factory()->create([
            'user_type' => UserType::MERCHANT,
        ]);

        Shipment::factory()->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::DELIVERED,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(1),
        ]);

        Shipment::factory()->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::DELIVERED,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(1),
        ]);

        Shipment::factory()->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::LINEHAUL_DEPARTED,
            'created_at' => now()->subDays(1),
        ]);

        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $admin->email,
            'password' => 'password',
        ], [
            'device_uuid' => 'performance-device',
        ])->assertStatus(200);

        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token, 'Authentication token was not returned');

        $response = $this->getJson('/api/v1/admin/metrics', [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'performance-device',
        ])->assertStatus(200);

        $metrics = $response->json('data');

        $this->assertSame(2, $metrics['delivered_shipments']);
        $this->assertSame(1, $metrics['in_transit_shipments']);
        $this->assertEqualsWithDelta(66.67, $metrics['delivery_rate'], 0.2);

        if (isset($metrics['average_delivery_time_days'])) {
            $this->assertGreaterThan(0, $metrics['average_delivery_time_days']);
        }
    }

    public function test_metrics_include_revenue_data(): void
    {
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'password' => bcrypt('password'),
        ]);

        $merchant = User::factory()->create([
            'user_type' => UserType::MERCHANT,
        ]);

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
            'price_amount' => 25.25,
            'current_status' => ShipmentStatus::LINEHAUL_DEPARTED,
        ]);

        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $admin->email,
            'password' => 'password',
        ], [
            'device_uuid' => 'metrics-revenue-device',
        ])->assertStatus(200);

        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token, 'Authentication token was not returned');

        $response = $this->getJson('/api/v1/admin/metrics', [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'metrics-revenue-device',
        ])->assertStatus(200);

        $metrics = $response->json('data');

        $this->assertArrayHasKey('revenue', $metrics);
        $this->assertArrayHasKey('total_revenue', $metrics['revenue']);
        $this->assertEqualsWithDelta(125.50, $metrics['revenue']['total_revenue'] ?? 0, 0.01);
    }
}
