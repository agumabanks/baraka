<?php

namespace App\Providers;

use App\Services\DynamicPricingService;
use App\Services\RateCardManagementService;
use App\Services\WebhookManagementService;
use Illuminate\Support\ServiceProvider;

/**
 * Dynamic Pricing Service Provider
 * 
 * Registers the DynamicPricingService and related dependencies
 * in the Laravel service container.
 */
class DynamicPricingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register DynamicPricingService
        $this->app->singleton(DynamicPricingService::class, function ($app) {
            return new DynamicPricingService(
                $app->make(RateCardManagementService::class),
                $app->make(WebhookManagementService::class)
            );
        });

        // Register related services
        $this->app->singleton(CompetitorPriceSyncService::class);
        $this->app->singleton(FuelIndexUpdateService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api-dynamic-pricing.php');

        // Publish configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dynamic-pricing.php',
            'dynamic-pricing'
        );

        // Load migrations (if any additional ones are needed)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'dynamic-pricing');
    }
}