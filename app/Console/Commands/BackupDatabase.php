<?php

namespace App\Console\Commands;

use App\Services\DisasterRecoveryService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--label=}';
    protected $description = 'Create a database backup';

    public function handle(DisasterRecoveryService $service): int
    {
        try {
            $label = $this->option('label') ?? 'scheduled';
            $backupName = $service->createBackup($label);
            
            $this->info("Backup created successfully: {$backupName}");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return 1;
        }
    }
}
