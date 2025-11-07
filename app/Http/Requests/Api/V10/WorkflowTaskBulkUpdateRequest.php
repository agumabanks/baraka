<?php

namespace App\Http\Requests\Api\V10;

use App\Models\WorkflowTask;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowTaskBulkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bulkUpdate', WorkflowTask::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:workflow_tasks,id'],
            'data' => ['required', 'array', 'min:1'],
            'data.status' => ['sometimes', 'string', 'in:' . implode(',', WorkflowTask::STATUSES)],
            'data.assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'data.priority' => ['sometimes', 'string', 'in:' . implode(',', WorkflowTask::PRIORITIES)],
        ];
    }
}
