<?php

namespace App\Services\Notifications;

use App\Models\Shipment;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Log;

class TrackingNotificationService
{
    protected SmsNotificationService $smsService;

    public function __construct(SmsNotificationService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Status events that trigger notifications
     */
    protected array $notifiableStatuses = [
        'created',
        'picked_up',
        'in_transit',
        'arrived_hub',
        'customs_hold',
        'customs_cleared', 
        'out_for_delivery',
        'delivered',
        'exception',
        'returned',
    ];

    /**
     * Notify on shipment status change
     */
    public function notifyStatusChange(Shipment $shipment, string $newStatus, ?string $location = null): void
    {
        // Check if this status should trigger notification
        if (!in_array($newStatus, $this->notifiableStatuses)) {
            return;
        }

        // Check customer notification preferences
        if (!$this->shouldNotify($shipment, $newStatus)) {
            return;
        }

        try {
            // Send to receiver
            if ($shipment->receiver_phone || $shipment->consignee_phone) {
                $this->smsService->sendTrackingUpdate($shipment, $newStatus, $location);
            }

            // Send to sender for certain statuses
            if (in_array($newStatus, ['delivered', 'exception', 'returned'])) {
                $this->notifySender($shipment, $newStatus, $location);
            }

        } catch (\Exception $e) {
            Log::error('Tracking notification failed', [
                'shipment_id' => $shipment->id,
                'status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if notification should be sent based on preferences
     */
    protected function shouldNotify(Shipment $shipment, string $status): bool
    {
        // Check customer preferences if exists
        if ($shipment->customer_id) {
            $prefs = NotificationPreference::where('customer_id', $shipment->customer_id)
                ->where('channel', 'sms')
                ->first();

            if ($prefs && !$prefs->enabled) {
                return false;
            }

            // Check specific event preferences
            if ($prefs && isset($prefs->events[$status]) && !$prefs->events[$status]) {
                return false;
            }
        }

        // Default: send notifications
        return true;
    }

    /**
     * Notify sender about shipment status
     */
    protected function notifySender(Shipment $shipment, string $status, ?string $location): void
    {
        $senderPhone = $shipment->sender_phone ?? $shipment->shipper_phone;
        if (!$senderPhone) {
            return;
        }

        $awb = $shipment->tracking_number ?? $shipment->waybill_number ?? "#{$shipment->id}";
        
        $messages = [
            'delivered' => "Shipment {$awb} has been delivered to {$shipment->receiver_name}.",
            'exception' => "Alert: Issue with shipment {$awb}. Please check tracking.",
            'returned' => "Shipment {$awb} is being returned. Contact us for details.",
        ];

        $message = $messages[$status] ?? "Shipment {$awb} update: {$status}";
        $message .= "\n\nBaraka Courier";

        $this->smsService->sendSms($senderPhone, $message, $shipment->id);
    }

    /**
     * Send bulk tracking updates
     */
    public function sendBulkUpdates(array $shipmentIds, string $status, ?string $location = null): array
    {
        $results = ['success' => 0, 'failed' => 0];

        foreach ($shipmentIds as $shipmentId) {
            $shipment = Shipment::find($shipmentId);
            if (!$shipment) {
                $results['failed']++;
                continue;
            }

            try {
                $this->notifyStatusChange($shipment, $status, $location);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Send delivery confirmation with POD
     */
    public function sendDeliveryConfirmation(Shipment $shipment, ?string $signedBy = null): void
    {
        $receiverPhone = $shipment->receiver_phone ?? $shipment->consignee_phone;
        if (!$receiverPhone) {
            return;
        }

        $awb = $shipment->tracking_number ?? $shipment->waybill_number;
        $message = "Delivery Confirmed!\n\n";
        $message .= "Shipment: {$awb}\n";
        $message .= "Delivered: " . now()->format('M d, Y H:i') . "\n";
        
        if ($signedBy) {
            $message .= "Received by: {$signedBy}\n";
        }
        
        $message .= "\nThank you for using Baraka Courier!";

        $this->smsService->sendSms($receiverPhone, $message, $shipment->id);
    }

    /**
     * Send customs clearance notification
     */
    public function sendCustomsNotification(Shipment $shipment, string $customsStatus, ?string $notes = null): void
    {
        $receiverPhone = $shipment->receiver_phone ?? $shipment->consignee_phone;
        if (!$receiverPhone) {
            return;
        }

        $awb = $shipment->tracking_number ?? $shipment->waybill_number;
        
        $messages = [
            'hold' => "Customs Notice: Shipment {$awb} requires clearance. Additional documents may be needed.",
            'inspection' => "Customs Notice: Shipment {$awb} selected for inspection. This may cause delays.",
            'cleared' => "Good news! Shipment {$awb} has cleared customs and will continue to delivery.",
            'duty_required' => "Action Required: Customs duty payment needed for shipment {$awb}.",
        ];

        $message = $messages[$customsStatus] ?? "Customs update for {$awb}: {$customsStatus}";
        
        if ($notes) {
            $message .= "\n\nNote: {$notes}";
        }
        
        $message .= "\n\nBaraka Courier - Contact: +243 XXX XXX XXX";

        $this->smsService->sendSms($receiverPhone, $message, $shipment->id);
    }
}
