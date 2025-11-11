<?php

namespace App\Console\Commands;

use App\Services\DisasterRecoveryService;
use Illuminate\Console\Command;

class CleanupOldBackups extends Command
{
    protected $signature = 'backup:cleanup {--days=30}';
    protected $description = 'Delete backups older than specified days';

    public function handle(DisasterRecoveryService $service): int
    {
        try {
            $days = $this->option('days');
            $deleted = $service->deleteOldBackups($days);
            
            $this->info("Deleted {$deleted} backup(s) older than {$days} days");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");
            return 1;
        }
    }
}
