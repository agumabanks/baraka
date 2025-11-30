<?php

namespace App\Models\Concerns;

use App\Models\Backend\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * BranchScoped Trait
 * 
 * Provides automatic branch-level data isolation for multi-tenant architecture.
 * Models using this trait will automatically filter queries by branch_id based on the authenticated user's context.
 * 
 * Usage:
 * - Add trait to model: use BranchScoped;
 * - Ensure model has 'branch_id' column
 * - Super Admins and Regional Managers can bypass scope using withoutBranchScope() method
 */
trait BranchScoped
{
    /**
     * Boot the BranchScoped trait for a model.
     */
    protected static function bootBranchScoped(): void
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            static::applyBranchScope($builder);
        });

        // Automatically set branch_id when creating new records
        static::creating(function (Model $model) {
            if (! static::hasBranchColumn($model)) {
                return;
            }

            if (! $model->branch_id && static::shouldAutofillBranch()) {
                $model->branch_id = static::getCurrentBranchId();
            }
        });
    }

    /**
     * Apply branch scope to the query builder
     */
    protected static function applyBranchScope(Builder $builder): void
    {
        if (! static::hasBranchColumn($builder->getModel())) {
            return;
        }

        $user = Auth::user();

        // Check if we should bypass the scope
        if (static::shouldBypassBranchScope($user)) {
            return;
        }

        $branchId = static::getCurrentBranchId($user);

        if ($branchId) {
            $builder->where($builder->getModel()->getTable() . '.branch_id', $branchId);
        }
    }

    /**
     * Determine if branch scope should be bypassed for this user
     */
    protected static function shouldBypassBranchScope($user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admin can see all branches
        if ($user->hasRole(['super-admin', 'super_admin'])) {
            return true;
        }

        // Regional Managers can see their region's branches
        // This will be handled by a different mechanism (regional scope)
        if ($user->hasRole(['regional-manager', 'regional_manager'])) {
            return true; // For now, let them see all; refine later with regional filtering
        }

        // Check if branch scope bypass is explicitly enabled in session
        if (session('branch_scope_bypassed', false)) {
            return true;
        }

        return false;
    }

    /**
     * Get the current branch ID for scoping
     */
    protected static function getCurrentBranchId($user = null): ?int
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return null;
        }

        // Check for session-based branch context (for users with multi-branch access)
        $sessionBranchId = session('current_branch_id');
        if ($sessionBranchId) {
            return (int) $sessionBranchId;
        }

        // Use user's primary branch
        if (isset($user->primary_branch_id) && $user->primary_branch_id) {
            return (int) $user->primary_branch_id;
        }

        // Fallback: check BranchWorker relationship
        if ($user->branchWorker && $user->branchWorker->branch_id) {
            return (int) $user->branchWorker->branch_id;
        }

        return null;
    }

    /**
     * Determine if branch_id should be auto-filled on creation
     */
    protected static function shouldAutofillBranch(): bool
    {
        return true;
    }

    protected static function hasBranchColumn(Model $model): bool
    {
        $table = $model->getTable();
        $connection = $model->getConnection();

        return $connection->getSchemaBuilder()->hasColumn($table, 'branch_id');
    }

    /**
     * Query without branch scope (useful for Super Admin operations)
     */
    public static function withoutBranchScope(): Builder
    {
        return static::withoutGlobalScope('branch');
    }

    /**
     * Scope query to specific branch
     */
    public function scopeForBranch(Builder $query, int|Branch $branch): Builder
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;
        
        return $query->withoutGlobalScope('branch')
            ->where($this->getTable() . '.branch_id', $branchId);
    }

    /**
     * Scope query to multiple branches
     */
    public function scopeForBranches(Builder $query, array $branchIds): Builder
    {
        return $query->withoutGlobalScope('branch')
            ->whereIn($this->getTable() . '.branch_id', $branchIds);
    }

    /**
     * Get branch relationship
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Check if model belongs to specific branch
     */
    public function belongsToBranch(int|Branch $branch): bool
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;
        
        return $this->branch_id === $branchId;
    }

    /**
     * Check if current user can access this record based on branch
     */
    public function userCanAccess($user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admin can access everything
        if ($user->hasRole(['super-admin', 'super_admin'])) {
            return true;
        }

        // Regional Managers have access to their region's branches
        if ($user->hasRole(['regional-manager', 'regional_manager'])) {
            // TODO: Implement regional branch checking
            return true;
        }

        // Check if user's branch matches
        $userBranchId = static::getCurrentBranchId($user);
        
        return $this->branch_id === $userBranchId;
    }
}
