<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'location' => 'required|string',
            'notes' => 'string',
            'signature' => 'file|mimes:png,jpg,jpeg',
        ];
    }
}
