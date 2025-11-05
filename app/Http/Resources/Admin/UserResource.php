<?php

namespace App\Http\Resources\Admin;

use App\Enums\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $role = $this->whenLoaded('role');
        $hub = $this->whenLoaded('hub');
        $department = $this->whenLoaded('department');
        $designation = $this->whenLoaded('designation');

        $permissions = is_array($this->permissions) ? array_values($this->permissions) : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'nid_number' => $this->nid_number,
            'address' => $this->address,
            'salary' => $this->salary,
            'joining_date' => $this->joining_date ? (string) $this->joining_date : null,
            'status' => (int) $this->status,
            'status_label' => (int) $this->status === Status::ACTIVE ? 'active' : 'inactive',
            'avatar' => $this->image,
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'status' => (int) $role->status,
            ] : null,
            'hub' => $hub ? [
                'id' => $hub->id,
                'name' => $hub->name,
            ] : null,
            'department' => $department ? [
                'id' => $department->id,
                'title' => $department->title,
            ] : null,
            'designation' => $designation ? [
                'id' => $designation->id,
                'title' => $designation->title,
            ] : null,
            'permissions' => $permissions,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
