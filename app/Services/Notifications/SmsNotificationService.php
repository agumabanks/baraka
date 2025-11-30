<?php

namespace App\Services\Notifications;

use App\Models\Shipment;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('notifications.sms.default_provider', 'twilio');
        $this->loadConfig();
    }

    protected function loadConfig(): void
    {
        if ($this->provider === 'twilio') {
            $this->config = [
                'sid' => config('services.twilio.sid'),
                'token' => config('services.twilio.token'),
                'from' => config('services.twilio.phone'),
                'whatsapp_from' => config('services.twilio.whatsapp'),
            ];
        } elseif ($this->provider === 'africas_talking') {
            $this->config = [
                'username' => config('services.africas_talking.username'),
                'api_key' => config('services.africas_talking.api_key'),
                'from' => config('services.africas_talking.sender_id'),
            ];
        }
    }

    /**
     * Send SMS (interface method for NotificationOrchestrationService)
     */
    public function send(string $to, string $message): array
    {
        return $this->sendSms($to, $message);
    }

    /**
     * Send SMS notification
     */
    public function sendSms(string $to, string $message, ?int $shipmentId = null): array
    {
        $to = $this->formatPhoneNumber($to);

        try {
            if ($this->provider === 'twilio') {
                $result = $this->sendViaTwilio($to, $message);
            } elseif ($this->provider === 'africas_talking') {
                $result = $this->sendViaAfricasTalking($to, $message);
            } else {
                $result = ['success' => false, 'error' => 'No SMS provider configured'];
            }

            // Log notification
            $this->logNotification('sms', $to, $message, $result['success'], $shipmentId, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error('SMS send failed', ['to' => $to, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp message
     */
    public function sendWhatsApp(string $to, string $message, ?int $shipmentId = null): array
    {
        $to = $this->formatPhoneNumber($to);

        try {
            if ($this->provider === 'twilio' && !empty($this->config['whatsapp_from'])) {
                $result = $this->sendWhatsAppViaTwilio($to, $message);
            } else {
                $result = ['success' => false, 'error' => 'WhatsApp not configured'];
            }

            $this->logNotification('whatsapp', $to, $message, $result['success'], $shipmentId, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', ['to' => $to, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send via Twilio SMS
     */
    protected function sendViaTwilio(string $to, string $message): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->config['sid'], $this->config['token'])
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->config['sid']}/Messages.json", [
                'From' => $this->config['from'],
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('sid'),
                'status' => $response->json('status'),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to send SMS',
        ];
    }

    /**
     * Send WhatsApp via Twilio
     */
    protected function sendWhatsAppViaTwilio(string $to, string $message): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->config['sid'], $this->config['token'])
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->config['sid']}/Messages.json", [
                'From' => "whatsapp:{$this->config['whatsapp_from']}",
                'To' => "whatsapp:{$to}",
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('sid'),
                'status' => $response->json('status'),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to send WhatsApp',
        ];
    }

    /**
     * Send via Africa's Talking
     */
    protected function sendViaAfricasTalking(string $to, string $message): array
    {
        $response = Http::withHeaders([
            'apiKey' => $this->config['api_key'],
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => $this->config['username'],
            'to' => $to,
            'message' => $message,
            'from' => $this->config['from'],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $recipient = $data['SMSMessageData']['Recipients'][0] ?? [];
            
            return [
                'success' => ($recipient['status'] ?? '') === 'Success',
                'message_id' => $recipient['messageId'] ?? null,
                'cost' => $recipient['cost'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to send SMS via Africa\'s Talking',
        ];
    }

    /**
     * Format phone number to E.164 format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (!str_starts_with($phone, '+')) {
            // Default to DRC country code
            if (strlen($phone) === 9) {
                $phone = '+243' . $phone;
            } elseif (strlen($phone) === 10 && str_starts_with($phone, '0')) {
                $phone = '+243' . substr($phone, 1);
            } elseif (!str_starts_with($phone, '243')) {
                $phone = '+' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Log notification attempt
     */
    protected function logNotification(
        string $channel,
        string $recipient,
        string $message,
        bool $success,
        ?int $shipmentId,
        array $response
    ): void {
        NotificationLog::create([
            'notification_id' => \Illuminate\Support\Str::uuid()->toString(),
            'channel' => $channel,
            'recipient_phone' => $recipient,
            'body' => $message,
            'related_type' => $shipmentId ? 'App\\Models\\Shipment' : null,
            'related_id' => $shipmentId,
            'status' => $success ? 'sent' : 'failed',
            'sent_at' => $success ? now() : null,
            'failed_at' => !$success ? now() : null,
            'provider_message_id' => $response['message_id'] ?? null,
            'error_message' => $response['error'] ?? null,
            'metadata' => $response,
        ]);
    }

    /**
     * Send tracking update notification
     */
    public function sendTrackingUpdate(Shipment $shipment, string $status, ?string $location = null): array
    {
        $recipientPhone = $shipment->receiver_phone ?? $shipment->consignee_phone;
        if (!$recipientPhone) {
            return ['success' => false, 'error' => 'No recipient phone number'];
        }

        $trackingUrl = route('tracking.show', $shipment->tracking_number ?? $shipment->waybill_number);
        $message = $this->buildTrackingMessage($shipment, $status, $location, $trackingUrl);

        // Try WhatsApp first, fallback to SMS
        $result = $this->sendWhatsApp($recipientPhone, $message, $shipment->id);
        
        if (!$result['success']) {
            $result = $this->sendSms($recipientPhone, $message, $shipment->id);
        }

        return $result;
    }

    /**
     * Build tracking update message
     */
    protected function buildTrackingMessage(
        Shipment $shipment,
        string $status,
        ?string $location,
        string $trackingUrl
    ): string {
        $awb = $shipment->tracking_number ?? $shipment->waybill_number ?? "#{$shipment->id}";
        
        $statusMessages = [
            'created' => "Your shipment {$awb} has been booked and is awaiting pickup.",
            'picked_up' => "Your shipment {$awb} has been picked up.",
            'in_transit' => "Your shipment {$awb} is in transit" . ($location ? " at {$location}" : "") . ".",
            'arrived_hub' => "Your shipment {$awb} has arrived at our hub" . ($location ? " in {$location}" : "") . ".",
            'customs_hold' => "Your shipment {$awb} is being processed by customs.",
            'customs_cleared' => "Your shipment {$awb} has cleared customs.",
            'out_for_delivery' => "Your shipment {$awb} is out for delivery today!",
            'delivered' => "Your shipment {$awb} has been delivered. Thank you for using Baraka Courier!",
            'exception' => "There's an issue with your shipment {$awb}. Please contact us.",
        ];

        $message = $statusMessages[$status] ?? "Shipment {$awb} status: {$status}";
        $message .= "\n\nTrack: {$trackingUrl}";
        $message .= "\n\nBaraka Courier";

        return $message;
    }
}
