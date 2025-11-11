<?php

return [
    /*
     * Allowed origins for CORS requests
     * 
     * Format:
     * - Exact domain: 'https://example.com'
     * - Wildcard subdomain: 'https://*.example.com'
     * - Any domain (NOT recommended for production): '*'
     */
    'allowed_origins' => [
        // Branch portals
        'https://branch.sanaa.co',
        'https://*.branch.sanaa.co',
        
        // Client portals
        'https://client.sanaa.co',
        'https://*.client.sanaa.co',
        
        // Mobile apps
        'https://mobile-app.sanaa.co',
        
        // Admin dashboard
        'https://admin.sanaa.co',
    ],

    /*
     * Allowed HTTP methods
     */
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    /*
     * Allowed request headers
     */
    'allowed_headers' => [
        'Accept',
        'Accept-Language',
        'Content-Language',
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-Token',
        'X-API-Key',
        'Idempotency-Key',
        'X-Webhook-Signature',
        'X-Device-ID',
        'X-App-Version',
    ],

    /*
     * Credentials allowed in CORS requests
     */
    'allow_credentials' => true,

    /*
     * Cache duration for preflight requests (in seconds)
     */
    'max_age' => 3600,
];
