<?php

return [
    // API Gateway General Configuration
    'version' => '1.0.0',
    'name' => 'WeCourier API Gateway',
    'description' => 'Unified API Gateway for WeCourier Platform',

    // Service Registry
    'services' => [
        'operational-reporting' => [
            'name' => 'Operational Reporting Service',
            'host' => env('OPERATIONAL_REPORTING_HOST', '127.0.0.1'),
            'port' => env('OPERATIONAL_REPORTING_PORT', 8001),
            'protocol' => env('OPERATIONAL_REPORTING_PROTOCOL', 'http'),
            'timeout' => 30,
            'health_check_path' => '/health',
        ],
        'financial-reporting' => [
            'name' => 'Financial Reporting Service',
            'host' => env('FINANCIAL_REPORTING_HOST', '127.0.0.1'),
            'port' => env('FINANCIAL_REPORTING_PORT', 8002),
            'protocol' => env('FINANCIAL_REPORTING_PROTOCOL', 'http'),
            'timeout' => 30,
            'health_check_path' => '/health',
        ],
        'customer-intelligence' => [
            'name' => 'Customer Intelligence Service',
            'host' => env('CUSTOMER_INTELLIGENCE_HOST', '127.0.0.1'),
            'port' => env('CUSTOMER_INTELLIGENCE_PORT', 8003),
            'protocol' => env('CUSTOMER_INTELLIGENCE_PROTOCOL', 'http'),
            'timeout' => 30,
            'health_check_path' => '/health',
        ],
        'real-time-dashboard' => [
            'name' => 'Real-time Dashboard Service',
            'host' => env('REALTIME_DASHBOARD_HOST', '127.0.0.1'),
            'port' => env('REALTIME_DASHBOARD_PORT', 8004),
            'protocol' => env('REALTIME_DASHBOARD_PROTOCOL', 'http'),
            'timeout' => 30,
            'health_check_path' => '/health',
        ],
    ],

    // Circuit Breaker Configuration
    'circuit_breaker' => [
        'services' => [
            'operational-reporting',
            'financial-reporting',
            'customer-intelligence',
            'real-time-dashboard',
        ],
        'failure_threshold' => 5,
        'recovery_timeout' => 60,
        'half_open_max_calls' => 3,
        'success_threshold' => 2,
    ],

    // Rate Limiting Configuration
    'rate_limiting' => [
        'enabled' => true,
        'default_limit' => 100, // requests per minute
        'default_window' => 60, // seconds
        'default_burst_limit' => 10,
        'storage_driver' => 'cache', // cache, database
        'algorithm' => 'sliding_window', // sliding_window, token_bucket
    ],

    // Authentication Configuration
    'authentication' => [
        'enabled' => true,
        'providers' => [
            'api_key' => [
                'enabled' => true,
                'header_name' => 'X-API-Key',
            ],
            'jwt' => [
                'enabled' => true,
                'algorithm' => 'HS256',
                'secret' => env('JWT_SECRET'),
            ],
            'bearer' => [
                'enabled' => true,
                'header_name' => 'Authorization',
                'schemes' => ['Bearer', 'Sanctum'],
            ],
            'oauth2' => [
                'enabled' => false,
            ],
        ],
    ],

    // Monitoring Configuration
    'monitoring' => [
        'enabled' => true,
        'slow_request_threshold' => 2000, // milliseconds
        'high_memory_threshold' => 10485760, // bytes (10MB)
        'metrics_retention_days' => 30,
        'log_retention_days' => 7,
        'cleanup_interval' => 86400, // seconds (24 hours)
        'alerts' => [
            'slack' => [
                'enabled' => false,
                'webhook_url' => env('SLACK_WEBHOOK_URL'),
            ],
            'pagerduty' => [
                'enabled' => false,
                'api_key' => env('PAGERDUTY_API_KEY'),
            ],
            'email' => [
                'enabled' => false,
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', '')),
            ],
        ],
    ],

    // Request/Response Transformation
    'transformation' => [
        'enabled' => true,
        'default_format' => 'json',
        'supported_formats' => ['json', 'xml', 'csv'],
        'normalization' => [
            'field_names' => true,
            'timestamps' => true,
            'currency' => true,
            'sanitize_sensitive' => true,
        ],
    ],

    // Validation Configuration
    'validation' => [
        'enabled' => true,
        'sanitize_input' => true,
        'max_request_size' => 10485760, // bytes (10MB)
        'allowed_content_types' => [
            'application/json',
            'application/xml',
            'text/plain',
            'application/x-www-form-urlencoded',
        ],
    ],

    // Security Configuration
    'security' => [
        'cors' => [
            'enabled' => true,
            'allow_origins' => ['*'],
            'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            'allow_headers' => ['*'],
            'max_age' => 86400,
        ],
        'headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ],
    ],

    // Caching Configuration
    'caching' => [
        'enabled' => true,
        'routes_cache_ttl' => 3600, // seconds (1 hour)
        'service_health_cache_ttl' => 300, // seconds (5 minutes)
        'metrics_cache_ttl' => 300, // seconds (5 minutes)
    ],

    // Load Balancing Configuration
    'load_balancing' => [
        'strategies' => [
            'round_robin' => [
                'enabled' => true,
            ],
            'least_connections' => [
                'enabled' => true,
            ],
            'weighted' => [
                'enabled' => true,
            ],
        ],
    ],

    // API Versioning Configuration
    'versioning' => [
        'enabled' => true,
        'strategy' => 'path', // path, header, query
        'default_version' => '1',
        'supported_versions' => ['1', '2'],
        'deprecated_versions' => [],
    ],

    // Skip middleware for certain paths
    'skip_rate_limit_paths' => [
        '/health',
        '/status',
        '/ping',
    ],

    'skip_auth_paths' => [
        '/health',
        '/status',
        '/ping',
        '/docs',
    ],

    'skip_validation_paths' => [
        '/health',
        '/status',
    ],

    // Retry Configuration
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'backoff_strategy' => 'exponential',
        'base_delay' => 1000, // milliseconds
        'max_delay' => 30000, // milliseconds
    ],

    // Health Check Configuration
    'health_checks' => [
        'enabled' => true,
        'interval' => 30, // seconds
        'timeout' => 5, // seconds
        'retries' => 3,
        'endpoints' => [
            'gateway' => '/api/gateway/health',
            'services' => [
                'operational-reporting' => '/health',
                'financial-reporting' => '/health',
                'customer-intelligence' => '/health',
                'real-time-dashboard' => '/health',
            ],
        ],
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'level' => env('API_GATEWAY_LOG_LEVEL', 'info'),
        'channels' => [
            'file' => [
                'enabled' => true,
                'path' => 'logs/api_gateway.log',
            ],
            'database' => [
                'enabled' => true,
                'retention_days' => 7,
            ],
        ],
    ],

    // Performance Configuration
    'performance' => [
        'connection_pooling' => true,
        'keep_alive' => true,
        'max_connections_per_service' => 100,
        'connection_timeout' => 10, // seconds
        'request_timeout' => 30, // seconds
    ],

    // Webhook Configuration for integrations
    'webhooks' => [
        'enabled' => true,
        'secret' => env('WEBHOOK_SECRET'),
        'timeout' => 30, // seconds
        'retries' => 3,
    ],
];