<?php

namespace Tests\Feature\Console;

use App\Models\Backend\Branch;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeedBranchesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('branches')) {
            Schema::drop('branches');
        }

        $migration = require database_path('migrations/2025_11_06_205000_create_branches_table.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('branches');
        parent::tearDown();
    }

    public function test_seed_branches_command_creates_default_hierarchy(): void
    {
        Config::set('seeders.branches', []);

        $this->artisan('seed:branches', ['--force' => true])
            ->assertExitCode(0);

        $hub = Branch::where('code', 'HUB-DUBAI')->first();
        $regional = Branch::where('code', 'REG-DUBAI-NORTH')->first();
        $local = Branch::where('code', 'LOC-DUBAI-DIPS')->first();

        $this->assertNotNull($hub);
        $this->assertNotNull($regional);
        $this->assertNotNull($local);
        $this->assertEquals($hub->id, $regional->parent_branch_id);
        $this->assertEquals($regional->id, $local->parent_branch_id);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->artisan('seed:branches', ['--force' => true])->assertExitCode(0);
        $countAfterFirstRun = Branch::count();

        $this->artisan('seed:branches', ['--force' => true])->assertExitCode(0);

        $this->assertEquals($countAfterFirstRun, Branch::count());
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $this->artisan('seed:branches', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(0, Branch::count());
    }
}
