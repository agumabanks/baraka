<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('database:autobackup')->daily();
        $schedule->command('invoice:generate')->daily('13:00');
        
        // Mark overdue invoices daily
        $schedule->command('invoices:mark-overdue')
            ->dailyAt('01:00')
            ->withoutOverlapping();

        // Disaster Recovery & Backups
        $schedule->command('backup:database --label=automatic')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Automatic database backup completed successfully');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Automatic database backup failed');
            });
        
        $schedule->command('backup:cleanup')
            ->dailyAt('03:00')
            ->withoutOverlapping();

        // Webhook retry queue
        $schedule->call(function () {
            $deliveries = \App\Models\WebhookDelivery::retryable()->get();
            foreach ($deliveries as $delivery) {
                dispatch(new \App\Jobs\DeliverWebhook($delivery));
            }
        })->everyFiveMinutes();

        // Auto-lock consolidations that reached cutoff time
        $schedule->command('consolidations:auto-lock')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Auto-lock consolidations completed');
            });

        $schedule->command('handoff:sla-monitor')
            ->everyTenMinutes()
            ->withoutOverlapping();

        $schedule->command('shipment:sla-monitor')
            ->everyTenMinutes()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
