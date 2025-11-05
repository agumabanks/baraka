<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_includes_pagination_payload(): void
    {
        $admin = $this->createAdminWithPermissions(['user_read']);

        User::factory()->count(3)->create(['user_type' => UserType::ADMIN]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'pagination' => [
                'current_page',
                'per_page',
                'from',
                'to',
                'total',
                'last_page',
                'links' => ['first', 'last', 'prev', 'next'],
            ],
        ]);
    }

    public function test_users_store_requires_permissions(): void
    {
        $admin = $this->createAdminWithPermissions([]);

        $department = Department::create(['title' => 'Operations']);
        $designation = Designation::create(['title' => 'Manager']);
        $role = Role::query()->create(['name' => 'Branch Ops', 'slug' => 'branch-ops']);

        $payload = [
            'name' => 'Restricted Admin',
            'email' => 'restricted@example.com',
            'password' => 'securePass1',
            'mobile' => '01700000002',
            'designation_id' => $designation->id,
            'department_id' => $department->id,
            'role_id' => $role->id,
            'joining_date' => '2024-01-01',
            'salary' => 5000,
            'address' => 'HQ',
            'status' => Status::ACTIVE,
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', $payload);

        $response->assertForbidden();
    }

    public function test_users_store_creates_admin_user(): void
    {
        $admin = $this->createAdminWithPermissions(['user_create']);

        $department = Department::create(['title' => 'Operations']);
        $designation = Designation::create(['title' => 'Manager']);
        $role = Role::query()->create(['name' => 'Branch Ops', 'slug' => 'branch-ops']);

        $payload = [
            'name' => 'Jane Admin',
            'email' => 'jane.admin@example.com',
            'password' => 'securePass1',
            'mobile' => '01700000001',
            'designation_id' => $designation->id,
            'department_id' => $department->id,
            'role_id' => $role->id,
            'joining_date' => '2024-01-01',
            'salary' => 7500,
            'address' => 'HQ',
            'status' => Status::ACTIVE,
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.email', 'jane.admin@example.com');
        $response->assertJsonPath('data.role.slug', 'branch-ops');

        $this->assertDatabaseHas('users', [
            'email' => 'jane.admin@example.com',
            'user_type' => UserType::ADMIN,
        ]);
    }

    public function test_users_store_returns_validation_errors_via_resource(): void
    {
        $admin = $this->createAdminWithPermissions(['user_create']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', []);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonStructure(['errors' => ['name', 'email', 'password']]);
    }

    private function createAdminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
        ]);

        $admin->permissions = $permissions;
        $admin->save();

        return $admin->refresh();
    }
}
