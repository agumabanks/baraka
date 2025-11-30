<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DevicesAndSessionsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_devices_and_sessions_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.devices'));

        $response->assertStatus(200);
        $response->assertViewIs('branch.account.devices');
    }

    /** @test */
    public function devices_page_displays_current_session_information()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.devices'));

        $response->assertStatus(200);
        $response->assertSee('Active Sessions');
        $response->assertSee('Current');
    }

    /** @test */
    public function devices_page_displays_login_history()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.devices'));

        $response->assertStatus(200);
        $response->assertSee('Recent Login Activity');
        $response->assertSee('Last 30 days');
    }

    /** @test */
    public function user_can_revoke_session()
    {
        $sessionId = 'test-session-123';

        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.session.revoke', $sessionId));

        $response->assertRedirect(route('branch.account.security'));
        $response->assertSessionHas('success', 'Session revoked successfully.');
    }

    /** @test */
    public function guest_cannot_access_devices_page()
    {
        $response = $this->get(route('branch.account.devices'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_revoke_sessions()
    {
        $response = $this->post(route('branch.account.security.session.revoke', 'session-id'));

        $response->assertRedirect(route('login'));
    }
}
