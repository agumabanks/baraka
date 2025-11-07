<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityPrivacyConsent extends Model
{
    protected $fillable = [
        'user_id',
        'consent_type', // 'marketing', 'analytics', 'necessary', 'third_party'
        'consent_given',
        'consent_source', // 'web_form', 'api', 'email', 'phone'
        'ip_address',
        'user_agent',
        'consent_data', // Additional consent metadata
        'expires_at',
        'withdrawn_at',
        'withdrawal_method',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'consent_data' => 'array',
        'expires_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    /**
     * Get the user who gave consent
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active consents
     */
    public function scopeActive($query)
    {
        return $query->where('consent_given', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->whereNull('withdrawn_at');
    }

    /**
     * Scope by consent type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    /**
     * Check if consent is still valid
     */
    public function isValid(): bool
    {
        if (!$this->consent_given) {
            return false;
        }

        if ($this->withdrawn_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Withdraw consent
     */
    public function withdraw(string $method = 'user_request'): void
    {
        $this->update([
            'consent_given' => false,
            'withdrawn_at' => now(),
            'withdrawal_method' => $method,
        ]);
    }
}