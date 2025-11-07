<?php

namespace App\Http\Resources;

use App\Models\WorkflowTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class WorkflowItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isArray = is_array($this->resource);
        $data = $this->resource;

        // Helper to safely get values from model or array
        $get = function ($key, $default = null) use ($isArray, $data) {
            if ($isArray) {
                return Arr::get($data, $key, $default);
            }
            return $data->{$key} ?? $default;
        };

        // Helper to safely get nested values
        $getDeep = function ($path, $default = null) use ($isArray, $data) {
            if ($isArray) {
                return Arr::get($data, $path, $default);
            }
            return data_get($data, $path, $default);
        };

        $assignee = $getDeep('assignee');
        $creator = $getDeep('creator');
        $allowedTransitions = $isArray ? ($data['allowed_transitions'] ?? null) : 
            (method_exists($data, 'allowedTransitions') ? $data->allowedTransitions() : null);

        $watchers = collect($get('watchers', []))
            ->map(function ($watcher) {
                if (is_array($watcher)) {
                    return [
                        'id' => isset($watcher['id']) ? (string) $watcher['id'] : null,
                        'name' => $watcher['name'] ?? null,
                        'avatar' => $watcher['avatar'] ?? null,
                    ];
                }
                if (is_object($watcher)) {
                    return [
                        'id' => isset($watcher->id) ? (string) $watcher->id : null,
                        'name' => $watcher->name ?? null,
                        'avatar' => $watcher->avatar ?? null,
                    ];
                }
                return null;
            })
            ->filter()
            ->values();

        $assigneeName = is_object($assignee) ? $assignee->name : ($assignee['name'] ?? null);
        $assigneeEmail = is_object($assignee) ? $assignee->email : ($assignee['email'] ?? null);
        $assigneeImage = is_object($assignee) ? ($assignee->image ?? null) : ($assignee['image'] ?? null);

        $creatorData = null;
        if ($creator) {
            $creatorId = is_object($creator) ? $creator->getKey() : $creator['id'];
            $creatorName = is_object($creator) ? $creator->name : $creator['name'];
            $creatorEmail = is_object($creator) ? $creator->email : $creator['email'];
            $creatorData = [
                'id' => (string) $creatorId,
                'name' => $creatorName,
                'email' => $creatorEmail,
            ];
        }

        return [
            'id' => (string) ($isArray ? $data['id'] : $data->getKey()),
            'title' => $get('title'),
            'description' => $get('description'),
            'status' => $get('status'),
            'priority' => $get('priority'),
            'assignedTo' => $assigneeName,
            'assigned_user_name' => $assigneeName,
            'assignedUserId' => $get('assigned_to') ? (string) $get('assigned_to') : null,
            'assigned_user_id' => $get('assigned_to') ? (string) $get('assigned_to') : null,
            'assignedUserEmail' => $assigneeEmail,
            'assignedUserAvatar' => $assigneeImage,
            'creator' => $creatorData,
            'trackingNumber' => $get('tracking_number'),
            'dueDate' => $getDeep('due_at'),
            'completedAt' => $getDeep('completed_at'),
            'lastStatusAt' => $getDeep('last_status_at'),
            'projectId' => $get('project_id') ? (string) $get('project_id') : null,
            'project' => $get('project_name'),
            'client' => $get('client'),
            'stage' => $get('stage'),
            'statusLabel' => $get('status_label'),
            'status_label' => $get('status_label'),
            'tags' => $get('tags', []),
            'metadata' => $get('metadata', []),
            'timeTracking' => $get('time_tracking', []),
            'time_tracking' => $get('time_tracking', []),
            'dependencies' => $get('dependencies', []),
            'attachments' => $get('attachments', []),
            'watchers' => $watchers,
            'allowedTransitions' => $allowedTransitions,
            'allowed_transitions' => $allowedTransitions,
            'restrictedRoles' => $isArray ? [] : (method_exists($data, 'restrictableRoles') ? $data->restrictableRoles() : []),
            'restricted_roles' => $isArray ? [] : (method_exists($data, 'restrictableRoles') ? $data->restrictableRoles() : []),
            'commentsCount' => $get('comments_count'),
            'comments_count' => $get('comments_count'),
            'activityCount' => $get('activity_count'),
            'activity_count' => $get('activity_count'),
            'actionUrl' => null,
        ];
    }
}