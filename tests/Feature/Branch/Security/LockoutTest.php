<?php

namespace Tests\Feature\Branch\Security;

use App\Models\User;
use App\Services\Security\LockoutManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LockoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_increments_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('branch.login.submit'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $user->refresh();
        $this->assertEquals(1, $user->failed_login_attempts);
    }

    public function test_account_locks_after_max_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Simulate max attempts - 1
        $maxAttempts = config('account_security.lockout.max_attempts', 5);
        
        // We need to manually insert security events because LockoutManager counts them
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->post(route('branch.login.submit'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        
        // Try to login with correct password
        $response = $this->post(route('branch.login.submit'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        // Should be redirected back with error
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
