<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_preferences_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.preferences'));

        $response->assertStatus(200);
        $response->assertViewIs('branch.account.preferences');
    }

    /** @test */
    public function user_can_update_language_and_timezone()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.preferences.update'), [
                'language' => 'fr',
                'timezone' => 'Africa/Lagos',
                'date_format' => 'Y-m-d',
                'time_format' => '24h',
                'currency_display' => 'symbol',
                'number_format' => '1,234.56',
                'theme' => 'dark',
            ]);

        $response->assertRedirect(route('branch.account.preferences'));
        $response->assertSessionHas('success', 'Preferences updated successfully!');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'preferred_language' => 'fr',
            'timezone' => 'Africa/Lagos',
        ]);
    }

    /** @test */
    public function user_can_update_display_formats()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.preferences.update'), [
                'language' => 'en',
                'timezone' => 'Africa/Nairobi',
                'date_format' => 'd/m/Y',
                'time_format' => '12h',
                'currency_display' => 'code',
                'number_format' => '1.234,56',
                'theme' => 'light',
            ]);

        $response->assertRedirect(route('branch.account.preferences'));

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'date_format' => 'd/m/Y',
            'time_format' => '12h',
            'currency_display' => 'code',
            'number_format' => '1.234,56',
        ]);
    }

    /** @test */
    public function user_can_change_theme()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.preferences.update'), [
                'language' => 'en',
                'timezone' => 'Africa/Nairobi',
                'date_format' => 'Y-m-d',
                'time_format' => '24h',
                'currency_display' => 'symbol',
                'number_format' => '1,234.56',
                'theme' => 'auto',
            ]);

        $response->assertRedirect(route('branch.account.preferences'));

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'theme' => 'auto',
        ]);
    }

    /** @test */
    public function language_must_be_valid_option()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.preferences.update'), [
                'language' => 'invalid',
                'timezone' => 'Africa/Nairobi',
                'date_format' => 'Y-m-d',
                'time_format' => '24h',
                'currency_display' => 'symbol',
                'number_format' => '1,234.56',
                'theme' => 'dark',
            ]);

        $response->assertSessionHasErrors(['language']);
    }

    /** @test */
    public function timezone_must_be_valid()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.preferences.update'), [
                'language' => 'en',
                'timezone' => 'Invalid/Timezone',
                'date_format' => 'Y-m-d',
                'time_format' => '24h',
                'currency_display' => 'symbol',
                'number_format' => '1,234.56',
                'theme' => 'dark',
            ]);

        $response->assertSessionHasErrors(['timezone']);
    }

    /** @test */
    public function date_format_must_be_valid_option()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.preferences.update'), [
                'language' => 'en',
                'timezone' => 'Africa/Nairobi',
                'date_format' => 'invalid-format',
                'time_format' => '24h',
                'currency_display' => 'symbol',
                'number_format' => '1,234.56',
                'theme' => 'dark',
            ]);

        $response->assertSessionHasErrors(['date_format']);
    }

    /** @test */
    public function theme_must_be_valid_option()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.preferences.update'), [
                'language' => 'en',
                'timezone' => 'Africa/Nairobi',
                'date_format' => 'Y-m-d',
                'time_format' => '24h',
                'currency_display' => 'symbol',
                'number_format' => '1,234.56',
                'theme' => 'invalid-theme',
            ]);

        $response->assertSessionHasErrors(['theme']);
    }

    /** @test */
    public function guest_cannot_access_preferences()
    {
        $this->get(route('branch.account.preferences'))
            ->assertRedirect(route('login'));

        $this->put(route('branch.account.preferences.update'), [])
            ->assertRedirect(route('login'));
    }
}
