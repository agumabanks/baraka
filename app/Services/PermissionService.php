<?php

namespace App\Services;

use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PermissionService
 * 
 * Centralized service for permission checking and role management
 * Supports hierarchical roles, capability-based permissions, and user-specific overrides
 */
class PermissionService
{
    protected const CACHE_TTL = 3600; // 1 hour
    protected const CACHE_PREFIX = 'permissions:';

    /**
     * Check if user has a specific capability
     */
    public static function hasCapability(User $user, string $capability): bool
    {
        // Super Admin has all capabilities
        if ($user->hasRole(['super-admin', 'super_admin'])) {
            return true;
        }

        $cacheKey = static::CACHE_PREFIX . "user:{$user->id}:capability:{$capability}";

        return Cache::remember($cacheKey, static::CACHE_TTL, function () use ($user, $capability) {
            return static::checkCapability($user, $capability);
        });
    }

    /**
     * Check if user has any of the given capabilities
     */
    public static function hasAnyCapability(User $user, array $capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if (static::hasCapability($user, $capability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given capabilities
     */
    public static function hasAllCapabilities(User $user, array $capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if (!static::hasCapability($user, $capability)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Internal capability check logic
     */
    protected static function checkCapability(User $user, string $capability): bool
    {
        // Check user-specific overrides first
        $override = DB::table('user_permission_overrides')
            ->where('user_id', $user->id)
            ->where('capability_key', $capability)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($override) {
            return $override->override_type === 'grant';
        }

        // Check role capabilities
        if (!$user->role) {
            return false;
        }

        $roleCapabilities = $user->role->capabilities ?? [];

        // Direct capability match
        if (in_array($capability, $roleCapabilities, true)) {
            return true;
        }

        // Check wildcard permissions (e.g., 'shipments.*' matches 'shipments.create')
        foreach ($roleCapabilities as $roleCapability) {
            if (str_ends_with($roleCapability, '.*')) {
                $module = substr($roleCapability, 0, -2);
                if (str_starts_with($capability, $module . '.')) {
                    return true;
                }
            }
        }

        // Check role permissions array (legacy support)
        $rolePermissions = $user->role->permissions ?? [];
        if (in_array($capability, $rolePermissions, true)) {
            return true;
        }

        // Check parent role capabilities (inheritance)
        if ($user->role->parent_role_id) {
            $parentRole = Role::find($user->role->parent_role_id);
            if ($parentRole) {
                $parentCapabilities = $parentRole->capabilities ?? [];
                if (in_array($capability, $parentCapabilities, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all capabilities for a user
     */
    public static function getUserCapabilities(User $user): array
    {
        if ($user->hasRole(['super-admin', 'super_admin'])) {
            return ['*']; // Super admin has all capabilities
        }

        $cacheKey = static::CACHE_PREFIX . "user:{$user->id}:all_capabilities";

        return Cache::remember($cacheKey, static::CACHE_TTL, function () use ($user) {
            $capabilities = [];

            // Role capabilities
            if ($user->role) {
                $capabilities = array_merge($capabilities, $user->role->capabilities ?? []);
                
                // Parent role capabilities
                if ($user->role->parent_role_id) {
                    $parentRole = Role::find($user->role->parent_role_id);
                    if ($parentRole) {
                        $capabilities = array_merge($capabilities, $parentRole->capabilities ?? []);
                    }
                }
            }

            // User-specific overrides
            $overrides = DB::table('user_permission_overrides')
                ->where('user_id', $user->id)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->get();

            foreach ($overrides as $override) {
                if ($override->override_type === 'grant') {
                    $capabilities[] = $override->capability_key;
                } elseif ($override->override_type === 'revoke') {
                    $capabilities = array_diff($capabilities, [$override->capability_key]);
                }
            }

            return array_unique($capabilities);
        });
    }

    /**
     * Grant a capability to a user
     */
    public static function grantCapability(User $user, string $capability, User $grantedBy, ?string $reason = null, ?\DateTime $expiresAt = null): bool
    {
        try {
            DB::table('user_permission_overrides')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'capability_key' => $capability,
                ],
                [
                    'override_type' => 'grant',
                    'granted_by' => $grantedBy->id,
                    'granted_at' => now(),
                    'expires_at' => $expiresAt,
                    'reason' => $reason,
                    'updated_at' => now(),
                ]
            );

            static::clearUserCache($user);

            Log::info('Capability granted to user', [
                'user_id' => $user->id,
                'capability' => $capability,
                'granted_by' => $grantedBy->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to grant capability', [
                'user_id' => $user->id,
                'capability' => $capability,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Revoke a capability from a user
     */
    public static function revokeCapability(User $user, string $capability, User $revokedBy, ?string $reason = null): bool
    {
        try {
            DB::table('user_permission_overrides')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'capability_key' => $capability,
                ],
                [
                    'override_type' => 'revoke',
                    'granted_by' => $revokedBy->id,
                    'granted_at' => now(),
                    'reason' => $reason,
                    'updated_at' => now(),
                ]
            );

            static::clearUserCache($user);

            Log::info('Capability revoked from user', [
                'user_id' => $user->id,
                'capability' => $capability,
                'revoked_by' => $revokedBy->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke capability', [
                'user_id' => $user->id,
                'capability' => $capability,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove a permission override (restore to role default)
     */
    public static function removeOverride(User $user, string $capability): bool
    {
        DB::table('user_permission_overrides')
            ->where('user_id', $user->id)
            ->where('capability_key', $capability)
            ->delete();

        static::clearUserCache($user);

        return true;
    }

    /**
     * Clear permission cache for a user
     */
    public static function clearUserCache(User $user): void
    {
        $pattern = static::CACHE_PREFIX . "user:{$user->id}:*";
        
        // Simple cache clearing by known keys
        $capabilities = DB::table('role_capabilities')->pluck('capability_key');
        
        foreach ($capabilities as $capability) {
            Cache::forget(static::CACHE_PREFIX . "user:{$user->id}:capability:{$capability}");
        }
        
        Cache::forget(static::CACHE_PREFIX . "user:{$user->id}:all_capabilities");
    }

    /**
     * Clear permission cache for a role (affects all users with that role)
     */
    public static function clearRoleCache(Role $role): void
    {
        $userIds = User::where('role_id', $role->id)->pluck('id');
        
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                static::clearUserCache($user);
            }
        }
    }

    /**
     * Get all available capabilities by module
     */
    public static function getCapabilitiesByModule(): array
    {
        return Cache::remember(static::CACHE_PREFIX . 'capabilities_by_module', static::CACHE_TTL * 24, function () {
            return DB::table('role_capabilities')
                ->where('is_active', true)
                ->get()
                ->groupBy('module')
                ->map(function ($capabilities) {
                    return $capabilities->map(function ($cap) {
                        return [
                            'key' => $cap->capability_key,
                            'name' => $cap->capability_name,
                            'description' => $cap->description,
                            'privilege_level' => $cap->privilege_level,
                        ];
                    })->toArray();
                })
                ->toArray();
        });
    }

    /**
     * Check if user's role level is sufficient for an operation
     */
    public static function hasRoleLevel(User $user, int $requiredLevel): bool
    {
        if (!$user->role) {
            return false;
        }

        $userLevel = $user->role->role_level ?? 999;

        // Lower number = higher privilege
        return $userLevel <= $requiredLevel;
    }
}
