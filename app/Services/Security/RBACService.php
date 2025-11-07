<?php

namespace App\Services\Security;

use App\Models\Security\SecurityRole;
use App\Models\Security\SecurityPermission;
use App\Models\Security\SecurityUserRole;
use App\Models\Security\SecurityRolePermission;
use App\Models\Security\SecurityAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RBACService
{
    /**
     * Check if user has a specific permission
     */
    public function hasPermission(User $user, string $permission): bool
    {
        try {
            $activeRoles = $this->getActiveUserRoles($user);
            
            foreach ($activeRoles as $userRole) {
                $role = $userRole->role;
                $permissions = $this->getRolePermissions($role);
                
                if (in_array($permission, $permissions)) {
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            Log::error('Permission check failed', [
                'user_id' => $user->id,
                'permission' => $permission,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Check if user has any of the specified permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all specified permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Assign a role to a user
     */
    public function assignRole(User $user, int $roleId, array $scopeRestrictions = null, $expiresAt = null): bool
    {
        try {
            DB::beginTransaction();
            
            $role = SecurityRole::findOrFail($roleId);
            
            // Check if role assignment already exists
            $existingAssignment = SecurityUserRole::where('user_id', $user->id)
                ->where('security_role_id', $roleId)
                ->first();
                
            if ($existingAssignment) {
                // Reactivate if it exists but is inactive
                $existingAssignment->update([
                    'is_active' => true,
                    'expires_at' => $expiresAt,
                    'scope_restrictions' => $scopeRestrictions,
                ]);
                
                SecurityAuditLog::logPermissionChange(
                    auth()->user(),
                    'reassigned',
                    $role->name,
                    $roleId,
                    ['is_active' => false],
                    ['is_active' => true]
                );
            } else {
                // Create new assignment
                SecurityUserRole::create([
                    'user_id' => $user->id,
                    'security_role_id' => $roleId,
                    'scope_restrictions' => $scopeRestrictions,
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id(),
                    'expires_at' => $expiresAt,
                    'is_active' => true,
                ]);
                
                SecurityAuditLog::logPermissionChange(
                    auth()->user(),
                    'assigned',
                    $role->name,
                    $roleId
                );
            }
            
            DB::commit();
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Role assignment failed', [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Revoke a role from a user
     */
    public function revokeRole(User $user, int $roleId): bool
    {
        try {
            DB::beginTransaction();
            
            $userRole = SecurityUserRole::where('user_id', $user->id)
                ->where('security_role_id', $roleId)
                ->first();
                
            if (!$userRole) {
                throw new Exception('Role assignment not found');
            }
            
            $userRole->update([
                'is_active' => false,
                'revoked_at' => now(),
                'revoked_by' => auth()->id(),
            ]);
            
            $role = SecurityRole::find($roleId);
            SecurityAuditLog::logPermissionChange(
                auth()->user(),
                'revoked',
                $role?->name ?? 'Unknown',
                $roleId
            );
            
            DB::commit();
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Role revocation failed', [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create a new role
     */
    public function createRole(array $data): SecurityRole
    {
        try {
            DB::beginTransaction();
            
            $role = SecurityRole::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? strtolower(str_replace(' ', '-', $data['name'])),
                'description' => $data['description'] ?? null,
                'parent_role_id' => $data['parent_role_id'] ?? null,
                'level' => $data['level'] ?? 'functional',
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
            
            // Update hierarchy path
            if ($role->parent_role_id) {
                $role->update(['role_hierarchy_path' => $role->getHierarchyPath()]);
            }
            
            SecurityAuditLog::logPermissionChange(
                auth()->user(),
                'created role',
                $role->name,
                $role->id
            );
            
            DB::commit();
            return $role;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Role creation failed', [
                'role_name' => $data['name'],
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to create role');
        }
    }

    /**
     * Create a new permission
     */
    public function createPermission(array $data): SecurityPermission
    {
        try {
            DB::beginTransaction();
            
            $permission = SecurityPermission::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? strtolower(str_replace(' ', '-', $data['name'])),
                'description' => $data['description'] ?? null,
                'resource' => $data['resource'],
                'action' => $data['action'],
                'conditions' => $data['conditions'] ?? null,
                'data_classification' => $data['data_classification'] ?? 'internal',
                'requires_approval' => $data['requires_approval'] ?? false,
                'approval_role_id' => $data['approval_role_id'] ?? null,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
            
            SecurityAuditLog::logPermissionChange(
                auth()->user(),
                'created permission',
                $permission->full_name,
                $permission->id
            );
            
            DB::commit();
            return $permission;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Permission creation failed', [
                'permission_name' => $data['name'],
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to create permission');
        }
    }

    /**
     * Grant a permission to a role
     */
    public function grantPermissionToRole(int $roleId, int $permissionId, array $conditions = null): bool
    {
        try {
            DB::beginTransaction();
            
            $role = SecurityRole::findOrFail($roleId);
            $permission = SecurityPermission::findOrFail($permissionId);
            
            SecurityRolePermission::create([
                'security_role_id' => $roleId,
                'security_permission_id' => $permissionId,
                'conditions' => $conditions,
                'granted_at' => now(),
                'granted_by' => auth()->id(),
            ]);
            
            SecurityAuditLog::logPermissionChange(
                auth()->user(),
                'granted permission to role',
                $permission->full_name,
                $roleId
            );
            
            DB::commit();
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Permission grant failed', [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get all active user roles
     */
    public function getActiveUserRoles(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return SecurityUserRole::with('role')
            ->where('user_id', $user->id)
            ->active()
            ->get();
    }

    /**
     * Get all permissions for a role (including inherited)
     */
    public function getRolePermissions(SecurityRole $role): array
    {
        $permissions = [];
        
        // Get direct permissions
        $directPermissions = SecurityRolePermission::with('permission')
            ->where('security_role_id', $role->id)
            ->active()
            ->get();
            
        foreach ($directPermissions as $rolePermission) {
            $permissions[] = $rolePermission->permission->full_name;
        }
        
        // Get inherited permissions from parent roles
        if ($role->parent_role_id) {
            $parentRole = SecurityRole::find($role->parent_role_id);
            if ($parentRole) {
                $parentPermissions = $this->getRolePermissions($parentRole);
                $permissions = array_merge($permissions, $parentPermissions);
            }
        }
        
        return array_unique($permissions);
    }

    /**
     * Get user permissions (including from all active roles)
     */
    public function getUserPermissions(User $user): array
    {
        $allPermissions = [];
        $activeRoles = $this->getActiveUserRoles($user);
        
        foreach ($activeRoles as $userRole) {
            $rolePermissions = $this->getRolePermissions($userRole->role);
            $allPermissions = array_merge($allPermissions, $rolePermissions);
        }
        
        return array_unique($allPermissions);
    }

    /**
     * Check if user can access resource based on scope restrictions
     */
    public function canAccessResource(User $user, string $resource, int $resourceId = null): bool
    {
        $activeRoles = $this->getActiveUserRoles($user);
        
        foreach ($activeRoles as $userRole) {
            $scope = $userRole->scope_restrictions;
            
            if (!$scope || $this->isWithinScope($scope, $resource, $resourceId)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if resource is within scope restrictions
     */
    private function isWithinScope(array $scope, string $resource, ?int $resourceId): bool
    {
        // Implement scope validation logic
        if (isset($scope['allowed_resources']) && in_array($resource, $scope['allowed_resources'])) {
            return true;
        }
        
        if (isset($scope['allowed_ids']) && $resourceId && in_array($resourceId, $scope['allowed_ids'])) {
            return true;
        }
        
        // Default: allow if no specific restrictions
        return empty($scope);
    }

    /**
     * Log permission check for audit
     */
    public function logPermissionCheck(User $user, string $permission, bool $result): void
    {
        SecurityAuditLog::create([
            'event_type' => 'permission_check',
            'event_category' => 'security',
            'severity' => 'low',
            'user_id' => $user->id,
            'resource_type' => 'permission',
            'action_details' => [
                'permission' => $permission,
                'result' => $result,
            ],
            'status' => 'success',
            'description' => "Permission check: {$permission} - " . ($result ? 'granted' : 'denied'),
        ]);
    }
}