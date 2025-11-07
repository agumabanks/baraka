<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PromotionalCampaign;
use App\Models\CustomerMilestone;
use App\Mail\MilestoneCelebrationMail;
use App\Mail\PromotionExpiryMail;
use App\Mail\PromotionActivationMail;
use App\Mail\PromotionRoiAlertMail;
use App\Notifications\PromotionNotification;
use App\Notifications\MilestoneNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Promotion Notification Service
 * 
 * Comprehensive notification system for promotion events including:
 * - Milestone celebrations and achievements
 * - Promotion activation and expiry alerts
 * - ROI threshold breach notifications
 * - Customer-specific promotion targeting
 * - Multi-channel communication (email, SMS, push, in-app)
 */
class NotificationService
{
    // Notification configuration
    private const NOTIFICATION_CHANNELS = ['mail', 'database', 'broadcast'];
    private const SMS_ENABLED = true;
    private const PUSH_ENABLED = true;
    
    // Rate limiting to prevent spam
    private const NOTIFICATION_COOLDOWN_HOURS = 1;
    private const MILESTONE_NOTIFICATION_COOLDOWN_HOURS = 24;
    
    // ROI alert thresholds
    private const ROI_ALERT_THRESHOLDS = [
        'low_performance' => 50,    // Below 50% ROI
        'high_performance' => 200,  // Above 200% ROI
        'budget_exceeded' => 150    // Above 150% of expected cost
    ];

    public function __construct() {}

