<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AssignDriverRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'driver_id' => 'required|exists:delivery_men,id',
        ];
    }
}
