<?php

namespace App\Http\Requests\Api\V10;

use App\Models\WorkflowTask;
use App\Models\WorkflowTaskComment;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowTaskCommentRequest extends FormRequest
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

        if ($this->isMethod('post')) {
            return $this->user()?->can('comment', $task) ?? false;
        }

        $comment = $this->route('comment');

        if (! $comment instanceof WorkflowTaskComment) {
            return false;
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return $this->user()?->can('updateComment', [$task, $comment]) ?? false;
        }

        if ($this->isMethod('delete')) {
            return $this->user()?->can('deleteComment', [$task, $comment]) ?? false;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