    /**
     * Send milestone celebration notification
     */
    public function sendMilestoneCelebration(Customer $customer, CustomerMilestone $milestone, array $celebrationData): void
    {
        $notificationData = $this->buildMilestoneNotificationData($customer, $milestone, $celebrationData);
        
        // Check rate limiting
        if (!$this->canSendMilestoneNotification($customer->id, $milestone->milestone_type)) {
            Log::info('Milestone notification rate limited', [
                'customer_id' => $customer->id,
                'milestone_type' => $milestone->milestone_type
            ]);
            return;
        }

        try {
            // Send email notification
            if ($this->shouldSendEmail($customer->id)) {
                Mail::to($customer->email)->send(new MilestoneCelebrationMail($notificationData));
            }

            // Send database notification
            $this->sendDatabaseNotification($customer->id, $notificationData, 'milestone_celebration');

            // Send push notification
            if (self::PUSH_ENABLED && $this->shouldSendPush($customer->id)) {
                $this->sendPushNotification($customer->id, $notificationData);
            }

            // Send SMS notification for major milestones
            if (self::SMS_ENABLED && $this->isMajorMilestone($milestone->milestone_value) && $this->shouldSendSms($customer->id)) {
                $this->sendSmsNotification($customer->phone, $this->buildSmsMessage($notificationData));
            }

            // Log successful notification
            $this->logNotificationSent('milestone_celebration', $customer->id, $milestone->id, [
                'channels' => $this->getUsedChannels($customer->id),
                'milestone_type' => $milestone->milestone_type,
                'milestone_value' => $milestone->milestone_value
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send milestone celebration notification', [
                'customer_id' => $customer->id,
                'milestone_id' => $milestone->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send milestone email notification
     */
    public function sendMilestoneEmail(string $email, array $data): void
    {
        try {
            Mail::to($email)->send(new MilestoneCelebrationMail($data));
            Log::info('Milestone email sent', ['email' => $email]);
        } catch (\Exception $e) {
            Log::error('Failed to send milestone email', ['email' => $email, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send milestone SMS notification
     */
    public function sendMilestoneSms(string $phone, array $data): void
    {
        if (!self::SMS_ENABLED) {
            return;
        }

        try {
            $message = $this->buildSmsMessage($data);
            $this->sendSms($phone, $message);
            Log::info('Milestone SMS sent', ['phone' => $phone]);
        } catch (\Exception $e) {
            Log::error('Failed to send milestone SMS', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send milestone push notification
     */
    public function sendMilestonePush(int $customerId, array $data): void
    {
        if (!self::PUSH_ENABLED) {
            return;
        }

        try {
            $notification = new MilestoneNotification($data);
            Notification::send(
                \App\Models\User::where('customer_id', $customerId)->get(),
                $notification
            );
            Log::info('Milestone push notification sent', ['customer_id' => $customerId]);
        } catch (\Exception $e) {
            Log::error('Failed to send milestone push notification', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send promotion expiry notifications
     */
    public function sendPromotionExpiryNotifications(PromotionalCampaign $promotion, array $expiryData): void
    {
        // Get customers who have used this promotion recently
        $recentUsers = $this->getRecentPromotionUsers($promotion->id, 30);
        
        $notificationData = $this->buildPromotionExpiryData($promotion, $expiryData);

        foreach ($recentUsers as $customer) {
            try {
                // Send email notification
                if ($this->shouldSendEmail($customer->id)) {
                    Mail::to($customer->email)->send(new PromotionExpiryMail($notificationData));
                }

                // Send in-app notification
                $this->sendDatabaseNotification($customer->id, $notificationData, 'promotion_expiry');

                Log::info('Promotion expiry notification sent', [
                    'customer_id' => $customer->id,
                    'promotion_id' => $promotion->id
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send promotion expiry notification', [
                    'customer_id' => $customer->id,
                    'promotion_id' => $promotion->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send promotion activation notifications
     */
    public function sendPromotionActivationNotifications(
        PromotionalCampaign $promotion,
        array $targetCustomers = [],
        array $customMessage = []
    ): void {
        $notificationData = $this->buildPromotionActivationData($promotion, $customMessage);
        
        $customers = $this->getEligibleCustomersForPromotion($promotion, $targetCustomers);

        foreach ($customers as $customer) {
            try {
                // Only send to customers who haven't been notified recently
                if ($this->canSendPromotionNotification($customer->id, $promotion->id)) {
                    // Send email notification
                    if ($this->shouldSendEmail($customer->id)) {
                        Mail::to($customer->email)->send(new PromotionActivationMail($notificationData));
                    }

                    // Send in-app notification
                    $this->sendDatabaseNotification($customer->id, $notificationData, 'promotion_activation');

                    // Log the notification
                    $this->logNotificationSent('promotion_activation', $customer->id, $promotion->id);

                }
            } catch (\Exception $e) {
                Log::error('Failed to send promotion activation notification', [
                    'customer_id' => $customer->id,
                    'promotion_id' => $promotion->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send ROI threshold breach alerts
     */
    public function sendRoiAlert(
        PromotionalCampaign $promotion,
        string $alertType,
        array $roiData,
        array $recipients = []
    ): void {
        $alertData = $this->buildRoiAlertData($promotion, $alertType, $roiData);
        
        // Default recipients: admins and marketing team
        $alertRecipients = !empty($recipients) ? $recipients : $this->getDefaultRoiAlertRecipients();
        
        foreach ($alertRecipients as $recipient) {
            try {
                Mail::to($recipient['email'])->send(new PromotionRoiAlertMail($alertData));
                
                Log::info('ROI alert sent', [
                    'promotion_id' => $promotion->id,
                    'alert_type' => $alertType,
                    'recipient' => $recipient['email']
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to send ROI alert', [
                    'promotion_id' => $promotion->id,
                    'alert_type' => $alertType,
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send customer-specific promotion alerts
     */
    public function sendCustomerPromotionAlert(
        int $customerId,
        PromotionalCampaign $promotion,
        string $alertType,
        array $customData = []
    ): void {
        $customer = Customer::findOrFail($customerId);
        
        $alertData = $this->buildCustomerPromotionAlertData($customer, $promotion, $alertType, $customData);
        
        try {
            // Send email alert
            if ($this->shouldSendEmail($customerId)) {
                $this->sendCustomerEmailAlert($customer, $alertData);
            }

            // Send in-app notification
            $this->sendDatabaseNotification($customerId, $alertData, 'customer_promotion_alert');

            // Send push notification for urgent alerts
            if ($alertType === 'urgent' && $this->shouldSendPush($customerId)) {
                $this->sendPushNotification($customerId, $alertData);
            }

            Log::info('Customer promotion alert sent', [
                'customer_id' => $customerId,
                'promotion_id' => $promotion->id,
                'alert_type' => $alertType
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send customer promotion alert', [
                'customer_id' => $customerId,
                'promotion_id' => $promotion->id,
                'alert_type' => $alertType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send bulk promotion notifications
     */
    public function sendBulkPromotionNotifications(
        array $promotionIds,
        string $notificationType,
        array $targetCriteria = [],
        array $customMessage = []
    ): array {
        $results = [];
        $startTime = microtime(true);

        foreach ($promotionIds as $promotionId) {
            try {
                $promotion = PromotionalCampaign::findOrFail($promotionId);
                
                $result = match($notificationType) {
                    'activation' => $this->sendPromotionActivationNotifications($promotion, [], $customMessage),
                    'expiry' => $this->sendPromotionExpiryNotifications($promotion, $customMessage),
                    default => null
                };

                $results[] = [
                    'promotion_id' => $promotionId,
                    'success' => true,
                    'result' => $result
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'promotion_id' => $promotionId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'notification_type' => $notificationType,
            'total_promotions' => count($promotionIds),
            'successful_sends' => collect($results)->where('success', true)->count(),
            'failed_sends' => collect($results)->where('success', false)->count(),
            'processing_time_ms' => $processingTime,
            'results' => $results,
            'sent_at' => now()->toISOString()
        ];
    }

    /**
     * Get notification preferences for a customer
     */
    public function getCustomerNotificationPreferences(int $customerId): array
    {
        $preferences = \DB::table('customer_promotion_preferences')
            ->where('customer_id', $customerId)
            ->first();

        return [
            'email_notifications' => $preferences->email_notifications ?? true,
            'sms_notifications' => $preferences->sms_notifications ?? false,
            'push_notifications' => $preferences->push_notifications ?? true,
            'preferred_campaign_types' => $preferences->preferred_campaign_types ?? [],
            'notification_frequency' => 'immediate', // immediate, daily, weekly
            'quiet_hours' => [
                'enabled' => false,
                'start' => '22:00',
                'end' => '08:00'
            ]
        ];
    }

    /**
     * Update customer notification preferences
     */
    public function updateNotificationPreferences(int $customerId, array $preferences): bool
    {
        try {
            $updateData = [
                'email_notifications' => $preferences['email_notifications'] ?? true,
                'sms_notifications' => $preferences['sms_notifications'] ?? false,
                'push_notifications' => $preferences['push_notifications'] ?? true,
                'preferred_campaign_types' => $preferences['preferred_campaign_types'] ?? [],
                'last_updated' => now()
            ];

            \DB::table('customer_promotion_preferences')
                ->updateOrInsert(['customer_id' => $customerId], $updateData);

            Log::info('Customer notification preferences updated', [
                'customer_id' => $customerId,
                'preferences' => $preferences
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update notification preferences', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Check if notification can be sent (rate limiting)
     */
    private function canSendNotification(int $customerId, string $notificationType, int $cooldownHours = 1): bool
    {
        $cacheKey = "notification_cooldown_{$customerId}_{$notificationType}";
        $lastSent = \Cache::get($cacheKey);
        
        if ($lastSent) {
            $hoursSinceLastSend = now()->diffInHours($lastSent);
            if ($hoursSinceLastSend < $cooldownHours) {
                return false;
            }
        }
        
        \Cache::put($cacheKey, now(), now()->addHours($cooldownHours));
        return true;
    }

    private function canSendMilestoneNotification(int $customerId, string $milestoneType): bool
    {
        return $this->canSendNotification($customerId, "milestone_{$milestoneType}", self::MILESTONE_NOTIFICATION_COOLDOWN_HOURS);
    }

    private function canSendPromotionNotification(int $customerId, int $promotionId): bool
    {
        return $this->canSendNotification($customerId, "promotion_{$promotionId}", self::NOTIFICATION_COOLDOWN_HOURS);
    }

    /**
     * Notification preference checkers
     */
    private function shouldSendEmail(int $customerId): bool
    {
        $preferences = $this->getCustomerNotificationPreferences($customerId);
        return $preferences['email_notifications'] && !$this->isInQuietHours();
    }

    private function shouldSendSms(int $customerId): bool
    {
        $preferences = $this->getCustomerNotificationPreferences($customerId);
        return $preferences['sms_notifications'];
    }

    private function shouldSendPush(int $customerId): bool
    {
        $preferences = $this->getCustomerNotificationPreferences($customerId);
        return $preferences['push_notifications'];
    }

    private function isInQuietHours(): bool
    {
        $currentTime = now()->format('H:i');
        $quietHours = $this->getSystemNotificationSettings()['quiet_hours'] ?? [];
        
        if (!$quietHours['enabled'] ?? false) {
            return false;
        }
        
        return $currentTime >= $quietHours['start'] && $currentTime <= $quietHours['end'];
    }

    // Private helper methods

    private function buildMilestoneNotificationData(Customer $customer, CustomerMilestone $milestone, array $celebrationData): array
    {
        return [
            'customer' => $customer,
            'milestone' => $milestone,
            'celebration_message' => $celebrationData['celebration_message'] ?? $this->generateCelebrationMessage($milestone),
            'reward_details' => $milestone->reward_details ?? [],
            'next_milestone' => $this->getNextMilestone($customer, $milestone->milestone_type),
            'social_sharing' => [
                'enabled' => true,
                'message' => $this->generateSocialShareMessage($milestone),
                'hashtags' => ['#milestone', '#logistics', '#achievement']
            ],
            'personalized_content' => $this->generatePersonalizedContent($customer, $milestone)
        ];
    }

    private function buildPromotionExpiryData(PromotionalCampaign $promotion, array $expiryData): array
    {
        return [
            'promotion' => $promotion,
            'expiry_date' => $expiryData['expiry_date'] ?? $promotion->effective_to,
            'time_remaining' => $expiryData['time_remaining'] ?? '7 days',
            'last_chance_message' => $expiryData['last_chance_message'] ?? 'Don\'t miss out on this limited-time offer!',
            'alternative_promotions' => $this->getAlternativePromotions($promotion),
            'action_required' => $expiryData['action_required'] ?? 'Use before expiry to enjoy the benefits'
        ];
    }

    private function buildPromotionActivationData(PromotionalCampaign $promotion, array $customMessage): array
    {
        return [
            'promotion' => $promotion,
            'activation_message' => $customMessage['message'] ?? 'New promotion available!',
            'urgency_element' => $customMessage['urgency'] ?? 'Limited time offer',
            'key_benefits' => $customMessage['benefits'] ?? $this->extractKeyBenefits($promotion),
            'eligibility_check' => $customMessage['eligibility_check'] ?? 'You are eligible for this promotion!',
            'call_to_action' => $customMessage['cta'] ?? 'Claim your offer now'
        ];
    }

    private function buildRoiAlertData(PromotionalCampaign $promotion, string $alertType, array $roiData): array
    {
        return [
            'promotion' => $promotion,
            'alert_type' => $alertType,
            'roi_metrics' => $roiData,
            'alert_message' => $this->generateRoiAlertMessage($alertType, $roiData),
            'recommendations' => $this->generateRoiRecommendations($alertType, $roiData),
            'urgency_level' => $this->getAlertUrgencyLevel($alertType)
        ];
    }

    private function buildCustomerPromotionAlertData(Customer $customer, PromotionalCampaign $promotion, string $alertType, array $customData): array
    {
        return [
            'customer' => $customer,
            'promotion' => $promotion,
            'alert_type' => $alertType,
            'personalized_message' => $customData['message'] ?? $this->generatePersonalizedAlertMessage($customer, $promotion, $alertType),
            'customer_specific_benefits' => $customData['benefits'] ?? $this->getCustomerSpecificBenefits($customer, $promotion),
            'next_steps' => $customData['next_steps'] ?? $this->getRecommendedNextSteps($customer, $promotion)
        ];
    }

    private function sendDatabaseNotification(int $customerId, array $data, string $type): void
    {
        $notification = new PromotionNotification($data);
        \App\Models\User::where('customer_id', $customerId)->first()?->notify($notification);
    }

    private function sendPushNotification(int $customerId, array $data): void
    {
        // Implementation would depend on your push notification service
        // This is a placeholder for Firebase, Pusher, etc.
        Log::info('Push notification would be sent', [
            'customer_id' => $customerId,
            'data' => $data
        ]);
    }

    private function sendSms(string $phone, string $message): void
    {
        // Implementation would use your SMS service (Twilio, AWS SNS, etc.)
        Log::info('SMS would be sent', ['phone' => $phone, 'message' => $message]);
    }

    private function sendSmsNotification(string $phone, array $data): void
    {
        $message = $data['celebration_message'] ?? 'Congratulations on your milestone!';
        $this->sendSms($phone, $message);
    }

    private function sendCustomerEmailAlert(Customer $customer, array $data): void
    {
        // Create a custom email for customer alerts
        // This would typically be a separate Mailable class
        Mail::to($customer->email)->send(
            new \App\Mail\GenericPromotionMail($data)
        );
    }

    private function generateCelebrationMessage(CustomerMilestone $milestone): string
    {
        return match($milestone->milestone_type) {
            'shipment_count' => "🎉 Incredible! You've shipped {$milestone->milestone_value} packages with us!",
            'volume' => "🏆 Amazing! You've reached {$milestone->milestone_value}kg in total shipping volume!",
            'revenue' => "💰 Fantastic! You've spent $" . number_format($milestone->milestone_value) . " with our services!",
            'tenure' => "🌟 Thank you! You've been with us for {$milestone->milestone_value} months!",
            default => "🎊 Congratulations on achieving this milestone!"
        };
    }

    private function generateSocialShareMessage(CustomerMilestone $milestone): string
    {
        return "Just achieved {$milestone->milestone_value} " . $milestone->milestone_type . " milestone with @LogisticsCompany! #milestone #achievement";
    }

    private function generatePersonalizedContent(Customer $customer, CustomerMilestone $milestone): array
    {
        return [
            'greeting' => "Dear {$customer->name},",
            'personal_note' => "Your dedication to shipping with us is truly appreciated.",
            'encouragement' => "Keep up the great work! Your next milestone is just around the corner."
        ];
    }

    private function isMajorMilestone(int $milestoneValue): bool
    {
        return in_array($milestoneValue, [100, 500, 1000, 5000]);
    }

    private function getUsedChannels(int $customerId): array
    {
        $channels = [];
        
        if ($this->shouldSendEmail($customerId)) $channels[] = 'email';
        if ($this->shouldSendSms($customerId)) $channels[] = 'sms';
        if ($this->shouldSendPush($customerId)) $channels[] = 'push';
        
        return $channels;
    }

    private function getRecentPromotionUsers(int $promotionId, int $days): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\CustomerPromotionUsage::where('promotional_campaign_id', $promotionId)
            ->where('used_at', '>=', now()->subDays($days))
            ->with('customer')
            ->get()
            ->pluck('customer')
            ->unique();
    }

    private function getEligibleCustomersForPromotion(PromotionalCampaign $promotion, array $targetCustomers): \Illuminate\Database\Eloquent\Collection
    {
        $query = Customer::query();
        
        if (!empty($targetCustomers)) {
            $query->whereIn('id', $targetCustomers);
        } else {
            // Apply promotion eligibility rules
            $eligibility = $promotion->customer_eligibility ?? [];
            
            if (isset($eligibility['customer_types'])) {
                $query->whereIn('customer_type', $eligibility['customer_types']);
            }
            
            if (isset($eligibility['minimum_spend'])) {
                $query->where('total_spent', '>=', $eligibility['minimum_spend']);
            }
            
            if (isset($eligibility['minimum_shipments'])) {
                $query->where('total_shipments', '>=', $eligibility['minimum_shipments']);
            }
        }
        
        return $query->get();
    }

    private function getDefaultRoiAlertRecipients(): array
    {
        return [
            ['email' => 'admin@company.com', 'role' => 'admin'],
            ['email' => 'marketing@company.com', 'role' => 'marketing'],
            ['email' => 'operations@company.com', 'role' => 'operations']
        ];
    }

    private function generateRoiAlertMessage(string $alertType, array $roiData): string
    {
        return match($alertType) {
            'low_performance' => "Promotion ROI has dropped below acceptable levels. Immediate review recommended.",
            'high_performance' => "Excellent performance! Consider expanding this promotion strategy.",
            'budget_exceeded' => "Promotion costs have exceeded budget. Action required to control expenses.",
            default => "Promotion performance alert generated."
        };
    }

    private function generateRoiRecommendations(string $alertType, array $roiData): array
    {
        return match($alertType) {
            'low_performance' => [
                'Review promotion visibility',
                'Adjust target audience',
                'Consider alternative incentive structures'
            ],
            'high_performance' => [
                'Expand promotion to similar segments',
                'Increase marketing spend',
                'Analyze success factors for replication'
            ],
            'budget_exceeded' => [
                'Reduce discount percentages',
                'Tighten eligibility criteria',
                'Set usage limits per customer'
            ],
            default => ['Review promotion parameters']
        };
    }

    private function getAlertUrgencyLevel(string $alertType): string
    {
        return match($alertType) {
            'budget_exceeded' => 'urgent',
            'low_performance' => 'high',
            'high_performance' => 'medium',
            default => 'low'
        };
    }

    private function generatePersonalizedAlertMessage(Customer $customer, PromotionalCampaign $promotion, string $alertType): string
    {
        return "Hi {$customer->name}, special promotion alert: {$promotion->name} - Don't miss out!";
    }

    private function getCustomerSpecificBenefits(Customer $customer, PromotionalCampaign $promotion): array
    {
        // Return customer-specific benefits based on their history and preferences
        return [
            'priority_support' => true,
            'extended_warranty' => $customer->total_spent > 1000,
            'exclusive_offers' => $customer->customer_type === 'premium'
        ];
    }

    private function getRecommendedNextSteps(Customer $customer, PromotionalCampaign $promotion): array
    {
        return [
            'Review promotion details',
            'Check eligibility requirements',
            'Take action before expiry',
            'Share with colleagues if B2B'
        ];
    }

    private function extractKeyBenefits(PromotionalCampaign $promotion): array
    {
        return match($promotion->campaign_type) {
            'percentage' => ["Save {$promotion->value}% on your next shipment"],
            'fixed_amount' => ["Get {$promotion->value} off your shipping costs"],
            'free_shipping' => ['Free shipping on your next order'],
            'tier_upgrade' => ['Upgrade to premium tier benefits'],
            default => ['Exclusive promotion available']
        };
    }

    private function getAlternativePromotions(PromotionalCampaign $currentPromotion): array
    {
        return PromotionalCampaign::where('campaign_type', $currentPromotion->campaign_type)
            ->where('is_active', true)
            ->where('id', '!=', $currentPromotion->id)
            ->limit(3)
            ->get()
            ->map(fn($promo) => [
                'id' => $promo->id,
                'name' => $promo->name,
                'value' => $promo->value,
                'type' => $promo->campaign_type
            ])
            ->toArray();
    }

    private function getNextMilestone(Customer $customer, string $currentType): ?array
    {
        // Logic to determine the next milestone for the customer
        $nextThresholds = \App\Services\MilestoneTrackingService::MILESTONE_CATEGORIES[$currentType]['thresholds'] ?? [];
        $currentProgress = $this->getCurrentProgress($customer, $currentType);
        
        foreach ($nextThresholds as $threshold) {
            if ($threshold > $currentProgress) {
                return [
                    'type' => $currentType,
                    'threshold' => $threshold,
                    'remaining' => $threshold - $currentProgress
                ];
            }
        }
        
        return null;
    }

    private function getCurrentProgress(Customer $customer, string $type): float
    {
        return match($type) {
            'shipment_count' => $customer->total_shipments ?? 0,
            'volume' => $this->getCustomerVolume($customer->id),
            'revenue' => $customer->total_spent ?? 0,
            'tenure' => $customer->created_at ? $customer->created_at->diffInMonths(now()) : 0,
            default => 0
        };
    }

    private function getCustomerVolume(int $customerId): float
    {
        return \DB::table('shipments')
            ->where('customer_id', $customerId)
            ->where('status', 'delivered')
            ->sum('total_weight');
    }

    private function getSystemNotificationSettings(): array
    {
        // This would typically come from a configuration table or .env
        return [
            'quiet_hours' => [
                'enabled' => config('promotions.notification.quiet_hours.enabled', false),
                'start' => config('promotions.notification.quiet_hours.start', '22:00'),
                'end' => config('promotions.notification.quiet_hours.end', '08:00')
            ]
        ];
    }

    private function buildSmsMessage(array $data): string
    {
        return $data['celebration_message'] ?? 'Congratulations on your achievement!';
    }

    private function logNotificationSent(string $type, int $customerId, ?int $resourceId, array $context = []): void
    {
        Log::info('Notification sent', [
            'type' => $type,
            'customer_id' => $customerId,
            'resource_id' => $resourceId,
            'context' => $context,
            'sent_at' => now()->toISOString()
        ]);
    }
}