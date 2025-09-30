<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchScopedPolicy
{
    use HandlesAuthorization;

    protected array $viewAnyRoles = [
        'hq_admin', 'branch_ops_manager', 'branch_ops', 'admin', 'super-admin', 'support', 'finance',
    ];

    protected array $writeRoles = [
        'hq_admin', 'branch_ops_manager', 'branch_ops', 'admin',
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole($this->viewAnyRoles);
    }

    public function view(User $user, $model): bool
    {
        if ($user->hasRole(['hq_admin', 'admin', 'super-admin'])) {
            return true;
        }

        $hubId = $user->hub_id;
        if (is_null($hubId)) {
            return $this->viewAny($user);
        }

        $ids = $this->extractHubIds($model);

        return in_array($hubId, $ids, true);
    }

    public function create(User $user): bool
    {
        return $user->hasRole($this->writeRoles);
    }

    public function update(User $user, $model): bool
    {
        if ($user->hasRole(['hq_admin', 'admin', 'super-admin'])) {
            return true;
        }

        return $user->hasRole($this->writeRoles) && $this->view($user, $model);
    }

    public function delete(User $user, $model): bool
    {
        return $user->hasRole(['hq_admin']);
    }

    private function extractHubIds($model): array
    {
        $fields = ['hub_id', 'branch_id', 'origin_branch_id', 'destination_branch_id'];
        $ids = [];
        foreach ($fields as $f) {
            if (isset($model->{$f}) && ! is_null($model->{$f})) {
                $ids[] = (int) $model->{$f};
            }
        }

        return array_values(array_unique($ids));
    }
}
