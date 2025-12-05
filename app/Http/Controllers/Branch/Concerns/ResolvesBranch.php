<?php

namespace App\Http\Controllers\Branch\Concerns;

use App\Models\Backend\Branch;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesBranch
{
    protected function resolveBranch(Request $request): Branch
    {
        $user = $request->user();
        /** @var Branch|null $branch */
        $branch = $request->attributes->get('branch') ?? $this->resolveBranchForUser($user);

        if (! $branch) {
            abort(403, 'No branch assigned to your account.');
        }

        return $branch;
    }

    protected function resolveBranchForUser(?User $user): ?Branch
    {
        if (! $user) {
            return null;
        }

        if ($user->primary_branch_id) {
            return Branch::find($user->primary_branch_id);
        }

        if ($user->relationLoaded('branchWorker') || method_exists($user, 'branchWorker')) {
            $worker = $user->branchWorker;
            if ($worker?->branch_id) {
                return Branch::find($worker->branch_id);
            }
        }

        if ($user->relationLoaded('branchManager') || method_exists($user, 'branchManager')) {
            $manager = $user->branchManager;
            if ($manager?->branch_id) {
                return Branch::find($manager->branch_id);
            }
        }

        return null;
    }

    protected function branchOptions(User $user)
    {
        if ($user->hasRole(['admin', 'super-admin', 'operations_admin']) || $user->hasPermission('branch_manage')) {
            return Branch::query()
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get();
        }

        return collect();
    }

    protected function assertBranchPermission(User $user): void
    {
        // Allow users with branch permissions
        if ($user->hasPermission(['branch_manage', 'branch_read'])) {
            return;
        }

        // Allow admin roles
        if ($user->hasRole(['admin', 'super-admin', 'operations_admin'])) {
            return;
        }

        // Allow branch managers and workers
        if ($user->hasRole(['branch_manager', 'branch_ops_manager', 'branch_worker', 'branch_staff'])) {
            return;
        }

        // Allow users with a branch association
        if ($user->primary_branch_id) {
            return;
        }

        // Check for branch worker/manager relationship
        if (method_exists($user, 'branchWorker') && $user->branchWorker?->branch_id) {
            return;
        }

        if (method_exists($user, 'branchManager') && $user->branchManager?->branch_id) {
            return;
        }

        abort(403, 'Access denied. You do not have permission to access this resource.');
    }
}
