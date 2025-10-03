<?php

namespace App\Services;

use App\Models\User;
use App\Models\Backend\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class OperationsNotificationService
{
    /**
     * Notify about exception creation
     */
    public function notifyException($shipment, array $exceptionData): void
    {
        try {
            $notification = [
                'type' => 'exception.created',
                'title' => 'New Exception Created',
                'message' => "Exception: {$exceptionData['exception_type']} for shipment {$exceptionData['tracking_number']}",
                'severity' => $exceptionData['severity'],
                'priority' => $exceptionData['priority'],
                'data' => $exceptionData,
                'timestamp' => now()->toISOString(),
                'requires_action' => true,
            ];

            // Broadcast to exception tower channel
            broadcast()->on('operations.exceptions', $notification);

            // Broadcast to branch-specific channel
            $branchId = $shipment->origin_branch_id ?? $shipment->dest_branch_id;
            if ($branchId) {
                broadcast()->on("operations.exceptions.branch.{$branchId}", $notification);
            }

            // Send push notification to relevant users
            $recipients = $this->getExceptionNotificationRecipients($shipment, $exceptionData);
            $this->sendPushNotification($notification, $recipients);

            Log::info('Exception notification sent', [
                'shipment_id' => $shipment->id,
                'exception_type' => $exceptionData['exception_type'],
                'recipients_count' => count($recipients),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send exception notification', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send operational alert
     */
    public function notifyAlert(string $alertType, array $alertData, array $recipients = []): void
    {
        try {
            $notification = array_merge([
                'type' => $alertType,
                'timestamp' => now()->toISOString(),
                'requires_action' => $this->alertRequiresAction($alertType),
            ], $alertData);

            // Broadcast to alerts channel
            broadcast()->on('operations.alerts', $notification);

            // Send to specific recipients if provided
            if (!empty($recipients)) {
                foreach ($recipients as $userId) {
                    broadcast()->on("operations.alerts.user.{$userId}", $notification);
                }
            }

            // Send push notifications for critical alerts
            if ($this->isCriticalAlert($alertType)) {
                $this->sendPushNotification($notification, $recipients);
            }

            Log::info('Operational alert sent', [
                'alert_type' => $alertType,
                'recipients_count' => count($recipients),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send operational alert', [
                'alert_type' => $alertType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast operational update
     */
    public function broadcastOperationalUpdate(string $event, array $data): void
    {
        try {
            $update = array_merge([
                'event' => $event,
                'timestamp' => now()->toISOString(),
            ], $data);

            // Broadcast to dashboard updates channel
            broadcast()->on('operations.dashboard', $update);

            // Broadcast to branch-specific dashboard if branch_id is provided
            if (isset($data['branch_id'])) {
                broadcast()->on("operations.dashboard.branch.{$data['branch_id']}", $update);
            }

        } catch (\Exception $e) {
            Log::error('Failed to broadcast operational update', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications(User $user): Collection
    {
        // In a real implementation, this would query a notifications table
        // For now, return cached notifications
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, collect());

        return $notifications->where('read', false);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(User $user, string $notificationId): bool
    {
        try {
            $cacheKey = "user_notifications_{$user->id}";
            $notifications = Cache::get($cacheKey, collect());

            $notifications = $notifications->map(function ($notification) use ($notificationId) {
                if (($notification['id'] ?? null) === $notificationId) {
                    $notification['read'] = true;
                    $notification['read_at'] = now()->toISOString();
                }
                return $notification;
            });

            Cache::put($cacheKey, $notifications, now()->addHours(24)); // Cache for 24 hours

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'user_id' => $user->id,
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get notification history for user
     */
    public function getNotificationHistory(User $user, int $days = 7): Collection
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, collect());

        $cutoffDate = now()->subDays($days);

        return $notifications->filter(function ($notification) use ($cutoffDate) {
            $notificationDate = isset($notification['timestamp']) ?
                Carbon::parse($notification['timestamp']) : now();
            return $notificationDate >= $cutoffDate;
        })->sortByDesc('timestamp');
    }

    /**
     * Store notification for user (for persistence)
     */
    public function storeNotificationForUser(User $user, array $notification): void
    {
        $cacheKey = "user_notifications_{$user->id}";
        $notifications = Cache::get($cacheKey, collect());

        // Add ID if not present
        if (!isset($notification['id'])) {
            $notification['id'] = uniqid('notif_', true);
        }

        // Add read status
        $notification['read'] = $notification['read'] ?? false;
        $notification['created_at'] = $notification['created_at'] ?? now()->toISOString();

        // Keep only last 100 notifications per user
        $notifications->push($notification);
        if ($notifications->count() > 100) {
            $notifications = $notifications->slice(-100);
        }

        Cache::put($cacheKey, $notifications, now()->addHours(24));
    }

    /**
     * Get user notification preferences
     */
    public function getUserNotificationPreferences(User $user): array
    {
        $cacheKey = "user_notification_preferences_{$user->id}";

        return Cache::get($cacheKey, [
            'exceptions' => [
                'enabled' => true,
                'channels' => ['websocket', 'push'],
                'severity_filter' => ['high', 'medium'], // Only high and medium severity
            ],
            'alerts' => [
                'enabled' => true,
                'channels' => ['websocket', 'push'],
                'types' => ['capacity_warning', 'sla_breach_risk', 'asset_maintenance'],
            ],
            'dashboard_updates' => [
                'enabled' => true,
                'channels' => ['websocket'],
            ],
            'quiet_hours' => [
                'enabled' => false,
                'start' => '22:00',
                'end' => '08:00',
            ],
        ]);
    }

    /**
     * Update user notification preferences
     */
    public function updateUserNotificationPreferences(User $user, array $preferences): bool
    {
        try {
            $cacheKey = "user_notification_preferences_{$user->id}";

            // Validate preferences structure
            $validatedPreferences = $this->validateNotificationPreferences($preferences);

            Cache::put($cacheKey, $validatedPreferences, now()->addDays(30)); // Cache for 30 days

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update notification preferences', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send push notification
     */
    private function sendPushNotification(array $notification, array $recipients): void
    {
        if (empty($recipients)) {
            return;
        }

        try {
            // In a real implementation, this would integrate with FCM/APNs
            // For now, we'll simulate by storing notifications for users

            foreach ($recipients as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $this->storeNotificationForUser($user, $notification);
                }
            }

            Log::info('Push notification sent', [
                'notification_type' => $notification['type'],
                'recipients_count' => count($recipients),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'error' => $e->getMessage(),
                'notification_type' => $notification['type'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Get recipients for exception notifications
     */
    private function getExceptionNotificationRecipients($shipment, array $exceptionData): array
    {
        $recipients = [];

        // Branch managers and supervisors
        $branchIds = array_filter([$shipment->origin_branch_id, $shipment->dest_branch_id]);

        foreach ($branchIds as $branchId) {
            $branch = Branch::find($branchId);
            if ($branch) {
                // Add branch managers
                $managers = $branch->branchManager()->with('user')->get();
                foreach ($managers as $manager) {
                    $recipients[] = $manager->user->id;
                }

                // Add active supervisors from the branch
                $supervisors = $branch->activeWorkers()
                    ->where('role', 'supervisor')
                    ->with('user')
                    ->get();

                foreach ($supervisors as $supervisor) {
                    $recipients[] = $supervisor->user->id;
                }
            }
        }

        // Add operations managers (users with operations_manager role)
        $operationsManagers = User::whereHas('roles', function ($query) {
            $query->where('name', 'operations_manager');
        })->pluck('id');

        $recipients = array_merge($recipients, $operationsManagers->toArray());

        // For high-priority exceptions, also notify senior management
        if (($exceptionData['priority'] ?? 1) >= 4) {
            $seniorManagers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['senior_manager', 'director', 'vp_operations']);
            })->pluck('id');

            $recipients = array_merge($recipients, $seniorManagers->toArray());
        }

        return array_unique($recipients);
    }

    /**
     * Check if alert requires action
     */
    private function alertRequiresAction(string $alertType): bool
    {
        $actionRequiredAlerts = [
            'exception.created',
            'alert.capacity_warning',
            'alert.sla_breach_risk',
            'alert.worker_overloaded',
            'alert.asset_maintenance',
            'alert.stuck_shipments',
        ];

        return in_array($alertType, $actionRequiredAlerts);
    }

    /**
     * Check if alert is critical
     */
    private function isCriticalAlert(string $alertType): bool
    {
        $criticalAlerts = [
            'exception.created', // High-priority exceptions
            'alert.sla_breach_risk',
            'alert.capacity_warning', // When capacity drops below critical threshold
        ];

        return in_array($alertType, $criticalAlerts);
    }

    /**
     * Validate notification preferences
     */
    private function validateNotificationPreferences(array $preferences): array
    {
        $validated = [];

        // Validate exceptions preferences
        if (isset($preferences['exceptions'])) {
            $validated['exceptions'] = [
                'enabled' => $preferences['exceptions']['enabled'] ?? true,
                'channels' => array_intersect(
                    $preferences['exceptions']['channels'] ?? ['websocket'],
                    ['websocket', 'push', 'email', 'sms']
                ),
                'severity_filter' => array_intersect(
                    $preferences['exceptions']['severity_filter'] ?? ['high', 'medium', 'low'],
                    ['high', 'medium', 'low']
                ),
            ];
        }

        // Validate alerts preferences
        if (isset($preferences['alerts'])) {
            $validated['alerts'] = [
                'enabled' => $preferences['alerts']['enabled'] ?? true,
                'channels' => array_intersect(
                    $preferences['alerts']['channels'] ?? ['websocket'],
                    ['websocket', 'push', 'email', 'sms']
                ),
                'types' => $preferences['alerts']['types'] ?? [
                    'capacity_warning',
                    'sla_breach_risk',
                    'asset_maintenance',
                    'worker_overload'
                ],
            ];
        }

        // Validate dashboard updates preferences
        if (isset($preferences['dashboard_updates'])) {
            $validated['dashboard_updates'] = [
                'enabled' => $preferences['dashboard_updates']['enabled'] ?? true,
                'channels' => array_intersect(
                    $preferences['dashboard_updates']['channels'] ?? ['websocket'],
                    ['websocket']
                ),
            ];
        }

        // Validate quiet hours
        if (isset($preferences['quiet_hours'])) {
            $validated['quiet_hours'] = [
                'enabled' => $preferences['quiet_hours']['enabled'] ?? false,
                'start' => $preferences['quiet_hours']['start'] ?? '22:00',
                'end' => $preferences['quiet_hours']['end'] ?? '08:00',
            ];
        }

        return $validated;
    }

    /**
     * Check if user should receive notification based on preferences and quiet hours
     */
    public function shouldSendNotification(User $user, array $notification): bool
    {
        $preferences = $this->getUserNotificationPreferences($user);

        // Check if notification type is enabled
        $notificationType = $notification['type'] ?? '';
        if (str_starts_with($notificationType, 'exception.')) {
            if (!$preferences['exceptions']['enabled']) {
                return false;
            }

            // Check severity filter
            $severity = $notification['severity'] ?? 'low';
            if (!in_array($severity, $preferences['exceptions']['severity_filter'])) {
                return false;
            }
        } elseif (str_starts_with($notificationType, 'alert.')) {
            if (!$preferences['alerts']['enabled']) {
                return false;
            }

            // Check alert type filter
            $alertType = str_replace('alert.', '', $notificationType);
            if (!in_array($alertType, $preferences['alerts']['types'])) {
                return false;
            }
        }

        // Check quiet hours
        if ($preferences['quiet_hours']['enabled']) {
            $now = now();
            $start = Carbon::createFromTimeString($preferences['quiet_hours']['start']);
            $end = Carbon::createFromTimeString($preferences['quiet_hours']['end']);

            // Handle overnight quiet hours
            if ($start > $end) {
                if ($now >= $start || $now <= $end) {
                    return false;
                }
            } else {
                if ($now >= $start && $now <= $end) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Clean up old notifications (housekeeping)
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $cleanedCount = 0;

        // In a real implementation, this would clean up database records
        // For now, we'll clean up cache entries that are older than the cutoff

        // This is a simplified cleanup - in production you'd want more sophisticated cleanup
        Log::info('Notification cleanup completed', [
            'days_old' => $daysOld,
            'cleaned_count' => $cleanedCount,
        ]);

        return $cleanedCount;
    }
}