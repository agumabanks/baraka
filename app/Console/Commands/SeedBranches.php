<?php

namespace App\Console\Commands;

use App\Models\Backend\Branch;
use App\Services\DisasterRecoveryService;
use Database\Seeders\BranchSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SeedBranches extends Command
{
    protected $signature = 'seed:branches
                            {--dry-run : Preview the branches that would be created without writing to the database}
                            {--force : Bypass safe-mode confirmation}
                            {--config= : Path to a JSON file with branch definitions}
                            {--no-backup : Skip the automatic database backup before seeding}';

    protected $description = 'Idempotent seeding of the branch hierarchy for production environments';

    public function handle(DisasterRecoveryService $disasterRecoveryService): int
    {
        if ($configPath = $this->option('config')) {
            try {
                $this->loadCustomConfig($configPath);
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                return self::INVALID;
            }
        }

        if ($this->option('dry-run')) {
            return $this->dryRun();
        }

        if ($this->requiresConfirmation() && !$this->option('force')) {
            if (!$this->confirm('Safe mode is enabled. Proceed with branch seeding?', false)) {
                $this->info('Branch seeding aborted.');
                return self::SUCCESS;
            }
        }

        if ($this->shouldBackup() && !$this->option('no-backup')) {
            $name = $disasterRecoveryService->createBackup('pre-branch-seed');
            $this->info("Database backup created: {$name}");
        }

        $transactional = config('seeders.safe_mode.transaction', true);
        $runner = function (): void {
            app(BranchSeeder::class)->run();
        };

        $transactional ? DB::transaction($runner) : $runner();

        $this->info('Branch seeding complete.');
        $this->displayStats();

        return self::SUCCESS;
    }

    private function dryRun(): int
    {
        $definitions = app(BranchSeeder::class)->definitions();

        $this->line('DRY RUN MODE');
        $this->info('DRY RUN: No database changes will be made.');

        $rows = collect($definitions)->map(function (array $branch) {
            return [
                'Code' => Str::upper($branch['code'] ?? ''),
                'Name' => $branch['name'] ?? 'N/A',
                'Type' => Str::upper($branch['type'] ?? 'LOCAL'),
                'Parent' => $branch['parent_code'] ?? '—',
                'Status' => Str::upper($branch['status'] ?? 'ACTIVE'),
            ];
        })->all();

        $this->table(['Code', 'Name', 'Type', 'Parent', 'Status'], $rows);

        $this->line(sprintf(
            'Existing branches in database: %d',
            Branch::count()
        ));

        return self::SUCCESS;
    }

    private function loadCustomConfig(string $path): void
    {
        if (!File::exists($path)) {
            throw new \InvalidArgumentException("Config file {$path} was not found.");
        }

        $contents = File::get($path);
        $decoded = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new \InvalidArgumentException("Config file {$path} does not contain valid JSON.");
        }

        Config::set('seeders.branches', $decoded);
    }

    private function requiresConfirmation(): bool
    {
        return (bool) config('seeders.safe_mode.enabled', true)
            && (bool) config('seeders.safe_mode.confirmation_required', false);
    }

    private function shouldBackup(): bool
    {
        return (bool) config('seeders.safe_mode.backup_before_seed', false);
    }

    private function displayStats(): void
    {
        $this->line(sprintf(
            'Current branch count: %d (Hubs: %d, Regional: %d, Local: %d)',
            Branch::count(),
            Branch::where('type', 'HUB')->count(),
            Branch::where('type', 'REGIONAL')->count(),
            Branch::where('type', 'LOCAL')->count()
        ));
    }
}
