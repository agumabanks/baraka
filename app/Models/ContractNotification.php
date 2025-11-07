<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class ContractNotification extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'contract_id',
        'notification_type',
        'trigger_event',
        'scheduled_at',
        'sent_at',
        'recipient_email',
        'recipient_phone',
        'notification_template',
        'message_data',
        'status',
        'attempts',
        'last_attempt_at',
        'error_message',
        'is_critical',
        'delivery_channel',
        'metadata'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'message_data' => 'array',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'is_critical' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'attempts' => 0,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ContractNotification')
            ->logOnly(['notification_type', 'trigger_event', 'status', 'recipient_email'])
            ->setDescriptionForEvent(fn (string $eventName) => "Contract notification {$eventName}");
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('scheduled_at', '<', now());
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('delivery_channel', $channel);
    }

    public function scopeScheduledFor($query, Carbon $dateTime)
    {
        return $query->where('scheduled_at', '<=', $dateTime)
                    ->where('status', 'pending');
    }

    // Business Logic
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null
        ]);
    }

    public function markAsFailed(string $errorMessage): bool
    {
        $attempts = $this->attempts + 1;
        $maxAttempts = 3;
        
        $status = $attempts >= $maxAttempts ? 'failed' : 'pending';
        
        return $this->update([
            'status' => $status,
            'attempts' => $attempts,
            'last_attempt_at' => now(),
            'error_message' => $errorMessage
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at->isPast();
    }

    public function getPriorityLevel(): string
    {
        if ($this->is_critical) {
            return 'critical';
        }

        return match($this->notification_type) {
            'expiry_warning' => 'high',
            'renewal_notice' => 'high',
            'compliance_breach' => 'high',
            'milestone_achieved' => 'medium',
            'volume_threshold' => 'medium',
            'performance_alert' => 'medium',
            'general_update' => 'low',
            default => 'normal'
        };
    }

    public function getDeliveryStatus(): array
    {
        return [
            'status' => $this->status,
            'is_overdue' => $this->isOverdue(),
            'attempts_made' => $this->attempts,
            'last_attempt' => $this->last_attempt_at?->toISOString(),
            'error_message' => $this->error_message,
            'sent_at' => $this->sent_at?->toISOString(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'priority' => $this->getPriorityLevel(),
            'channel' => $this->delivery_channel
        ];
    }

    public function scheduleReminder(Carbon $reminderDate, array $overrides = []): ?self
    {
        $reminderData = array_merge([
            'contract_id' => $this->contract_id,
            'notification_type' => $this->notification_type . '_reminder',
            'trigger_event' => 'manual_reminder',
            'scheduled_at' => $reminderDate,
            'recipient_email' => $this->recipient_email,
            'recipient_phone' => $this->recipient_phone,
            'notification_template' => $this->notification_template . '_reminder',
            'message_data' => $this->message_data,
            'is_critical' => $this->is_critical,
            'delivery_channel' => $this->delivery_channel,
            'metadata' => array_merge($this->metadata ?? [], ['reminder_for_notification_id' => $this->id])
        ], $overrides);

        return self::create($reminderData);
    }

    public function getFormattedMessage(): array
    {
        $template = $this->notification_template;
        $data = $this->message_data ?? [];
        
        // Process template variables
        $processedTemplate = $this->replaceTemplateVariables($template, $data);
        
        return [
            'subject' => $processedTemplate['subject'] ?? '',
            'body' => $processedTemplate['body'] ?? '',
            'variables' => $data,
            'channel' => $this->delivery_channel,
            'recipient' => $this->recipient_email ?: $this->recipient_phone
        ];
    }

    private function replaceTemplateVariables(string $template, array $data): array
    {
        // Simple template variable replacement
        // In production, this would use a more sophisticated templating system
        $replaced = $template;
        
        foreach ($data as $key => $value) {
            $replaced = str_replace("{{$key}}", (string) $value, $replaced);
        }
        
        return [
            'subject' => $this->extractSubject($replaced),
            'body' => $this->extractBody($replaced)
        ];
    }

    private function extractSubject(string $template): string
    {
        // Simple extraction - in production would be more sophisticated
        $lines = explode("\n", $template);
        return trim($lines[0] ?? 'Contract Notification');
    }

    private function extractBody(string $template): string
    {
        $lines = explode("\n", $template);
        return trim(implode("\n", array_slice($lines, 1)));
    }

    public static function createFromEvent(
        Contract $contract,
        string $eventType,
        array $eventData = [],
        ?Carbon $scheduledAt = null
    ): self {
        $notificationConfig = self::getNotificationConfig($eventType);
        
        if (!$notificationConfig) {
            throw new \Exception("No notification configuration found for event: {$eventType}");
        }

        $scheduledAt = $scheduledAt ?? now()->addMinutes($notificationConfig['delay_minutes'] ?? 0);

        return self::create([
            'contract_id' => $contract->id,
            'notification_type' => $eventType,
            'trigger_event' => $eventType,
            'scheduled_at' => $scheduledAt,
            'recipient_email' => $notificationConfig['email_recipient'] ?? $contract->customer->email,
            'recipient_phone' => $notificationConfig['phone_recipient'] ?? $contract->customer->phone,
            'notification_template' => $notificationConfig['template'] ?? 'default',
            'message_data' => array_merge($eventData, [
                'contract_name' => $contract->name,
                'customer_name' => $contract->customer->company_name ?? $contract->customer->contact_person,
                'contract_id' => $contract->id,
                'event_type' => $eventType,
                'generated_at' => now()->toISOString()
            ]),
            'is_critical' => $notificationConfig['is_critical'] ?? false,
            'delivery_channel' => $notificationConfig['channel'] ?? 'email',
            'metadata' => $notificationConfig['metadata'] ?? []
        ]);
    }

    private static function getNotificationConfig(string $eventType): ?array
    {
        $configs = [
            'contract_expiring' => [
                'delay_minutes' => 0,
                'email_recipient' => 'customer',
                'template' => 'contract_expiring',
                'is_critical' => true,
                'channel' => 'email',
                'metadata' => ['priority' => 'high']
            ],
            'contract_expired' => [
                'delay_minutes' => 0,
                'email_recipient' => 'customer',
                'template' => 'contract_expired',
                'is_critical' => true,
                'channel' => 'email_sms',
                'metadata' => ['priority' => 'critical']
            ],
            'renewal_due' => [
                'delay_minutes' => 0,
                'email_recipient' => 'customer',
                'template' => 'renewal_notice',
                'is_critical' => false,
                'channel' => 'email',
                'metadata' => ['type' => 'business']
            ],
            'volume_milestone' => [
                'delay_minutes' => 0,
                'email_recipient' => 'customer',
                'template' => 'milestone_achieved',
                'is_critical' => false,
                'channel' => 'email',
                'metadata' => ['type' => 'celebration']
            ],
            'compliance_breach' => [
                'delay_minutes' => 0,
                'email_recipient' => 'both',
                'template' => 'compliance_breach',
                'is_critical' => true,
                'channel' => 'email_sms',
                'metadata' => ['priority' => 'critical', 'requires_action' => true]
            ]
        ];

        return $configs[$eventType] ?? null;
    }

    public static function getPendingNotifications(Carbon $beforeTime = null): \Illuminate\Database\Eloquent\Collection
    {
        $beforeTime = $beforeTime ?? now();
        
        return self::where('status', 'pending')
                  ->where('scheduled_at', '<=', $beforeTime)
                  ->orderBy('is_critical', 'desc')
                  ->orderBy('scheduled_at', 'asc')
                  ->get();
    }

    public function retry(): bool
    {
        if ($this->status !== 'failed' || $this->attempts >= 3) {
            return false;
        }

        return $this->update([
            'status' => 'pending',
            'last_attempt_at' => now(),
            'error_message' => null
        ]);
    }
}