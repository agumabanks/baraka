<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAuditLog extends Model
{
    protected $fillable = [
        'event_type',
        'event_category',
        'severity',
        'user_id',
        'user_type',
        'session_id',
        'ip_address',
        'user_agent',
        'resource_type',
        'resource_id',
        'action_details',
        'old_values',
        'new_values',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'action_details' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for security events
     */
    public function scopeSecurity($query)
    {
        return $query->where('event_category', 'security');
    }

    /**
     * Scope for financial events
     */
    public function scopeFinancial($query)
    {
        return $query->where('event_category', 'financial');
    }

    /**
     * Scope for privacy events
     */
    public function scopePrivacy($query)
    {
        return $query->where('event_category', 'privacy');
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for failed events
     */
    public function scopeFailed($query)
    {
        return $query->where('status', '!=', 'success');
    }

    /**
     * Log a login event
     */
    public static function logLogin($user, $request, $status = 'success')
    {
        return self::create([
            'event_type' => 'login',
            'event_category' => 'security',
            'severity' => $status === 'success' ? 'low' : 'medium',
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'status' => $status,
            'description' => $status === 'success' 
                ? "User {$user?->email} logged in successfully"
                : "Failed login attempt",
        ]);
    }

    /**
     * Log a permission change event
     */
    public static function logPermissionChange($user, $action, $permission, $roleId, $oldValues = null, $newValues = null)
    {
        return self::create([
            'event_type' => 'permission_change',
            'event_category' => 'security',
            'severity' => 'high',
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'resource_type' => 'permissions',
            'resource_id' => $roleId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'status' => 'success',
            'description' => "User {$user?->email} {$action} permission: {$permission}",
        ]);
    }

    /**
     * Log a data access event
     */
    public static function logDataAccess($user, $resourceType, $resourceId, $action, $request = null)
    {
        return self::create([
            'event_type' => 'data_access',
            'event_category' => 'operational',
            'severity' => 'low',
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'status' => 'success',
            'description' => "User {$user?->email} accessed {$resourceType} ID: {$resourceId} ({$action})",
        ]);
    }
}