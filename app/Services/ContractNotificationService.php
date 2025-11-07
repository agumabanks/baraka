<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractNotification;
use App\Models\Customer;
use App\Events\ContractActivated;
use App\Events\ContractExpiring;
use App\Events\ContractExpired;
use App\Events\ContractRenewed;
use App\Events\ContractVolumeCommitmentReached;
use App\Events\ContractComplianceBreached;
use App\Events\ContractComplianceEscalated;
use App\Events\ContractMilestoneAchieved;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Contract Notification Service
 * 
 * Handles all contract-related notifications including:
 * - Renewal notification system
 * - Compliance alerts
 * - Milestone achievement notifications
 * - Contract lifecycle notifications
 * - Multi-channel delivery (email, SMS, in-app)
 */
class ContractNotificationService
{
    private const RENEWAL_NOTIFICATION_DAYS = [30, 15, 7, 3, 1];
    private const EXPIRY_NOTIFICATION_DAYS = [7, 3, 1];
    private const COMPLIANCE_ALERT_THRESHOLDS = [80, 60, 40]; // Performance percentage thresholds
    private const MILESTONE_NOTIFICATION_COOLDOWN_HOURS = 24;

    public function __construct(
        private WebhookManagementService $webhookService
    ) {}

    /**
     * Send contract activation notifications
     */
    public function sendContractActivationNotifications(Contract $contract): array
    {
        $customer = $contract->customer;
        $notifications = [];

        // Send to customer
        $customerNotification = $this->createAndSendNotification(
            $contract,
            $customer->email,
            'contract_activated',
            'contract_activation',
            [
                'customer_name' => $customer->company_name ?? $customer->contact_person,
                'contract_name' => $contract->name,
                'contract_type' => $contract->contract_type,
                'start_date' => $contract->start_date->toISOString(),
                'end_date' => $contract->end_date->toISOString(),
                'activation_url' => $this->getContractDashboardUrl($contract->id),
                'contract_summary' => $contract->getContractSummary()
            ],
            'email'
        );

        if ($customerNotification) {
            $notifications[] = $customerNotification;
        }

        // Send to internal team
        $internalNotification = $this->createAndSendNotification(
            $contract,
            config('mail.from.address'),
            'contract_activated_internal',
            'contract_activation_internal',
            [
                'contract_details' => $contract->getContractSummary(),
                'customer_details' => [
                    'name' => $customer->company_name ?? $customer->contact_person,
                    'email' => $customer->email,
                    'phone' => $customer->phone
                ],
                'action_required' => 'Contract monitoring and compliance setup'
            ],
            'email'
        );

        if ($internalNotification) {
            $notifications[] = $internalNotification;
        }

        // Send SMS if configured
        if ($this->shouldSendSms($contract, 'contract_activated') && $customer->phone) {
            $smsNotification = $this->sendSmsNotification(
                $customer->phone,
                "Your contract '{$contract->name}' has been activated. View details: " . $this->getContractDashboardUrl($contract->id)
            );
            if ($smsNotification) {
                $notifications[] = $smsNotification;
            }
        }

        // Send webhook notifications
        $this->sendActivationWebhooks($contract, $customer);

        Log::info('Contract activation notifications sent', [
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'notifications_sent' => count($notifications)
        ]);

        return $notifications;
    }

