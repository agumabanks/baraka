<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Security Settings
    |--------------------------------------------------------------------------
    */
    'password' => [
        // Minimum password length
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        
        // Complexity requirements
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        
        // Password history - prevent reusing last N passwords
        'history_count' => env('PASSWORD_HISTORY_COUNT', 5),
        
        // Password expiry in days (0 = never expires)
        'expires_days' => env('PASSWORD_EXPIRES_DAYS', 90),
        
        // Check against haveibeenpwned breach database
        'check_breach' => env('PASSWORD_CHECK_BREACH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout Settings
    |--------------------------------------------------------------------------
    */
    'lockout' => [
        // Maximum failed login attempts before lockout
        'max_attempts' => env('LOCKOUT_MAX_ATTEMPTS', 5),
        
        // Time window in minutes to count attempts
        'window_minutes' => env('LOCKOUT_WINDOW_MINUTES', 15),
        
        // Lockout duration in minutes
        'duration_minutes' => env('LOCKOUT_DURATION_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management Settings
    |--------------------------------------------------------------------------
    */
    'session' => [
        // Inactivity timeout in minutes
        'inactivity_timeout_minutes' => env('SESSION_INACTIVITY_TIMEOUT', 30),
        
        // Maximum concurrent sessions per user
        'max_concurrent_sessions' => env('SESSION_MAX_CONCURRENT', 5),
        
        // Warning time before auto-logout (minutes)
        'timeout_warning_minutes' => env('SESSION_TIMEOUT_WARNING', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Settings
    |--------------------------------------------------------------------------
    */
    'audit' => [
        // Audit log retention in days
        'retention_days' => env('AUDIT_RETENTION_DAYS', 730), // 2 years
    ],

    /*
    |--------------------------------------------------------------------------
    | 2FA Settings
    |--------------------------------------------------------------------------
    */
    '2fa' => [
        // Number of backup codes to generate
        'backup_codes_count' => env('2FA_BACKUP_CODES_COUNT', 10),
        
        // Enforce 2FA for admin roles
        'enforce_for_admins' => env('2FA_ENFORCE_FOR_ADMINS', false),
    ],
];
