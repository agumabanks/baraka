<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_notifications_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.notifications'));

        $response->assertStatus(200);
        $response->assertViewIs('branch.account.notifications');
    }

    /** @test */
    public function user_can_update_email_notification_preferences()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.notifications.update'), [
                'email' => [
                    'shipments' => '1',
                    'operations' => '1',
                    'reports' => '1',
                ],
                'sms' => [],
                'quiet_hours' => [
                    'start' => '22:00',
                    'end' => '08:00',
                ],
                'frequency' => 'immediate',
            ]);

        $response->assertRedirect(route('branch.account.notifications'));
        $response->assertSessionHas('success', 'Notification preferences updated successfully!');

        $prefs = json_decode($this->user->fresh()->notification_prefs, true);
        
        $this->assertEquals('1', $prefs['email']['shipments']);
        $this->assertEquals('1', $prefs['email']['operations']);
        $this->assertEquals('immediate', $prefs['frequency']);
    }

    /** @test */
    public function user_can_update_sms_notification_preferences()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.notifications.update'), [
                'email' => [],
                'sms' => [
                    'critical' => '1',
                    'security' => '1',
                ],
                'quiet_hours' => [
                    'start' => '23:00',
                    'end' => '07:00',
                ],
                'frequency' => 'hourly',
            ]);

        $response->assertRedirect(route('branch.account.notifications'));

        $prefs = json_decode($this->user->fresh()->notification_prefs, true);
        
        $this->assertEquals('1', $prefs['sms']['critical']);
        $this->assertEquals('hourly', $prefs['frequency']);
    }

    /** @test */
    public function user_can_set_quiet_hours()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.notifications.update'), [
                'email' => [],
                'sms' => [],
                'quiet_hours' => [
                    'start' => '21:00',
                    'end' => '09:00',
                ],
                'frequency' => 'daily',
            ]);

        $response->assertRedirect(route('branch.account.notifications'));

        $prefs = json_decode($this->user->fresh()->notification_prefs, true);
        
        $this->assertEquals('21:00', $prefs['quiet_hours']['start']);
        $this->assertEquals('09:00', $prefs['quiet_hours']['end']);
    }

    /** @test */
    public function frequency_must_be_valid_option()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.notifications.update'), [
                'email' => [],
                'sms' => [],
                'quiet_hours' => [],
                'frequency' => 'invalid-option',
            ]);

        $response->assertSessionHasErrors(['frequency']);
    }

    /** @test */
    public function quiet_hours_must_be_valid_time_format()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.notifications.update'), [
                'email' => [],
                'sms' => [],
                'quiet_hours' => [
                    'start' => '25:00', // Invalid time
                    'end' => '08:00',
                ],
                'frequency' => 'immediate',
            ]);

        $response->assertSessionHasErrors(['quiet_hours.start']);
    }

    /** @test */
    public function guest_cannot_access_notification_preferences()
    {
        $this->get(route('branch.account.notifications'))
            ->assertRedirect(route('login'));

        $this->put(route('branch.account.notifications.update'), [])
            ->assertRedirect(route('login'));
    }
}
