<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class RunHealthCheck extends Command
{
    protected $signature = 'app:health-check {--json : Output as JSON}';

    protected $description = 'Run comprehensive system health check';

    protected array $checks = [];

    public function handle()
    {
        $this->info('Running system health check...');
        $this->newLine();

        $this->checkDatabase();
        $this->checkCache();
        $this->checkStorage();
        $this->checkQueue();
        $this->checkDiskSpace();
        $this->checkMemory();

        if ($this->option('json')) {
            $this->line(json_encode($this->checks, JSON_PRETTY_PRINT));
            return;
        }

        // Summary
        $this->newLine();
        $passed = collect($this->checks)->where('status', 'OK')->count();
        $failed = collect($this->checks)->where('status', 'FAIL')->count();
        $warnings = collect($this->checks)->where('status', 'WARN')->count();

        $this->info("Health Check Complete: {$passed} passed, {$warnings} warnings, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }

    protected function checkDatabase(): void
    {
        $this->info('Checking database...');

        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            $this->addCheck('database', 'OK', "Connection successful ({$responseTime}ms)");
            $this->line("  [OK] Database connection ({$responseTime}ms)");

            // Check pending migrations
            $pendingMigrations = count(app('migrator')->pendingMigrations(
                app('migrator')->paths()
            ));

            if ($pendingMigrations > 0) {
                $this->addCheck('migrations', 'WARN', "{$pendingMigrations} pending migrations");
                $this->warn("  [WARN] {$pendingMigrations} pending migrations");
            } else {
                $this->addCheck('migrations', 'OK', 'All migrations applied');
                $this->line("  [OK] Migrations up to date");
            }

        } catch (\Exception $e) {
            $this->addCheck('database', 'FAIL', $e->getMessage());
            $this->error("  [FAIL] Database: " . $e->getMessage());
        }
    }

    protected function checkCache(): void
    {
        $this->info('Checking cache...');

        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value === 'test') {
                $this->addCheck('cache', 'OK', 'Cache read/write successful');
                $this->line("  [OK] Cache working");
            } else {
                $this->addCheck('cache', 'FAIL', 'Cache read/write failed');
                $this->error("  [FAIL] Cache read/write mismatch");
            }

        } catch (\Exception $e) {
            $this->addCheck('cache', 'FAIL', $e->getMessage());
            $this->error("  [FAIL] Cache: " . $e->getMessage());
        }
    }

    protected function checkStorage(): void
    {
        $this->info('Checking storage...');

        try {
            $testFile = 'health_check_' . time() . '.txt';
            Storage::put($testFile, 'test');
            $content = Storage::get($testFile);
            Storage::delete($testFile);

            if ($content === 'test') {
                $this->addCheck('storage', 'OK', 'Storage read/write successful');
                $this->line("  [OK] Storage working");
            } else {
                $this->addCheck('storage', 'FAIL', 'Storage read/write failed');
                $this->error("  [FAIL] Storage read/write mismatch");
            }

        } catch (\Exception $e) {
            $this->addCheck('storage', 'FAIL', $e->getMessage());
            $this->error("  [FAIL] Storage: " . $e->getMessage());
        }
    }

    protected function checkQueue(): void
    {
        $this->info('Checking queue...');

        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            if ($failedJobs > 10) {
                $this->addCheck('queue', 'WARN', "{$failedJobs} failed jobs");
                $this->warn("  [WARN] {$failedJobs} failed jobs");
            } else {
                $this->addCheck('queue', 'OK', "{$pendingJobs} pending, {$failedJobs} failed");
                $this->line("  [OK] Queue: {$pendingJobs} pending, {$failedJobs} failed");
            }

        } catch (\Exception $e) {
            $this->addCheck('queue', 'WARN', 'Queue table not available');
            $this->warn("  [WARN] Queue table not available");
        }
    }

    protected function checkDiskSpace(): void
    {
        $this->info('Checking disk space...');

        $path = storage_path();
        $freeSpace = disk_free_space($path);
        $totalSpace = disk_total_space($path);
        $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1);

        $freeGb = round($freeSpace / 1024 / 1024 / 1024, 2);

        if ($usedPercent > 90) {
            $this->addCheck('disk', 'FAIL', "{$usedPercent}% used ({$freeGb}GB free)");
            $this->error("  [FAIL] Disk {$usedPercent}% used ({$freeGb}GB free)");
        } elseif ($usedPercent > 80) {
            $this->addCheck('disk', 'WARN', "{$usedPercent}% used ({$freeGb}GB free)");
            $this->warn("  [WARN] Disk {$usedPercent}% used ({$freeGb}GB free)");
        } else {
            $this->addCheck('disk', 'OK', "{$usedPercent}% used ({$freeGb}GB free)");
            $this->line("  [OK] Disk {$usedPercent}% used ({$freeGb}GB free)");
        }
    }

    protected function checkMemory(): void
    {
        $this->info('Checking memory...');

        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $usedPercent = round(($memoryUsage / $memoryLimit) * 100, 1);
        $usedMb = round($memoryUsage / 1024 / 1024, 2);

        if ($usedPercent > 80) {
            $this->addCheck('memory', 'WARN', "{$usedPercent}% used ({$usedMb}MB)");
            $this->warn("  [WARN] Memory {$usedPercent}% used ({$usedMb}MB)");
        } else {
            $this->addCheck('memory', 'OK', "{$usedPercent}% used ({$usedMb}MB)");
            $this->line("  [OK] Memory {$usedPercent}% used ({$usedMb}MB)");
        }
    }

    protected function addCheck(string $name, string $status, string $message): void
    {
        $this->checks[$name] = [
            'status' => $status,
            'message' => $message,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }
}
