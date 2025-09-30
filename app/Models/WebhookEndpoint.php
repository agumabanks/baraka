<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEndpoint extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'last_delivery_at',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_delivery_at' => 'datetime',
    ];

    /**
     * Get the user that owns this webhook endpoint.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the webhook deliveries for this endpoint.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if this endpoint should receive the given event.
     */
    public function shouldReceiveEvent(string $event): bool
    {
        return $this->is_active && in_array($event, $this->events);
    }

    /**
     * Generate a signature for webhook verification.
     */
    public function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }
}