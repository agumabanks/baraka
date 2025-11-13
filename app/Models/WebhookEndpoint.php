<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WebhookEndpoint extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'events',
        'description',
        'is_active',
        'active',
        'secret',
        'secret_key',
        'retry_policy',
        'last_triggered_at',
        'last_delivery_at',
        'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'active' => 'boolean',
        'retry_policy' => 'array',
        'last_triggered_at' => 'datetime',
        'last_delivery_at' => 'datetime',
        'failure_count' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (self $model) {
            // Support both secret and secret_key
            if (!$model->secret_key && !$model->secret) {
                $model->secret_key = Str::random(32);
                $model->secret = Str::random(64);
            }
            if (!$model->retry_policy) {
                $model->retry_policy = [
                    'max_attempts' => 5,
                    'backoff_multiplier' => 2,
                    'initial_delay' => 60,
                    'max_delay' => 3600,
                ];
            }
            // Set is_active from active if not set
            if ($model->active && !isset($model->is_active)) {
                $model->is_active = $model->active;
            }
        });
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function rotateSecret(): string
    {
        $this->update(['secret_key' => Str::random(32)]);
        return $this->secret_key;
    }

    public function generateSignature(string $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $this->secret_key);
    }

    public function isHealthy(): bool
    {
        return $this->failure_count < ($this->retry_policy['max_attempts'] ?? 5);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
