<?php

namespace App\Policies;

use App\Models\DriverRoster;
use App\Models\User;

class DriverRosterPolicy
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

    public function view(User $user, DriverRoster $driverRoster): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, DriverRoster $driverRoster): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, DriverRoster $driverRoster): bool
    {
        return $this->canManage($user);
    }
}
