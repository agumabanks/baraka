<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$this->route('customer')->id,
            'mobile' => 'sometimes|string|max:20',
            'user_type' => 'sometimes|string|in:merchant,customer,client',
            'status' => 'sometimes|string|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string',
            'name.max' => 'Name must not exceed 255 characters',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already in use',
            'mobile.string' => 'Mobile must be a string',
            'mobile.max' => 'Mobile must not exceed 20 characters',
            'user_type.in' => 'User type must be merchant, customer, or client',
            'status.in' => 'Status must be active or inactive',
        ];
    }
}
