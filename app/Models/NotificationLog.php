<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'notification_id',
        'template_code',
        'notifiable_type',
        'notifiable_id',
        'recipient_email',
        'recipient_phone',
        'recipient_device_token',
        'channel',
        'subject',
        'body',
        'status',
        'error_message',
        'provider_message_id',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'related_type',
        'related_id',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function related(): MorphTo
    {
        return $this->morphTo('related');
    }

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

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Mark as sent
     */
    public function markAsSent(?string $providerId = null): self
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => $providerId,
        ]);

        return $this;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): self
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): self
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $error,
        ]);

        return $this;
    }

    /**
     * Mark as read
     */
    public function markAsRead(): self
    {
        $this->update([
            'read_at' => now(),
        ]);

        return $this;
    }
}
