<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_support_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.support'));

        $response->assertStatus(200);
        $response->assertViewIs('branch.account.support');
    }

    /** @test */
    public function support_page_displays_system_status()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.support'));

        $response->assertStatus(200);
        $response->assertSee('System Status');
        $response->assertSee('All Systems Operational');
    }

    /** @test */
    public function support_page_displays_quick_help_links()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.support'));

        $response->assertStatus(200);
        $response->assertSee('Quick Help');
        $response->assertSee('User Guide');
        $response->assertSee('Video Tutorials');
        $response->assertSee('FAQs');
    }

    /** @test */
    public function user_can_submit_support_ticket()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Support ticket submitted', \Mockery::on(function ($data) {
                return $data['user_id'] === $this->user->id
                    && $data['subject'] === 'Test Issue'
                    && $data['category'] === 'technical'
                    && $data['priority'] === 'medium';
            }));

        $response = $this->actingAs($this->user)
            ->post(route('branch.account.support.submit'), [
                'subject' => 'Test Issue',
                'category' => 'technical',
                'priority' => 'medium',
                'message' => 'This is a test support ticket with detailed information.',
            ]);

        $response->assertRedirect(route('branch.account.support'));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function support_ticket_requires_subject()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.support.submit'), [
                'subject' => '',
                'category' => 'technical',
                'priority' => 'medium',
                'message' => 'Test message',
            ]);

        $response->assertSessionHasErrors(['subject']);
    }

    /** @test */
    public function support_ticket_requires_category()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.support.submit'), [
                'subject' => 'Test',
                'category' => '',
                'priority' => 'medium',
                'message' => 'Test message',
            ]);

        $response->assertSessionHasErrors(['category']);
    }

    /** @test */
    public function support_ticket_category_must_be_valid()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.support.submit'), [
                'subject' => 'Test',
                'category' => 'invalid-category',
                'priority' => 'medium',
                'message' => 'Test message',
            ]);

        $response->assertSessionHasErrors(['category']);
    }

    /** @test */
    public function support_ticket_requires_priority()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.support.submit'), [
                'subject' => 'Test',
                'category' => 'technical',
                'priority' => '',
                'message' => 'Test message',
            ]);

        $response->assertSessionHasErrors(['priority']);
    }

    /** @test */
    public function support_ticket_priority_must_be_valid()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.support.submit'), [
                'subject' => 'Test',
                'category' => 'technical',
                'priority' => 'invalid-priority',
                'message' => 'Test message',
            ]);

        $response->assertSessionHasErrors(['priority']);
    }

    /** @test */
    public function support_ticket_requires_message()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.support.submit'), [
                'subject' => 'Test',
                'category' => 'technical',
                'priority' => 'medium',
                'message' => '',
            ]);

        $response->assertSessionHasErrors(['message']);
    }

    /** @test */
    public function support_ticket_message_must_be_at_least_10_characters()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.support.submit'), [
                'subject' => 'Test',
                'category' => 'technical',
                'priority' => 'medium',
                'message' => 'Short',
            ]);

        $response->assertSessionHasErrors(['message']);
    }

    /** @test */
    public function guest_cannot_access_support_page()
    {
        $this->get(route('branch.account.support'))
            ->assertRedirect(route('login'));

        $this->post(route('branch.account.support.submit'), [])
            ->assertRedirect(route('login'));
    }
}
