<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dynamic Pricing Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Dynamic Rate Calculation Module
    |
    */

    // Service Level Configuration
    'service_levels' => [
        'express' => [
            'name' => 'Express Service',
            'multiplier' => 1.5,
            'delivery_time' => '24-48 hours',
            'reliability_score' => 95.0,
            'fuel_multiplier' => 1.1,
            'sla_claims_covered' => true,
        ],
        'priority' => [
            'name' => 'Priority Service',
            'multiplier' => 1.25,
            'delivery_time' => '2-3 business days',
            'reliability_score' => 92.0,
            'fuel_multiplier' => 1.0,
            'sla_claims_covered' => true,
        ],
        'standard' => [
            'name' => 'Standard Service',
            'multiplier' => 1.0,
            'delivery_time' => '3-5 business days',
            'reliability_score' => 88.0,
            'fuel_multiplier' => 1.0,
            'sla_claims_covered' => false,
        ],
        'economy' => [
            'name' => 'Economy Service',
            'multiplier' => 0.8,
            'delivery_time' => '5-7 business days',
            'reliability_score' => 85.0,
            'fuel_multiplier' => 0.9,
            'sla_claims_covered' => false,
        ],
    ],

    // Customer Tier Discounts
    'customer_tiers' => [
        'platinum' => [
            'name' => 'Platinum Customer',
            'discount_rate' => 15.0,
            'minimum_monthly_shipments' => 500,
            'minimum_monthly_revenue' => 15000,
            'special_handling' => true,
            'dedicated_account_manager' => true,
        ],
        'gold' => [
            'name' => 'Gold Customer',
            'discount_rate' => 10.0,
            'minimum_monthly_shipments' => 200,
            'minimum_monthly_revenue' => 5000,
            'special_handling' => true,
            'dedicated_account_manager' => false,
        ],
        'silver' => [
            'name' => 'Silver Customer',
            'discount_rate' => 5.0,
            'minimum_monthly_shipments' => 50,
            'minimum_monthly_revenue' => 1000,
            'special_handling' => false,
            'dedicated_account_manager' => false,
        ],
        'standard' => [
            'name' => 'Standard Customer',
            'discount_rate' => 0.0,
            'minimum_monthly_shipments' => 0,
            'minimum_monthly_revenue' => 0,
            'special_handling' => false,
            'dedicated_account_manager' => false,
        ],
    ],

    // Dimensional Weight Configuration
    'dimensional_weight' => [
        'domestic' => [
            'divisor' => 5000,
            'name' => 'Domestic Shipping',
            'minimum_weight' => 0.5, // kg
        ],
        'international' => [
            'divisor' => 4000,
            'name' => 'International Shipping',
            'minimum_weight' => 0.5, // kg
        ],
        'express' => [
            'divisor' => 6000,
            'name' => 'Express Service',
            'minimum_weight' => 0.5, // kg
        ],
    ],

    // Fuel Surcharge Configuration
    'fuel_surcharge' => [
        'base_index' => 100.0,
        'surcharge_factor' => 0.08, // 8%
        'update_frequency_hours' => 24,
        'sources' => [
            'eia' => [
                'name' => 'Energy Information Administration',
                'api_endpoint' => env('EIA_API_ENDPOINT'),
                'api_key' => env('EIA_API_KEY'),
                'region' => 'US',
            ],
            'platts' => [
                'name' => 'Platts Oil Price',
                'api_endpoint' => env('PLATTS_API_ENDPOINT'),
                'api_key' => env('PLATTS_API_KEY'),
                'region' => 'Global',
            ],
        ],
    ],

    // Tax Configuration by Jurisdiction
    'tax_rules' => [
        'US' => [
            'default_rate' => 8.0,
            'fuel_tax_rate' => 0.2,
            'states' => [
                'CA' => ['sales_tax' => 8.25, 'fuel_tax' => 0.3],
                'NY' => ['sales_tax' => 8.0, 'fuel_tax' => 0.25],
                'TX' => ['sales_tax' => 6.25, 'fuel_tax' => 0.2],
                'FL' => ['sales_tax' => 6.0, 'fuel_tax' => 0.15],
            ],
        ],
        'CA' => [
            'gst_rate' => 5.0,
            'pst_rate' => 7.0,
            'hst_rate' => 15.0, // Combined GST/HST
            'provinces' => [
                'ON' => ['gst' => 5.0, 'hst' => 13.0],
                'BC' => ['gst' => 5.0, 'pst' => 12.0],
                'AB' => ['gst' => 5.0, 'pst' => 0.0],
            ],
        ],
        'EU' => [
            'vat_rates' => [
                'DE' => 19.0,
                'FR' => 20.0,
                'IT' => 22.0,
                'ES' => 21.0,
                'NL' => 21.0,
            ],
        ],
    ],

    // Customs Configuration
    'customs' => [
        'base_clearance_fee' => 25.00,
        'percentage_of_value' => 0.02, // 2% of declared value
        'minimum_fee' => 25.00,
        'maximum_fee' => 500.00,
        'documentation_fee' => 15.00,
        'broker_fee' => 35.00,
    ],

    // Currency Configuration
    'currencies' => [
        'base_currency' => 'USD',
        'supported_currencies' => ['USD', 'EUR', 'GBP', 'CAD', 'JPY', 'AUD'],
        'exchange_rate_providers' => [
            'fixer' => [
                'api_endpoint' => env('FIXER_API_ENDPOINT'),
                'api_key' => env('FIXER_API_KEY'),
                'base_url' => 'https://api.fixer.io/v1',
            ],
            'currencyapi' => [
                'api_endpoint' => env('CURRENCYAPI_ENDPOINT'),
                'api_key' => env('CURRENCYAPI_KEY'),
                'base_url' => 'https://api.currencyapi.com/v3',
            ],
        ],
        'cache_ttl_minutes' => 30,
    ],

    // Competitor Pricing Configuration
    'competitor_pricing' => [
        'data_sources' => [
            'fedex' => [
                'name' => 'FedEx',
                'api_endpoint' => env('FEDEX_API_ENDPOINT'),
                'api_key' => env('FEDEX_API_KEY'),
                'update_frequency_hours' => 6,
            ],
            'ups' => [
                'name' => 'UPS',
                'api_endpoint' => env('UPS_API_ENDPOINT'),
                'api_key' => env('UPS_API_KEY'),
                'update_frequency_hours' => 6,
            ],
            'dhl' => [
                'name' => 'DHL',
                'api_endpoint' => env('DHL_API_ENDPOINT'),
                'api_key' => env('DHL_API_KEY'),
                'update_frequency_hours' => 6,
            ],
        ],
        'cache_ttl_hours' => 24,
        'minimum_data_points' => 3,
    ],

    // Time-based Pricing Configuration
    'time_based_pricing' => [
        'peak_hours' => [
            'start' => 7, // 7 AM
            'end' => 9,   // 9 AM
        ],
        'evening_peak' => [
            'start' => 17, // 5 PM
            'end' => 19,   // 7 PM
        ],
        'peak_surcharge' => 0.05, // 5%
        'weekend_discount' => -0.10, // 10% discount
        'holiday_surcharge' => 0.15, // 15% surcharge
        'seasonal_adjustments' => [
            'peak_season' => [
                'months' => [11, 12], // November, December
                'surcharge' => 0.10, // 10% surcharge
            ],
        ],
    ],

    // Rate Limiting Configuration
    'rate_limiting' => [
        'default_requests_per_minute' => 60,
        'quote_requests_per_hour' => 100,
        'bulk_quote_limit' => 50,
        'api_keys' => [
            'free_tier' => [
                'requests_per_day' => 100,
                'requests_per_hour' => 10,
            ],
            'paid_tier' => [
                'requests_per_day' => 1000,
                'requests_per_hour' => 100,
            ],
            'enterprise' => [
                'requests_per_day' => 10000,
                'requests_per_hour' => 1000,
            ],
        ],
    ],

    // Caching Configuration
    'caching' => [
        'quote_cache_ttl_minutes' => 15,
        'rate_cache_ttl_minutes' => 30,
        'competitor_cache_ttl_hours' => 1,
        'fuel_index_cache_ttl_hours' => 1,
        'exchange_rate_cache_ttl_minutes' => 30,
        'tax_rules_cache_ttl_hours' => 24,
    ],

    // Queue Configuration
    'queue' => [
        'bulk_quote_batch_size' => 10,
        'competitor_sync_batch_size' => 25,
        'fuel_index_update_interval' => 3600, // 1 hour
        'competitor_sync_interval' => 21600, // 6 hours
        'quote_cleanup_days' => 30,
    ],

    // Webhook Configuration
    'webhooks' => [
        'quote_generated' => [
            'enabled' => true,
            'events' => ['quote.created', 'quote.approved', 'quote.rejected'],
            'retry_attempts' => 3,
            'timeout_seconds' => 30,
        ],
        'rate_updated' => [
            'enabled' => true,
            'events' => ['rate.updated', 'fuel_surcharge.changed'],
            'retry_attempts' => 3,
            'timeout_seconds' => 30,
        ],
    ],

    // Business Rule Validation
    'validation' => [
        'maximum_shipment_weight' => 70.0, // kg
        'maximum_dimensions' => [
            'length' => 120, // cm
            'width' => 80,   // cm
            'height' => 80,  // cm
        ],
        'minimum_declared_value' => 1.0, // USD
        'maximum_declared_value' => 50000.0, // USD
        'quote_validity_hours' => 24,
        'maximum_processing_time_ms' => 2000,
    ],

    // Logging Configuration
    'logging' => [
        'log_quote_calculations' => true,
        'log_api_requests' => true,
        'log_errors' => true,
        'log_performance_metrics' => true,
        'log_level' => env('LOG_LEVEL', 'info'),
        'channels' => [
            'quote_calculations' => 'daily',
            'api_requests' => 'daily',
            'errors' => 'error',
            'performance' => 'daily',
        ],
    ],

    // Performance Thresholds
    'performance' => [
        'max_processing_time_ms' => 2000,
        'cache_hit_rate_threshold' => 0.8, // 80%
        'error_rate_threshold' => 0.05, // 5%
        'availability_threshold' => 0.99, // 99%
    ],
];