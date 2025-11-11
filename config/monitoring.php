<?php

return [
    'prometheus' => [
        'enabled' => env('PROMETHEUS_ENABLED', false),
        'push_gateway_url' => env('PROMETHEUS_PUSH_GATEWAY_URL', 'http://localhost:9091'),
        'job_name' => env('APP_NAME', 'baraka'),
    ],

    'sentry' => [
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'enabled' => env('SENTRY_ENABLED', false),
        'environment' => env('APP_ENV', 'production'),
        'release' => env('APP_VERSION', '1.0.0'),
    ],

    'logging' => [
        'structured' => true,
        'json_format' => true,
        'include_context' => true,
        'channels' => [
            'metrics' => [
                'driver' => 'daily',
                'path' => storage_path('logs/metrics.log'),
                'level' => env('LOG_LEVEL', 'info'),
                'days' => 14,
                'formatter' => \Monolog\Formatter\JsonFormatter::class,
            ],
            'webhooks' => [
                'driver' => 'daily',
                'path' => storage_path('logs/webhooks.log'),
                'level' => env('LOG_LEVEL', 'info'),
                'days' => 30,
            ],
            'performance' => [
                'driver' => 'daily',
                'path' => storage_path('logs/performance.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 7,
            ],
        ],
    ],

    'performance' => [
        'slow_query_threshold_ms' => 1000,
        'slow_request_threshold_ms' => 5000,
        'enable_query_logging' => env('APP_DEBUG', false),
    ],

    'disaster_recovery' => [
        'backup_enabled' => env('BACKUP_ENABLED', true),
        'backup_schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'), // 2 AM daily
        'backup_retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'backup_location' => env('BACKUP_LOCATION', 'backups'),
    ],

    'slo' => [
        'availability' => 99.9, // 99.9%
        'latency_p99' => 1000, // 1 second
        'error_rate' => 0.1, // 0.1%
    ],
];
