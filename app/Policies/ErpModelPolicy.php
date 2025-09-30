<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ErpModelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['hq_admin', 'branch_ops_manager', 'admin', 'super-admin', 'support', 'finance', 'driver']);
    }

    public function view(User $user, $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['hq_admin', 'branch_ops_manager', 'admin']);
    }

    public function update(User $user, $model): bool
    {
        return $user->hasRole(['hq_admin', 'branch_ops_manager', 'admin']);
    }

    public function delete(User $user, $model): bool
    {
        return $user->hasRole(['hq_admin']);
    }
}
