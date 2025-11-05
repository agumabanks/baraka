<?php

namespace App\Http\Requests\Api\Admin\Role;

use App\Enums\Status;
use App\Http\Requests\Api\Admin\AdminFormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191', 'unique:roles,name'],
            'status' => ['required', Rule::in([Status::ACTIVE, Status::INACTIVE])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct'],
        ];
    }
}
