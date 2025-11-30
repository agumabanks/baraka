<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);
    }

    /** @test */
    public function user_can_view_security_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.security'));

        $response->assertStatus(200);
        $response->assertViewIs('branch.account.security');
    }

    /** @test */
    public function user_can_change_password_with_valid_credentials()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.password'), [
                'current_password' => 'current-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ]);

        $response->assertRedirect(route('branch.account.security'));
        $response->assertSessionHas('success', 'Password changed successfully!');

        // Verify new password works
        $this->assertTrue(Hash::check('new-secure-password', $this->user->fresh()->password));
    }

    /** @test */
    public function user_cannot_change_password_with_wrong_current_password()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.password'), [
                'current_password' => 'wrong-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    /** @test */
    public function user_cannot_change_password_if_confirmation_does_not_match()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.password'), [
                'current_password' => 'current-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'different-password',
            ]);

        $response->assertSessionHasErrors(['new_password']);
    }

    /** @test */
    public function new_password_must_be_at_least_8_characters()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.password'), [
                'current_password' => 'current-password',
                'new_password' => 'short',
                'new_password_confirmation' => 'short',
            ]);

        $response->assertSessionHasErrors(['new_password']);
    }

    /** @test */
    public function new_password_must_be_different_from_current()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.password'), [
                'current_password' => 'current-password',
                'new_password' => 'current-password',
                'new_password_confirmation' => 'current-password',
            ]);

        $response->assertSessionHasErrors(['new_password']);
    }

    /** @test */
    public function user_can_generate_2fa_secret()
    {
        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.2fa.generate'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['secret', 'qr_code_url']);
    }

    /** @test */
    public function user_can_enable_2fa_with_valid_code()
    {
        $securityService = app(SecurityService::class);
        $data = $securityService->generate2FASecret($this->user);
        $secret = $data['secret'];
        
        // Generate a valid code
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.2fa.enable'), [
                'secret' => $secret,
                'verification_code' => $validCode,
            ]);

        $response->assertRedirect(route('branch.account.security'));
        $response->assertSessionHas('success', 'Two-factor authentication enabled successfully!');
    }

    /** @test */
    public function user_cannot_enable_2fa_with_invalid_code()
    {
        $securityService = app(SecurityService::class);
        $data = $securityService->generate2FASecret($this->user);

        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.2fa.enable'), [
                'secret' => $data['secret'],
                'verification_code' => '000000', // Invalid code
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invalid verification code. Please try again.');
    }

    /** @test */
    public function user_can_disable_2fa()
    {
        // First enable 2FA
        $securityService = app(SecurityService::class);
        $data = $securityService->generate2FASecret($this->user);
        $securityService->enable2FA($this->user, $data['secret']);

        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.2fa.disable'));

        $response->assertRedirect(route('branch.account.security'));
        $response->assertSessionHas('success', 'Two-factor authentication disabled.');

        // Verify 2FA is disabled
        $this->assertFalse($securityService->has2FAEnabled($this->user->fresh()));
    }

    /** @test */
    public function user_can_revoke_session()
    {
        $sessionId = 'test-session-id';

        $response = $this->actingAs($this->user)
            ->post(route('branch.account.security.session.revoke', $sessionId));

        $response->assertRedirect(route('branch.account.security'));
        $response->assertSessionHas('success', 'Session revoked successfully.');
    }

    /** @test */
    public function guest_cannot_access_security_features()
    {
        $this->get(route('branch.account.security'))
            ->assertRedirect(route('login'));

        $this->post(route('branch.account.security.password'), [])
            ->assertRedirect(route('login'));

        $this->post(route('branch.account.security.2fa.enable'), [])
            ->assertRedirect(route('login'));
    }
}
