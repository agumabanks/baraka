<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;

class DriverPolicy
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

    public function view(User $user, Driver $driver): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Driver $driver): bool
    {
        return $this->canManage($user);
    }

    public function toggleStatus(User $user, Driver $driver): bool
    {
        return $this->canManage($user);
    }
}
