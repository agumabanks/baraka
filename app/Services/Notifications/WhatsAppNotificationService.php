<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppNotificationService
 * 
 * Handles WhatsApp Business API notifications via Twilio with:
 * - Template messages (pre-approved)
 * - Rich media support
 * - Delivery tracking
 */
class WhatsAppNotificationService
{
    protected ?string $accountSid;
    protected ?string $authToken;
    protected ?string $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.sid');
        $this->authToken = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.whatsapp');
    }

    /**
     * Send WhatsApp message
     */
    public function send(string $to, array $content): array
    {
        if (!$this->isConfigured()) {
            Log::warning('WhatsApp not configured, message not sent');
            return [
                'success' => false,
                'message' => 'WhatsApp service not configured',
            ];
        }

        try {
            // Format phone number for WhatsApp
            $to = $this->formatWhatsAppNumber($to);
            $from = $this->formatWhatsAppNumber($this->fromNumber);

            // Build message body
            $body = $this->buildMessageBody($content);

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'To' => $to,
                    'From' => $from,
                    'Body' => $body,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('WhatsApp message sent', [
                    'to' => $to,
                    'message_sid' => $data['sid'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['sid'] ?? null,
                    'status' => $data['status'] ?? 'sent',
                ];
            }

            $error = $response->json()['message'] ?? 'Unknown error';
            Log::error('WhatsApp send failed', [
                'to' => $to,
                'error' => $error,
            ]);

            return [
                'success' => false,
                'message' => $error,
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp send exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send WhatsApp template message
     */
    public function sendTemplate(string $to, string $templateSid, array $variables = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp not configured'];
        }

        try {
            $to = $this->formatWhatsAppNumber($to);
            $from = $this->formatWhatsAppNumber($this->fromNumber);

            $params = [
                'To' => $to,
                'From' => $from,
                'ContentSid' => $templateSid,
            ];

            // Add template variables
            if (!empty($variables)) {
                $params['ContentVariables'] = json_encode($variables);
            }

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json()['sid'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Template send failed',
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp message with media
     */
    public function sendWithMedia(string $to, string $message, string $mediaUrl): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp not configured'];
        }

        try {
            $to = $this->formatWhatsAppNumber($to);
            $from = $this->formatWhatsAppNumber($this->fromNumber);

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'To' => $to,
                    'From' => $from,
                    'Body' => $message,
                    'MediaUrl' => $mediaUrl,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json()['sid'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Media send failed',
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Build message body from content array
     */
    protected function buildMessageBody(array $content): string
    {
        // If template ID provided, this would use template
        if (!empty($content['template_id'])) {
            // Template messages require pre-approval from WhatsApp
            // Variables would be substituted by WhatsApp
            return "Template: {$content['template_id']}";
        }

        // For session messages (within 24hr window)
        if (!empty($content['body'])) {
            return $content['body'];
        }

        // Default fallback
        return $content['title'] ?? 'Notification';
    }

    /**
     * Format phone number for WhatsApp
     */
    protected function formatWhatsAppNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Ensure + prefix
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '0')) {
                $phone = '+256' . substr($phone, 1);
            } elseif (strlen($phone) === 9) {
                $phone = '+256' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }

        // WhatsApp format: whatsapp:+1234567890
        return 'whatsapp:' . $phone;
    }

    /**
     * Check if WhatsApp is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->accountSid) && 
               !empty($this->authToken) && 
               !empty($this->fromNumber);
    }

    /**
     * Get message status
     */
    public function getMessageStatus(string $messageSid): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages/{$messageSid}.json");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }
}