    /**
     * Send contract renewal notifications
     */
    public function sendContractRenewalNotifications(Contract $contract): array
    {
        $customer = $contract->customer;
        $daysUntilExpiry = $contract->getDaysUntilExpiry();
        $notifications = [];

        // Create notification record
        $notification = ContractNotification::createFromEvent(
            $contract,
            'renewal_notice',
            [
                'days_until_expiry' => $daysUntilExpiry,
                'expiry_date' => $contract->end_date->toISOString(),
                'auto_renewal' => $contract->auto_renewal_terms['auto_renewal'] ?? false,
                'renewal_terms' => $contract->auto_renewal_terms
            ]
        );

        if ($notification) {
            $notifications[] = $notification;
        }

        // Email notification
        $emailData = [
            'customer_name' => $customer->company_name ?? $customer->contact_person,
            'contract_name' => $contract->name,
            'days_until_expiry' => $daysUntilExpiry,
            'expiry_date' => $contract->end_date->toFormattedDateString(),
            'auto_renewal' => $contract->auto_renewal_terms['auto_renewal'] ?? false,
            'renewal_terms' => $contract->auto_renewal_terms,
            'contract_summary' => $contract->getContractSummary(),
            'action_required' => $this->getRenewalActionRequired($contract),
            'renewal_url' => $this->getRenewalUrl($contract->id),
            'dashboard_url' => $this->getContractDashboardUrl($contract->id)
        ];

        $this->sendEmailNotification(
            $customer->email,
            "Contract Renewal Notice - {$contract->name}",
            'emails.contract.renewal_notice',
            $emailData
        );

        // SMS for urgent renewals
        if ($daysUntilExpiry <= 7 && $this->shouldSendSms($contract, 'renewal_notice') && $customer->phone) {
            $this->sendSmsNotification(
                $customer->phone,
                "URGENT: Your contract '{$contract->name}' expires in {$daysUntilExpiry} days. Renew now: " . $this->getRenewalUrl($contract->id)
            );
        }

        // Internal team notification for expiring contracts
        if ($daysUntilExpiry <= 30) {
            $this->sendEmailNotification(
                config('mail.from.address'),
                "Contract Renewal Required - {$contract->name}",
                'emails.contract.renewal_internal',
                array_merge($emailData, [
                    'priority' => $daysUntilExpiry <= 7 ? 'high' : 'medium',
                    'action_required' => 'Follow up with customer for renewal'
                ])
            );
        }

        // Webhook notifications
        $this->webhookService->triggerEvent('contract_renewal_notification', [
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'days_until_expiry' => $daysUntilExpiry,
            'auto_renewal' => $contract->auto_renewal_terms['auto_renewal'] ?? false,
            'timestamp' => now()->toISOString()
        ]);

        return $notifications;
    }

    /**
     * Send compliance breach notifications
     */
    public function sendComplianceBreachNotifications(Contract $contract, array $breachDetails): array
    {
        $customer = $contract->customer;
        $notifications = [];

        // Create notification record
        $notification = ContractNotification::createFromEvent(
            $contract,
            'compliance_breach',
            $breachDetails
        );

        if ($notification) {
            $notifications[] = $notification;
        }

        // Critical breach - immediate notification
        if ($breachDetails['is_critical'] ?? false) {
            $this->sendCriticalComplianceAlert($contract, $breachDetails);
        } else {
            // Standard compliance notification
            $emailData = [
                'customer_name' => $customer->company_name ?? $customer->contact_person,
                'contract_name' => $contract->name,
                'compliance_issues' => $breachDetails,
                'current_performance' => $breachDetails['performance_percentage'] ?? 0,
                'target_performance' => $breachDetails['target_value'] ?? 0,
                'resolution_deadline' => $breachDetails['resolution_deadline'] ?? null,
                'required_actions' => $breachDetails['required_actions'] ?? [],
                'support_contact' => config('app.support_email'),
                'dashboard_url' => $this->getContractDashboardUrl($contract->id, 'compliance')
            ];

            $this->sendEmailNotification(
                $customer->email,
                "Compliance Alert - Action Required",
                'emails.contract.compliance_breach',
                $emailData
            );
        }

        // SMS for critical breaches
        if (($breachDetails['is_critical'] ?? false) && $customer->phone) {
            $this->sendSmsNotification(
                $customer->phone,
                "CRITICAL: Compliance breach detected for contract '{$contract->name}'. Immediate action required. Check your dashboard."
            );
        }

        // Internal team notification
        $this->sendEmailNotification(
            config('mail.from.address'),
            "Internal: Contract Compliance Breach - {$contract->name}",
            'emails.contract.compliance_internal',
            [
                'contract_details' => $contract->getContractSummary(),
                'customer_details' => [
                    'name' => $customer->company_name ?? $customer->contact_person,
                    'email' => $customer->email
                ],
                'breach_details' => $breachDetails,
                'escalation_required' => ($breachDetails['consecutive_breaches'] ?? 0) >= 3
            ]
        );

        return $notifications;
    }

