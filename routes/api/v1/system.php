<?php

use App\Http\Controllers\Api\V1\SystemController;
use Illuminate\Support\Facades\Route;

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'api.throttle'])->group(function () {
    
    // System operations
    Route::middleware(['throttle:60,1'])->group(function () {
        
        // System Information permissions
        Route::middleware('permission:system_read')->group(function () {
            // System Information
            Route::get('system/info', [SystemController::class, 'info'])
                ->name('api.system.info');

            Route::get('system/status', [SystemController::class, 'status'])
                ->name('api.system.status');

            Route::get('system/health', [SystemController::class, 'health'])
                ->name('api.system.health');

            Route::get('system/version', [SystemController::class, 'version'])
                ->name('api.system.version');

            Route::get('system/environment', [SystemController::class, 'environment'])
                ->name('api.system.environment');

            Route::get('system/uptime', [SystemController::class, 'uptime'])
                ->name('api.system.uptime');

            // Settings & Configuration
            Route::get('system/settings', [SystemController::class, 'settings'])
                ->name('api.system.settings');

            Route::get('system/settings/general', [SystemController::class, 'generalSettings'])
                ->name('api.system.general-settings');

            Route::get('system/settings/security', [SystemController::class, 'securitySettings'])
                ->name('api.system.security-settings');

            Route::get('system/settings/performance', [SystemController::class, 'performanceSettings'])
                ->name('api.system.performance-settings');

            // Languages & Translations
            Route::get('system/languages', [SystemController::class, 'languages'])
                ->name('api.system.languages');

            Route::get('system/languages/supported', [SystemController::class, 'supportedLanguages'])
                ->name('api.system.supported-languages');

            Route::get('system/translations/{locale}/completion', [SystemController::class, 'translationCompletion'])
                ->name('api.system.translation-completion');

            // Permissions & Roles
            Route::get('system/permissions', [SystemController::class, 'permissions'])
                ->name('api.system.permissions');

            Route::get('system/permissions/definitions', [SystemController::class, 'permissionDefinitions'])
                ->name('api.system.permission-definitions');

            Route::get('system/roles', [SystemController::class, 'roles'])
                ->name('api.system.roles');

            Route::get('system/role-hierarchy', [SystemController::class, 'roleHierarchy'])
                ->name('api.system.role-hierarchy');

            // Features & Modules
            Route::get('system/features', [SystemController::class, 'features'])
                ->name('api.system.features');

            Route::get('system/modules', [SystemController::class, 'modules'])
                ->name('api.system.modules');

            Route::get('system/integrations', [SystemController::class, 'integrations'])
                ->name('api.system.integrations');

            // Statistics
            Route::get('system/statistics/summary', [SystemController::class, 'statisticsSummary'])
                ->name('api.system.statistics-summary');

            Route::get('system/statistics/users', [SystemController::class, 'userStatistics'])
                ->name('api.system.user-statistics');

            Route::get('system/statistics/activity', [SystemController::class, 'activityStatistics'])
                ->name('api.system.activity-statistics');
        });

        // System Update permissions
        Route::middleware('permission:system_update')->group(function () {
            // Settings Management
            Route::put('system/settings', [SystemController::class, 'updateSettings'])
                ->name('api.system.update-settings');

            Route::put('system/settings/general', [SystemController::class, 'updateGeneralSettings'])
                ->name('api.system.update-general-settings');

            Route::put('system/settings/security', [SystemController::class, 'updateSecuritySettings'])
                ->name('api.system.update-security-settings');

            Route::put('system/settings/performance', [SystemController::class, 'updatePerformanceSettings'])
                ->name('api.system.update-performance-settings');

            // Language Management
            Route::post('system/languages/set-default', [SystemController::class, 'setDefaultLanguage'])
                ->name('api.system.set-default-language');

            Route::post('system/languages/sync-from-files', [SystemController::class, 'syncLanguagesFromFiles'])
                ->name('api.system.sync-languages-from-files');

            // Feature Management
            Route::post('system/features/{feature}/enable', [SystemController::class, 'enableFeature'])
                ->name('api.system.enable-feature');

            Route::post('system/features/{feature}/disable', [SystemController::class, 'disableFeature'])
                ->name('api.system.disable-feature');

            // Cache Management
            Route::post('system/cache/clear', [SystemController::class, 'clearCache'])
                ->name('api.system.clear-cache');

            Route::post('system/cache/warm', [SystemController::class, 'warmCache'])
                ->name('api.system.warm-cache');

            Route::get('system/cache/status', [SystemController::class, 'cacheStatus'])
                ->name('api.system.cache-status');

            // Maintenance
            Route::post('system/maintenance/enable', [SystemController::class, 'enableMaintenance'])
                ->name('api.system.enable-maintenance');

            Route::post('system/maintenance/disable', [SystemController::class, 'disableMaintenance'])
                ->name('api.system.disable-maintenance');

            Route::get('system/maintenance/status', [SystemController::class, 'maintenanceStatus'])
                ->name('api.system.maintenance-status');

            // Backup & Restore
            Route::post('system/backup/create', [SystemController::class, 'createBackup'])
                ->name('api.system.create-backup');

            Route::get('system/backup/history', [SystemController::class, 'backupHistory'])
                ->name('api.system.backup-history');

            Route::post('system/backup/restore', [SystemController::class, 'restoreBackup'])
                ->name('api.system.restore-backup');

            // System Tools
            Route::post('system/tools/optimize', [SystemController::class, 'optimizeSystem'])
                ->name('api.system.optimize');

            Route::post('system/tools/clean-up', [SystemController::class, 'cleanUp'])
                ->name('api.system.clean-up');

            Route::post('system/tools/integrity-check', [SystemController::class, 'integrityCheck'])
                ->name('api.system.integrity-check');

            Route::get('system/tools/disk-usage', [SystemController::class, 'diskUsage'])
                ->name('api.system.disk-usage');

            Route::get('system/tools/database-status', [SystemController::class, 'databaseStatus'])
                ->name('api.system.database-status');
        });

        // Notification Management permissions
        Route::middleware('permission:notification_manage')->group(function () {
            // Notifications
            Route::get('system/notifications', [SystemController::class, 'notifications'])
                ->name('api.system.notifications');

            Route::get('system/notifications/unread', [SystemController::class, 'unreadNotifications'])
                ->name('api.system.unread-notifications');

            Route::get('system/notifications/{notification}', [SystemController::class, 'showNotification'])
                ->name('api.system.show-notification');

            Route::post('system/notifications', [SystemController::class, 'sendNotification'])
                ->name('api.system.send-notification');

            Route::put('system/notifications/{notification}/read', [SystemController::class, 'markNotificationRead'])
                ->name('api.system.mark-notification-read');

            Route::put('system/notifications/mark-all-read', [SystemController::class, 'markAllNotificationsRead'])
                ->name('api.system.mark-all-notifications-read');

            Route::delete('system/notifications/{notification}', [SystemController::class, 'deleteNotification'])
                ->name('api.system.delete-notification');

            // Notification Templates
            Route::get('system/notification-templates', [SystemController::class, 'notificationTemplates'])
                ->name('api.system.notification-templates');

            Route::post('system/notification-templates', [SystemController::class, 'createNotificationTemplate'])
                ->name('api.system.create-notification-template');

            Route::put('system/notification-templates/{template}', [SystemController::class, 'updateNotificationTemplate'])
                ->name('api.system.update-notification-template');

            Route::delete('system/notification-templates/{template}', [SystemController::class, 'deleteNotificationTemplate'])
                ->name('api.system.delete-notification-template');

            // Broadcast Settings
            Route::get('system/broadcast/settings', [SystemController::class, 'broadcastSettings'])
                ->name('api.system.broadcast-settings');

            Route::put('system/broadcast/settings', [SystemController::class, 'updateBroadcastSettings'])
                ->name('api.system.update-broadcast-settings');

            Route::post('system/broadcast/test', [SystemController::class, 'testBroadcast'])
                ->name('api.system.test-broadcast');
        });
    });

    // System Monitoring (higher rate limit for real-time data)
    Route::middleware(['throttle:120,1'])->group(function () {
        Route::get('system/metrics/current', [SystemController::class, 'currentMetrics'])
            ->name('api.system.current-metrics');

        Route::get('system/metrics/real-time', [SystemController::class, 'realTimeMetrics'])
                ->name('api.system.real-time-metrics');

        Route::get('system/metrics/cpu', [SystemController::class, 'cpuMetrics'])
            ->name('api.system.cpu-metrics');

        Route::get('system/metrics/memory', [SystemController::class, 'memoryMetrics'])
            ->name('api.system.memory-metrics');

        Route::get('system/metrics/database', [SystemController::class, 'databaseMetrics'])
            ->name('api.system.database-metrics');
    });
});

