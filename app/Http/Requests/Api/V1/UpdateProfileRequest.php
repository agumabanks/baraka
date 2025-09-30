<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'mobile' => 'sometimes|string|max:20',
            'notification_prefs' => 'sometimes|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string',
            'name.max' => 'Name must not exceed 255 characters',
            'mobile.string' => 'Mobile must be a string',
            'mobile.max' => 'Mobile must not exceed 20 characters',
            'notification_prefs.array' => 'Notification preferences must be an array',
        ];
    }
}
