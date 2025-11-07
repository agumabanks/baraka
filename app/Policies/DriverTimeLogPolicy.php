<?php

namespace App\Policies;

use App\Models\DriverTimeLog;
use App\Models\User;

class DriverTimeLogPolicy
{
    protected function canManage(User $user): bool
    {
        return $user->hasRole(['admin', 'operations_admin'])
            || $user->hasPermission('driver_manage');
    }

    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, DriverTimeLog $log): bool
    {
        return $this->canManage($user);
    }
}
