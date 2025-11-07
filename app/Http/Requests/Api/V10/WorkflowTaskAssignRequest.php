<?php

namespace App\Http\Requests\Api\V10;

use App\Models\WorkflowTask;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowTaskAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('workflow_task') ?? $this->route('workflowTask');

        if (! $task instanceof WorkflowTask) {
            $task = $this->route('task');
        }

        return $task instanceof WorkflowTask
            ? ($this->user()?->can('assign', $task) ?? false)
            : false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'exists:users,id'],
        ];
    }
}
