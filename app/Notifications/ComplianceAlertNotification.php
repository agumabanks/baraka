<?php

namespace App\Notifications;

use App\Models\ComplianceViolation;
use App\Models\ComplianceMonitoringRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplianceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ComplianceViolation $violation,
        public ComplianceMonitoringRule $rule
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Compliance Alert: {$this->rule->compliance_framework} Violation")
            ->line("A compliance violation has been detected.")
            ->line("**Framework:** {$this->rule->compliance_framework}")
            ->line("**Severity:** {$this->violation->severity}")
            ->line("**Description:** {$this->violation->description}")
            ->line("**Discovered:** {$this->violation->discovered_at->format('Y-m-d H:i:s')}")
            ->action('View Violation Details', route('admin.compliance.violations.show', $this->violation->id))
            ->line('Please take immediate action to resolve this issue.');

        // Add urgency for critical violations
        if ($this->violation->severity === 'critical') {
            $mail->priority(2); // High priority
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'violation_id' => $this->violation->id,
            'rule_id' => $this->rule->id,
            'framework' => $this->rule->compliance_framework,
            'severity' => $this->violation->severity,
            'description' => $this->violation->description,
            'discovered_at' => $this->violation->discovered_at,
            'action_required' => true,
        ];
    }
}