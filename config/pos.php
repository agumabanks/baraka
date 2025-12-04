<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enhanced POS Feature Toggle
    |--------------------------------------------------------------------------
    | Controls availability of the enhanced POS experience (admin/branch).
    | Override per environment via POS_ENHANCED_ENABLED=true/false.
    */
    'enhanced_enabled' => env('POS_ENHANCED_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Volumetric Weight Divisor (POS-BR-01)
    |--------------------------------------------------------------------------
    | Used to calculate volumetric weight: (L * W * H) / divisor
    | Standard divisors: 5000 (air freight), 6000 (road freight)
    */
    'volumetric_divisor' => env('POS_VOLUMETRIC_DIVISOR', 5000),

    /*
    |--------------------------------------------------------------------------
    | Draft Expiry Hours (POS-REL-01)
    |--------------------------------------------------------------------------
    | How long shipment drafts remain valid before auto-expiring.
    */
    'draft_expiry_hours' => env('POS_DRAFT_EXPIRY_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Supervisor Override Expiry Minutes (POS-SEC-03)
    |--------------------------------------------------------------------------
    | How long a supervisor override request remains valid.
    */
    'override_expiry_minutes' => env('POS_OVERRIDE_EXPIRY_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Max Discount Percent Without Approval (POS-RATE-06)
    |--------------------------------------------------------------------------
    | Counter agents can apply discounts up to this percentage without
    | requiring supervisor approval.
    */
    'max_discount_without_approval' => env('POS_MAX_DISCOUNT_WITHOUT_APPROVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | Label Reprint Requires Approval (POS-REL-03)
    |--------------------------------------------------------------------------
    | If true, reprinting labels requires supervisor approval for non-admins.
    */
    'reprint_requires_approval' => env('POS_REPRINT_REQUIRES_APPROVAL', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (POS-SEC-06)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'search' => env('POS_RATE_LIMIT_SEARCH', 60),   // per minute
        'quote' => env('POS_RATE_LIMIT_QUOTE', 30),     // per minute
        'create' => env('POS_RATE_LIMIT_CREATE', 10),   // per minute
        'payment' => env('POS_RATE_LIMIT_PAYMENT', 10), // per minute
    ],
];
