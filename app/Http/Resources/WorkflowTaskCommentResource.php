<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowTaskCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $author = $this->author;

        return [
            'id' => (string) $this->getKey(),
            'body' => $this->body,
            'metadata' => $this->metadata ?? [],
            'createdAt' => optional($this->created_at)?->toIso8601String(),
            'updatedAt' => optional($this->updated_at)?->toIso8601String(),
            'author' => $author ? [
                'id' => (string) $author->getKey(),
                'name' => $author->name,
                'email' => $author->email,
                'avatar' => $author->image ?? null,
            ] : null,
        ];
    }
}
