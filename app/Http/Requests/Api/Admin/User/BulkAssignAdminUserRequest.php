<?php

namespace App\Http\Requests\Api\Admin\User;

use App\Enums\Status as StatusEnum;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAssignAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('user_update') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(static function ($query) {
                    return $query->where('user_type', UserType::ADMIN);
                }),
            ],
            'role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            'hub_id' => ['sometimes', 'nullable', 'integer', 'exists:hubs,id'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'designation_id' => ['sometimes', 'nullable', 'integer', 'exists:designations,id'],
            'status' => ['sometimes', 'nullable', 'integer', Rule::in([StatusEnum::ACTIVE, StatusEnum::INACTIVE])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $mutableFields = ['role_id', 'hub_id', 'department_id', 'designation_id', 'status'];

            $hasAssignment = collect($mutableFields)->some(fn ($field) => $this->has($field));

            if (! $hasAssignment) {
                $validator->errors()->add('assignments', 'Provide at least one assignment field to update.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Select at least one user to update.',
            'user_ids.*.exists' => 'One or more selected users are invalid.',
        ];
    }
}
