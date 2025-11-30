<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Services\Notifications\NotificationOrchestrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendShipmentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';
    public $delay = 5; // 5 second delay to allow batch updates

    protected NotificationOrchestrationService $notificationService;

    public function __construct(NotificationOrchestrationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ShipmentStatusChanged $event): void
    {
        $shipment = $event->shipment;
        $newStatus = $event->newStatus;

        // Map status to notification event
        $eventMapping = [
            'created' => 'created',
            'booked' => 'created',
            'picked_up' => 'picked_up',
            'in_transit' => 'in_transit',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'returned' => 'returned',
            'exception' => 'exception',
        ];

        $statusValue = is_object($newStatus) ? $newStatus->value : $newStatus;
        $notificationEvent = $eventMapping[strtolower($statusValue)] ?? null;

        if (!$notificationEvent) {
            Log::debug('No notification mapping for status', [
                'shipment_id' => $shipment->id,
                'status' => $statusValue,
            ]);
            return;
        }

        try {
            $result = $this->notificationService->sendShipmentNotification(
                $shipment,
                $notificationEvent
            );

            Log::info('Shipment notification sent', [
                'shipment_id' => $shipment->id,
                'event' => $notificationEvent,
                'result' => $result['success'] ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send shipment notification', [
                'shipment_id' => $shipment->id,
                'event' => $notificationEvent,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(ShipmentStatusChanged $event): bool
    {
        // Don't queue for test/development environments
        if (app()->environment('testing')) {
            return false;
        }

        // Only queue if customer exists
        return $event->shipment->customer_id !== null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(ShipmentStatusChanged $event, \Throwable $exception): void
    {
        Log::error('Shipment notification job failed', [
            'shipment_id' => $event->shipment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
