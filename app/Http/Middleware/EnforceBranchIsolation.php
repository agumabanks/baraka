<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Backend\Branch;

/**
 * EnforceBranchIsolation Middleware
 * 
 * Ensures strict branch data isolation to prevent cross-branch data access.
 * Required for DHL-grade multi-tenant security.
 * 
 * This middleware:
 * - Verifies the user has access to the requested branch
 * - Prevents unauthorized cross-branch data access
 * - Enforces branch-specific RBAC rules
 * - Logs potential security violations
 */
class EnforceBranchIsolation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'Authentication required');
        }

        // Get the branch context from route parameter or session
        $branchId = $this->resolveBranchId($request);
        
        if (!$branchId) {
            abort(400, 'Branch context required');
        }

        // Verify user has access to this branch
        if (!$this->userHasBranchAccess($user, $branchId)) {
            // Log security violation
            \Log::warning('Branch isolation violation attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'requested_branch_id' => $branchId,
                'user_branches' => $this->getUserBranchIds($user),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            abort(403, 'Unauthorized access to branch data');
        }

        // Set branch context for the request
        $request->attributes->set('branch_id', $branchId);
        $request->attributes->set('branch_isolation_enforced', true);

        return $next($request);
    }

    /**
     * Resolve the branch ID from the request
     */
    protected function resolveBranchId(Request $request): ?int
    {
        // Priority 1: Route parameter
        if ($branchId = $request->route('branch')) {
            return is_numeric($branchId) ? (int) $branchId : $branchId->id ?? null;
        }

        // Priority 2: Route parameter 'branch_id'
        if ($branchId = $request->route('branch_id')) {
            return (int) $branchId;
        }

        // Priority 3: Session (legacy and new context keys)
        if ($branchId = session('branch_id')) {
            return (int) $branchId;
        }

        if ($branchId = session('branch_context_id')) {
            return (int) $branchId;
        }

        // Priority 4: User's primary/associated branch
        $user = Auth::user();
        if ($user) {
            if ($user->primary_branch_id) {
                return (int) $user->primary_branch_id;
            }

            if (method_exists($user, 'branchManager') && $user->branchManager?->branch_id) {
                return (int) $user->branchManager->branch_id;
            }

            if (method_exists($user, 'branchWorker') && $user->branchWorker?->branch_id) {
                return (int) $user->branchWorker->branch_id;
            }

            if (method_exists($user, 'current_branch_id')) {
                return $user->current_branch_id;
            }
        }

        return null;
    }

    /**
     * Check if user has access to the specified branch
     */
    protected function userHasBranchAccess($user, int $branchId): bool
    {
        // Super admins have access to all branches
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }

        // Check if user is assigned to this branch
        $userBranchIds = $this->getUserBranchIds($user);
        
        return in_array($branchId, $userBranchIds, true);
    }

    /**
     * Get all branch IDs the user has access to
     */
    protected function getUserBranchIds($user): array
    {
        $branchIds = [];

        // Check direct branch assignment
        if ($user->current_branch_id) {
            $branchIds[] = $user->current_branch_id;
        }

        // Primary branch (legacy field used for managers/supervisors)
        if ($user->primary_branch_id ?? false) {
            $branchIds[] = (int) $user->primary_branch_id;
        }

        // Branch manager assignment
        if (method_exists($user, 'branchManager') && $user->branchManager) {
            $branchIds[] = (int) $user->branchManager->branch_id;
        }

        // Single worker assignment shortcut
        if (method_exists($user, 'branchWorker') && $user->branchWorker) {
            $branchIds[] = (int) $user->branchWorker->branch_id;
        }

        // Branch worker assignments via dedicated relation (if present)
        if (method_exists($user, 'branchWorkerAssignments')) {
            $assignmentBranches = $user->branchWorkerAssignments()
                ->whereNull('unassigned_at')
                ->pluck('branch_id')
                ->toArray();

            $branchIds = array_merge($branchIds, $assignmentBranches);
        }

        // Branch worker assignments (multiple)
        if (method_exists($user, 'branchWorkers')) {
            $workerBranches = $user->branchWorkers()
                ->whereNull('unassigned_at')
                ->pluck('branch_id')
                ->toArray();

            $branchIds = array_merge($branchIds, $workerBranches);
        }

        // Check branch manager assignments
        if (method_exists($user, 'managedBranches')) {
            $managedBranches = $user->managedBranches()
                ->pluck('id')
                ->toArray();
            
            $branchIds = array_merge($branchIds, $managedBranches);
        }

        return array_unique(array_filter($branchIds));
    }
}
