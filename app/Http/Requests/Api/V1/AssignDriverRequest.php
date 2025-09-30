<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AssignDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_id' => 'required|integer|exists:shipments,id',
            'driver_id' => 'required|integer|exists:delivery_man,id',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'scheduled_at' => 'sometimes|date',
            'notes' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'shipment_id.required' => 'Shipment ID is required',
            'shipment_id.exists' => 'Shipment must exist',
            'driver_id.required' => 'Driver ID is required',
            'driver_id.exists' => 'Driver must exist',
            'priority.in' => 'Priority must be low, normal, high, or urgent',
            'scheduled_at.date' => 'Scheduled time must be a valid date',
            'notes.max' => 'Notes must not exceed 500 characters',
        ];
    }
}
