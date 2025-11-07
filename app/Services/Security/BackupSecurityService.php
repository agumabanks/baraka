<?php

namespace App\Services\Security;

use App\Services\Security\EncryptionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Exception;

class BackupSecurityService
{
    private EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Create encrypted backup
     */
    public function createSecureBackup(string $type = 'full', array $options = []): array
    {
        try {
            $backupId = uniqid('backup_');
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupPath = "backups/{$type}/{$backupId}_{$timestamp}";
            
            // Log backup creation attempt
            Log::channel('backup')->info('Backup creation started', [
                'backup_id' => $backupId,
                'type' => $type,
                'options' => $options,
            ]);
            
            switch ($type) {
                case 'full':
                    return $this->createFullBackup($backupPath, $options);
                case 'database':
                    return $this->createDatabaseBackup($backupPath, $options);
                case 'files':
                    return $this->createFileBackup($backupPath, $options);
                default:
                    throw new Exception('Invalid backup type');
            }
            
        } catch (Exception $e) {
            Log::channel('backup')->error('Backup creation failed', [
                'backup_id' => $backupId ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Backup creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Create full system backup
     */
    private function createFullBackup(string $backupPath, array $options): array
    {
        $results = [
            'backup_id' => basename($backupPath),
            'status' => 'in_progress',
            'components' => [],
            'started_at' => now(),
        ];
        
        // Create database backup
        $dbBackup = $this->createDatabaseBackup($backupPath . '/database', $options);
        $results['components']['database'] = $dbBackup;
        
        // Create file backup
        $fileBackup = $this->createFileBackup($backupPath . '/files', $options);
        $results['components']['files'] = $fileBackup;
        
        // Create configuration backup
        $configBackup = $this->createConfigurationBackup($backupPath . '/config', $options);
        $results['components']['config'] = $configBackup;
        
        // Create backup manifest
        $this->createBackupManifest($backupPath, $results);
        
        // Verify backup integrity
        $this->verifyBackupIntegrity($backupPath);
        
        $results['status'] = 'completed';
        $results['completed_at'] = now();
        $results['duration'] = $results['completed_at']->diffForHumans($results['started_at'], true);
        
        // Log successful backup
        Log::channel('backup')->info('Full backup completed', $results);
        
        return $results;
    }

    /**
     * Create database backup with encryption
     */
    private function createDatabaseBackup(string $backupPath, array $options): array
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        
        // Create database dump
        $dumpFile = storage_path("app/{$backupPath}/dump.sql");
        $command = "mysqldump -h{$dbHost} -u{$dbUser} -p{$dbPass} {$dbName} > {$dumpFile}";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Database dump failed');
        }
        
        // Encrypt the dump file
        $encryptedFile = storage_path("app/{$backupPath}/dump.sql.enc");
        $dumpContent = file_get_contents($dumpFile);
        $encryptedContent = $this->encryptionService->encryptData($dumpContent, 'backup');
        file_put_contents($encryptedFile, $encryptedContent);
        
        // Remove unencrypted file
        unlink($dumpFile);
        
        $backupInfo = [
            'type' => 'database',
            'encrypted_file' => $encryptedFile,
            'original_size' => strlen($dumpContent),
            'encrypted_size' => strlen($encryptedContent),
            'created_at' => now(),
        ];
        
        // Save backup metadata
        $this->saveBackupMetadata($backupPath, 'database', $backupInfo);
        
        return $backupInfo;
    }

    /**
     * Create file backup with encryption
     */
    private function createFileBackup(string $backupPath, array $options): array
    {
        $includePaths = $options['include_paths'] ?? [
            'app',
            'config',
            'resources',
            'storage',
            'public',
        ];
        
        $excludePatterns = $options['exclude_patterns'] ?? [
            'node_modules',
            'vendor',
            '.git',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
        ];
        
        $backupInfo = [
            'type' => 'files',
            'files' => [],
            'total_size' => 0,
            'compressed_size' => 0,
            'created_at' => now(),
        ];
        
        foreach ($includePaths as $path) {
            $fullPath = base_path($path);
            if (is_dir($fullPath)) {
                $files = $this->backupDirectory($fullPath, $path, $backupPath, $excludePatterns);
                $backupInfo['files'] = array_merge($backupInfo['files'], $files);
            }
        }
        
        // Calculate total sizes
        foreach ($backupInfo['files'] as $file) {
            $backupInfo['total_size'] += $file['size'];
        }
        
        // Save backup metadata
        $this->saveBackupMetadata($backupPath, 'files', $backupInfo);
        
        return $backupInfo;
    }

    /**
     * Create configuration backup
     */
    private function createConfigurationBackup(string $backupPath, array $options): array
    {
        $configFiles = [
            '.env',
            'config/app.php',
            'config/database.php',
            'config/auth.php',
            'config/filesystems.php',
            'config/cache.php',
            'config/session.php',
        ];
        
        $backupInfo = [
            'type' => 'config',
            'files' => [],
            'created_at' => now(),
        ];
        
        foreach ($configFiles as $configFile) {
            $sourcePath = base_path($configFile);
            if (file_exists($sourcePath)) {
                $backupFile = storage_path("app/{$backupPath}/" . basename($configFile));
                $content = file_get_contents($sourcePath);
                
                // Remove sensitive data from config files
                $content = $this->sanitizeConfigFile($content);
                
                // Encrypt configuration
                $encryptedContent = $this->encryptionService->encryptData($content, 'backup');
                file_put_contents($backupFile . '.enc', $encryptedContent);
                
                $backupInfo['files'][] = [
                    'original' => $configFile,
                    'backup' => $backupFile . '.enc',
                    'size' => strlen($content),
                    'encrypted_size' => strlen($encryptedContent),
                ];
            }
        }
        
        // Save backup metadata
        $this->saveBackupMetadata($backupPath, 'config', $backupInfo);
        
        return $backupInfo;
    }

    /**
     * Restore from secure backup
     */
    public function restoreFromBackup(string $backupId, array $options = []): array
    {
        try {
            // Log restore attempt
            Log::channel('backup')->info('Backup restore started', [
                'backup_id' => $backupId,
                'options' => $options,
                'initiated_by' => auth()->id(),
            ]);
            
            // Verify backup integrity first
            $this->verifyBackupIntegrity("backups/full/{$backupId}");
            
            $results = [
                'backup_id' => $backupId,
                'status' => 'in_progress',
                'components' => [],
                'started_at' => now(),
            ];
            
            // Create pre-restore backup for safety
            if ($options['create_safety_backup'] ?? true) {
                $results['safety_backup'] = $this->createSecureBackup('full');
            }
            
            // Restore components
            $manifest = $this->loadBackupManifest("backups/full/{$backupId}");
            
            foreach ($manifest['components'] as $component => $info) {
                $results['components'][$component] = $this->restoreComponent($component, $info, $backupId, $options);
            }
            
            $results['status'] = 'completed';
            $results['completed_at'] = now();
            $results['duration'] = $results['completed_at']->diffForHumans($results['started_at'], true);
            
            // Log successful restore
            Log::channel('backup')->info('Backup restore completed', $results);
            
            return $results;
            
        } catch (Exception $e) {
            Log::channel('backup')->error('Backup restore failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Backup restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify backup integrity
     */
    private function verifyBackupIntegrity(string $backupPath): void
    {
        $manifestFile = storage_path("app/{$backupPath}/manifest.json");
        
        if (!file_exists($manifestFile)) {
            throw new Exception('Backup manifest not found');
        }
        
        $manifest = json_decode(file_get_contents($manifestFile), true);
        
        // Verify each component
        foreach ($manifest['components'] as $component => $info) {
            switch ($component) {
                case 'database':
                    $this->verifyDatabaseBackup($info);
                    break;
                case 'files':
                    $this->verifyFileBackup($info);
                    break;
                case 'config':
                    $this->verifyConfigBackup($info);
                    break;
            }
        }
        
        // Verify backup age
        $backupAge = now()->diffInDays($manifest['created_at']);
        $maxAge = 30; // 30 days
        
        if ($backupAge > $maxAge) {
            Log::channel('backup')->warning('Backup older than recommended', [
                'backup_id' => basename($backupPath),
                'age_days' => $backupAge,
                'max_age' => $maxAge,
            ]);
        }
    }

    /**
     * Schedule automatic backups
     */
    public function scheduleAutomaticBackups(): void
    {
        $schedule = config('backup.schedule', [
            'full' => '0 2 * * 0',     // Weekly full backup on Sunday at 2 AM
            'database' => '0 2 * * *', // Daily database backup at 2 AM
            'incremental' => '0 */6 * * *', // Every 6 hours
        ]);
        
        foreach ($schedule as $type => $cron) {
            // This would typically integrate with Laravel's task scheduler
            Log::channel('backup')->info('Backup scheduled', [
                'type' => $type,
                'schedule' => $cron,
            ]);
        }
    }

    /**
     * Clean up old backups
     */
    public function cleanupOldBackups(): array
    {
        $retentionDays = config('backup.retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);
        
        $cleaned = [
            'deleted_backups' => 0,
            'freed_space' => 0,
            'errors' => [],
        ];
        
        $backupDirectories = Storage::disk('local')->directories('backups');
        
        foreach ($backupDirectories as $dir) {
            try {
                $files = Storage::disk('local')->allFiles($dir);
                
                foreach ($files as $file) {
                    $fileTime = Storage::disk('local')->lastModified($file);
                    if ($fileTime < $cutoffDate->timestamp) {
                        $size = Storage::disk('local')->size($file);
                        Storage::disk('local')->delete($file);
                        $cleaned['deleted_backups']++;
                        $cleaned['freed_space'] += $size;
                    }
                }
                
            } catch (Exception $e) {
                $cleaned['errors'][] = $e->getMessage();
            }
        }
        
        Log::channel('backup')->info('Backup cleanup completed', $cleaned);
        
        return $cleaned;
    }

    /**
     * Backup directory recursively
     */
    private function backupDirectory(string $sourceDir, string $relativePath, string $backupPath, array $excludePatterns): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            $relativeFilePath = str_replace(base_path() . '/', '', $filePath);
            
            // Skip excluded patterns
            foreach ($excludePatterns as $pattern) {
                if (str_contains($relativeFilePath, $pattern)) {
                    continue 2;
                }
            }
            
            if ($file->isFile()) {
                $content = file_get_contents($filePath);
                $backupFile = storage_path("app/{$backupPath}/{$relativePath}/" . basename($file->getPathname()));
                
                // Create directory if it doesn't exist
                $backupDir = dirname($backupFile);
                if (!is_dir($backupDir)) {
                    mkdir($backupDir, 0755, true);
                }
                
                // Encrypt and save file
                $encryptedContent = $this->encryptionService->encryptData($content, 'backup');
                file_put_contents($backupFile . '.enc', $encryptedContent);
                
                $files[] = [
                    'original' => $relativeFilePath,
                    'backup' => $backupFile . '.enc',
                    'size' => strlen($content),
                    'encrypted_size' => strlen($encryptedContent),
                ];
            }
        }
        
        return $files;
    }

    /**
     * Sanitize configuration file content
     */
    private function sanitizeConfigFile(string $content): string
    {
        $sensitiveKeys = [
            'DB_PASSWORD',
            'MAIL_PASSWORD',
            'REDIS_PASSWORD',
            'AWS_SECRET_ACCESS_KEY',
            'STRIPE_SECRET',
        ];
        
        foreach ($sensitiveKeys as $key) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}=[REDACTED]", $content);
        }
        
        return $content;
    }

    /**
     * Create backup manifest
     */
    private function createBackupManifest(string $backupPath, array $results): void
    {
        $manifest = [
            'backup_id' => basename($backupPath),
            'created_at' => $results['started_at'],
            'type' => 'full',
            'version' => '1.0',
            'components' => $results['components'],
            'checksum' => hash('sha256', json_encode($results['components'])),
        ];
        
        $manifestFile = storage_path("app/{$backupPath}/manifest.json");
        $manifestDir = dirname($manifestFile);
        
        if (!is_dir($manifestDir)) {
            mkdir($manifestDir, 0755, true);
        }
        
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Load backup manifest
     */
    private function loadBackupManifest(string $backupPath): array
    {
        $manifestFile = storage_path("app/{$backupPath}/manifest.json");
        
        if (!file_exists($manifestFile)) {
            throw new Exception('Backup manifest not found');
        }
        
        return json_decode(file_get_contents($manifestFile), true);
    }

    /**
     * Save backup metadata
     */
    private function saveBackupMetadata(string $backupPath, string $component, array $info): void
    {
        $metadataFile = storage_path("app/{$backupPath}/{$component}_metadata.json");
        $metadataDir = dirname($metadataFile);
        
        if (!is_dir($metadataDir)) {
            mkdir($metadataDir, 0755, true);
        }
        
        file_put_contents($metadataFile, json_encode($info, JSON_PRETTY_PRINT));
    }

    /**
     * Verify database backup
     */
    private function verifyDatabaseBackup(array $info): void
    {
        $file = $info['encrypted_file'];
        if (!file_exists($file)) {
            throw new Exception('Database backup file missing');
        }
        
        // Test decryption
        $encryptedContent = file_get_contents($file);
        try {
            $this->encryptionService->decryptData($encryptedContent, 'backup');
        } catch (Exception $e) {
            throw new Exception('Database backup decryption failed');
        }
    }

    /**
     * Verify file backup
     */
    private function verifyFileBackup(array $info): void
    {
        foreach ($info['files'] as $file) {
            if (!file_exists($file['backup'])) {
                throw new Exception("Backup file missing: {$file['backup']}");
            }
        }
    }

    /**
     * Verify configuration backup
     */
    private function verifyConfigBackup(array $info): void
    {
        foreach ($info['files'] as $file) {
            if (!file_exists($file['backup'])) {
                throw new Exception("Config backup file missing: {$file['backup']}");
            }
        }
    }
}