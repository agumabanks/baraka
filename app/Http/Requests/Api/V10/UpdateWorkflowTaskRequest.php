<?php

namespace App\Http\Requests\Api\V10;

use App\Models\WorkflowTask;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('workflow_task') ?? $this->route('workflowTask');

        if (! $task instanceof WorkflowTask) {
            $task = $this->route('task');
        }

        if (! $task instanceof WorkflowTask) {
            return false;
        }

        return $this->user()?->can('update', $task) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', WorkflowTask::STATUSES)],
            'priority' => ['sometimes', 'string', 'in:' . implode(',', WorkflowTask::PRIORITIES)],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'project_id' => ['sometimes', 'nullable', 'integer'],
            'project_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stage' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'client' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'time_tracking' => ['sometimes', 'nullable', 'array'],
            'dependencies' => ['sometimes', 'nullable', 'array'],
            'attachments' => ['sometimes', 'nullable', 'array'],
            'watchers' => ['sometimes', 'nullable', 'array'],
            'watchers.*.id' => ['nullable'],
            'watchers.*.name' => ['nullable', 'string', 'max:255'],
            'watchers.*.avatar' => ['nullable', 'string', 'max:2048'],
            'allowed_transitions' => ['sometimes', 'nullable', 'array'],
            'restricted_roles' => ['sometimes', 'nullable', 'array'],
            'restricted_roles.*' => ['string', 'max:100'],
        ];
    }
}
