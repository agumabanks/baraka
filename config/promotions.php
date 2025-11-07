<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Promotion Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the promotion and discount engine including
    | validation rules, business logic, thresholds, and system behavior.
    |
    */

    // Business Rules Configuration
    'business_rules' => [
        // Anti-stacking configuration
        'anti_stacking' => [
            'enabled' => true,
            'default_policy' => 'prevent_stacking', // prevent_stacking, allow_stacking, priority_based
            'priority_order' => ['contract', 'promotion', 'loyalty', 'seasonal'],
            'max_stackable_discounts' => 3,
            'exempt_promotion_types' => ['free_shipping', 'tier_upgrade']
        ],

        // Discount calculation priorities
        'discount_priorities' => [
            'contract_discounts' => 1,    // Highest priority
            'volume_discounts' => 2,
            'promotional_discounts' => 3,
            'loyalty_discounts' => 4,
            'seasonal_discounts' => 5      // Lowest priority
        ],

        // Maximum discount limits
        'maximum_discounts' => [
            'absolute_max_percentage' => 70.0, // Maximum 70% discount
            'maximum_discount_amount' => 500.00, // Maximum $500 discount per order
            'customer_daily_limit' => 1000.00, // Max discounts per customer per day
            'campaign_overspend_threshold' => 150.0 // Allow 150% of planned budget
        ]
    ],

    // Milestone System Configuration
    'milestones' => [
        'categories' => [
            'shipment_count' => [
                'thresholds' => [10, 25, 50, 100, 250, 500, 1000, 2500, 5000],
                'reward_types' => ['percentage_discount', 'fixed_discount', 'free_shipping', 'tier_upgrade'],
                'notification_triggers' => [100, 500, 1000, 5000] // Milestones that trigger notifications
            ],
            'volume' => [
                'thresholds' => [10, 50, 100, 500, 1000, 2500, 5000], // in kg
                'reward_types' => ['volume_discount', 'tier_upgrade', 'exclusive_offers'],
                'measurement_unit' => 'kg'
            ],
            'revenue' => [
                'thresholds' => [500, 1000, 2500, 5000, 10000, 25000, 50000, 100000], // in USD
                'reward_types' => ['percentage_discount', 'fixed_discount', 'priority_support'],
                'currency' => 'USD'
            ],
            'tenure' => [
                'thresholds' => [3, 6, 12, 24, 36, 60], // in months
                'reward_types' => ['loyalty_bonus', 'tier_upgrade', 'exclusive_access'],
                'cumulative' => true // All previous milestones count
            ]
        ],

        // Milestone achievement settings
        'achievement' => [
            'auto_achievement' => true, // Automatically detect and record milestones
            'achievement_delay_minutes' => 5, // Delay before recording milestone
            'duplicate_prevention_hours' => 24, // Prevent duplicate milestone triggers
            'reward_distribution' => 'immediate', // immediate, scheduled, manual
            'celebration_notification' => true
        ]
    ],

    // ROI and Analytics Configuration
    'analytics' => [
        'roi_thresholds' => [
            'low_performance' => 50.0,    // Below 50% ROI
            'acceptable_performance' => 75.0,
            'good_performance' => 100.0,  // 100% ROI
            'excellent_performance' => 150.0, // Above 150% ROI
            'exceptional_performance' => 200.0 // Above 200% ROI
        ],

        'alert_thresholds' => [
            'roi_alert_low' => 50.0,
            'roi_alert_high' => 200.0,
            'budget_exceeded' => 150.0,
            'usage_exceeded' => 90.0, // 90% of planned usage
            'conversion_rate_low' => 0.05, // 5% conversion rate
            'customer_satisfaction_low' => 3.0 // 3/5 rating
        ],

        'measurement_periods' => [
            'realtime' => '1h',    // Real-time metrics
            'short_term' => '1d',  // Daily metrics
            'medium_term' => '7d', // Weekly metrics
            'long_term' => '30d',  // Monthly metrics
            'strategic' => '90d'   // Quarterly metrics
        ],

        'data_retention' => [
            'usage_logs_days' => 365,     // Keep usage logs for 1 year
            'analytics_data_days' => 1095, // Keep analytics for 3 years
            'roi_calculations_days' => 2555, // Keep ROI data for 7 years
            'customer_preferences_days' => null // Keep indefinitely
        ]
    ],

    // Notification Configuration
    'notifications' => [
        'channels' => [
            'email' => [
                'enabled' => true,
                'from_address' => env('PROMOTION_NOTIFICATIONS_EMAIL', 'promotions@company.com'),
                'template_style' => 'professional', // professional, casual, festive
                'rate_limit_per_hour' => 10
            ],
            'sms' => [
                'enabled' => true,
                'provider' => 'twilio', // twilio, aws_sns, custom
                'rate_limit_per_hour' => 5,
                'character_limit' => 160
            ],
            'push' => [
                'enabled' => true,
                'service' => 'firebase', // firebase, aws_sns, custom
                'rate_limit_per_hour' => 20
            ],
            'in_app' => [
                'enabled' => true,
                'persistence_days' => 30,
                'dismissible' => true
            ],
            'webhook' => [
                'enabled' => true,
                'retry_attempts' => 3,
                'timeout_seconds' => 30
            ]
        ],

        // Event-specific notification settings
        'events' => [
            'milestone_achieved' => [
                'channels' => ['email', 'push', 'in_app'],
                'immediate' => true,
                'rate_limited' => true,
                'quiet_hours_respect' => true
            ],
            'promotion_expiring' => [
                'channels' => ['email', 'in_app'],
                'advance_notice_days' => [7, 3, 1],
                'immediate' => false
            ],
            'roi_threshold_breach' => [
                'channels' => ['email', 'webhook'],
                'immediate' => true,
                'rate_limited' => false
            ],
            'promotion_activated' => [
                'channels' => ['webhook'],
                'immediate' => true,
                'high_priority' => true
            ]
        ],

        // Rate limiting and spam prevention
        'rate_limiting' => [
            'global_hourly_limit' => 1000,
            'per_customer_hourly_limit' => 10,
            'per_event_type_daily_limit' => 50,
            'cooldown_periods' => [
                'milestone_celebration' => 60, // minutes
                'promotion_notification' => 30,
                'roi_alert' => 120
            ]
        ]
    ],

    // Code Generation Configuration
    'code_generation' => [
        'formats' => [
            'static' => [
                'pattern' => '[A-Z0-9]{8}',
                'prefix' => '',
                'suffix' => ''
            ],
            'random' => [
                'length' => 8,
                'charset' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
                'prefix' => '',
                'suffix' => ''
            ],
            'date_based' => [
                'format' => 'SAVE[YYYY][MM][DD][XX]',
                'prefix' => '',
                'suffix' => ''
            ],
            'sequential' => [
                'prefix' => 'SAVE',
                'start_number' => 1000,
                'pad_length' => 4
            ]
        ],

        'uniqueness' => [
            'max_attempts' => 10,
            'check_existing' => true,
            'case_sensitive' => false
        ],

        'validation' => [
            'min_length' => 3,
            'max_length' => 20,
            'forbidden_patterns' => ['ADMIN', 'SYSTEM', 'TEST'],
            'allowed_characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
        ]
    ],

    // API Configuration
    'api' => [
        'rate_limits' => [
            'validate_code' => '60,1', // 60 requests per minute
            'apply_discount' => '30,1',
            'track_milestone' => '100,1',
            'analytics' => '20,1',
            'webhooks' => '1000,1'
        ],

        'response_format' => 'json', // json, xml
        'include_metadata' => true,
        'cache_ttl_seconds' => 300, // 5 minutes
        'request_timeout_seconds' => 30,

        'webhook_security' => [
            'signature_algorithm' => 'sha256',
            'timestamp_tolerance_seconds' => 300, // 5 minutes
            'ip_whitelist' => [], // Empty array = allow all
            'require_https' => true
        ]
    ],

    // Performance and Caching Configuration
    'performance' => [
        'caching' => [
            'enabled' => true,
            'driver' => env('CACHE_DRIVER', 'redis'),
            'ttl' => [
                'promotion_lookup' => 300,    // 5 minutes
                'roi_calculations' => 900,    // 15 minutes
                'milestone_progress' => 600,  // 10 minutes
                'analytics_data' => 1800      // 30 minutes
            ]
        ],

        'queue_processing' => [
            'enabled' => true,
            'connection' => 'redis',
            'queue' => 'promotions',
            'max_jobs' => 100,
            'timeout' => 300, // 5 minutes
            'tries' => 3
        ],

        'database_optimization' => [
            'index_usage' => true,
            'query_optimization' => true,
            'connection_pooling' => true,
            'read_replica_usage' => true
        ]
    ],

    // Security Configuration
    'security' => [
        'fraud_prevention' => [
            'enabled' => true,
            'suspicious_pattern_detection' => true,
            'velocity_checks' => true,
            'geographic_validation' => false,
            'device_fingerprinting' => true
        ],

        'access_control' => [
            'api_key_required' => true,
            'role_based_access' => true,
            'audit_logging' => true,
            'session_timeout_minutes' => 60
        ],

        'data_protection' => [
            'encrypt_sensitive_data' => true,
            'pii_anonymization' => true,
            'data_retention_policy' => '7years',
            'gdpr_compliance' => true
        ]
    ],

    // Integration Configuration
    'integrations' => [
        'external_apis' => [
            'competitor_pricing' => [
                'enabled' => false,
                'api_url' => '',
                'api_key' => '',
                'update_frequency_hours' => 24
            ],
            'market_data' => [
                'enabled' => false,
                'provider' => 'bloomberg',
                'update_frequency_hours' => 1
            ]
        ],

        'webhook_endpoints' => [
            'internal_services' => true,
            'third_party_integrations' => true,
            'event_broadcasting' => true
        ],

        'external_systems' => [
            'crm_integration' => true,
            'marketing_automation' => true,
            'business_intelligence' => true
        ]
    ],

    // Environment-specific overrides
    'environments' => [
        'production' => [
            'strict_validation' => true,
            'comprehensive_logging' => true,
            'performance_monitoring' => true,
            'security_auditing' => true
        ],
        'staging' => [
            'strict_validation' => true,
            'comprehensive_logging' => true,
            'test_data_mode' => true
        ],
        'development' => [
            'strict_validation' => false,
            'detailed_logging' => true,
            'mock_external_apis' => true,
            'disable_rate_limiting' => true
        ]
    ],

    // Feature Flags
    'features' => [
        'advanced_analytics' => env('PROMOTION_ADVANCED_ANALYTICS', true),
        'ml_based_optimization' => env('PROMOTION_ML_OPTIMIZATION', false),
        'real_time_roi' => env('PROMOTION_REALTIME_ROI', true),
        'predictive_analytics' => env('PROMOTION_PREDICTIVE_ANALYTICS', false),
        'ab_testing' => env('PROMOTION_AB_TESTING', true),
        'customer_segmentation' => env('PROMOTION_CUSTOMER_SEGMENTATION', true),
        'multi_currency' => env('PROMOTION_MULTI_CURRENCY', true),
        'api_v2' => env('PROMOTION_API_V2', false)
    ],

    // Compliance and Legal
    'compliance' => [
        'gdpr' => [
            'data_processing_consent' => true,
            'right_to_be_forgotten' => true,
            'data_portability' => true,
            'consent_tracking' => true
        ],
        'tax_compliance' => [
            'automatic_tax_calculation' => true,
            'multi_jurisdiction_support' => true,
            'tax_reporting' => true
        ],
        'accounting' => [
            'discount_accounting' => true,
            'revenue_recognition' => true,
            'financial_reporting' => true
        ]
    ],

    // Monitoring and Observability
    'monitoring' => [
        'health_checks' => [
            'database_connectivity' => true,
            'external_api_availability' => true,
            'cache_performance' => true,
            'queue_health' => true
        ],

        'metrics' => [
            'promotion_usage_rate' => true,
            'roi_tracking' => true,
            'customer_satisfaction' => true,
            'system_performance' => true
        ],

        'alerting' => [
            'performance_degradation' => true,
            'error_rate_spike' => true,
            'unusual_activity' => true,
            'resource_exhaustion' => true
        ]
    ]

];