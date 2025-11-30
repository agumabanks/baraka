<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mobile Money Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for mobile money providers in DRC and Rwanda
    |
    */

    'default_provider' => env('MOBILE_MONEY_DEFAULT', 'mtn_momo'),

    'providers' => [
        'mtn_momo' => [
            'name' => 'MTN Mobile Money',
            'enabled' => env('MTN_MOMO_ENABLED', false),
            'sandbox' => env('MTN_MOMO_SANDBOX', true),
            
            // API Credentials
            'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
            'api_user' => env('MTN_MOMO_API_USER'),
            'api_key' => env('MTN_MOMO_API_KEY'),
            
            // Environment URLs
            'base_url' => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
            'callback_url' => env('MTN_MOMO_CALLBACK_URL'),
            
            // Product configuration
            'collection_product' => env('MTN_MOMO_COLLECTION_PRODUCT', 'collection'),
            'disbursement_product' => env('MTN_MOMO_DISBURSEMENT_PRODUCT', 'disbursement'),
            
            // Supported countries (ISO codes)
            'countries' => ['CD', 'RW', 'UG'],
            
            // Currency mapping by country
            'currencies' => [
                'CD' => 'CDF',  // Congo
                'RW' => 'RWF',  // Rwanda
                'UG' => 'UGX',  // Uganda
            ],
        ],

        'orange_money' => [
            'name' => 'Orange Money',
            'enabled' => env('ORANGE_MONEY_ENABLED', false),
            'sandbox' => env('ORANGE_MONEY_SANDBOX', true),
            
            // API Credentials
            'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
            'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
            'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
            
            // Environment URLs
            'base_url' => env('ORANGE_MONEY_BASE_URL', 'https://api.orange.com'),
            'callback_url' => env('ORANGE_MONEY_CALLBACK_URL'),
            
            // Supported countries
            'countries' => ['CD'],
            
            // Currency mapping
            'currencies' => [
                'CD' => 'CDF',
            ],
        ],

        'airtel_money' => [
            'name' => 'Airtel Money',
            'enabled' => env('AIRTEL_MONEY_ENABLED', false),
            'sandbox' => env('AIRTEL_MONEY_SANDBOX', true),
            
            'client_id' => env('AIRTEL_MONEY_CLIENT_ID'),
            'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
            'base_url' => env('AIRTEL_MONEY_BASE_URL', 'https://openapi.airtel.africa'),
            'callback_url' => env('AIRTEL_MONEY_CALLBACK_URL'),
            
            'countries' => ['CD', 'RW', 'UG'],
            'currencies' => [
                'CD' => 'CDF',
                'RW' => 'RWF',
                'UG' => 'UGX',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    */
    'transaction' => [
        'timeout' => 300, // 5 minutes for payment completion
        'retry_attempts' => 3,
        'retry_delay' => 5, // seconds between retries
    ],

    /*
    |--------------------------------------------------------------------------
    | Fee Configuration
    |--------------------------------------------------------------------------
    */
    'fees' => [
        'collection_fee_percent' => env('MOBILE_MONEY_COLLECTION_FEE', 1.5),
        'disbursement_fee_percent' => env('MOBILE_MONEY_DISBURSEMENT_FEE', 1.0),
        'min_transaction' => 100, // Minimum transaction in local currency
        'max_transaction' => 5000000, // Maximum transaction in local currency
    ],
];
