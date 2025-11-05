<?php

namespace App\Http\Requests\Api\Admin\Role;

use App\Enums\Status;
use App\Http\Requests\Api\Admin\AdminFormRequest;
use App\Models\Backend\Role;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $role = $this->route('role');
        $roleId = $role instanceof Role ? $role->id : $role;

        return [
            'name' => ['required', 'string', 'max:191', Rule::unique('roles', 'name')->ignore($roleId)],
            'status' => ['required', Rule::in([Status::ACTIVE, Status::INACTIVE])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct'],
        ];
    }
}
