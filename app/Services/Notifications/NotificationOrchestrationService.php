<?php

namespace App\Services\Notifications;

use App\Models\NotificationTemplate;
use App\Models\NotificationPreference;
use App\Models\NotificationLog;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * NotificationOrchestrationService
 * 
 * Central hub for all notification operations:
 * - Multi-channel dispatch (email, SMS, push, WhatsApp)
 * - Template management
 * - User preference handling
 * - Rate limiting and quiet hours
 * - Delivery tracking and logging
 */
class NotificationOrchestrationService
{
    protected EmailNotificationService $emailService;
    protected SmsNotificationService $smsService;
    protected PushNotificationService $pushService;
    protected WhatsAppNotificationService $whatsappService;

    public function __construct(
        EmailNotificationService $emailService,
        SmsNotificationService $smsService,
        PushNotificationService $pushService,
        WhatsAppNotificationService $whatsappService
    ) {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->pushService = $pushService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send notification using template
     */
    public function sendNotification(
        string $templateCode,
        $recipient,
        array $variables = [],
        array $options = []
    ): array {
        $template = NotificationTemplate::active()->byCode($templateCode)->first();

        if (!$template) {
            Log::warning("Notification template not found: {$templateCode}");
            return [
                'success' => false,
                'message' => "Template '{$templateCode}' not found",
            ];
        }

        // Get recipient preferences
        $preferences = NotificationPreference::getOrCreate($recipient);

        // Check if event is enabled for recipient
        if (!$preferences->isEventEnabled($templateCode)) {
            return [
                'success' => true,
                'message' => 'Notification skipped - disabled by user preference',
                'skipped' => true,
            ];
        }

        // Check quiet hours (unless urgent)
        if (!($options['urgent'] ?? false) && $preferences->isQuietHours()) {
            // Queue for later if not urgent
            return $this->queueForLater($templateCode, $recipient, $variables, $options);
        }

        // Generate unique notification ID for tracking
        $notificationId = Str::uuid()->toString();

        // Determine channels to use
        $channels = $options['channels'] ?? $this->determineChannels($template, $preferences, $options);

        $results = [];

        foreach ($channels as $channel) {
            if (!$template->hasChannel($channel)) {
                continue;
            }

            $result = $this->sendToChannel($channel, $template, $recipient, $variables, $notificationId, $options);
            $results[$channel] = $result;
        }

        return [
            'success' => true,
            'notification_id' => $notificationId,
            'channels' => $results,
        ];
    }

    /**
     * Send shipment notification
     */
    public function sendShipmentNotification(
        Shipment $shipment,
        string $event,
        array $additionalData = []
    ): array {
        $templateCode = "shipment_{$event}";
        
        // Build variables from shipment
        $variables = array_merge([
            'tracking_number' => $shipment->tracking_number,
            'status' => ucfirst(str_replace('_', ' ', $shipment->status)),
            'origin' => $shipment->originBranch?->name ?? 'N/A',
            'destination' => $shipment->destBranch?->name ?? 'N/A',
            'recipient_name' => $shipment->customer?->name ?? 'Customer',
            'eta' => $shipment->expected_delivery_date?->format('M d, Y h:i A') ?? 'N/A',
            'tracking_url' => url("/tracking/{$shipment->tracking_number}"),
            'company_name' => config('app.name'),
        ], $additionalData);

        // Determine recipient
        $recipient = $shipment->customer;
        
        if (!$recipient) {
            Log::warning("No recipient for shipment notification", [
                'shipment_id' => $shipment->id,
                'event' => $event,
            ]);
            return ['success' => false, 'message' => 'No recipient found'];
        }

        return $this->sendNotification($templateCode, $recipient, $variables, [
            'related_type' => 'shipment',
            'related_id' => $shipment->id,
        ]);
    }

    /**
     * Send to specific channel
     */
    protected function sendToChannel(
        string $channel,
        NotificationTemplate $template,
        $recipient,
        array $variables,
        string $notificationId,
        array $options
    ): array {
        // Render template for channel
        $content = $template->render($channel, $variables);

        // Get recipient contact info
        $contactInfo = $this->getRecipientContact($recipient, $channel);

        if (!$contactInfo) {
            return [
                'success' => false,
                'message' => "No {$channel} contact info for recipient",
            ];
        }

        // Create log entry
        $log = NotificationLog::create([
            'notification_id' => $notificationId,
            'template_code' => $template->code,
            'notifiable_type' => get_class($recipient),
            'notifiable_id' => $recipient->id,
            'recipient_email' => $channel === 'email' ? $contactInfo : null,
            'recipient_phone' => in_array($channel, ['sms', 'whatsapp']) ? $contactInfo : null,
            'recipient_device_token' => $channel === 'push' ? $contactInfo : null,
            'channel' => $channel,
            'subject' => $content['subject'] ?? $content['title'] ?? null,
            'body' => $content['body'] ?? $content['body_text'] ?? null,
            'status' => 'pending',
            'related_type' => $options['related_type'] ?? null,
            'related_id' => $options['related_id'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ]);

        try {
            $result = match ($channel) {
                'email' => $this->emailService->send($contactInfo, $content),
                'sms' => $this->smsService->send($contactInfo, $content['body']),
                'push' => $this->pushService->send($contactInfo, $content),
                'whatsapp' => $this->whatsappService->send($contactInfo, $content),
                default => ['success' => false, 'message' => 'Unknown channel'],
            };

            if ($result['success']) {
                $log->markAsSent($result['message_id'] ?? null);
            } else {
                $log->markAsFailed($result['message'] ?? 'Unknown error');
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Notification send failed", [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            $log->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Determine which channels to use
     */
    protected function determineChannels(
        NotificationTemplate $template,
        NotificationPreference $preferences,
        array $options
    ): array {
        $channels = [];

        // Priority order: push (instant), SMS (fast), email (detailed), WhatsApp
        if ($preferences->isChannelEnabled('push') && $template->hasChannel('push')) {
            $channels[] = 'push';
        }

        if ($preferences->isChannelEnabled('sms') && $template->hasChannel('sms')) {
            // Check rate limits
            if ($this->canSendSms($preferences)) {
                $channels[] = 'sms';
            }
        }

        if ($preferences->isChannelEnabled('email') && $template->hasChannel('email')) {
            $channels[] = 'email';
        }

        if ($preferences->isChannelEnabled('whatsapp') && $template->hasChannel('whatsapp')) {
            $channels[] = 'whatsapp';
        }

        // If no channels available, default to email
        if (empty($channels) && $template->hasChannel('email')) {
            $channels[] = 'email';
        }

        return $channels;
    }

    /**
     * Get recipient contact info for channel
     */
    protected function getRecipientContact($recipient, string $channel): ?string
    {
        return match ($channel) {
            'email' => $recipient->email ?? null,
            'sms', 'whatsapp' => $recipient->phone ?? $recipient->mobile ?? null,
            'push' => $this->getActivePushToken($recipient),
            default => null,
        };
    }

    /**
     * Get active push token for recipient
     */
    protected function getActivePushToken($recipient): ?string
    {
        $token = $recipient->deviceTokens()
            ->active()
            ->orderByDesc('last_used_at')
            ->first();

        return $token?->token;
    }

    /**
     * Check SMS rate limit
     */
    protected function canSendSms(NotificationPreference $preferences): bool
    {
        $sentToday = NotificationLog::where('notifiable_type', $preferences->notifiable_type)
            ->where('notifiable_id', $preferences->notifiable_id)
            ->where('channel', 'sms')
            ->where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();

        return $sentToday < $preferences->max_sms_per_day;
    }

    /**
     * Queue notification for later (quiet hours)
     */
    protected function queueForLater(
        string $templateCode,
        $recipient,
        array $variables,
        array $options
    ): array {
        // In a real implementation, this would dispatch a job to be processed later
        Log::info("Notification queued for after quiet hours", [
            'template' => $templateCode,
            'recipient_id' => $recipient->id,
        ]);

        return [
            'success' => true,
            'queued' => true,
            'message' => 'Notification queued for delivery after quiet hours',
        ];
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotification(
        string $templateCode,
        array $recipients,
        array $commonVariables = [],
        array $options = []
    ): array {
        $results = [
            'total' => count($recipients),
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($recipients as $recipient) {
            // Allow per-recipient variable overrides
            $variables = array_merge(
                $commonVariables,
                $recipient['variables'] ?? []
            );

            $result = $this->sendNotification(
                $templateCode,
                $recipient['entity'] ?? $recipient,
                $variables,
                $options
            );

            if ($result['success']) {
                if ($result['skipped'] ?? false) {
                    $results['skipped']++;
                } else {
                    $results['sent']++;
                }
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = NotificationLog::query();

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        return [
            'total' => (clone $query)->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'delivered' => (clone $query)->where('status', 'delivered')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'by_channel' => [
                'email' => (clone $query)->where('channel', 'email')->count(),
                'sms' => (clone $query)->where('channel', 'sms')->count(),
                'push' => (clone $query)->where('channel', 'push')->count(),
                'whatsapp' => (clone $query)->where('channel', 'whatsapp')->count(),
            ],
        ];
    }
}