// System webhooks (for external services integration)
Route::middleware(['api.prefix'])->group(function () {
    Route::post('webhooks/system/backup-completed', [SystemController::class, 'backupCompletedWebhook'])
        ->name('api.webhooks.system.backup-completed');

    Route::post('webhooks/system/maintenance-enabled', [SystemController::class, 'maintenanceEnabledWebhook'])
        ->name('api.webhooks.system.maintenance-enabled');

    Route::post('webhooks/system/maintenance-disabled', [SystemController::class, 'maintenanceDisabledWebhook'])
        ->name('api.webhooks.system.maintenance-disabled');

    Route::post('webhooks/system/error-detected', [SystemController::class, 'errorDetectedWebhook'])
        ->name('api.webhooks.system.error-detected');
});

// Public system information (no authentication required for basic info)
Route::middleware(['api.prefix', 'throttle:100,1'])->group(function () {
    Route::get('system/public/info', [SystemController::class, 'publicInfo'])
        ->name('api.system.public-info');

    Route::get('system/public/health', [SystemController::class, 'publicHealth'])
        ->name('api.system.public-health');

    Route::get('system/public/status', [SystemController::class, 'publicStatus'])
        ->name('api.system.public-status');

    Route::get('system/public/announcement', [SystemController::class, 'publicAnnouncement'])
        ->name('api.system.public-announcement');
});
