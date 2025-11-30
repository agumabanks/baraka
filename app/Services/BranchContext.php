<?php

namespace App\Services;

use App\Models\Backend\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * BranchContext Service
 * 
 * Manages branch context for multi-tenant operations.
 * Handles branch switching for Super Admin and Regional Managers.
 * Provides validation and audit trail for branch access.
 */
class BranchContext
{
    /**
     * Get the current active branch for the authenticated user
     */
    public static function current(): ?Branch
    {
        $branchId = static::currentId();
        
        if (!$branchId) {
            return null;
        }

        return Branch::find($branchId);
    }

    /**
     * Get the current branch ID
     */
    public static function currentId(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // Check session-based context (for multi-branch users)
        $sessionBranchId = session('current_branch_id');
        if ($sessionBranchId) {
            return (int) $sessionBranchId;
        }

        // Use primary branch
        if ($user->primary_branch_id) {
            return (int) $user->primary_branch_id;
        }

        // Fallback to branch worker relationship
        if ($user->branchWorker && $user->branchWorker->branch_id) {
            return (int) $user->branchWorker->branch_id;
        }

        return null;
    }

    /**
     * Switch branch context (for Super Admin and Regional Managers)
     */
    public static function switchTo(int|Branch $branch, User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            Log::warning('Attempted to switch branch context without authenticated user');
            return false;
        }

        $branchId = $branch instanceof Branch ? $branch->id : $branch;
        $branchModel = $branch instanceof Branch ? $branch : Branch::find($branchId);

        if (!$branchModel) {
            Log::warning('Attempted to switch to non-existent branch', ['branch_id' => $branchId]);
            return false;
        }

        // Validate user has permission to switch branches
        if (!static::canSwitchBranch($user, $branchModel)) {
            Log::warning('Unauthorized branch context switch attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role?->slug,
                'target_branch_id' => $branchId,
            ]);
            return false;
        }

        // Set session context
        session(['current_branch_id' => $branchId]);

        // Log the branch switch for audit
        activity()
            ->causedBy($user)
            ->performedOn($branchModel)
            ->withProperties([
                'previous_branch_id' => session()->pull('previous_branch_id'),
                'new_branch_id' => $branchId,
                'branch_name' => $branchModel->name,
            ])
            ->log('Branch context switched');

        session(['previous_branch_id' => static::currentId()]);

        Log::info('Branch context switched', [
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'branch_name' => $branchModel->name,
        ]);

        return true;
    }

    /**
     * Clear branch context (revert to user's primary branch)
     */
    public static function clear(): void
    {
        session()->forget('current_branch_id');
        session()->forget('previous_branch_id');
    }

    /**
     * Check if user can switch to a different branch
     */
    public static function canSwitchBranch(User $user, Branch $targetBranch): bool
    {
        // Super Admin can switch to any branch
        if ($user->hasRole(['super-admin', 'super_admin'])) {
            return true;
        }

        // Regional Manager can switch to branches in their region
        if ($user->hasRole(['regional-manager', 'regional_manager'])) {
            // TODO: Implement regional branch validation
            // For now, allow all branches
            return true;
        }

        // Branch Admin can only access their own branch
        if ($user->hasRole(['branch-admin', 'branch_admin'])) {
            return $user->primary_branch_id === $targetBranch->id;
        }

        // Other roles cannot switch branches
        return false;
    }

    /**
     * Get all branches accessible by the user
     */
    public static function accessibleBranches(User $user = null): array
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return [];
        }

        // Super Admin can access all active branches
        if ($user->hasRole(['super-admin', 'super_admin'])) {
            return Branch::active()->orderBy('name')->get()->toArray();
        }

        // Regional Manager can access branches in their region
        if ($user->hasRole(['regional-manager', 'regional_manager'])) {
            // TODO: Filter by region
            return Branch::active()->orderBy('name')->get()->toArray();
        }

        // Other users can only access their primary branch
        if ($user->primary_branch_id) {
            $branch = Branch::find($user->primary_branch_id);
            return $branch ? [$branch->toArray()] : [];
        }

        return [];
    }

    /**
     * Enable branch scope bypass (with audit)
     */
    public static function bypassScope(User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Only Super Admin can bypass scope
        if (!$user->hasRole(['super-admin', 'super_admin'])) {
            Log::warning('Unauthorized attempt to bypass branch scope', [
                'user_id' => $user->id,
                'user_role' => $user->role?->slug,
            ]);
            return false;
        }

        session(['branch_scope_bypassed' => true]);

        activity()
            ->causedBy($user)
            ->log('Branch scope bypassed');

        return true;
    }

    /**
     * Disable branch scope bypass
     */
    public static function restoreScope(): void
    {
        session()->forget('branch_scope_bypassed');
    }

    /**
     * Check if branch scope is currently bypassed
     */
    public static function isScopeBypassed(): bool
    {
        return (bool) session('branch_scope_bypassed', false);
    }

    /**
     * Execute a callback without branch scope
     */
    public static function withoutScope(callable $callback)
    {
        $wasBypassed = static::isScopeBypassed();
        
        if (!$wasBypassed) {
            static::bypassScope();
        }

        try {
            return $callback();
        } finally {
            if (!$wasBypassed) {
                static::restoreScope();
            }
        }
    }
}
