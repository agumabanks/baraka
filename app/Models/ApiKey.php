<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'key',
        'secret_hash',
        'user_id',
        'customer_id',
        'permissions',
        'allowed_ips',
        'rate_limit_per_minute',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'allowed_ips' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['secret_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Generate new API key pair
     */
    public static function generate(array $attributes): array
    {
        $key = 'bk_' . Str::random(32);
        $secret = 'bs_' . Str::random(48);

        $apiKey = self::create(array_merge($attributes, [
            'key' => $key,
            'secret_hash' => hash('sha256', $secret),
        ]));

        return [
            'api_key' => $apiKey,
            'key' => $key,
            'secret' => $secret, // Only returned once!
        ];
    }

    /**
     * Verify secret
     */
    public function verifySecret(string $secret): bool
    {
        return hash('sha256', $secret) === $this->secret_hash;
    }

    /**
     * Check if key has permission
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // No restrictions = full access
        }

        // Check exact match or wildcard
        if (in_array($permission, $this->permissions)) {
            return true;
        }

        // Check resource-level wildcard (e.g., 'shipments:*')
        $resource = explode(':', $permission)[0];
        if (in_array("{$resource}:*", $this->permissions)) {
            return true;
        }

        // Check full wildcard
        return in_array('*', $this->permissions);
    }

    /**
     * Check if IP is allowed
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Check if key is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Touch last used timestamp
     */
    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}
