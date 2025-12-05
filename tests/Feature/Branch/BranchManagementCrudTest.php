<?php

namespace Tests\Feature\Branch;

use App\Enums\BranchStatus;
use App\Enums\BranchType;
use App\Models\Backend\Branch;
use App\Models\BranchMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchManagementCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => database_path('testing.sqlite')]);
    }

    public function test_admin_can_list_branches(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        Branch::factory()->count(5)->create();

        Sanctum::actingAs($admin);

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->getJson('/api/v10/branches');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'code', 'status'],
            ],
        ]);
    }

    public function test_admin_can_view_single_branch(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        $branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'code' => 'TB-001',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->getJson("/api/v10/branches/{$branch->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Test Branch');
        $response->assertJsonPath('data.code', 'TB-001');
    }

    public function test_admin_can_create_hub_branch(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'Main Hub',
            'code' => 'MAIN-HUB-01',
            'type' => BranchType::HUB->value,
            'country' => 'Uganda',
            'city' => 'Kampala',
            'time_zone' => 'Africa/Kampala',
            'capacity_parcels_per_day' => 2000,
            'status' => BranchStatus::ACTIVE->value,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/branches', $payload);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.code', 'MAIN-HUB-01');
        $response->assertJsonPath('data.type', BranchType::HUB->value);
    }

    public function test_admin_can_create_agent_point_branch(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'Agent Point Alpha',
            'code' => 'AP-ALPHA-01',
            'type' => BranchType::AGENT_POINT->value,
            'country' => 'Kenya',
            'city' => 'Nairobi',
            'time_zone' => 'Africa/Nairobi',
            'capacity_parcels_per_day' => 500,
            'status' => BranchStatus::ACTIVE->value,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/branches', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.type', BranchType::AGENT_POINT->value);
    }

    public function test_admin_can_update_branch_capacity(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        $branch = Branch::factory()->create([
            'capacity_parcels_per_day' => 500,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'capacity_parcels_per_day' => 1500,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->putJson("/api/v10/branches/{$branch->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.capacity_parcels_per_day', 1500);
    }

    public function test_admin_can_set_branch_to_maintenance(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        $branch = Branch::factory()->create([
            'status' => BranchStatus::ACTIVE->toLegacy(),
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'status' => BranchStatus::MAINTENANCE->value,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->putJson("/api/v10/branches/{$branch->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.status_enum', BranchStatus::MAINTENANCE->value);
    }

    public function test_admin_can_set_branch_hierarchy(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        $parentBranch = Branch::factory()->create([
            'type' => BranchType::HUB->value,
            'name' => 'Parent Hub',
        ]);

        $childBranch = Branch::factory()->create([
            'type' => BranchType::REGIONAL_BRANCH->value,
            'name' => 'Child Regional Branch',
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'parent_branch_id' => $parentBranch->id,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->putJson("/api/v10/branches/{$childBranch->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.parent_branch_id', $parentBranch->id);
    }

    public function test_branch_code_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        Branch::factory()->create(['code' => 'UNIQUE-001']);

        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'Duplicate Code Branch',
            'code' => 'UNIQUE-001',
            'type' => BranchType::HUB->value,
            'country' => 'Uganda',
            'city' => 'Kampala',
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/branches', $payload);

        $response->assertStatus(422);
    }

    public function test_non_admin_cannot_create_branch(): void
    {
        $user = User::factory()->create([
            'user_type' => \App\Enums\UserType::MERCHANT,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Unauthorized Branch',
            'code' => 'UNAUTH-001',
            'type' => BranchType::HUB->value,
            'country' => 'Uganda',
            'city' => 'Kampala',
            'time_zone' => 'Africa/Kampala',
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/branches', $payload);

        $response->assertStatus(403);
    }

    public function test_branch_creates_default_metrics(): void
    {
        $admin = User::factory()->create([
            'user_type' => \App\Enums\UserType::ADMIN,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'Metrics Test Branch',
            'code' => 'MTB-001',
            'type' => BranchType::HUB->value,
            'country' => 'Tanzania',
            'city' => 'Dar es Salaam',
            'time_zone' => 'Africa/Dar_es_Salaam',
            'status' => BranchStatus::ACTIVE->value,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/branches', $payload);

        $response->assertCreated();
        
        $branch = Branch::where('code', 'MTB-001')->first();
        
        $this->assertTrue(
            BranchMetric::where('branch_id', $branch->id)
                ->where('window', 'daily')
                ->exists()
        );
    }
}
