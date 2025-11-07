<?php

namespace App\Http\Requests\Api\V10;

use App\Models\WorkflowTask;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('workflow_task') ?? $this->route('workflowTask');

        if (! $task instanceof WorkflowTask) {
            $task = $this->route('task');
        }

        return $task instanceof WorkflowTask
            ? ($this->user()?->can('changeStatus', $task) ?? false)
            : false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . implode(',', WorkflowTask::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
