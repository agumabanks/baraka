<?php

namespace App\Console\Commands;

use Database\Seeders\BranchSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeedBranches extends Command
{
    protected $signature = 'seed:branches {--force : Run without confirmation} {--dry-run : Show what would be seeded} {--config= : Load from config file}';
    protected $description = 'Idempotently seed branches (safe for production)';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            return $this->dryRun();
        }

        if (!$this->option('force')) {
            $this->warn('⚠️  This command will create/update branch records.');
            $existingCount = \App\Models\Backend\Branch::count();
            
            if ($existingCount > 0) {
                $this->info("Found {$existingCount} existing branches.");
                $this->info('Seeder will update existing branches if code matches.');
            }

            if (!$this->confirm('Continue with branch seeding?')) {
                $this->info('Seeding cancelled.');
                return 0;
            }
        }

        return $this->seed();
    }

    private function seed(): int
    {
        try {
            $this->info('Starting branch seeding...');
            $startTime = microtime(true);

            DB::transaction(function () {
                $this->call(BranchSeeder::class);
            });

            $duration = round(microtime(true) - $startTime, 2);
            $totalBranches = \App\Models\Backend\Branch::count();

            $this->info("✅ Branch seeding completed in {$duration}s");
            $this->info("📊 Total branches in system: {$totalBranches}");

            Log::info('Branch seeding command completed', [
                'total_branches' => $totalBranches,
                'duration_seconds' => $duration,
            ]);

            return 0;
        } catch (\Throwable $e) {
            $this->error("❌ Seeding failed: {$e->getMessage()}");
            
            Log::error('Branch seeding failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return 1;
        }
    }

    private function dryRun(): int
    {
        $this->info('🔍 DRY RUN MODE - No changes will be made');
        $this->info('');

        // Show what would be created
        $this->line('Branch Configuration:');
        $this->line('┌─ Branches to seed/update:');

        $branches = [
            'HUB-DUBAI' => 'Dubai Main Hub',
            'HUB-ABU-DHABI' => 'Abu Dhabi Hub',
            'REG-DUBAI-NORTH' => 'Dubai North Regional',
            'REG-DUBAI-SOUTH' => 'Dubai South Regional',
            'LOC-DUBAI-DIPS' => 'Dubai DIPS Local',
        ];

        foreach ($branches as $code => $name) {
            $existing = \App\Models\Backend\Branch::where('code', $code)->exists();
            $status = $existing ? '✏️  UPDATE' : '✨ CREATE';
            $this->line("├─ [{$status}] {$code} - {$name}");
        }

        $this->line('└─ End');
        $this->info('');
        $this->info('Run with --force flag to apply changes.');

        return 0;
    }
}
