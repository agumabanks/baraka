<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityRolePermission extends Model
{
    protected $fillable = [
        'security_role_id',
        'security_permission_id',
        'conditions',
        'granted_at',
        'granted_by',
        'revoked_at',
        'revoked_by',
        'notes',
    ];

    protected $casts = [
        'conditions' => 'array',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the role this permission is assigned to
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(SecurityRole::class, 'security_role_id');
    }

    /**
     * Get the permission
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(SecurityPermission::class, 'security_permission_id');
    }

    /**
     * Get the user who granted this permission
     */
    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Get the user who revoked this permission
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Scope for active permissions
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Check if this permission grant is still active
     */
    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }
}