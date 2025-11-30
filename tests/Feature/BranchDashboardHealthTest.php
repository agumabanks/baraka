<?php

namespace Tests\Feature;

use App\Models\Backend\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchDashboardHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_surfaces_render_for_authorized_user(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
        ]);
        $user->permissions = ['branch_read', 'branch_manage'];
        $user->save();

        $this->actingAs($user);

        $this->get(route('branch.dashboard'))
            ->assertOk()
            ->assertSee($branch->code);

        $this->get(route('branch.operations'))->assertOk();
        $this->get(route('branch.workforce'))->assertOk();
        $this->get(route('branch.clients'))->assertOk();
        $this->get(route('branch.finance'))->assertOk();
        $this->get(route('branch.warehouse'))->assertOk();
        $this->get(route('branch.fleet'))->assertOk();
    }
}
