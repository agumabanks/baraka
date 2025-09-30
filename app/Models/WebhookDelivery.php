<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_endpoint_id',
        'event',
        'payload',
        'response_status',
        'response_body',
        'attempts',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_body' => 'array',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the webhook endpoint that this delivery belongs to.
     */
    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    /**
     * Check if delivery was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    /**
     * Check if delivery failed.
     */
    public function isFailed(): bool
    {
        return !is_null($this->failed_at);
    }

    /**
     * Mark delivery as successful.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'delivered_at' => now(),
            'failed_at' => null,
        ]);
    }

    /**
     * Mark delivery as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'failed_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);
    }
}