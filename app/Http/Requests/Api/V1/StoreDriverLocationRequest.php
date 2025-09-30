<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverLocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'locations' => 'required|array|min:1|max:100',
            'locations.*.latitude' => 'required|numeric|between:-90,90',
            'locations.*.longitude' => 'required|numeric|between:-180,180',
            'locations.*.timestamp' => 'sometimes|date',
            'locations.*.accuracy' => 'sometimes|numeric|min:0',
            'locations.*.speed' => 'sometimes|numeric|min:0',
            'locations.*.heading' => 'sometimes|numeric|between:0,360',
        ];
    }
}