    /**
     * Send milestone achievement notifications
     */
    public function sendMilestoneNotifications(\App\Models\CustomerMilestone $milestone): array
    {
        $customer = $milestone->customer;
        $notifications = [];

        // Check cooldown period
        $recentNotification = ContractNotification::where('customer_id', $customer->id)
                                                ->where('notification_type', 'milestone_achieved')
                                                ->where('scheduled_at', '>=', now()->subHours(self::MILESTONE_NOTIFICATION_COOLDOWN_HOURS))
                                                ->first();

        if ($recentNotification) {
            return []; // Skip notification due to cooldown
        }

        // Create notification record
        $notification = ContractNotification::createFromEvent(
            null, // No specific contract for customer-level milestone
            'milestone_achieved',
            [
                'milestone_type' => $milestone->milestone_type,
                'milestone_value' => $milestone->milestone_value,
                'achieved_at' => $milestone->achieved_at->toISOString(),
                'reward_details' => $milestone->reward_details
            ],
            null,
            $customer->id
        );

        if ($notification) {
            $notifications[] = $notification;
        }

        // Send congratulatory email
        $emailData = [
            'customer_name' => $customer->company_name ?? $customer->contact_person,
            'milestone_title' => $milestone->getMilestoneTitle(),
            'milestone_type' => $milestone->milestone_type,
            'milestone_value' => $milestone->milestone_value,
            'achieved_at' => $milestone->achieved_at->toFormattedDateString(),
            'reward_given' => $milestone->reward_given,
            'reward_details' => $milestone->reward_details,
            'congratulations_message' => $this->getCongratulationsMessage($milestone),
            'next_milestone' => $this->getNextMilestone($customer, $milestone->milestone_type, $milestone->milestone_value),
            'dashboard_url' => $this->getCustomerDashboardUrl($customer->id)
        ];

        $this->sendEmailNotification(
            $customer->email,
            "🎉 Congratulations! {$milestone->getMilestoneTitle()}",
            'emails.contract.milestone_achieved',
            $emailData
        );

        // Send SMS for significant milestones
        if ($this->isSignificantMilestone($milestone->milestone_type, $milestone->milestone_value) && $customer->phone) {
            $this->sendSmsNotification(
                $customer->phone,
                "🎉 Congratulations! You've achieved: {$milestone->getMilestoneTitle()}. Reward: {$milestone->reward_given}"
            );
        }

        // Internal team notification for major milestones
        if ($this->isMajorMilestone($milestone->milestone_type, $milestone->milestone_value)) {
            $customerName = $customer->company_name ?? $customer->contact_person;
            $this->sendEmailNotification(
                config('mail.from.address'),
                "Major Customer Milestone Achieved - {$customerName}",
                'emails.contract.milestone_internal',
                [
                    'customer_details' => [
                        'name' => $customer->company_name ?? $customer->contact_person,
                        'email' => $customer->email
                    ],
                    'milestone' => [
                        'type' => $milestone->milestone_type,
                        'value' => $milestone->milestone_value,
                        'title' => $milestone->getMilestoneTitle()
                    ],
                    'reward_given' => $milestone->reward_given
                ]
            );
        }

        return $notifications;
    }

