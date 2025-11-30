<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class OptimizePerformance extends Command
{
    protected $signature = 'app:optimize-performance 
                            {--clear-cache : Clear all caches}
                            {--optimize-db : Run database optimizations}
                            {--warm-cache : Warm up caches}
                            {--all : Run all optimizations}';

    protected $description = 'Optimize application performance';

    public function handle()
    {
        $this->info('Starting performance optimization...');

        if ($this->option('all') || $this->option('clear-cache')) {
            $this->clearCaches();
        }

        if ($this->option('all') || $this->option('optimize-db')) {
            $this->optimizeDatabase();
        }

        if ($this->option('all') || $this->option('warm-cache')) {
            $this->warmCaches();
        }

        if (!$this->option('clear-cache') && !$this->option('optimize-db') && !$this->option('warm-cache') && !$this->option('all')) {
            $this->info('No optimization options specified. Use --all or specific options.');
            $this->line('  --clear-cache   Clear all caches');
            $this->line('  --optimize-db   Run database optimizations');
            $this->line('  --warm-cache    Warm up caches');
            $this->line('  --all           Run all optimizations');
            return;
        }

        $this->info('Performance optimization complete!');
    }

    protected function clearCaches(): void
    {
        $this->info('Clearing caches...');

        Artisan::call('cache:clear');
        $this->line('  - Application cache cleared');

        Artisan::call('config:clear');
        $this->line('  - Config cache cleared');

        Artisan::call('route:clear');
        $this->line('  - Route cache cleared');

        Artisan::call('view:clear');
        $this->line('  - View cache cleared');

        // Clear query cache if using Redis
        if (config('cache.default') === 'redis') {
            Cache::tags(['queries'])->flush();
            $this->line('  - Query cache cleared');
        }
    }

    protected function optimizeDatabase(): void
    {
        $this->info('Optimizing database...');

        // Analyze tables
        $tables = [
            'shipments',
            'scan_events',
            'invoices',
            'customers',
            'branches',
            'users',
        ];

        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
                $this->line("  - Analyzed table: {$table}");
            } catch (\Exception $e) {
                $this->warn("  - Could not analyze {$table}: " . $e->getMessage());
            }
        }

        // Clean up old records
        $this->info('Cleaning up old records...');

        $deleted = DB::table('api_request_logs')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();
        $this->line("  - Deleted {$deleted} old API logs");

        $deleted = DB::table('webhook_deliveries')
            ->where('created_at', '<', now()->subDays(30))
            ->where('status', 'success')
            ->delete();
        $this->line("  - Deleted {$deleted} old webhook deliveries");
    }

    protected function warmCaches(): void
    {
        $this->info('Warming caches...');

        // Cache config
        Artisan::call('config:cache');
        $this->line('  - Config cached');

        // Cache routes
        Artisan::call('route:cache');
        $this->line('  - Routes cached');

        // Cache views
        Artisan::call('view:cache');
        $this->line('  - Views cached');

        // Cache frequently accessed data
        $this->cacheFrequentData();
    }

    protected function cacheFrequentData(): void
    {
        // Cache branch list
        $branches = DB::table('branches')
            ->where('is_active', true)
            ->get(['id', 'name', 'code']);
        Cache::put('active_branches', $branches, now()->addHours(6));
        $this->line('  - Cached active branches');

        // Cache shipment status counts
        $statusCounts = DB::table('shipments')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');
        Cache::put('shipment_status_counts', $statusCounts, now()->addMinutes(15));
        $this->line('  - Cached shipment status counts');
    }
}
