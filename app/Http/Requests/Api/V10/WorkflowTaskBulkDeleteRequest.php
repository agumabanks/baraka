<?php

namespace App\Http\Requests\Api\V10;

use App\Models\WorkflowTask;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowTaskBulkDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bulkDelete', WorkflowTask::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:workflow_tasks,id'],
        ];
    }
}
