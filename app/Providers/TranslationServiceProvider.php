<?php

namespace App\Providers;

use App\Translation\DatabaseTranslationLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new DatabaseTranslationLoader(new Filesystem(), $app['path.lang']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
