<?php

return [
    'default' => [
        'pipelines' => [
            'shipments_realtime' => [
                'name' => 'Real-time Shipments Processing',
                'schedule' => 'every_5_minutes',
                'priority' => 'HIGH',
                'sources' => [
                    'internal_shipments' => [
                        'type' => 'database',
                        'table' => 'shipments',
                        'connection' => 'mysql',
                        'incremental_field' => 'updated_at',
                        'batch_size' => 1000,
                        'where_clause' => "current_status != 'DELIVERED'",
                    ],
                    'tms_api' => [
                        'type' => 'api',
                        'endpoint' => env('TMS_API_ENDPOINT', 'https://api.tms.example.com/v1/shipments'),
                        'auth' => 'bearer_token',
                        'token' => env('TMS_API_TOKEN'),
                        'incremental_field' => 'updated_at',
                        'batch_size' => 500,
                        'timeout' => 30,
                    ],
                ],
                'transformations' => [
                    'data_cleansing' => [
                        'trim_fields' => ['tracking_number', 'customer_name', 'address'],
                        'standardize_status' => 'App\\Transformers\\StatusTransformer',
                        'validate_coordinates' => true,
                        'handle_nulls' => [
                            'weight_kg' => 1.0,
                            'declared_value' => 0.0,
                        ],
                    ],
                    'business_rules' => [
                        'calculate_delivery_time' => 'App\\Transformers\\DeliveryTimeCalculator',
                        'enrich_with_branch_data' => 'App\\Transformers\\BranchEnricher',
                        'calculate_financial_metrics' => 'App\\Transformers\\FinancialMetricsCalculator',
                        'apply_client_pricing' => 'App\\Transformers\\ClientPricingApplier',
                    ],
                    'geographical_enrichment' => [
                        'calculate_distance' => 'App\\Transformers\\DistanceCalculator',
                        'determine_service_area' => 'App\\Transformers\\ServiceAreaDeterminer',
                    ],
                ],
                'validations' => [
                    'required_fields' => [
                        'tracking_number', 'client_id', 'origin_branch_id', 'dest_branch_id', 'customer_id'
                    ],
                    'data_types' => [
                        'declared_value' => 'decimal',
                        'weight_kg' => 'decimal',
                        'delivery_attempts' => 'integer',
                    ],
                    'business_constraints' => [
                        'delivery_time_positive' => 'delivery_duration_minutes > 0',
                        'financial_balance' => 'ABS(shipping_charge - total_cost) >= 0',
                        'tracking_number_format' => 'tracking_number ~ ^[A-Z0-9]{6,20}$',
                    ],
                ],
                'destinations' => [
                    'fact_shipments' => [
                        'load_type' => 'upsert',
                        'merge_key' => 'shipment_id',
                        'batch_size' => 5000,
                    ],
                    'staging' => [
                        'load_type' => 'append',
                        'table' => 'stg_shipments',
                    ],
                ],
                'quality_checks' => [
                    'delivery_time_outliers' => [
                        'rule' => 'delivery_duration_minutes BETWEEN 5 AND 10080', // 5 min to 7 days
                        'severity' => 'WARNING',
                        'action' => 'flag_for_review',
                    ],
                    'financial_anomalies' => [
                        'rule' => 'ABS(margin_percentage - avg_margin_percentage) > 3 * margin_std_dev',
                        'severity' => 'HIGH',
                        'action' => 'reject_record',
                    ],
                    'duplicate_tracking' => [
                        'rule' => 'tracking_number NOT IN (SELECT tracking_number FROM fact_shipments WHERE tracking_number IS NOT NULL)',
                        'severity' => 'CRITICAL',
                        'action' => 'reject_record',
                    ],
                ],
                'error_handling' => [
                    'retry_attempts' => 3,
                    'retry_delay' => 60, // seconds
                    'dead_letter_queue' => true,
                    'alert_on_failure' => true,
                ],
                'performance' => [
                    'batch_size' => 5000,
                    'max_concurrent_jobs' => 5,
                    'memory_limit' => '2G',
                    'timeout' => 3600,
                    'parallel_processing' => true,
                ],
            ],
            
            'financial_transactions' => [
                'name' => 'Financial Transactions Processing',
                'schedule' => 'every_10_minutes',
                'priority' => 'MEDIUM',
                'sources' => [
                    'accounting_system' => [
                        'type' => 'database',
                        'table' => 'financial_transactions',
                        'connection' => 'accounting_db',
                        'incremental_field' => 'transaction_date',
                        'batch_size' => 2000,
                    ],
                    'payment_gateway' => [
                        'type' => 'api',
                        'endpoint' => env('PAYMENT_API_ENDPOINT'),
                        'auth' => 'api_key',
                        'api_key' => env('PAYMENT_API_KEY'),
                        'batch_size' => 1000,
                    ],
                ],
                'destinations' => [
                    'fact_financial_transactions' => [
                        'load_type' => 'append',
                        'batch_size' => 3000,
                    ],
                ],
                'quality_checks' => [
                    'balance_check' => [
                        'rule' => 'ABS(debit_amount - credit_amount) < 0.01',
                        'severity' => 'ERROR',
                        'action' => 'reject_record',
                    ],
                    'transaction_duplication' => [
                        'rule' => 'transaction_id NOT IN (SELECT transaction_id FROM fact_financial_transactions)',
                        'severity' => 'CRITICAL',
                        'action' => 'reject_record',
                    ],
                ],
            ],

            'performance_metrics' => [
                'name' => 'Daily Performance Metrics Aggregation',
                'schedule' => '0 2 * * *', // Daily at 2 AM
                'priority' => 'LOW',
                'sources' => [
                    'fact_shipments' => [
                        'type' => 'fact_table',
                        'query' => 'SELECT * FROM fact_shipments WHERE pickup_date_key = ?',
                    ],
                ],
                'transformations' => [
                    'aggregate_metrics' => 'App\\Transformers\\PerformanceMetricsAggregator',
                ],
                'destinations' => [
                    'fact_performance_metrics' => [
                        'load_type' => 'upsert',
                        'merge_key' => 'branch_key,date_key',
                    ],
                ],
            ],
        ],

        'data_sources' => [
            'mysql_internal' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'forge'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ],
            
            'accounting_db' => [
                'driver' => 'mysql',
                'host' => env('ACCOUNTING_DB_HOST', '127.0.0.1'),
                'port' => env('ACCOUNTING_DB_PORT', '3306'),
                'database' => env('ACCOUNTING_DB_DATABASE', 'accounting'),
                'username' => env('ACCOUNTING_DB_USERNAME'),
                'password' => env('ACCOUNTING_DB_PASSWORD'),
            ],
        ],

        'cache' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'analytics:',
            'ttl' => [
                'dashboard_metrics' => 3600,      // 1 hour
                'operational_reports' => 1800,     // 30 minutes
                'financial_reports' => 7200,       // 2 hours
                'customer_analytics' => 14400,     // 4 hours
                'performance_metrics' => 300,      // 5 minutes
            ],
            'tags' => [
                'branch' => 'branch_:id',
                'client' => 'client_:id',
                'date_range' => 'date_range_:hash',
            ],
        ],

        'performance' => [
            'database' => [
                'connection_pool_size' => 20,
                'query_timeout' => 30,
                'slow_query_threshold' => 2, // seconds
            ],
            
            'etl' => [
                'max_concurrent_pipelines' => 10,
                'job_queue_connection' => 'database',
                'job_queue_high' => 'high',
                'job_queue_medium' => 'medium',
                'job_queue_low' => 'low',
            ],
            
            'cache' => [
                'redis_cluster' => false,
                'connection_timeout' => 5,
                'read_timeout' => 5,
            ],
        ],

        'monitoring' => [
            'alerts' => [
                'pipeline_failures' => [
                    'threshold' => 3,
                    'time_window' => 3600, // 1 hour
                    'recipients' => ['ops-team@company.com', 'data-team@company.com'],
                ],
                'data_quality_issues' => [
                    'threshold' => 100, // violations per hour
                    'recipients' => ['data-quality@company.com'],
                ],
                'anomaly_detection' => [
                    'severity_threshold' => 0.8,
                    'recipients' => ['analytics-team@company.com'],
                ],
            ],
            
            'metrics' => [
                'record_counts' => true,
                'processing_times' => true,
                'error_rates' => true,
                'data_quality_scores' => true,
            ],
        ],
    ],

    'environments' => [
        'development' => [
            'pipelines' => [
                'shipments_realtime' => [
                    'schedule' => '*/15 * * * *', // Every 15 minutes
                    'batch_size' => 100, // Smaller batches for development
                ],
            ],
            'performance' => [
                'max_concurrent_pipelines' => 2,
            ],
            'monitoring' => [
                'alerts' => false, // Disable alerts in development
            ],
        ],

        'staging' => [
            'pipelines' => [
                'shipments_realtime' => [
                    'batch_size' => 1000,
                ],
            ],
            'performance' => [
                'max_concurrent_pipelines' => 5,
            ],
        ],

        'production' => [
            'pipelines' => [
                'shipments_realtime' => [
                    'batch_size' => 5000,
                ],
            ],
            'performance' => [
                'max_concurrent_pipelines' => 20,
            ],
        ],
    ],
];