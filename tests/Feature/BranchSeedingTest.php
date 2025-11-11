<?php

namespace Tests\Feature;

use App\Models\Backend\Branch;
use Database\Seeders\BranchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_branches_idempotently(): void
    {
        // First run
        $this->artisan('seed:branches --force')
            ->assertExitCode(0);

        $firstCount = Branch::count();
        $this->assertGreaterThan(0, $firstCount);

        // Second run - should not create duplicates
        $this->artisan('seed:branches --force')
            ->assertExitCode(0);

        $secondCount = Branch::count();
        $this->assertEquals($firstCount, $secondCount);
    }

    public function test_seeder_creates_correct_branch_hierarchy(): void
    {
        $this->seed(BranchSeeder::class);

        $hubDubai = Branch::where('code', 'HUB-DUBAI')->first();
        $this->assertNotNull($hubDubai);
        $this->assertTrue($hubDubai->is_hub);
        $this->assertEquals('HUB', $hubDubai->type);

        // Verify regional branches can link to hub
        $regionalBranch = Branch::where('code', 'REG-DUBAI-NORTH')->first();
        $this->assertNotNull($regionalBranch);
        $this->assertEquals('REGIONAL', $regionalBranch->type);
    }

    public function test_dry_run_shows_branches_without_creating(): void
    {
        $this->artisan('seed:branches --dry-run')
            ->assertExitCode(0)
            ->expectsOutput('DRY RUN MODE');

        $this->assertEquals(0, Branch::count());
    }
}
