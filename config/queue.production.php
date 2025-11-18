<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. PRODUCTION: Redis for optimal performance.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections - PRODUCTION OPTIMIZED
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. PRODUCTION: Enterprise-grade configuration.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
            'timeout' => 60,
            'sleep' => 3,
            'tries' => 3,
            'max_exceptions' => 3,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
        'timeout' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. PRODUCTION: Enhanced failed job tracking and alerting.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
        'queue' => 'failed_jobs',
        'timeout' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Queue Worker Configuration
    |--------------------------------------------------------------------------
    */

    'workers' => [
        'processes' => env('QUEUE_WORKERS', 8),
        'max_jobs' => env('QUEUE_MAX_JOBS', 1000),
        'max_time' => env('QUEUE_MAX_TIME', 3600),
        'sleep' => env('QUEUE_SLEEP', 3),
        'tries' => env('QUEUE_TRIES', 3),
        'timeout' => env('QUEUE_TIMEOUT', 60),
        'force' => false,
        'memory' => env('QUEUE_MEMORY_LIMIT', 128),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Supervisors - PRODUCTION
    |--------------------------------------------------------------------------
    */

    'supervisors' => [
        'high_priority' => [
            'queue' => 'high_priority,default',
            'workers' => 4,
            'memory' => 256,
            'timeout' => 60,
            'sleep' => 2,
            'tries' => 3,
        ],
        'default_queue' => [
            'queue' => 'default',
            'workers' => 6,
            'memory' => 128,
            'timeout' => 60,
            'sleep' => 3,
            'tries' => 3,
        ],
        'low_priority' => [
            'queue' => 'low_priority',
            'workers' => 2,
            'memory' => 128,
            'timeout' => 60,
            'sleep' => 5,
            'tries' => 3,
        ],
        'notifications' => [
            'queue' => 'notifications',
            'workers' => 2,
            'memory' => 128,
            'timeout' => 30,
            'sleep' => 2,
            'tries' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring & Alerting - PRODUCTION
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled' => true,
        'max_queue_size' => 1000,
        'max_job_time' => 300, // 5 minutes
        'alert_threshold' => 80,
        'recheck_interval' => 60, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Health Checks - PRODUCTION
    |--------------------------------------------------------------------------
    */

    'health' => [
        'enabled' => true,
        'check_interval' => 30,
        'alert_on_failure' => true,
        'failure_threshold' => 5,
    ],

];
