<?php

namespace App\Http\Resources\Admin;

use App\Enums\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $permissions = is_array($this->permissions) ? array_values($this->permissions) : [];
        $usersCount = $this->users_count
            ?? ($this->relationLoaded('users') ? $this->users->count() : 0);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => (int) $this->status,
            'status_label' => (int) $this->status === Status::ACTIVE ? 'active' : 'inactive',
            'permissions' => $permissions,
            'permissions_count' => count($permissions),
            'users_count' => $usersCount,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
