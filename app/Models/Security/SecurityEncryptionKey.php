<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEncryptionKey extends Model
{
    protected $fillable = [
        'key_name',
        'key_type',
        'key_value',
        'algorithm',
        'key_length',
        'expires_at',
        'rotated_at',
        'rotated_by',
        'status',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'rotated_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user who created this key
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this key
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who rotated this key
     */
    public function rotator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rotated_by');
    }

    /**
     * Scope for active keys
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for keys by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('key_type', $type);
    }

    /**
     * Check if this key is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if this key is compromised
     */
    public function isCompromised(): bool
    {
        return $this->status === 'compromised';
    }

    /**
     * Generate a new key value
     */
    public function generateKey(int $length = 256): string
    {
        return base64_encode(random_bytes($length / 8));
    }

    /**
     * Mark this key as compromised
     */
    public function markAsCompromised($userId = null)
    {
        $this->update([
            'status' => 'compromised',
            'rotated_by' => $userId,
            'rotated_at' => now(),
        ]);
    }

    /**
     * Rotate this key (mark as inactive and create new one)
     */
    public function rotate($userId = null)
    {
        $this->update([
            'status' => 'inactive',
            'rotated_by' => $userId,
            'rotated_at' => now(),
        ]);

        // Return new active key of same type
        return self::byType($this->key_type)->active()->first();
    }
}