<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines which server will power your Octane application.
    | By default, the value is set to "swoole", but you are free to change
    | this to "roadrunner" if that's your preferred HTTP application server.
    |
    | Supported: "swoole", "roadrunner"
    |
    */

    'server' => env('OCTANE_SERVER', 'swoole'),

    /*
    |--------------------------------------------------------------------------
    | Octane Configuration
    |--------------------------------------------------------------------------
    |
    | Swoole & RoadRunner configuration options that will be passed to the
    | server when it is started. These options are sensitive and should be
    | carefully reviewed before being altered in your application.
    |
    */

    'swoole' => [
        'options' => [
            'worker_num' => env('SWOOLE_WORKERS', 4),
            'task_worker_num' => env('SWOOLE_TASK_WORKERS', 2),
            'backlog' => 128,
            'max_request' => 500,
            'max_request_execution_time' => 30,
        ],
    ],

    'roadrunner' => [
        'listeners' => [
            '0.0.0.0:8000',
        ],

        'subnets' => [
            '127.0.0.1/8',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ],

        'relay' => env('RR_RELAY', 'tcp://127.0.0.1:6001'),

        'relay_timeout' => '60s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | These listeners will be executed when Octane starts and when it receives
    | a request. They provide an opportunity to bootstrap your application in
    | a way that's compatible with long-running PHP application servers.
    |
    */

    'listeners' => [
        // ...
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection
    |--------------------------------------------------------------------------
    |
    | Octane can automatically collect garbage every n requests to ensure
    | that long-running processes do not allocate unbounded memory. You may
    | disable this option if you have manually optimized memory management.
    |
    */

    'garbage_collection_interval' => 50,

    /*
    |--------------------------------------------------------------------------
    | Warm Cache
    |--------------------------------------------------------------------------
    |
    | Octane can warm your application cache when the server starts. You are
    | free to disable this option if you would prefer that the cache remains
    | cold when the application starts. The cache will then be warmed as needed.
    |
    */

    'warm_cache_on_restart' => true,
];
