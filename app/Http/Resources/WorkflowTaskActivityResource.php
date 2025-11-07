<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowTaskActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $this->actor;
        $task = $this->task;

        return [
            'id' => (string) $this->getKey(),
            'action' => $this->action,
            'details' => $this->details ?? [],
            'createdAt' => optional($this->created_at)?->toIso8601String(),
            'actor' => $actor ? [
                'id' => (string) $actor->getKey(),
                'name' => $actor->name,
                'email' => $actor->email,
                'avatar' => $actor->image ?? null,
            ] : null,
            'task' => $task ? [
                'id' => (string) $task->getKey(),
                'title' => $task->title,
                'status' => $task->status,
            ] : null,
        ];
    }
}
