<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|string',
            'occurred_at' => 'date',
            'location' => 'string',
            'notes' => 'string',
        ];
    }
}
