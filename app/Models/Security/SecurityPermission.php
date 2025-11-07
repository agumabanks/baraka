<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityPermission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'resource',
        'action',
        'conditions',
        'data_classification',
        'requires_approval',
        'approval_role_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the role that must approve this permission
     */
    public function approvalRole(): BelongsTo
    {
        return $this->belongsTo(SecurityRole::class, 'approval_role_id');
    }

    /**
     * Get roles that have this permission
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(SecurityRolePermission::class);
    }

    /**
     * Get the creator of this permission
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the last updater of this permission
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for permissions by resource
     */
    public function scopeByResource($query, $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope for permissions by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for permissions by data classification
     */
    public function scopeByClassification($query, $classification)
    {
        return $query->where('data_classification', $classification);
    }

    /**
     * Get full permission name (resource.action)
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->resource}.{$this->action}";
    }

    /**
     * Check if permission requires approval
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval && $this->approval_role_id !== null;
    }
}