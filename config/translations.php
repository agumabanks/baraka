<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | Central place to manage the languages that the system exposes to users.
    | Keep this list in sync with the admin interface and translation records.
    | Add new language codes here to enable them system-wide.
    |
    */
    'supported' => [
        'en',
        'fr',
        'sw',
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Metadata
    |--------------------------------------------------------------------------
    |
    | Extended metadata for supported languages including native names,
    | RTL (right-to-left) support, and flag emoji codes.
    |
    */
    'metadata' => [
        'en' => ['name' => 'English', 'native' => 'English', 'rtl' => false, 'flag' => 'gb'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'rtl' => false, 'flag' => 'fr'],
        'sw' => ['name' => 'Swahili', 'native' => 'Kiswahili', 'rtl' => false, 'flag' => 'ke'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'rtl' => true, 'flag' => 'sa'],
        'zh' => ['name' => 'Chinese', 'native' => '中文', 'rtl' => false, 'flag' => 'cn'],
        'es' => ['name' => 'Spanish', 'native' => 'Español', 'rtl' => false, 'flag' => 'es'],
        'de' => ['name' => 'German', 'native' => 'Deutsch', 'rtl' => false, 'flag' => 'de'],
        'pt' => ['name' => 'Portuguese', 'native' => 'Português', 'rtl' => false, 'flag' => 'pt'],
        'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी', 'rtl' => false, 'flag' => 'in'],
        'ja' => ['name' => 'Japanese', 'native' => '日本語', 'rtl' => false, 'flag' => 'jp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Database-driven translations are cached to avoid repeated lookups. The
    | default is a three hour TTL, which equals 10,800 seconds. Set to 0 to
    | disable caching (not recommended for production).
    |
    */
    'cache_ttl' => (int) env('TRANSLATION_CACHE_TTL', 10_800),

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default items per page for the translation management interface.
    |
    */
    'pagination' => [
        'default' => 25,
        'options' => [10, 25, 50, 100, 250],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Rules for validating translations. Used by the validation endpoint.
    |
    */
    'validation' => [
        'max_length' => 5000,
        'warn_length' => 500,
        'check_placeholders' => true,
        'check_html' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed audit logging for translation changes.
    |
    */
    'audit' => [
        'enabled' => true,
        'log_channel' => 'daily',
    ],

    /*
    |--------------------------------------------------------------------------
    | Import/Export Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for bulk import and export operations.
    |
    */
    'import_export' => [
        'max_file_size' => 5120, // KB
        'allowed_formats' => ['json', 'csv'],
        'backup_before_import' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Chain
    |--------------------------------------------------------------------------
    |
    | When a translation is missing, the system will try these locales in order.
    |
    */
    'fallback_chain' => ['en'],
];
