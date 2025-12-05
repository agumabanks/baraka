<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use App\Models\Backend\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => database_path('testing.sqlite')]);
    }

    public function test_branch_login_page_loads(): void
    {
        $response = $this->get(route('branch.login'));
        
        $response->assertStatus(200);
        $response->assertViewIs('branch.auth.login');
    }

    public function test_user_can_login_with_email(): void
    {
        $branch = Branch::factory()->create([
            'status' => 1,
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => '1',
            'primary_branch_id' => $branch->id,
        ]);

        $response = $this->post(route('branch.login.submit'), [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('branch.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_mobile(): void
    {
        $branch = Branch::factory()->create([
            'status' => 1,
        ]);

        $user = User::factory()->create([
            'mobile' => '+1234567890',
            'password' => Hash::make('password123'),
            'status' => '1',
            'primary_branch_id' => $branch->id,
        ]);

        $response = $this->post(route('branch.login.submit'), [
            'login' => '+1234567890',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('branch.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => '1',
        ]);

        $response = $this->post(route('branch.login.submit'), [
            'login' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'status' => '0',
        ]);

        $response = $this->post(route('branch.login.submit'), [
            'login' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $branch = Branch::factory()->create([
            'status' => 1,
        ]);

        $user = User::factory()->create([
            'status' => '1',
            'primary_branch_id' => $branch->id,
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post(route('branch.logout'));

        $response->assertRedirect(route('branch.login'));
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $branch = Branch::factory()->create([
            'status' => 1,
        ]);

        $user = User::factory()->create([
            'status' => '1',
            'primary_branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($user)->get(route('branch.login'));

        $response->assertRedirect();
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->post(route('branch.login.submit'), [
            'login' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['login', 'password']);
    }
}
