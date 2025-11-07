<?php

namespace App\Policies;

use App\Models\Backend\Branch;
use App\Models\User;

class BranchPolicy
{
    protected function canManage(User $user): bool
    {
        return $user->hasRole(['admin', 'operations_admin'])
            || $user->hasPermission('branch_manage');
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Branch $branch): bool
    {
        return $this->canManage($user);
    }

    public function toggleStatus(User $user, Branch $branch): bool
    {
        return $this->canManage($user);
    }
}
