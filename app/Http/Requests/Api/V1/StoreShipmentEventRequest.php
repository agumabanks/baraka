<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sscc' => 'required|string',
            'type' => 'required|string|in:out_for_delivery,delivered,exception,rto',
            'branch_id' => 'required|integer|exists:hubs,id',
            'location' => 'sometimes|array',
            'location.latitude' => 'required_with:location|numeric|between:-90,90',
            'location.longitude' => 'required_with:location|numeric|between:-180,180',
            'note' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'sscc.required' => 'SSCC is required',
            'type.required' => 'Event type is required',
            'type.in' => 'Event type must be out_for_delivery, delivered, exception, or rto',
            'branch_id.required' => 'Branch ID is required',
            'branch_id.exists' => 'Branch must exist',
            'location.array' => 'Location must be an array',
            'location.latitude.required_with' => 'Latitude is required when location is provided',
            'location.longitude.required_with' => 'Longitude is required when location is provided',
            'note.max' => 'Note must not exceed 500 characters',
        ];
    }
}
