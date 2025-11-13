<?php

namespace App\Http\Requests\Api\Admin\User;

use App\Enums\Status;
use App\Http\Requests\Api\Admin\AdminFormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'mobile' => ['required', 'numeric', 'digits_between:11,14', 'unique:users,mobile'],
            'nid_number' => ['nullable', 'numeric', 'digits_between:1,20'],
            'designation_id' => ['required', 'exists:designations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'hub_id' => ['nullable', 'exists:hubs,id'],
            'primary_branch_id' => ['nullable', 'exists:branches,id'],
            'joining_date' => ['required', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'address' => ['required', 'string', 'max:191'],
            'status' => ['required', Rule::in([Status::ACTIVE, Status::INACTIVE])],
            'preferred_language' => ['nullable', 'in:en,fr,sw'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }
}
