<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PushNotificationService
 * 
 * Handles push notifications via Firebase Cloud Messaging (FCM) with:
 * - Single device notifications
 * - Topic-based broadcasting
 * - Rich notifications (images, actions)
 * - Delivery tracking
 */
class PushNotificationService
{
    protected ?string $serverKey;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * Send push notification to a device
     */
    public function send(string $deviceToken, array $content): array
    {
        if (!$this->isConfigured()) {
            Log::warning('Firebase not configured, push not sent');
            return [
                'success' => false,
                'message' => 'Push service not configured',
            ];
        }

        try {
            $payload = $this->buildPayload($deviceToken, $content);

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                if (($data['success'] ?? 0) > 0) {
                    Log::info('Push notification sent', [
                        'token' => substr($deviceToken, 0, 20) . '...',
                        'message_id' => $data['results'][0]['message_id'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'message_id' => $data['results'][0]['message_id'] ?? null,
                    ];
                }

                // Handle token errors
                $error = $data['results'][0]['error'] ?? 'Unknown error';
                
                if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
                    // Token is invalid - should be removed
                    $this->handleInvalidToken($deviceToken);
                }

                return [
                    'success' => false,
                    'message' => $error,
                ];
            }

            return [
                'success' => false,
                'message' => 'FCM request failed',
            ];

        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send to multiple devices
     */
    public function sendToMultiple(array $deviceTokens, array $content): array
    {
        if (empty($deviceTokens)) {
            return ['success' => false, 'message' => 'No device tokens'];
        }

        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Push service not configured'];
        }

        try {
            $payload = [
                'registration_ids' => array_values($deviceTokens),
                'notification' => [
                    'title' => $content['title'] ?? 'Notification',
                    'body' => $content['body'] ?? '',
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => $content['data'] ?? [],
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'total' => count($deviceTokens),
                    'sent' => $data['success'] ?? 0,
                    'failed' => $data['failure'] ?? 0,
                ];
            }

            return ['success' => false, 'message' => 'FCM request failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send to a topic
     */
    public function sendToTopic(string $topic, array $content): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Push service not configured'];
        }

        try {
            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $content['title'] ?? 'Notification',
                    'body' => $content['body'] ?? '',
                    'sound' => 'default',
                ],
                'data' => $content['data'] ?? [],
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json()['message_id'] ?? null,
                ];
            }

            return ['success' => false, 'message' => 'FCM request failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Build FCM payload
     */
    protected function buildPayload(string $deviceToken, array $content): array
    {
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $content['title'] ?? 'Notification',
                'body' => $content['body'] ?? '',
                'sound' => 'default',
                'badge' => 1,
            ],
            'priority' => 'high',
        ];

        // Add data payload for custom handling
        if (!empty($content['data'])) {
            $payload['data'] = $content['data'];
        }

        // Add image if provided
        if (!empty($content['image'])) {
            $payload['notification']['image'] = $content['image'];
        }

        // Add click action
        if (!empty($content['click_action'])) {
            $payload['notification']['click_action'] = $content['click_action'];
        }

        // Android specific options
        $payload['android'] = [
            'priority' => 'high',
            'notification' => [
                'channel_id' => $content['channel_id'] ?? 'default',
            ],
        ];

        // iOS specific options
        $payload['apns'] = [
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                ],
            ],
        ];

        return $payload;
    }

    /**
     * Check if Firebase is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->serverKey);
    }

    /**
     * Handle invalid token (remove from database)
     */
    protected function handleInvalidToken(string $token): void
    {
        \App\Models\DeviceToken::where('token', $token)->update(['is_active' => false]);
        
        Log::info('Deactivated invalid device token', [
            'token' => substr($token, 0, 20) . '...',
        ]);
    }

    /**
     * Subscribe device to topic
     */
    public function subscribeToTopic(string $deviceToken, string $topic): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
            ])->post("https://iid.googleapis.com/iid/v1/{$deviceToken}/rel/topics/{$topic}");

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Topic subscription failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
