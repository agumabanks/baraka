<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SecurityRole extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_role_id',
        'inherited_permissions',
        'role_hierarchy_path',
        'level',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'inherited_permissions' => 'array',
        'role_hierarchy_path' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent role
     */
    public function parentRole(): BelongsTo
    {
        return $this->belongsTo(SecurityRole::class, 'parent_role_id');
    }

    /**
     * Get child roles
     */
    public function childRoles(): HasMany
    {
        return $this->hasMany(SecurityRole::class, 'parent_role_id');
    }

    /**
     * Get permissions for this role
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(SecurityRolePermission::class);
    }

    /**
     * Get users with this role
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(SecurityUserRole::class);
    }

    /**
     * Get the creator of this role
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the last updater of this role
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all inherited permissions from parent roles
     */
    public function getAllInheritedPermissions(): array
    {
        $permissions = $this->inherited_permissions ?? [];

        if ($this->parentRole) {
            $parentPermissions = $this->parentRole->getAllInheritedPermissions();
            $permissions = array_merge($permissions, $parentPermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Check if this role is a child of another role
     */
    public function isChildOf(SecurityRole $role): bool
    {
        $current = $this;
        while ($current->parentRole) {
            if ($current->parentRole->id === $role->id) {
                return true;
            }
            $current = $current->parentRole;
        }
        return false;
    }

    /**
     * Check if this role is a parent of another role
     */
    public function isParentOf(SecurityRole $role): bool
    {
        return $role->isChildOf($this);
    }

    /**
     * Get role hierarchy path as array
     */
    public function getHierarchyPath(): array
    {
        if ($this->role_hierarchy_path) {
            return $this->role_hierarchy_path;
        }

        $path = [$this->id];
        $current = $this;

        while ($current->parentRole) {
            $current = $current->parentRole;
            array_unshift($path, $current->id);
        }

        return $path;
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for roles by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }
}