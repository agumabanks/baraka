<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityMfaDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_name',
        'device_type', // 'sms', 'email', 'totp', 'hardware'
        'device_identifier',
        'is_verified',
        'is_primary',
        'secret_key',
        'backup_codes',
        'last_used_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_primary' => 'boolean',
        'backup_codes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user this device belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Scope for verified devices
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for primary device
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Check if device is active
     */
    public function isActive(): bool
    {
        if (!$this->is_verified) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid() . $this->user_id . time()), 0, 8));
        }
        return $codes;
    }

    /**
     * Use a backup code
     */
    public function useBackupCode(string $code): bool
    {
        $codes = $this->backup_codes ?? [];
        $codeIndex = array_search(strtoupper($code), $codes);
        
        if ($codeIndex !== false) {
            unset($codes[$codeIndex]);
            $this->update([
                'backup_codes' => array_values($codes),
                'last_used_at' => now(),
            ]);
            return true;
        }
        
        return false;
    }
}