<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_endpoint_id',
        'event_type',
        'payload',
        'response',
        'http_status',
        'attempts',
        'next_retry_at',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'http_status' => 'integer',
        'attempts' => 'integer',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    public function isFailed(): bool
    {
        return $this->failed_at !== null;
    }

    public function isPending(): bool
    {
        return $this->delivered_at === null && $this->failed_at === null;
    }

    public function scopeDelivered($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    public function scopeFailed($query)
    {
        return $query->whereNotNull('failed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('delivered_at')->whereNull('failed_at');
    }

    public function scopeRetryable($query)
    {
        return $query->pending()
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now());
    }
}
