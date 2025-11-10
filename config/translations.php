<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | Central place to manage the languages that the system exposes to users.
    | Keep this list in sync with the admin interface and translation records.
    |
    */
    'supported' => [
        'en',
        'fr',
        'sw',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Database-driven translations are cached to avoid repeated lookups. The
    | requirement is a three hour TTL, which equals 10,800 seconds.
    |
    */
    'cache_ttl' => (int) env('TRANSLATION_CACHE_TTL', 10_800),
];
