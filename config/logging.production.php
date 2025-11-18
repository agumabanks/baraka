<?php

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. PRODUCTION: Use stack for multiple channels.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels - PRODUCTION ENTERPRISE
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. PRODUCTION:
    | Enterprise-grade logging with structured output and monitoring.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'performance', 'security', 'webhooks'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'replace_placeholders' => true,
            'formatter' => JsonFormatter::class,
            'formatter_with' => [
                'includeStack' => true,
                'includeContext' => true,
            ],
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => env('LOG_DAYS', 30),
            'replace_placeholders' => true,
            'formatter' => JsonFormatter::class,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Baraka Logistics',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
            'formatter' => JsonFormatter::class,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
            'formatter' => JsonFormatter::class,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/emergency.log'),
        ],

        // Production-specific channels
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7,
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 90, // Keep security logs longer
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        'metrics' => [
            'driver' => 'daily',
            'path' => storage_path('logs/metrics.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 14,
            'formatter' => JsonFormatter::class,
        ],

        'webhooks' => [
            'driver' => 'single',
            'path' => storage_path('logs/webhooks.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'formatter' => JsonFormatter::class,
            'processors' => [PsrLogMessageProcessor::class, UidProcessor::class],
        ],

        'database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/database.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => 14,
            'formatter' => JsonFormatter::class,
        ],

        'queue' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queue.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 14,
            'formatter' => JsonFormatter::class,
        ],

        'cache' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cache.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 7,
            'formatter' => JsonFormatter::class,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 14,
            'formatter' => JsonFormatter::class,
        ],

        'user_activities' => [
            'driver' => 'daily',
            'path' => storage_path('logs/user_activities.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30,
            'formatter' => JsonFormatter::class,
        ],

        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 365, // Keep audit logs for 1 year
            'formatter' => JsonFormatter::class,
        ],

        'notifications' => [
            'driver' => 'daily',
            'path' => storage_path('logs/notifications.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 14,
            'formatter' => JsonFormatter::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Logging Configuration
    |--------------------------------------------------------------------------
    */

    'production' => [
        'enable_structured_logging' => true,
        'enable_correlation_ids' => true,
        'enable_performance_tracking' => true,
        'enable_security_monitoring' => true,
        'log_request_response' => true,
        'log_sql_queries' => false, // Only for debugging
        'max_log_size' => '100M',
        'auto_rotate_logs' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Alerting & Monitoring - PRODUCTION
    |--------------------------------------------------------------------------
    */

    'alerting' => [
        'enabled' => true,
        'error_threshold' => 100,
        'critical_threshold' => 10,
        'response_time_threshold' => 5000, // ms
        'database_error_threshold' => 50,
        'queue_failure_threshold' => 20,
    ],

];
