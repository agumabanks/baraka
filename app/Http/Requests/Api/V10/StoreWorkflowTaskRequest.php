<?php

namespace App\Http\Requests\Api\V10;

use App\Models\WorkflowTask;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkflowTask::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', WorkflowTask::STATUSES)],
            'priority' => ['required', 'string', 'in:' . implode(',', WorkflowTask::PRIORITIES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'project_id' => ['nullable', 'integer'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', 'string', 'max:255'],
            'status_label' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'metadata' => ['nullable', 'array'],
            'time_tracking' => ['nullable', 'array'],
            'dependencies' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array'],
            'watchers' => ['nullable', 'array'],
            'watchers.*.id' => ['nullable'],
            'watchers.*.name' => ['nullable', 'string', 'max:255'],
            'watchers.*.avatar' => ['nullable', 'string', 'max:2048'],
            'allowed_transitions' => ['nullable', 'array'],
            'restricted_roles' => ['nullable', 'array'],
            'restricted_roles.*' => ['string', 'max:100'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (! isset($data['status'])) {
            $data['status'] = 'pending';
        }

        if (! isset($data['allowed_transitions'])) {
            $data['allowed_transitions'] = null;
        }

        return $data;
    }
}
