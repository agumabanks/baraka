<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
            'address' => 'required|string',
            'contact_person' => 'required|string',
            'contact_phone' => 'required|string',
        ];
    }
}
