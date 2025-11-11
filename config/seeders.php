<?php

return [
    /*
     * Branch seeding configuration
     * 
     * Configure default branches for seeding. Format:
     * [
     *   'code' => 'BRANCH-CODE',
     *   'name' => 'Branch Name',
     *   'type' => 'HUB|REGIONAL|LOCAL',
     *   'country' => 'ISO Country Code',
     *   'city' => 'City Name',
     *   'address' => 'Branch Address',
     *   'is_hub' => true|false,
     *   'parent_code' => 'PARENT-CODE' (optional, for linking hierarchy),
     *   'status' => 'active|inactive',
     * ]
     */
    'branches' => env('SEED_BRANCHES_CONFIG')
        ? json_decode(env('SEED_BRANCHES_CONFIG'), true)
        : [],

    /*
     * Worker roles to seed
     * Each role includes name, description, and permissions
     */
    'worker_roles' => [
        [
            'name' => 'Branch Manager',
            'description' => 'Senior management for branch operations',
            'permissions' => ['view-branches', 'manage-workers', 'approve-shipments'],
        ],
        [
            'name' => 'Operations Supervisor',
            'description' => 'Supervise daily operations',
            'permissions' => ['view-branches', 'manage-shipments', 'assign-workers'],
        ],
    ],

    /*
     * Safe mode settings
     * 
     * safe_mode: When true, prevents seeding if record count exceeds threshold
     * confirmation_required: Require confirmation before seeding
     * transaction: Run seeder in database transaction (rollback on error)
     * backup_before_seed: Create backup before seeding
     */
    'safe_mode' => [
        'enabled' => env('SEED_SAFE_MODE', true),
        'confirmation_required' => env('APP_ENV') === 'production',
        'transaction' => true,
        'backup_before_seed' => env('APP_ENV') === 'production',
    ],

    /*
     * Logging configuration
     * 
     * log_changes: Log all created/updated records
     * log_channel: Specify which log channel to use
     * verbose: Show detailed output during seeding
     */
    'logging' => [
        'log_changes' => true,
        'log_channel' => 'seeding',
        'verbose' => env('APP_DEBUG', false),
    ],
];
