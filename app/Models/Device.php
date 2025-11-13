<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'device_uuid',
        'push_token',
        'last_seen_at',
        // Mobile scanning specific fields
        'device_id',
        'device_name',
        'device_token',
        'app_version',
        'fcm_token',
        'is_active',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get device identifier (either device_uuid or device_id)
     */
    public function getDeviceIdentifier(): ?string
    {
        return $this->device_id ?? $this->device_uuid;
    }

    /**
     * Get push token (either fcm_token or push_token)
     */
    public function getPushToken(): ?string
    {
        return $this->fcm_token ?? $this->push_token;
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for mobile scanning devices
     */
    public function scopeMobileScanning($query)
    {
        return $query->whereNotNull('device_id')->whereNotNull('device_token');
    }

    /**
     * Check if device is registered for mobile scanning
     */
    public function isMobileScanningDevice(): bool
    {
        return !is_null($this->device_id) && !is_null($this->device_token);
    }
}