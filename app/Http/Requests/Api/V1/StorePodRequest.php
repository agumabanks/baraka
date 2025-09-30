<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'notes' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'signature.required' => 'Signature is required',
            'signature.image' => 'Signature must be an image',
            'signature.mimes' => 'Signature must be jpeg, png, or jpg',
            'signature.max' => 'Signature must not exceed 2MB',
            'photo.required' => 'Photo is required',
            'photo.image' => 'Photo must be an image',
            'photo.mimes' => 'Photo must be jpeg, png, or jpg',
            'photo.max' => 'Photo must not exceed 5MB',
            'notes.max' => 'Notes must not exceed 500 characters',
        ];
    }
}
