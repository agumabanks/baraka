<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Test Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Enhanced Logistics Pricing System test suite
    |
    */

    // Test Environment Settings
    'environment' => [
        'name' => env('TEST_ENV', 'testing'),
        'app_debug' => env('TEST_APP_DEBUG', true),
        'app_url' => env('TEST_APP_URL', 'http://localhost'),
        'timezone' => env('TEST_TIMEZONE', 'UTC'),
        'faker_locale' => env('TEST_FAKER_LOCALE', 'en_US'),
    ],

    // Test Database Configuration
    'database' => [
        'default' => env('TEST_DB_CONNECTION', 'sqlite'),
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'mysql_testing' => [
                'driver' => 'mysql',
                'host' => env('TEST_DB_HOST', '127.0.0.1'),
                'port' => env('TEST_DB_PORT', '3306'),
                'database' => env('TEST_DB_DATABASE', 'logistics_testing'),
                'username' => env('TEST_DB_USERNAME', 'root'),
                'password' => env('TEST_DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ],
            'postgres_testing' => [
                'driver' => 'pgsql',
                'host' => env('TEST_DB_HOST', '127.0.0.1'),
                'port' => env('TEST_DB_PORT', '5432'),
                'database' => env('TEST_DB_DATABASE', 'logistics_testing'),
                'username' => env('TEST_DB_USERNAME', 'postgres'),
                'password' => env('TEST_DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
            ],
        ],
    ],

    // Cache Configuration for Testing
    'cache' => [
        'driver' => env('TEST_CACHE_DRIVER', 'array'),
        'prefix' => env('TEST_CACHE_PREFIX', 'logistics_testing_'),
        'stores' => [
            'array' => [
                'driver' => 'array',
                'serialize' => false,
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'cache',
                'lock_connection' => 'default',
            ],
        ],
    ],

    // Queue Configuration for Testing
    'queue' => [
        'default' => env('TEST_QUEUE_CONNECTION', 'sync'),
        'connections' => [
            'sync' => [
                'driver' => 'sync',
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
            ],
            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
            ],
        ],
    ],

    // Session Configuration for Testing
    'session' => [
        'driver' => env('TEST_SESSION_DRIVER', 'array'),
        'lifetime' => env('TEST_SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => storage_path('framework/sessions'),
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => env('TEST_SESSION_COOKIE', 'logistics_testing_session'),
        'path' => '/',
        'domain' => env('TEST_SESSION_DOMAIN', null),
        'secure' => env('TEST_SESSION_SECURE_COOKIE', false),
        'http_only' => true,
        'same_site' => 'lax',
    ],

    // Mail Configuration for Testing
    'mail' => [
        'default' => env('TEST_MAIL_MAILER', 'array'),
        'mailers' => [
            'array' => [
                'transport' => 'array',
            ],
            'smtp' => [
                'transport' => 'smtp',
                'host' => env('TEST_MAIL_HOST', 'mailhog'),
                'port' => env('TEST_MAIL_PORT', 1025),
                'encryption' => env('TEST_MAIL_ENCRYPTION', null),
                'username' => env('TEST_MAIL_USERNAME'),
                'password' => env('TEST_MAIL_PASSWORD'),
                'timeout' => null,
                'local_domain' => env('TEST_MAIL_EHLO_DOMAIN'),
            ],
        ],
    ],

    // Broadcast Configuration for Testing
    'broadcasting' => [
        'default' => env('TEST_BROADCAST_DRIVER', 'log'),
        'connections' => [
            'pusher' => [
                'driver' => 'pusher',
                'key' => env('TEST_PUSHER_APP_KEY'),
                'secret' => env('TEST_PUSHER_APP_SECRET'),
                'app_id' => env('TEST_PUSHER_APP_ID'),
                'options' => [
                    'cluster' => env('TEST_PUSHER_APP_CLUSTER'),
                    'useTLS' => true,
                ],
            ],
            'ably' => [
                'driver' => 'ably',
                'key' => env('TEST_ABLY_KEY'),
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
            ],
            'log' => [
                'driver' => 'log',
            ],
            'null' => [
                'driver' => 'null',
            ],
        ],
    ],

    // External Services Configuration for Testing
    'external_services' => [
        'carrier_apis' => [
            'fedex' => [
                'base_url' => env('TEST_FEDEX_BASE_URL', 'https://api-test.fedex.com'),
                'api_key' => env('TEST_FEDEX_API_KEY', 'test_key_123'),
                'secret' => env('TEST_FEDEX_SECRET', 'test_secret_123'),
                'timeout' => 30,
            ],
            'ups' => [
                'base_url' => env('TEST_UPS_BASE_URL', 'https://api-test.ups.com'),
                'api_key' => env('TEST_UPS_API_KEY', 'test_key_123'),
                'username' => env('TEST_UPS_USERNAME', 'test_user'),
                'password' => env('TEST_UPS_PASSWORD', 'test_pass'),
                'timeout' => 30,
            ],
            'dhl' => [
                'base_url' => env('TEST_DHL_BASE_URL', 'https://api-test.dhl.com'),
                'api_key' => env('TEST_DHL_API_KEY', 'test_key_123'),
                'timeout' => 30,
            ],
        ],
        'payment_gateways' => [
            'stripe' => [
                'base_url' => env('TEST_STRIPE_BASE_URL', 'https://api.stripe.com'),
                'secret_key' => env('TEST_STRIPE_SECRET_KEY', 'sk_test_123'),
                'webhook_secret' => env('TEST_STRIPE_WEBHOOK_SECRET', 'whsec_123'),
                'timeout' => 30,
            ],
            'paypal' => [
                'base_url' => env('TEST_PAYPAL_BASE_URL', 'https://api.sandbox.paypal.com'),
                'client_id' => env('TEST_PAYPAL_CLIENT_ID', 'test_client_id'),
                'client_secret' => env('TEST_PAYPAL_CLIENT_SECRET', 'test_secret'),
                'timeout' => 30,
            ],
        ],
        'fuel_indexes' => [
            'eia' => [
                'base_url' => env('TEST_EIA_BASE_URL', 'https://api-test.eia.gov'),
                'api_key' => env('TEST_EIA_API_KEY', 'test_key_123'),
                'cache_duration' => 3600, // 1 hour
            ],
        ],
    ],

    // Testing Performance Configuration
    'performance' => [
        'thresholds' => [
            'api_response_time' => 2000, // 2 seconds
            'quote_generation' => 1000, // 1 second
            'bulk_operations' => 10000, // 10 seconds
            'database_query' => 100, // 100ms
            'cache_lookup' => 50, // 50ms
        ],
        'load_testing' => [
            'concurrent_users' => 50,
            'duration' => 300, // 5 minutes
            'ramp_up_time' => 60, // 1 minute
        ],
        'memory_limits' => [
            'max_memory' => 128 * 1024 * 1024, // 128MB
            'gc_frequency' => 100, // Every 100 operations
        ],
    ],

    // Security Testing Configuration
    'security' => [
        'rate_limiting' => [
            'default_limit' => 1000, // requests per hour
            'api_limit' => 10000,
            'login_limit' => 5, // attempts per minute
        ],
        'encryption' => [
            'algorithm' => 'AES-256-CBC',
            'key_rotation_days' => 90,
        ],
        'audit_logging' => [
            'retention_days' => 2555, // 7 years
            'batch_size' => 1000,
            'compression' => true,
        ],
    ],

    // Accessibility Testing Configuration
    'accessibility' => [
        'wcag_version' => '2.1',
        'compliance_level' => 'AA',
        'tools' => [
            'axe_core' => [
                'enabled' => true,
                'rules' => [
                    'color-contrast' => 'error',
                    'image-alt' => 'error',
                    'form-field-multiple-labels' => 'warning',
                ],
            ],
            'lighthouse' => [
                'enabled' => true,
                'categories' => ['accessibility', 'performance', 'seo'],
            ],
        ],
        'testing' => [
            'batch_size' => 10,
            'timeout' => 30000, // 30 seconds
            'retries' => 3,
        ],
    ],

    // Test Data Configuration
    'test_data' => [
        'factories' => [
            'count' => [
                'customers' => 1000,
                'shipments' => 5000,
                'contracts' => 200,
                'promotions' => 50,
            ],
            'relationships' => [
                'customer_to_contract' => 0.8, // 80% of customers have contracts
                'shipment_to_customer' => 0.9, // 90% of shipments have customers
            ],
        ],
        'mock_data' => [
            'carrier_rates' => true,
            'competitor_pricing' => true,
            'fuel_indexes' => true,
            'weather_data' => false, // External API
        ],
        'cleanup' => [
            'auto_cleanup' => true,
            'retention_hours' => 24,
            'batch_cleanup' => true,
        ],
    ],

    // Coverage Configuration
    'coverage' => [
        'minimum' => [
            'overall' => 90,
            'services' => 95,
            'controllers' => 85,
            'models' => 90,
        ],
        'exclude' => [
            'paths' => [
                'app/Http/Middleware/VerifyCsrfToken.php',
                'app/Exceptions/Handler.php',
                'bootstrap/app.php',
            ],
            'patterns' => [
                '/tests/*',
                '/vendor/*',
                '/config/*',
            ],
        ],
        'format' => ['html', 'xml', 'text'],
    ],

    // CI/CD Configuration
    'cicd' => [
        'test_suites' => [
            'unit' => [
                'command' => 'php artisan test --testsuite=Unit --parallel',
                'timeout' => 600, // 10 minutes
            ],
            'feature' => [
                'command' => 'php artisan test --testsuite=Feature --parallel',
                'timeout' => 900, // 15 minutes
            ],
            'integration' => [
                'command' => 'php artisan test --testsuite=Integration --parallel',
                'timeout' => 1200, // 20 minutes
            ],
            'performance' => [
                'command' => 'php artisan test --testsuite=Performance',
                'timeout' => 1800, // 30 minutes
            ],
            'security' => [
                'command' => 'php artisan test --testsuite=Security',
                'timeout' => 600, // 10 minutes
            ],
        ],
        'artifacts' => [
            'coverage_report' => true,
            'performance_metrics' => true,
            'security_scan' => true,
            'accessibility_report' => true,
        ],
    ],

    // Notification Configuration
    'notifications' => [
        'test_results' => [
            'email' => env('TEST_RESULTS_EMAIL'),
            'slack_webhook' => env('TEST_RESULTS_SLACK_WEBHOOK'),
            'discord_webhook' => env('TEST_RESULTS_DISCORD_WEBHOOK'),
        ],
        'failures' => [
            'immediate' => true,
            'batch_threshold' => 5, // Notify after 5 failures
            'cooldown_minutes' => 30,
        ],
    ],

    // Logging Configuration for Testing
    'logging' => [
        'default' => env('TEST_LOG_CHANNEL', 'stack'),
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single', 'daily'],
                'ignore_exceptions' => false,
            ],
            'single' => [
                'driver' => 'single',
                'path' => storage_path('logs/testing.log'),
                'level' => 'debug',
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => storage_path('logs/testing/daily'),
                'level' => 'debug',
                'days' => 14,
            ],
        ],
    ],

    // Application-specific Test Configuration
    'pricing_system' => [
        'features' => [
            'dynamic_pricing' => true,
            'contract_management' => true,
            'promotion_engine' => true,
            'bulk_operations' => true,
            'real_time_quotation' => true,
        ],
        'limits' => [
            'max_weight_kg' => 1000,
            'max_pieces' => 100,
            'max_bulk_requests' => 1000,
            'max_promotions_per_customer' => 10,
        ],
        'business_rules' => [
            'dimensional_weight_factor' => 5000,
            'fuel_surcharge_base' => 100.0,
            'minimum_charge' => 5.0,
            'volume_discount_tiers' => [
                ['min' => 100, 'discount' => 5],
                ['min' => 500, 'discount' => 10],
                ['min' => 1000, 'discount' => 15],
            ],
        ],
    ],
];