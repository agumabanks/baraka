<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/admin/dashboard-blade';
    // public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        Route::model('branch', \App\Models\Backend\Branch::class);
        Route::model('driver', \App\Models\Driver::class);
        Route::model('roster', \App\Models\DriverRoster::class);

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            // Always enable V1 API routes for production readiness
            Route::prefix('api/v1')
                ->middleware('api')
                ->group(base_path('routes/api_v1.php'));

            // Load additional V1 route files
            Route::middleware('api')
                ->group(base_path('routes/api/v1/auth.php'));
            Route::middleware('api')
                ->group(base_path('routes/api/v1/shipments.php'));
            Route::middleware('api')
                ->group(base_path('routes/api/v1/users.php'));
            Route::middleware('api')
                ->group(base_path('routes/api/v1/branches.php'));
            Route::middleware('api')
                ->group(base_path('routes/api/v1/files.php'));
            Route::middleware('api')
                ->group(base_path('routes/api/v1/reports.php'));
            Route::middleware('api')
                ->group(base_path('routes/api/v1/system.php'));

            // Readiness improvements routes (webhooks, mobile scanning, EDI)
            Route::middleware('api')
                ->group(base_path('routes/api-readiness-improvements.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
