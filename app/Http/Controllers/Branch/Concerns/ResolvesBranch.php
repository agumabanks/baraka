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
        if ($user->hasPermission(['branch_manage', 'branch_read']) || $user->hasRole(['admin', 'super-admin', 'operations_admin'])) {
            return;
        }

        abort(403);
    }
}
