<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Baraka Logistics'),
    'app_installed' => env('APP_INSTALLED'),
    
    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode - PRODUCTION OPTIMIZED
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    | PRODUCTION: Debug is disabled for security and performance.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),
    'debug_blacklist' => [
        '_COOKIE' => array_keys($_COOKIE),
        '_SERVER' => array_keys($_SERVER),
        '_ENV' => array_keys($_ENV),
    ],

    'feature_mobile_api' => env('FEATURE_MOBILE_API', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'https://baraka.sanaa.ug'),
    'asset_url' => env('ASSET_URL', 'https://baraka.sanaa.ug'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and-time functions. Set to UTC for
    | consistency across all production environments.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. Production-optimized for English.
    |
    */

    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key - PRODUCTION SECURE
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver - PRODUCTION
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. PRODUCTION: Use database
    | driver for better scaling and zero-downtime deployment.
    |
    */

    'maintenance' => [
        'driver' => 'database',
        'store' => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers - PRODUCTION OPTIMIZED
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. PRODUCTION: Optimized for performance.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([

        /*
         * Package Service Providers...
         */

        Maatwebsite\Excel\ExcelServiceProvider::class,
        RealRashid\SweetAlert\SweetAlertServiceProvider::class,
        Barryvdh\Debugbar\ServiceProvider::class,
        Milon\Barcode\BarcodeServiceProvider::class,
        Brian2694\Toastr\ToastrServiceProvider::class,
        Cartalyst\Stripe\Laravel\StripeServiceProvider::class,
        Srmklive\PayPal\Providers\PayPalServiceProvider::class,
        Obydul\LaraSkrill\LaraSkrillServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        \App\Providers\ViewServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        // Production-specific providers
        Laravel\Telescope\TelescopeServiceProvider::class,
        Laravel\Horizon\HorizonServiceProvider::class,

    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases - PRODUCTION OPTIMIZED
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. PRODUCTION: Optimized for performance and security.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([

        'Alert' => RealRashid\SweetAlert\Facades\Alert::class,
        'Debugbar' => Barryvdh\Debugbar\Facades\Debugbar::class,
        'DNS1D' => Milon\Barcode\Facades\DNS1DFacade::class,
        'DNS2D' => Milon\Barcode\Facades\DNS2DFacade::class,
        'Toastr' => Brian2694\Toastr\Facades\Toastr::class,
        'Stripe' => Cartalyst\Stripe\Laravel\Facades\Stripe::class,

    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Production Performance Optimizations
    |--------------------------------------------------------------------------
    */

    'config_cache' => true,
    'route_cache' => true,
    'view_cache' => true,

    /*
    |--------------------------------------------------------------------------
    | Production Security Headers
    |--------------------------------------------------------------------------
    */

    'security_headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],

];
