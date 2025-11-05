<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_index_returns_paginated_collection(): void
    {
        $admin = $this->createAdminWithPermissions(['role_read']);

        Role::query()->create(['name' => 'Operations', 'slug' => 'operations']);
        Role::query()->create(['name' => 'Finance', 'slug' => 'finance']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/roles');

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

    public function test_roles_store_returns_validation_errors_with_resource_payload(): void
    {
        $admin = $this->createAdminWithPermissions(['role_create']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/roles', []);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonStructure(['errors' => ['name', 'status']]);
    }

    public function test_roles_store_persists_role_when_authorized(): void
    {
        $admin = $this->createAdminWithPermissions(['role_create', 'role_read']);

        $payload = [
            'name' => 'Operations Lead',
            'status' => Status::ACTIVE,
            'permissions' => ['role_read', 'role_update'],
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/roles', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Operations Lead');
        $response->assertJsonPath('data.permissions_count', 2);

        $this->assertDatabaseHas('roles', [
            'name' => 'Operations Lead',
            'slug' => 'operations-lead',
        ]);
    }

    public function test_roles_routes_are_forbidden_without_permission(): void
    {
        $admin = $this->createAdminWithPermissions([]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/roles');

        $response->assertForbidden();
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
