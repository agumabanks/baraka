<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Backend\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchFullHealthTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'code' => 'TB01',
            'status' => 1,
        ]);

        $this->user = User::factory()->create([
            'primary_branch_id' => $this->branch->id,
        ]);
        
        // Assign permissions/roles needed
        $this->user->permissions = ['branch_read', 'branch_manage'];
        $this->user->save();
    }

    public function test_branch_dashboard_loads()
    {
        $response = $this->actingAs($this->user)->get(route('branch.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Test Branch');
    }

    public function test_branch_operations_loads()
    {
        $response = $this->actingAs($this->user)->get(route('branch.operations'));
        $response->assertStatus(200);
        $response->assertSee('Operations Board');
    }

    public function test_branch_workforce_loads()
    {
        $response = $this->actingAs($this->user)->get(route('branch.workforce'));
        $response->assertStatus(200);
        $response->assertSee('Workforce');
    }

    public function test_branch_clients_loads()
    {
        $response = $this->actingAs($this->user)->get(route('branch.clients'));
        $response->assertStatus(200);
        $response->assertSee('Clients & CRM');
    }

    public function test_branch_finance_loads()
    {
        $response = $this->actingAs($this->user)->get(route('branch.finance'));
        $response->assertStatus(200);
        $response->assertSee('Finance');
    }

    public function test_branch_warehouse_loads()
    {
        $response = $this->actingAs($this->user)->get(route('branch.warehouse'));
        $response->assertStatus(200);
        $response->assertSee('Warehouse');
    }

    public function test_branch_fleet_loads()
    {
        $response = $this->actingAs($this->user)->get(route('branch.fleet'));
        $response->assertStatus(200);
        $response->assertSee('Fleet');
    }

    public function test_branch_settings_loads()
    {
        $response = $this->actingAs($this->user)->get(route('branch.settings'));
        $response->assertStatus(200);
        $response->assertSee('Branch Settings');
    }

    public function test_unauthorized_user_cannot_access_branch()
    {
        $otherUser = User::factory()->create(); // No permissions
        $response = $this->actingAs($otherUser)->get(route('branch.dashboard'));
        $response->assertStatus(403);
    }
}
