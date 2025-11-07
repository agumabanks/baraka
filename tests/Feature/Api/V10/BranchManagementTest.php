<?php

namespace Tests\Feature\Api\V10;

use App\Enums\BranchStatus;
use App\Enums\BranchType;
use App\Models\Backend\Branch;
use App\Models\BranchMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => database_path('testing.sqlite')]);
    }

    public function test_admin_can_create_branch(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'Riyadh Mega Hub',
            'code' => 'RYD-HUB-01',
            'type' => BranchType::HUB->value,
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'time_zone' => 'Asia/Riyadh',
            'capacity_parcels_per_day' => 1500,
            'geo_lat' => 24.7136,
            'geo_lng' => 46.6753,
            'status' => BranchStatus::ACTIVE->value,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/branches', $payload);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.code', 'RYD-HUB-01');

        $this->assertDatabaseHas('branches', [
            'code' => 'RYD-HUB-01',
            'type' => BranchType::HUB->value,
            'status' => BranchStatus::ACTIVE->toLegacy(),
        ]);

        $branch = Branch::where('code', 'RYD-HUB-01')->firstOrFail();

        $this->assertTrue(
            BranchMetric::where('branch_id', $branch->id)
                ->where('window', 'daily')
                ->exists()
        );
    }

    public function test_admin_can_update_branch_details(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        Sanctum::actingAs($admin);

        $branch = Branch::factory()->create([
            'code' => 'JED-01',
            'capacity_parcels_per_day' => 500,
        ]);

        $payload = [
            'capacity_parcels_per_day' => 900,
            'status' => BranchStatus::MAINTENANCE->value,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->putJson("/api/v10/branches/{$branch->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.capacity_parcels_per_day', 900);
        $response->assertJsonPath('data.status_enum', BranchStatus::MAINTENANCE->value);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'capacity_parcels_per_day' => 900,
            'status' => BranchStatus::MAINTENANCE->toLegacy(),
        ]);
    }
}
