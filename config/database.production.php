<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. PRODUCTION:
    | MySQL with optimized settings for enterprise scale.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections - PRODUCTION OPTIMIZED
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | PRODUCTION: Enterprise-grade MySQL configuration with performance
    | optimizations and security enhancements.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => env('DB_ENGINE', 'InnoDB'),
            'options' => extension_loaded('pdo_mysql')
                ? (function () {
                    $options = [
                        PDO::ATTR_TIMEOUT => 30,
                        PDO::ATTR_PERSISTENT => true,
                    ];

                    if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                        $options[PDO::MYSQL_ATTR_SSL_CA] = env('MYSQL_ATTR_SSL_CA');
                    }

                    if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                        $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
                    }

                    if (defined('PDO::MYSQL_ATTR_FOUND_ROWS')) {
                        $options[PDO::MYSQL_ATTR_FOUND_ROWS] = true;
                    }

                    if (defined('PDO::MYSQL_ATTR_MAX_BUFFER_SIZE')) {
                        $options[PDO::MYSQL_ATTR_MAX_BUFFER_SIZE] = 16 * 1024 * 1024;
                    }

                    return array_filter($options, static function ($value) {
                        return !is_null($value);
                    });
                })()
                : [],
            'pool' => [
                'min_connections' => env('DB_POOL_MIN_CONNECTIONS', 5),
                'max_connections' => env('DB_POOL_MAX_CONNECTIONS', 100),
                'acquire_timeout' => env('DB_POOL_ACQUIRE_TIMEOUT', 60),
                'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 600),
                'retry_delay' => env('DB_POOL_RETRY_DELAY', 100),
            ],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases - PRODUCTION OPTIMIZED
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. PRODUCTION: Multi-DB setup for different
    | purposes with connection pooling.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', false),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
            'timeout' => env('REDIS_TIMEOUT', 5),
            'read_timeout' => env('REDIS_READ_TIMEOUT', 5),
            'connect_timeout' => env('REDIS_CONNECT_TIMEOUT', 5),
            'retry_connection_on_error' => true,
            'lazy_connect' => true,
            'persistent' => false,
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],

        'session' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_SESSION_DB', 2),
        ],

        'queue' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_QUEUE_DB', 3),
        ],

        'horizon' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_HORIZON_DB', 4),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Production Database Configuration
    |--------------------------------------------------------------------------
    */

    'production' => [
        'enable_query_logging' => false,
        'slow_query_threshold' => 2000, // milliseconds
        'enable_performance_monitoring' => true,
        'connection_pooling' => true,
        'connection_retry_logic' => true,
        'connection_validation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security Configuration - PRODUCTION
    |--------------------------------------------------------------------------
    */

    'security' => [
        'enable_encryption_at_rest' => true,
        'enable_ssl_connections' => true,
        'password_rotation_days' => 90,
        'audit_logging' => true,
        'access_control' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Backup & Recovery - PRODUCTION
    |--------------------------------------------------------------------------
    */

    'backup' => [
        'enabled' => true,
        'automated_backups' => true,
        'backup_frequency' => 'daily',
        'backup_retention_days' => 30,
        'backup_encryption' => true,
        'backup_location' => 'local',
    ],

];
