<?php

namespace Tests\Feature;

use App\Models\Backend\Branch;
use App\Models\Backend\GeneralSettings;
use App\Models\User;
use App\Support\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_system_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('settings.general'))
            ->assertStatus(403);
    }

    public function test_admin_with_permission_can_access_system_settings(): void
    {
        $user = User::factory()->create([
            'permissions' => ['settings_manage'],
        ]);

        $this->actingAs($user)
            ->get(route('settings.general'))
            ->assertOk();
    }

    public function test_branch_settings_respect_branch_locale_override(): void
    {
        $branch = Branch::factory()->create([
            'metadata' => [
                'settings' => [
                    'preferred_language' => 'fr',
                ],
            ],
        ]);
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);

        $this->actingAs($user)
            ->get(route('branch.settings', ['branch_id' => $branch->id]))
            ->assertOk();

        $this->get(route('branch.dashboard', ['branch_id' => $branch->id]))->assertOk();
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_system_default_currency_reads_from_settings(): void
    {
        GeneralSettings::create([
            'name' => 'Baraka',
            'details' => [
                'finance' => [
                    'default_currency' => 'EUR',
                ],
                'localization' => [
                    'default_locale' => 'en',
                ],
            ],
        ]);

        SystemSettings::flush();

        $this->assertSame('EUR', SystemSettings::defaultCurrency());
        $this->assertSame('en', SystemSettings::defaultLocale());
    }
}
