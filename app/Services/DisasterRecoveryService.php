<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DisasterRecoveryService
{
    private string $backupPath = 'backups';

    public function createBackup(?string $label = null): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "backup_{$timestamp}_" . ($label ?? 'manual') . ".sql";
        $backupPath = "{$this->backupPath}/{$backupName}";
        $storagePath = storage_path("app/{$this->backupPath}");

        if (!is_dir($storagePath)) {
            Storage::disk('local')->makeDirectory($this->backupPath);
        }

        try {
            $mysqlConfig = config('database.connections.mysql');

            if (!$mysqlConfig) {
                throw new \RuntimeException('MySQL connection is not configured.');
            }

            $password = $mysqlConfig['password'] ?? '';
            $passwordArg = $password !== '' ? sprintf('-p%s', escapeshellarg($password)) : '';

            if (!shell_exec('command -v mysqldump')) {
                throw new \RuntimeException('mysqldump binary is not available on the server.');
            }

            // For MySQL
            $command = sprintf(
                'mysqldump -h%s -u%s %s %s > %s',
                escapeshellarg($mysqlConfig['host'] ?? '127.0.0.1'),
                escapeshellarg($mysqlConfig['username'] ?? 'root'),
                $passwordArg,
                escapeshellarg($mysqlConfig['database'] ?? ''),
                escapeshellarg(storage_path("app/{$backupPath}"))
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Backup command failed');
            }

            Log::info('Database backup created', [
                'backup_name' => $backupName,
                'size' => filesize(storage_path("app/{$backupPath}")),
                'timestamp' => $timestamp,
            ]);

            return $backupName;
        } catch (\Throwable $e) {
            Log::error('Backup creation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function restoreFromBackup(string $backupName): bool
    {
        try {
            $backupPath = storage_path("app/{$this->backupPath}/{$backupName}");

            if (!file_exists($backupPath)) {
                throw new \Exception("Backup file not found: {$backupName}");
            }

            $command = sprintf(
                'mysql -h%s -u%s -p%s %s < %s',
                escapeshellarg(config('database.connections.mysql.host')),
                escapeshellarg(config('database.connections.mysql.username')),
                escapeshellarg(config('database.connections.mysql.password')),
                escapeshellarg(config('database.connections.mysql.database')),
                escapeshellarg($backupPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Restore command failed');
            }

            Log::info('Database restored from backup', [
                'backup_name' => $backupName,
                'timestamp' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Backup restoration failed', [
                'backup' => $backupName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function listBackups(): array
    {
        $backups = [];
        $backupDir = storage_path("app/{$this->backupPath}");

        if (!is_dir($backupDir)) {
            return $backups;
        }

        foreach (scandir($backupDir) as $file) {
            if (strpos($file, 'backup_') === 0) {
                $backups[] = [
                    'name' => $file,
                    'size' => filesize("{$backupDir}/{$file}"),
                    'created_at' => filemtime("{$backupDir}/{$file}"),
                ];
            }
        }

        usort($backups, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    public function deleteOldBackups(int $retainDays = 30): int
    {
        $cutoffDate = now()->subDays($retainDays)->timestamp;
        $deleted = 0;
        $backupDir = storage_path("app/{$this->backupPath}");

        foreach ($this->listBackups() as $backup) {
            if ($backup['created_at'] < $cutoffDate) {
                if (unlink("{$backupDir}/{$backup['name']}")) {
                    $deleted++;
                    Log::info('Old backup deleted', [
                        'backup' => $backup['name'],
                    ]);
                }
            }
        }

        return $deleted;
    }

    public function verifyIntegrity(): array
    {
        $checks = [
            'database_connection' => true,
            'recent_backup_exists' => false,
            'replication_status' => 'unknown',
            'redis_health' => 'unknown',
        ];

        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $checks['database_connection'] = false;
        }

        $backups = $this->listBackups();
        if (!empty($backups)) {
            $recentBackup = $backups[0];
            $checks['recent_backup_exists'] = (now()->timestamp - $recentBackup['created_at']) < 86400;
        }

        return $checks;
    }

    public function scheduleAutomaticBackups(): void
    {
        // This would be called from the scheduler
        $this->createBackup('automatic');
        $this->deleteOldBackups(30);
    }
}
