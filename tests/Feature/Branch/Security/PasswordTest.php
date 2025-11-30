<?php

namespace Tests\Feature\Branch\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use App\Events\Account\PasswordChanged;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password()
    {
        Event::fake();
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->post(route('branch.account.security.password'), [
            'current_password' => 'OldPassword123!',
            'new_password' => 'NewStrongPassword123!',
            'new_password_confirmation' => 'NewStrongPassword123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Password updated successfully');
        
        $this->assertTrue(Hash::check('NewStrongPassword123!', $user->fresh()->password));
        
        Event::assertDispatched(PasswordChanged::class);
    }

    public function test_password_must_meet_strength_requirements()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->post(route('branch.account.security.password'), [
            'current_password' => 'OldPassword123!',
            'new_password' => 'weak',
            'new_password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors('new_password');
        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }

    public function test_cannot_reuse_recent_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);
        
        // Add old password to history manually or rely on the fact that we just set it?
        // The PasswordStrengthChecker::isInHistory checks the password_history table.
        // We need to populate it.
        \DB::table('password_history')->insert([
            'user_id' => $user->id,
            'password_hash' => Hash::make('RecentPassword123!'),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('branch.account.security.password'), [
            'current_password' => 'OldPassword123!',
            'new_password' => 'RecentPassword123!',
            'new_password_confirmation' => 'RecentPassword123!',
        ]);

        $response->assertSessionHasErrors('new_password');
    }
}
