<?php

return [
    'enabled' => env('APP_DEBUG', false) && env('APP_ENV') !== 'production',
    'storage' => [
        'enabled' => env('DEBUGBAR_STORAGE_ENABLED', true),
        'driver' => env('DEBUGBAR_STORAGE_DRIVER', 'file'),
        'path' => storage_path('debugbar'),
        'connection' => null,
        'lifetime' => 525600,
    ],
];
