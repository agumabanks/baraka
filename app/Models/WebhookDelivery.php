<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'subscription_id',
        'event',
        'payload',
        'status',
        'attempts',
        'response_code',
        'response_body',
        'response_time_ms',
        'error_message',
        'delivered_at',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNeedsRetry($query)
    {
        return $query->where('status', 'pending')
            ->where('next_retry_at', '<=', now());
    }

    /**
     * Mark as successful
     */
    public function markSuccess(int $responseCode, ?string $responseBody, int $responseTimeMs): void
    {
        $this->update([
            'status' => 'success',
            'response_code' => $responseCode,
            'response_body' => $responseBody ? substr($responseBody, 0, 1000) : null,
            'response_time_ms' => $responseTimeMs,
            'delivered_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);

        $this->subscription->recordSuccess();
    }

    /**
     * Mark as failed
     */
    public function markFailed(?int $responseCode, ?string $error, int $responseTimeMs): void
    {
        $attempts = $this->attempts + 1;
        $maxRetries = $this->subscription->retry_count;

        $updates = [
            'attempts' => $attempts,
            'response_code' => $responseCode,
            'error_message' => $error ? substr($error, 0, 500) : null,
            'response_time_ms' => $responseTimeMs,
        ];

        if ($attempts >= $maxRetries) {
            $updates['status'] = 'failed';
            $this->subscription->recordFailure();
        } else {
            // Exponential backoff: 1min, 5min, 30min
            $delays = [60, 300, 1800];
            $delay = $delays[$attempts - 1] ?? 1800;
            $updates['next_retry_at'] = now()->addSeconds($delay);
        }

        $this->update($updates);
    }

    /**
     * Get retry delay based on attempts
     */
    public function getRetryDelay(): int
    {
        $delays = [60, 300, 1800, 3600]; // 1min, 5min, 30min, 1hr
        return $delays[$this->attempts] ?? 3600;
    }
}
