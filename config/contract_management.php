<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contract Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Contract Management Framework including:
    | - Business rules and thresholds
    | - Notification settings
    | - Compliance monitoring
    | - Volume discount tiers
    | - Contract lifecycle settings
    |
    */

    // Contract Lifecycle Settings
    'lifecycle' => [
        'default_duration_months' => 12,
        'minimum_duration_months' => 1,
        'maximum_duration_months' => 60,
        'auto_renewal' => [
            'default_notice_period_days' => 30,
            'max_extensions' => 3,
            'notice_intervals' => [30, 15, 7, 3, 1]
        ],
        'statuses' => [
            'draft' => ['color' => 'gray', 'description' => 'Contract is being prepared'],
            'active' => ['color' => 'green', 'description' => 'Contract is active and enforceable'],
            'suspended' => ['color' => 'yellow', 'description' => 'Contract temporarily suspended'],
            'expired' => ['color' => 'red', 'description' => 'Contract has expired'],
            'cancelled' => ['color' => 'red', 'description' => 'Contract was cancelled'],
            'renegotiated' => ['color' => 'blue', 'description' => 'Contract is being renegotiated']
        ]
    ],

    // Volume Discount Tiers
    'volume_discounts' => [
        'tiers' => [
            'bronze' => [
                'name' => 'Bronze',
                'volume_requirement' => 0,
                'discount_percentage' => 0,
                'benefits' => ['standard_support', 'basic_reporting']
            ],
            'silver' => [
                'name' => 'Silver',
                'volume_requirement' => 50,
                'discount_percentage' => 5,
                'benefits' => ['priority_support', 'weekly_reporting', 'dedicated_account_manager']
            ],
            'gold' => [
                'name' => 'Gold',
                'volume_requirement' => 200,
                'discount_percentage' => 10,
                'benefits' => ['24_7_support', 'daily_reporting', 'api_access', 'custom_integrations']
            ],
            'platinum' => [
                'name' => 'Platinum',
                'volume_requirement' => 500,
                'discount_percentage' => 15,
                'benefits' => ['white_glove_service', 'real_time_monitoring', 'custom_solutions', 'volume_pricing']
            ]
        ],
        'milestone_thresholds' => [10, 50, 100, 500, 1000, 5000],
        'reward_multipliers' => [
            'base' => 1.0,
            'tier_bonus' => 2.0,
            'milestone_bonus' => 5.0,
            'loyalty_bonus' => 10.0
        ]
    ],

    // Compliance Monitoring
    'compliance' => [
        'thresholds' => [
            'critical' => 60,
            'warning' => 80,
            'good' => 95
        ],
        'check_frequencies' => [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90
        ],
        'escalation_levels' => [
            1 => 'Management Attention Required',
            2 => 'Senior Management Intervention',
            3 => 'Executive Escalation'
        ],
        'required_actions' => [
            'delivery_delay' => ['investigation_required', 'customer_communication', 'process_improvement'],
            'reliability_issue' => ['root_cause_analysis', 'remedy_plan', 'follow_up_scheduling'],
            'performance_drop' => ['immediate_review', 'service_restoration', 'preventive_measures']
        ]
    ],

    // Service Level Definitions
    'service_levels' => [
        'express' => [
            'delivery_window' => [2, 24], // hours
            'reliability_threshold' => 98.0,
            'price_multiplier' => 1.5,
            'sla_claims_covered' => true
        ],
        'priority' => [
            'delivery_window' => [4, 48], // hours
            'reliability_threshold' => 95.0,
            'price_multiplier' => 1.25,
            'sla_claims_covered' => true
        ],
        'standard' => [
            'delivery_window' => [24, 72], // hours
            'reliability_threshold' => 92.0,
            'price_multiplier' => 1.0,
            'sla_claims_covered' => false
        ],
        'economy' => [
            'delivery_window' => [48, 120], // hours
            'reliability_threshold' => 88.0,
            'price_multiplier' => 0.8,
            'sla_claims_covered' => false
        ]
    ],

    // Contract Types
    'contract_types' => [
        'standard' => [
            'name' => 'Standard Contract',
            'description' => 'Basic service contract with standard terms',
            'features' => ['basic_service_levels', 'standard_discounts', 'monthly_billing'],
            'setup_fee' => 0,
            'minimum_commitment' => 1
        ],
        'premium' => [
            'name' => 'Premium Contract',
            'description' => 'Enhanced service contract with additional benefits',
            'features' => ['enhanced_service_levels', 'volume_discounts', 'priority_support', 'weekly_reporting'],
            'setup_fee' => 500,
            'minimum_commitment' => 6
        ],
        'enterprise' => [
            'name' => 'Enterprise Contract',
            'description' => 'Full-service contract with custom terms and dedicated support',
            'features' => ['custom_service_levels', 'tiered_discounts', 'dedicated_account_manager', 'daily_reporting', 'api_access'],
            'setup_fee' => 2000,
            'minimum_commitment' => 12
        ]
    ],

    // Notification Settings
    'notifications' => [
        'email' => [
            'enabled' => true,
            'from_address' => env('CONTRACT_NOTIFICATIONS_FROM', 'contracts@company.com'),
            'from_name' => env('CONTRACT_NOTIFICATIONS_NAME', 'Contract Management'),
            'templates' => [
                'contract_activated' => 'emails.contract.activation',
                'contract_expiring' => 'emails.contract.expiry_notice',
                'contract_expired' => 'emails.contract.expiration',
                'compliance_breach' => 'emails.contract.compliance_breach',
                'milestone_achieved' => 'emails.contract.milestone',
                'tier_achieved' => 'emails.contract.tier'
            ]
        ],
        'sms' => [
            'enabled' => true,
            'cooldown_hours' => 24,
            'urgent_only' => false
        ],
        'webhook' => [
            'enabled' => true,
            'retry_attempts' => 3,
            'timeout_seconds' => 30
        ],
        'renewal_schedules' => [30, 15, 7, 3, 1], // days before expiry
        'milestone_cooldown_hours' => 24
    ],

    // Business Rules
    'business_rules' => [
        'minimum_volume_commitment' => 1,
        'maximum_discount_percentage' => 25,
        'compliance_resolution_days' => 30,
        'tier_progression_check_hours' => 1,
        'milestone_check_frequency' => 'daily',
        'auto_renewal_processing_days' => 30,
        'contract_archive_after_years' => 2,
        'notification_cleanup_after_months' => 12
    ],

    // Performance Metrics
    'performance' => [
        'cache_ttl_minutes' => 30,
        'batch_processing_size' => 50,
        'query_timeout_seconds' => 30,
        'background_job_timeout_minutes' => 60,
        'rate_limits' => [
            'api_calls_per_minute' => 100,
            'contract_creations_per_hour' => 20,
            'compliance_checks_per_day' => 1000
        ]
    ],

    // Security and Audit
    'security' => [
        'require_approval_for' => [
            'contract_activations' => true,
            'contract_suspensions' => true,
            'contract_cancellations' => true,
            'compliance_waivers' => true
        ],
        'audit_trail' => [
            'log_all_changes' => true,
            'retention_years' => 7,
            'include_sensitive_data' => false
        ],
        'access_control' => [
            'roles' => [
                'contract_viewer' => ['view_contracts', 'view_compliance'],
                'contract_manager' => ['manage_contracts', 'view_compliance', 'approve_tier_changes'],
                'contract_admin' => ['full_access', 'admin_operations'],
                'compliance_officer' => ['view_compliance', 'manage_compliance', 'issue_waivers']
            ]
        ]
    ],

    // Integration Settings
    'integration' => [
        'dynamic_pricing' => [
            'enabled' => true,
            'cache_pricing_for_minutes' => 15,
            'fallback_to_static_pricing' => true
        ],
        'billing_system' => [
            'auto_apply_discounts' => true,
            'sync_frequency' => 'daily',
            'retry_failed_sync' => true
        ],
        'customer_intelligence' => [
            'track_engagement' => true,
            'analyze_contract_performance' => true,
            'predict_renewal_risk' => true
        ],
        'third_party_apis' => [
            'enabled' => true,
            'rate_limiting' => true,
            'error_handling' => 'retry_with_backoff'
        ]
    ],

    // Reporting and Analytics
    'reporting' => [
        'metrics' => [
            'contract_utilization' => true,
            'compliance_scores' => true,
            'volume_progression' => true,
            'renewal_rates' => true,
            'revenue_recognition' => true
        ],
        'dashboards' => [
            'executive_dashboard' => ['contract_summary', 'compliance_overview', 'revenue_metrics'],
            'operations_dashboard' => ['contract_details', 'compliance_issues', 'volume_trends'],
            'sales_dashboard' => ['new_contracts', 'renewals', 'tier_performance']
        ],
        'export_formats' => ['pdf', 'excel', 'csv', 'json'],
        'scheduled_reports' => [
            'daily' => ['contract_summary', 'compliance_alerts'],
            'weekly' => ['performance_metrics', 'volume_analysis'],
            'monthly' => ['revenue_report', 'compliance_summary'],
            'quarterly' => ['executive_summary', 'strategic_analysis']
        ]
    ],

    // Environment-specific Overrides
    'environments' => [
        'development' => [
            'debug_notifications' => true,
            'skip_approval_for_testing' => true,
            'test_data_generation' => true
        ],
        'staging' => [
            'limited_notifications' => true,
            'no_production_webhooks' => true
        ],
        'production' => [
            'full_audit_trail' => true,
            'production_webhooks_only' => true,
            'comprehensive_monitoring' => true
        ]
    ]
];