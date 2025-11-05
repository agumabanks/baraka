<?php

namespace App\Http\Requests\Api\Admin\User;

use App\Enums\Status;
use App\Http\Requests\Api\Admin\AdminFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : $user;

        $rules = [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'mobile' => ['required', 'numeric', 'digits_between:11,14', Rule::unique('users', 'mobile')->ignore($userId)],
            'nid_number' => ['nullable', 'numeric', 'digits_between:1,20'],
            'designation_id' => ['required', 'exists:designations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'hub_id' => ['nullable', 'exists:hubs,id'],
            'joining_date' => ['required', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'address' => ['required', 'string', 'max:191'],
            'status' => ['required', Rule::in([Status::ACTIVE, Status::INACTIVE])],
            'password' => ['nullable', 'string', 'min:8'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];

        if ($user instanceof User && $user->id === 1) {
            $rules['designation_id'] = ['sometimes', 'exists:designations,id'];
            $rules['department_id'] = ['sometimes', 'exists:departments,id'];
            $rules['hub_id'] = ['sometimes', 'nullable', 'exists:hubs,id'];
            $rules['status'] = ['sometimes', Rule::in([Status::ACTIVE, Status::INACTIVE])];
        }

        return $rules;
    }
}