    /**
     * Send volume tier achievement notifications
     */
    public function sendVolumeTierNotifications(Contract $contract, array $tierAchievements): array
    {
        $customer = $contract->customer;
        $notifications = [];

        foreach ($tierAchievements as $tier) {
            // Create notification record
            $notification = ContractNotification::createFromEvent(
                $contract,
                'tier_achieved',
                $tier
            );

            if ($notification) {
                $notifications[] = $notification;
            }

            // Send tier achievement email
            $emailData = [
                'customer_name' => $customer->company_name ?? $customer->contact_person,
                'contract_name' => $contract->name,
                'tier_name' => $tier['tier_name'],
                'volume_requirement' => $tier['volume_requirement'],
                'discount_percentage' => $tier['discount_percentage'],
                'achieved_at' => $tier['achieved_at'],
                'benefits_unlocked' => $this->getTierBenefits($tier['tier_name']),
                'congratulations_message' => "Congratulations! You've unlocked the {$tier['tier_name']} tier with {$tier['discount_percentage']}% discount.",
                'next_tier' => $this->getNextTierInfo($contract, $tier['tier_name']),
                'dashboard_url' => $this->getContractDashboardUrl($contract->id, 'tiers')
            ];

            $this->sendEmailNotification(
                $customer->email,
                "🏆 Volume Tier Achieved: {$tier['tier_name']} Tier!",
                'emails.contract.tier_achieved',
                $emailData
            );
        }

        return $notifications;
    }

    /**
     * Send batch renewal alerts
     */
    public function sendBatchRenewalAlerts(int $daysBefore = 30): array
    {
        $expiringContracts = Contract::where('status', 'active')
                                   ->where('end_date', '<=', now()->addDays($daysBefore))
                                   ->where('end_date', '>=', now())
                                   ->whereDoesntHave('notifications', function($q) use ($daysBefore) {
                                       $q->where('notification_type', 'renewal_notice')
                                         ->where('scheduled_at', '>=', now()->subDays(7));
                                   })
                                   ->with(['customer'])
                                   ->get();

        $results = [
            'contracts_checked' => $expiringContracts->count(),
            'alerts_sent' => 0,
            'alerts' => []
        ];

        foreach ($expiringContracts as $contract) {
            $alerts = $this->sendContractRenewalNotifications($contract);
            $results['alerts_sent'] += count($alerts);
            $results['alerts'][] = [
                'contract_id' => $contract->id,
                'contract_name' => $contract->name,
                'customer_id' => $contract->customer_id,
                'days_until_expiry' => $contract->getDaysUntilExpiry(),
                'alerts_sent' => count($alerts)
            ];
        }

        Log::info('Batch renewal alerts processed', $results);

        return $results;
    }

    /**
     * Send compliance monitoring alerts
     */
    public function sendComplianceMonitoringAlerts(): array
    {
        $contracts = Contract::whereHas('compliances', function($q) {
                $q->where(function($subQ) {
                    $subQ->where('compliance_status', 'breached')
                         ->orWhere('next_check_due', '<', now())
                         ->orWhere('consecutive_breaches', '>=', 2);
                });
            })
            ->where('status', 'active')
            ->with(['customer', 'compliances'])
            ->get();

        $results = [
            'contracts_checked' => $contracts->count(),
            'alerts_sent' => 0,
            'alerts' => []
        ];

        foreach ($contracts as $contract) {
            $breachDetails = [];
            
            foreach ($contract->compliances as $compliance) {
                if ($compliance->compliance_status === 'breached' || 
                    $compliance->next_check_due->isPast() || 
                    $compliance->consecutive_breaches >= 2) {
                    
                    $breachDetails[] = [
                        'requirement_name' => $compliance->requirement_name,
                        'compliance_type' => $compliance->compliance_type,
                        'current_status' => $compliance->compliance_status,
                        'performance_percentage' => $compliance->performance_percentage,
                        'target_value' => $compliance->target_value,
                        'is_critical' => $compliance->is_critical,
                        'consecutive_breaches' => $compliance->consecutive_breaches,
                        'resolution_deadline' => $compliance->resolution_deadline?->toISOString(),
                        'required_actions' => $compliance->getRequiredActions()
                    ];
                }
            }

            if (!empty($breachDetails)) {
                $alerts = $this->sendComplianceBreachNotifications($contract, [
                    'breach_details' => $breachDetails,
                    'total_breaches' => count($breachDetails),
                    'critical_breaches' => collect($breachDetails)->where('is_critical', true)->count()
                ]);

                $results['alerts_sent'] += count($alerts);
                $results['alerts'][] = [
                    'contract_id' => $contract->id,
                    'customer_id' => $contract->customer_id,
                    'breach_count' => count($breachDetails),
                    'alerts_sent' => count($alerts)
                ];
            }
        }

        Log::info('Compliance monitoring alerts processed', $results);

        return $results;
    }

