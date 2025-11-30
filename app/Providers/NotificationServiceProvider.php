<?php

namespace App\Providers;

use App\Services\Notifications\EmailNotificationService;
use App\Services\Notifications\NotificationOrchestrationService;
use App\Services\Notifications\PushNotificationService;
use App\Services\Notifications\SmsNotificationService;
use App\Services\Notifications\WhatsAppNotificationService;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register channel services as singletons
        $this->app->singleton(EmailNotificationService::class);
        $this->app->singleton(SmsNotificationService::class);
        $this->app->singleton(PushNotificationService::class);
        $this->app->singleton(WhatsAppNotificationService::class);

        // Register orchestration service
        $this->app->singleton(NotificationOrchestrationService::class, function ($app) {
            return new NotificationOrchestrationService(
                $app->make(EmailNotificationService::class),
                $app->make(SmsNotificationService::class),
                $app->make(PushNotificationService::class),
                $app->make(WhatsAppNotificationService::class)
            );
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
