<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Accessibility Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WCAG 2.1 AA compliance testing and accessibility
    | features. This section controls accessibility testing, user preferences,
    | and compliance monitoring.
    |
    */

    'accessibility' => [
        'enabled' => env('ACCESSIBILITY_TESTING_ENABLED', true),
        'wcag_version' => env('WCAG_VERSION', '2.1'),
        'compliance_level' => env('COMPLIANCE_LEVEL', 'AA'),
        'auto_testing' => env('AUTO_ACCESSIBILITY_TESTING', true),
        'test_timeout' => env('ACCESSIBILITY_TEST_TIMEOUT', 30),
        'concurrent_tests' => env('MAX_CONCURRENT_TESTS', 5),
        
        'thresholds' => [
            'critical' => 50,  // Score below 50 is critical
            'warning' => 70,   // Score below 70 needs attention
            'good' => 85,      // Score above 85 is good
            'excellent' => 95, // Score above 95 is excellent
        ],

        'violation_weights' => [
            'critical' => 10,
            'serious' => 5,
            'moderate' => 2,
            'minor' => 1,
        ],

        'test_types' => [
            'automated' => [
                'enabled' => true,
                'tools' => ['axe', 'lighthouse', 'pa11y'],
            ],
            'manual' => [
                'enabled' => true,
                'requester_role' => 'admin',
            ],
            'user_testing' => [
                'enabled' => true,
                'requester_role' => 'admin',
            ],
        ],

        'supported_frameworks' => [
            'WCAG' => ['2.0', '2.1', '2.2'],
            'Section 508' => true,
            'ADA' => true,
        ],

        'reporting' => [
            'auto_generate' => true,
            'frequency' => 'weekly',
            'recipients' => [
                'accessibility@company.com',
                'compliance@company.com',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Trail Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for comprehensive audit trail logging. This controls
    | what actions are logged, storage settings, and retention policies.
    |
    */

    'audit' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'log_all_requests' => env('AUDIT_LOG_ALL_REQUESTS', false),
        'log_sensitive_data' => env('AUDIT_LOG_SENSITIVE_DATA', false),
        'batch_logging' => env('AUDIT_BATCH_LOGGING', true),
        'log_retention_days' => env('AUDIT_RETENTION_DAYS', 2555), // 7 years
        
        'modules' => [
            'api' => [
                'enabled' => true,
                'log_requests' => true,
                'log_responses' => false,
                'exclude_paths' => [
                    'health',
                    'version',
                    'metrics',
                ],
            ],
            'admin' => [
                'enabled' => true,
                'log_all_actions' => true,
                'include_sensitive' => true,
            ],
            'frontend' => [
                'enabled' => true,
                'log_user_actions' => true,
                'log_page_views' => false,
            ],
            'backend' => [
                'enabled' => true,
                'log_system_events' => true,
                'log_background_jobs' => true,
            ],
        ],

        'action_types' => [
            'create' => 'info',
            'read' => 'info',
            'update' => 'info',
            'delete' => 'warning',
            'login' => 'info',
            'logout' => 'info',
            'failed_login' => 'warning',
            'privilege_escalation' => 'critical',
            'data_access' => 'warning',
            'export' => 'info',
            'import' => 'info',
            'backup' => 'info',
            'restore' => 'warning',
        ],

        'severity_levels' => [
            'info' => [
                'color' => 'blue',
                'alert' => false,
            ],
            'warning' => [
                'color' => 'yellow',
                'alert' => false,
            ],
            'error' => [
                'color' => 'orange',
                'alert' => true,
            ],
            'critical' => [
                'color' => 'red',
                'alert' => true,
                'immediate' => true,
            ],
        ],

        'storage' => [
            'driver' => env('AUDIT_STORAGE_DRIVER', 'database'),
            'encryption' => env('AUDIT_ENCRYPTION_ENABLED', true),
            'compression' => env('AUDIT_COMPRESSION_ENABLED', false),
            'rotation' => [
                'enabled' => true,
                'size_mb' => 100,
                'age_days' => 30,
            ],
        ],

        'anomaly_detection' => [
            'enabled' => true,
            'suspicious_patterns' => [
                'rapid_requests' => 100, // requests per minute
                'failed_logins' => 5,    // failed logins per minute
                'privilege_escalation' => true,
                'data_exfiltration' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for real-time compliance monitoring and regulatory
    | compliance tracking across multiple frameworks.
    |
    */

    'compliance' => [
        'enabled' => env('COMPLIANCE_MONITORING_ENABLED', true),
        'frameworks' => [
            'WCAG' => [
                'enabled' => true,
                'version' => '2.1',
                'level' => 'AA',
                'auto_monitor' => true,
            ],
            'GDPR' => [
                'enabled' => true,
                'auto_monitor' => true,
                'data_retention_days' => 2555, // 7 years
                'consent_tracking' => true,
            ],
            'SOX' => [
                'enabled' => env('SOX_COMPLIANCE_ENABLED', false),
                'auto_monitor' => true,
                'financial_data_only' => true,
            ],
            'HIPAA' => [
                'enabled' => env('HIPAA_COMPLIANCE_ENABLED', false),
                'auto_monitor' => true,
                'phi_tracking' => true,
            ],
            'PCI-DSS' => [
                'enabled' => env('PCI_DSS_COMPLIANCE_ENABLED', false),
                'auto_monitor' => true,
                'payment_data_only' => true,
            ],
        ],

        'monitoring_rules' => [
            [
                'name' => 'WCAG Low Score Alert',
                'framework' => 'WCAG',
                'type' => 'threshold',
                'metric' => 'accessibility_score',
                'threshold' => 70,
                'severity' => 'warning',
                'enabled' => true,
            ],
            [
                'name' => 'Critical Accessibility Violation',
                'framework' => 'WCAG',
                'type' => 'real_time',
                'violation_impact' => 'critical',
                'severity' => 'critical',
                'enabled' => true,
            ],
            [
                'name' => 'Excessive Failed Logins',
                'framework' => 'Security',
                'type' => 'threshold',
                'metric' => 'failed_login_attempts',
                'threshold' => 10,
                'time_window' => 60, // 1 hour
                'severity' => 'high',
                'enabled' => true,
            ],
        ],

        'alerting' => [
            'email' => [
                'enabled' => env('COMPLIANCE_EMAIL_ALERTS', true),
                'smtp_host' => env('COMPLIANCE_SMTP_HOST'),
                'smtp_port' => env('COMPLIANCE_SMTP_PORT', 587),
                'smtp_username' => env('COMPLIANCE_SMTP_USERNAME'),
                'smtp_password' => env('COMPLIANCE_SMTP_PASSWORD'),
                'from_address' => env('COMPLIANCE_ALERT_FROM', 'compliance@company.com'),
                'recipients' => [
                    'admin@company.com',
                    'compliance@company.com',
                    'security@company.com',
                ],
            ],
            'slack' => [
                'enabled' => env('COMPLIANCE_SLACK_ALERTS', false),
                'webhook_url' => env('SLACK_WEBHOOK_URL'),
                'channel' => env('SLACK_CHANNEL', '#compliance'),
            ],
            'sms' => [
                'enabled' => env('COMPLIANCE_SMS_ALERTS', false),
                'provider' => env('SMS_PROVIDER'),
                'critical_only' => true,
            ],
        ],

        'reporting' => [
            'auto_reports' => [
                'daily' => [
                    'enabled' => true,
                    'recipients' => ['admin@company.com'],
                    'frameworks' => ['WCAG', 'Security'],
                ],
                'weekly' => [
                    'enabled' => true,
                    'recipients' => ['compliance@company.com', 'management@company.com'],
                    'frameworks' => ['WCAG', 'GDPR', 'SOX'],
                ],
                'monthly' => [
                    'enabled' => true,
                    'recipients' => ['board@company.com'],
                    'frameworks' => ['WCAG', 'GDPR', 'SOX', 'HIPAA', 'PCI-DSS'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export and Reporting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for audit report generation, export formats, and
    | distribution options.
    |
    */

    'reporting' => [
        'enabled' => env('REPORTING_ENABLED', true),
        'formats' => [
            'pdf' => [
                'enabled' => true,
                'template' => 'compliance-report',
                'orientation' => 'portrait',
            ],
            'csv' => [
                'enabled' => true,
                'delimiter' => ',',
                'enclosure' => '"',
            ],
            'excel' => [
                'enabled' => true,
                'sheets' => ['summary', 'details', 'violations'],
            ],
            'json' => [
                'enabled' => true,
                'pretty_print' => env('APP_ENV') === 'local',
            ],
        ],

        'storage' => [
            'driver' => env('REPORT_STORAGE_DRIVER', 'local'),
            'path' => env('REPORT_STORAGE_PATH', 'reports'),
            'retention_days' => env('REPORT_RETENTION_DAYS', 90),
        ],

        'scheduling' => [
            'enabled' => true,
            'timezone' => env('REPORT_TIMEZONE', 'UTC'),
            'jobs' => [
                [
                    'name' => 'Daily Compliance Report',
                    'schedule' => '0 8 * * *', // 8 AM daily
                    'type' => 'compliance',
                    'format' => 'pdf',
                ],
                [
                    'name' => 'Weekly Audit Summary',
                    'schedule' => '0 9 * * 1', // 9 AM Mondays
                    'type' => 'audit',
                    'format' => 'excel',
                ],
                [
                    'name' => 'Monthly Accessibility Report',
                    'schedule' => '0 10 1 * *', // 10 AM on 1st of month
                    'type' => 'accessibility',
                    'format' => 'pdf',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Accessibility Preferences
    |--------------------------------------------------------------------------
    |
    | Configuration for user accessibility settings and personal
    | accessibility experience customization.
    |
    */

    'user_preferences' => [
        'enabled' => env('USER_ACCESSIBILITY_PREFS', true),
        'storage' => 'database', // database, session, cookie
        
        'preferences' => [
            'high_contrast' => [
                'enabled' => true,
                'default' => false,
            ],
            'large_text' => [
                'enabled' => true,
                'default' => false,
                'sizes' => ['small', 'medium', 'large', 'extra-large'],
            ],
            'reduced_motion' => [
                'enabled' => true,
                'default' => false,
            ],
            'screen_reader_mode' => [
                'enabled' => true,
                'default' => false,
            ],
            'keyboard_navigation_only' => [
                'enabled' => true,
                'default' => false,
            ],
            'color_scheme' => [
                'enabled' => true,
                'default' => 'default',
                'options' => ['default', 'dark', 'high-contrast'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for accessibility and audit middleware behavior.
    |
    */

    'middleware' => [
        'audit_logging' => [
            'enabled' => true,
            'include_request_body' => env('AUDIT_INCLUDE_REQUEST_BODY', false),
            'include_response_body' => env('AUDIT_INCLUDE_RESPONSE_BODY', false),
            'exclude_paths' => [
                'health',
                'version',
                'csrf-token',
            ],
        ],
        
        'accessibility_validation' => [
            'enabled' => true,
            'test_mode' => env('ACCESSIBILITY_TEST_MODE', 'production'), // development, staging, production
            'cache_results' => true,
            'cache_ttl' => 3600, // 1 hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Performance settings for accessibility testing and audit logging
    | to ensure system responsiveness.
    |
    */

    'performance' => [
        'accessibility_testing' => [
            'timeout' => 30, // seconds
            'concurrency' => 5,
            'cache_enabled' => true,
            'cache_ttl' => 3600,
        ],
        
        'audit_logging' => [
            'batch_size' => 100,
            'flush_interval' => 60, // seconds
            'async_enabled' => true,
        ],
        
        'database' => [
            'connection' => env('DB_CONNECTION'),
            'pool_size' => 10,
            'timeout' => 30,
        ],
        
        'caching' => [
            'driver' => env('CACHE_DRIVER', 'redis'),
            'prefix' => 'accessibility_audit_',
            'ttl' => 3600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for audit trail integrity and accessibility
    | data protection.
    |
    */

    'security' => [
        'audit_trail' => [
            'encryption_enabled' => env('AUDIT_ENCRYPTION_ENABLED', true),
            'integrity_checking' => true,
            'tamper_detection' => true,
            'hash_algorithm' => 'sha256',
        ],
        
        'accessibility_data' => [
            'encrypt_user_preferences' => false,
            'hash_test_results' => false,
            'secure_storage' => true,
        ],
        
        'compliance' => [
            'encrypt_violation_data' => true,
            'secure_report_generation' => true,
            'audit_report_access' => true,
        ],
    ],

];