<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'OTP is required',
            'otp.size' => 'OTP must be 6 characters',
        ];
    }
}