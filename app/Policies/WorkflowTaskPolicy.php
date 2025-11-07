<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowTask;
use App\Models\WorkflowTaskComment;

class WorkflowTaskPolicy
{
    protected function isPrivileged(User $user): bool
    {
        return $user->hasRole(['super-admin', 'admin']);
    }

    protected function canManage(User $user): bool
    {
        if ($this->isPrivileged($user)) {
            return true;
        }

        $managePermissions = [
            'workflow.manage',
            'workflow.update',
            'workflow.board.manage',
            'workflow.board',
            'todo_update',
        ];

        foreach ($managePermissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function canView(User $user): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        $viewPermissions = [
            'workflow.view',
            'workflow.read',
            'workflow.board',
            'todo_read',
            'todo_update',
        ];

        foreach ($viewPermissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, WorkflowTask $task): bool
    {
        if ($this->canView($user)) {
            return true;
        }

        if ($task->assigned_to && $task->assigned_to === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, WorkflowTask $task): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        return $task->assigned_to && $task->assigned_to === $user->id;
    }

    public function delete(User $user, WorkflowTask $task): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, WorkflowTask $task): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, WorkflowTask $task): bool
    {
        return $this->canManage($user);
    }

    public function assign(User $user, WorkflowTask $task): bool
    {
        return $this->canManage($user);
    }

    public function changeStatus(User $user, WorkflowTask $task): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        if ($task->creator_id && $task->creator_id === $user->id) {
            return true;
        }

        if ($task->restricted_roles && is_array($task->restricted_roles)) {
            $roleSlug = strtolower(optional($user->role)->slug ?? optional($user->role)->name ?? '');
            if ($roleSlug && ! in_array($roleSlug, array_map('strtolower', $task->restricted_roles), true)) {
                return false;
            }
        }

        return $task->assigned_to && $task->assigned_to === $user->id;
    }

    public function bulkUpdate(User $user): bool
    {
        return $this->canManage($user);
    }

    public function bulkDelete(User $user): bool
    {
        return $this->canManage($user);
    }

    public function comment(User $user, WorkflowTask $task): bool
    {
        if ($this->canView($user) || $this->canManage($user)) {
            return true;
        }

        return $task->assigned_to && $task->assigned_to === $user->id;
    }

    public function updateComment(User $user, WorkflowTask $task, WorkflowTaskComment $comment): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        if ($task->assigned_to && $task->assigned_to === $user->id) {
            return $comment->user_id === $user->id;
        }

        return $comment->user_id === $user->id;
    }

    public function deleteComment(User $user, WorkflowTask $task, WorkflowTaskComment $comment): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        return $comment->user_id === $user->id;
    }
}
