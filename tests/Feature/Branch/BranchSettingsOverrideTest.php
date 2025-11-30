<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Support\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSettingsOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_currency_override_wins_over_global(): void
    {
        $branch = Branch::factory()->create();
        $branch->settings = ['currency' => 'EUR'];
        $branch->save();

        $resolved = SystemSettings::resolveCurrency($branch->id);
        $this->assertEquals('EUR', $resolved);
    }
}
