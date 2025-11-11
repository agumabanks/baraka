<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the path where Horizon will be accessible from. Feel free to
    | change this path to anything you like. Note that the URI will not
    | affect the path of the API that this Horizon UI will communicate with.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection that Horizon will use to
    | communicate with Redis. It will submit jobs, process jobs, and monitor
    | queued jobs.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you have multiple installations running
    | in the same Redis instance so that they do not have problems.
    |
    */

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can just stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | These are the alert thresholds for queue wait times. When a queue wait
    | time exceeds a threshold, Horizon will send an alert. If you set a
    | value to zero, Horizon will not send an alert for that metric.
    |
    */

    'waits' => [
        'redis:default' => 60,
        'redis:webhooks' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Job Retention (Hours)
    |--------------------------------------------------------------------------
    |
    | Horizon stores all of your failed queue jobs in the Redis cache so that
    | they may be examined even after the job has been processed. You may
    | modify the amount of time the jobs will be retained by this setting.
    |
    */

    'failed_job_retention' => 1440,

    /*
    |--------------------------------------------------------------------------
    | Trim Snapshots Every (Hours)
    |--------------------------------------------------------------------------
    |
    | Horizon allows you to beautifully view information on each job for a
    | full hour after it is processed. After that, the historical snapshot
    | will only be retained for a given length of time. You may change the
    | number of hours that snapshots are retained.
    |
    */

    'trim_snapshots_every' => 24,

    /*
    |--------------------------------------------------------------------------
    | Job Metrics
    |--------------------------------------------------------------------------
    |
    | Horizon can track various metrics about your jobs to give you better
    | insight into their performance and throughput. You may disable these
    | metrics if you decide they are using too much of your resources.
    |
    */

    'metrics' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon will automatically terminate itself
    | if it has processed a job in the last minute. You may disable this if
    | you wish to let Horizon run longer between automatic terminations.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum amount of memory that Horizon worker
    | processes are allowed to consume before they are terminated and restarted.
    | You should set this value based on the free memory available on the host.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Work Timeout (Seconds)
    |--------------------------------------------------------------------------
    |
    | The default timeout that will be used by queue:work to determine how
    | long a child process should run before it is terminated and a new
    | child process should be spawned to continue processing jobs.
    |
    */

    'timeout' => 60,

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify queue configurations that will be
    | used by Horizon. Each queue should contain a minimum and maximum
    | number of workers.
    |
    */

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default', 'notifications'],
                'balance' => 'simple',
                'processes' => 10,
                'tries' => 2,
            ],
            'supervisor-2' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'simple',
                'processes' => 5,
                'tries' => 5,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 1,
            ],
        ],
    ],
];
