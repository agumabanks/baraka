<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Notification extends Model
{
    use LogsActivity;

    protected $fillable = [
        'channel',
        'template',
        'to_address',
        'status',
        'provider_message_id',
        'payload_json',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'sent_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('notification')
            ->logOnly(['channel', 'template', 'to_address', 'status'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName} notification");
    }

    // Scopes
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'SENT');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED');
    }

    // Business Logic
    public function markAsSent(?string $providerMessageId = null): void
    {
        $this->update([
            'status' => 'SENT',
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'FAILED',
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update(['status' => 'DELIVERED']);
    }
}
