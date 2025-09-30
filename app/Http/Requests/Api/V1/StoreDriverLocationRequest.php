<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverLocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'locations' => 'required|array',
            'locations.*.latitude' => 'required|numeric',
            'locations.*.longitude' => 'required|numeric',
            'locations.*.timestamp' => 'date',
        ];
    }
}