    // Private helper methods

    private function createAndSendNotification(
        Contract $contract,
        string $recipient,
        string $notificationType,
        string $template,
        array $data,
        string $channel = 'email'
    ): ?ContractNotification {
        try {
            $notification = ContractNotification::createFromEvent(
                $contract,
                $notificationType,
                $data,
                null,
                null,
                $recipient,
                $channel
            );

            if ($notification) {
                // Send the actual notification based on channel
                match($channel) {
                    'email' => $this->sendEmailNotification($recipient, $data['subject'] ?? '', $template, $data),
                    'sms' => $this->sendSmsNotification($recipient, $data['message'] ?? ''),
                    default => null
                };
            }

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create/send notification', [
                'contract_id' => $contract->id,
                'notification_type' => $notificationType,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    private function sendEmailNotification(string $to, string $subject, string $template, array $data): void
    {
        try {
            Mail::to($to)->send(new \App\Mail\ContractNotification($subject, $template, $data));
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendSmsNotification(string $to, string $message): void
    {
        // Implementation would depend on your SMS service
        // For now, just log the SMS
        Log::info('SMS notification would be sent', [
            'to' => $to,
            'message' => $message
        ]);
    }

    private function sendCriticalComplianceAlert(Contract $contract, array $breachDetails): void
    {
        $customer = $contract->customer;
        
        $emailData = [
            'customer_name' => $customer->company_name ?? $customer->contact_person,
            'contract_name' => $contract->name,
            'critical_breaches' => $breachDetails,
            'urgent_action_required' => true,
            'escalation_contact' => config('app.escalation_email'),
            'dashboard_url' => $this->getContractDashboardUrl($contract->id, 'compliance')
        ];

        $this->sendEmailNotification(
            $customer->email,
            "🚨 URGENT: Critical Compliance Breach - Immediate Action Required",
            'emails.contract.critical_compliance_breach',
            $emailData
        );

        // Also send to escalation contacts
        $escalationContacts = config('app.escalation_contacts', []);
        foreach ($escalationContacts as $contact) {
            $this->sendEmailNotification(
                $contact,
                "Critical Compliance Escalation - {$contract->name}",
                'emails.contract.compliance_escalation',
                array_merge($emailData, [
                    'customer_contact' => $customer->email,
                    'immediate_action_required' => true
                ])
            );
        }
    }

    private function sendActivationWebhooks(Contract $contract, Customer $customer): void
    {
        $webhookData = [
            'event' => 'contract_activated',
            'contract' => $contract->getContractSummary(),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->company_name ?? $customer->contact_person,
                'email' => $customer->email
            ],
            'timestamp' => now()->toISOString()
        ];

        $this->webhookService->triggerEvent('contract_activated', $webhookData);
    }

    private function shouldSendSms(Contract $contract, string $notificationType): bool
    {
        $notificationSettings = $contract->notification_settings ?? [];
        return $notificationSettings['sms_notifications'] ?? false;
    }

    private function getContractDashboardUrl(int $contractId, ?string $section = null): string
    {
        $baseUrl = config('app.url') . "/contracts/{$contractId}";
        return $section ? "{$baseUrl}/{$section}" : $baseUrl;
    }

    private function getCustomerDashboardUrl(int $customerId): string
    {
        return config('app.url') . "/dashboard/customers/{$customerId}";
    }

    private function getRenewalUrl(int $contractId): string
    {
        return config('app.url') . "/contracts/{$contractId}/renew";
    }

    private function getRenewalActionRequired(Contract $contract): string
    {
        $daysUntilExpiry = $contract->getDaysUntilExpiry();
        
        if ($contract->auto_renewal_terms['auto_renewal'] ?? false) {
            if ($daysUntilExpiry > ($contract->auto_renewal_terms['notice_period_days'] ?? 30)) {
                return 'Auto-renewal will be processed. No action required unless you wish to modify terms.';
            } else {
                return 'Auto-renewal processing soon. Please confirm terms or contact us to discuss changes.';
            }
        } else {
            return 'Manual renewal required. Please contact us to renew your contract.';
        }
    }

    private function getCongratulationsMessage(\App\Models\CustomerMilestone $milestone): string
    {
        return match($milestone->milestone_type) {
            'shipment_count' => "Amazing work! You've shipped {$milestone->milestone_value} packages with us.",
            'revenue_volume' => "Outstanding! You've generated $" . number_format($milestone->milestone_value) . " in revenue.",
            'tenure' => "Thank you for your loyalty! You've been with us for {$milestone->getTenureText()}.",
            default => "Congratulations on reaching this milestone!"
        };
    }

    private function getNextMilestone(Customer $customer, string $type, int $currentValue): ?array
    {
        $nextValues = match($type) {
            'shipment_count' => [50, 100, 500, 1000, 5000],
            'revenue_volume' => [5000, 10000, 50000, 100000],
            'tenure' => [12, 24, 36, 48, 60],
            default => []
        };

        foreach ($nextValues as $value) {
            if ($value > $currentValue) {
                return [
                    'type' => $type,
                    'value' => $value,
                    'remaining' => $value - $currentValue
                ];
            }
        }

        return null;
    }

    private function isSignificantMilestone(string $type, int $value): bool
    {
        return match($type) {
            'shipment_count' => in_array($value, [100, 500, 1000, 5000]),
            'revenue_volume' => in_array($value, [10000, 50000, 100000]),
            'tenure' => in_array($value, [12, 24, 36]),
            default => false
        };
    }

    private function isMajorMilestone(string $type, int $value): bool
    {
        return match($type) {
            'shipment_count' => in_array($value, [1000, 5000]),
            'revenue_volume' => in_array($value, [50000, 100000]),
            'tenure' => in_array($value, [24, 36, 60]),
            default => false
        };
    }

    private function getTierBenefits(string $tierName): array
    {
        $benefits = match($tierName) {
            'Bronze' => ['Standard support', 'Monthly reporting'],
            'Silver' => ['Priority support', 'Weekly reporting', 'Dedicated account manager'],
            'Gold' => ['24/7 support', 'Daily reporting', 'Custom integrations', 'Priority processing'],
            'Platinum' => ['White-glove service', 'Custom solutions', 'API access', 'Volume discounts'],
            default => ['Basic benefits']
        };

        return $benefits;
    }

    private function getNextTierInfo(Contract $contract, string $currentTierName): ?array
    {
        $tierOrder = ['Bronze', 'Silver', 'Gold', 'Platinum'];
        $currentIndex = array_search($currentTierName, $tierOrder);
        
        if ($currentIndex !== false && $currentIndex < count($tierOrder) - 1) {
            $nextTierName = $tierOrder[$currentIndex + 1];
            $nextTier = $contract->volumeDiscounts()->where('tier_name', $nextTierName)->first();
            
            if ($nextTier) {
                return [
                    'tier_name' => $nextTierName,
                    'volume_requirement' => $nextTier->volume_requirement,
                    'discount_percentage' => $nextTier->discount_percentage,
                    'benefits' => $this->getTierBenefits($nextTierName)
                ];
            }
        }

        return null;
    }
}