<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CancelShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'Cancellation reason must not exceed 500 characters',
        ];
    }
}
