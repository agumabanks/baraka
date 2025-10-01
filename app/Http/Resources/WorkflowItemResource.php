<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'description' => $this->resource['description'] ?? null,
            'status' => $this->resource['status'],
            'priority' => $this->resource['priority'] ?? null,
            'assignedTo' => $this->resource['assignedTo'] ?? null,
            'dueDate' => $this->resource['dueDate'] ?? null,
            'actionUrl' => $this->resource['actionUrl'] ?? null,
        ];
    }
}