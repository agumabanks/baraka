<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WebhookSubscription extends Model
{
    protected $fillable = [
        'name',
        'url',
        'secret',
        'user_id',
        'customer_id',
        'events',
        'headers',
        'is_active',
        'retry_count',
        'timeout_seconds',
        'consecutive_failures',
        'disabled_at',
        'last_triggered_at',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'disabled_at' => 'datetime',
        'last_triggered_at' => 'datetime',
    ];

    protected $hidden = ['secret'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    /**
     * Create subscription with generated secret
     */
    public static function createWithSecret(array $attributes): self
    {
        return self::create(array_merge($attributes, [
            'secret' => 'whsec_' . Str::random(32),
        ]));
    }

    /**
     * Check if subscription should receive event
     */
    public function shouldReceiveEvent(string $event): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (in_array('*', $this->events)) {
            return true;
        }

        return in_array($event, $this->events);
    }

    /**
     * Generate signature for payload
     */
    public function generateSignature(string $payload, int $timestamp): string
    {
        $signedPayload = "{$timestamp}.{$payload}";
        return hash_hmac('sha256', $signedPayload, $this->secret);
    }

    /**
     * Record successful delivery
     */
    public function recordSuccess(): void
    {
        $this->update([
            'consecutive_failures' => 0,
            'last_triggered_at' => now(),
        ]);
    }

    /**
     * Record failed delivery
     */
    public function recordFailure(): void
    {
        $failures = $this->consecutive_failures + 1;
        
        $this->update([
            'consecutive_failures' => $failures,
            'last_triggered_at' => now(),
        ]);

        // Auto-disable after too many failures
        if ($failures >= 10) {
            $this->update([
                'is_active' => false,
                'disabled_at' => now(),
            ]);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where(function ($q) use ($event) {
            $q->whereJsonContains('events', $event)
              ->orWhereJsonContains('events', '*');
        });
    }

    /**
     * Available webhook events
     */
    public static function availableEvents(): array
    {
        return [
            'shipment.created',
            'shipment.picked_up',
            'shipment.in_transit',
            'shipment.out_for_delivery',
            'shipment.delivered',
            'shipment.cancelled',
            'shipment.returned',
            'shipment.exception',
            'shipment.status_changed',
            'invoice.created',
            'invoice.paid',
            'settlement.created',
            'settlement.paid',
            'tracking.scanned',
        ];
    }
}
