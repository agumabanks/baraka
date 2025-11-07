<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminShipmentTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'user_type' => UserType::ADMIN,
            'password' => bcrypt('password'),
        ]);
    }

    private function createMerchant(): User
    {
        return User::factory()->create([
            'user_type' => UserType::MERCHANT,
            'password' => bcrypt('password'),
        ]);
    }

    private function authenticate(User $user, string $deviceUuid): string
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ], [
            'device_uuid' => $deviceUuid,
        ])->assertStatus(200);

        $token = $response->json('data.token');
        $this->assertNotNull($token, 'Authentication token was not returned');

        return $token;
    }

    public function test_admin_can_update_shipment_status_with_audit_logging(): void
    {
        $admin = $this->createAdmin();
        $merchant = $this->createMerchant();

        $shipment = Shipment::factory()->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::BOOKED,
        ]);

        $token = $this->authenticate($admin, 'admin-shipment-device');

        $this->patchJson("/api/v1/admin/shipments/{$shipment->id}/status", [
            'status' => ShipmentStatus::AT_ORIGIN_HUB->value,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'admin-shipment-device',
            'Idempotency-Key' => 'update-status-123',
        ])->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'shipment' => ['id', 'current_status'],
                ],
            ]);

        $shipment->refresh();
        $this->assertSame(ShipmentStatus::AT_ORIGIN_HUB, $shipment->current_status);

        $activity = Activity::where('subject_type', Shipment::class)
            ->where('subject_id', $shipment->id)
            ->where('description', 'updated shipment')
            ->first();

        $this->assertNotNull($activity);
        $changes = $activity->changes();
        $this->assertSame(ShipmentStatus::AT_ORIGIN_HUB->value, $changes['attributes']['current_status'] ?? null);
        $this->assertSame(ShipmentStatus::BOOKED->value, $changes['old']['current_status'] ?? null);
    }

    public function test_admin_can_filter_shipments_by_status(): void
    {
        $admin = $this->createAdmin();
        $merchant = $this->createMerchant();

        Shipment::factory()->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::BOOKED,
        ]);

        Shipment::factory()->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::DELIVERED,
        ]);

        Shipment::factory()->create([
            'customer_id' => $merchant->id,
            'current_status' => ShipmentStatus::DELIVERED,
        ]);

        $token = $this->authenticate($admin, 'admin-filter-device');

        $response = $this->getJson('/api/v1/admin/shipments?status=' . ShipmentStatus::DELIVERED->value, [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'admin-filter-device',
        ])->assertStatus(200);

        $shipments = $response->json('data.shipments.data') ?? [];
        $this->assertCount(2, $shipments);

        foreach ($shipments as $shipment) {
            $this->assertSame(ShipmentStatus::DELIVERED->value, $shipment['current_status']);
        }
    }

    public function test_admin_can_filter_shipments_by_customer(): void
    {
        $admin = $this->createAdmin();
        $merchant1 = $this->createMerchant();
        $merchant2 = $this->createMerchant();

        Shipment::factory()->count(2)->create([
            'customer_id' => $merchant1->id,
        ]);

        Shipment::factory()->create([
            'customer_id' => $merchant2->id,
        ]);

        $token = $this->authenticate($admin, 'admin-customer-filter-device');

        $response = $this->getJson('/api/v1/admin/shipments?customer_id=' . $merchant1->id, [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'admin-customer-filter-device',
        ])->assertStatus(200);

        $shipments = $response->json('data.shipments.data') ?? [];
        $this->assertCount(2, $shipments);

        foreach ($shipments as $shipment) {
            $this->assertSame($merchant1->id, $shipment['customer_id']);
        }
    }

    public function test_admin_can_paginate_shipments(): void
    {
        $admin = $this->createAdmin();
        $merchant = $this->createMerchant();

        Shipment::factory()->count(10)->create([
            'customer_id' => $merchant->id,
        ]);

        $token = $this->authenticate($admin, 'admin-pagination-device');

        $response = $this->getJson('/api/v1/admin/shipments?per_page=5&page=1', [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'admin-pagination-device',
        ])->assertStatus(200);

        $data = $response->json('data.shipments');
        $this->assertCount(5, $data['data'] ?? []);
        $this->assertSame(1, $data['current_page']);
        $this->assertSame(5, $data['per_page']);
        $this->assertSame(10, $data['total']);
    }

    public function test_status_transition_creates_activity_log_entry(): void
    {
        $admin = $this->createAdmin();
        $shipment = Shipment::factory()->create([
            'current_status' => ShipmentStatus::BOOKED,
        ]);

        $token = $this->authenticate($admin, 'activity-log-device');

        $this->patchJson("/api/v1/admin/shipments/{$shipment->id}/status", [
            'status' => ShipmentStatus::AT_ORIGIN_HUB->value,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'activity-log-device',
            'Idempotency-Key' => 'status-transition-456',
        ])->assertStatus(200);

        $activities = Activity::where('subject_type', Shipment::class)
            ->where('subject_id', $shipment->id)
            ->get();

        $this->assertCount(1, $activities);

        $activity = $activities->first();
        $this->assertSame('updated shipment', $activity?->description);
        $changes = $activity?->changes() ?? [];
        $this->assertSame(ShipmentStatus::AT_ORIGIN_HUB->value, $changes['attributes']['current_status'] ?? null);
        $this->assertSame(ShipmentStatus::BOOKED->value, $changes['old']['current_status'] ?? null);
    }

    public function test_invalid_status_transition_still_updates_shipment(): void
    {
        $admin = $this->createAdmin();
        $shipment = Shipment::factory()->create([
            'current_status' => ShipmentStatus::DELIVERED,
        ]);

        $token = $this->authenticate($admin, 'invalid-status-device');

        $this->patchJson("/api/v1/admin/shipments/{$shipment->id}/status", [
            'status' => ShipmentStatus::BOOKED->value,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'invalid-status-device',
        ])->assertStatus(200);

        $shipment->refresh();
        $this->assertSame(ShipmentStatus::BOOKED, $shipment->current_status);
    }

    public function test_non_admin_cannot_update_shipment_status(): void
    {
        $merchant = $this->createMerchant();
        $shipment = Shipment::factory()->create([
            'customer_id' => $merchant->id,
        ]);

        $token = $this->authenticate($merchant, 'non-admin-status-device');

        $this->patchJson("/api/v1/admin/shipments/{$shipment->id}/status", [
            'status' => ShipmentStatus::DELIVERED->value,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'device_uuid' => 'non-admin-status-device',
        ])->assertStatus(403);
    }
}
