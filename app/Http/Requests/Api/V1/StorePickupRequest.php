<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_date' => 'required|date|after:today',
            'pickup_time' => 'required|string',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'instructions' => 'sometimes|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_date.required' => 'Pickup date is required',
            'pickup_date.date' => 'Pickup date must be a valid date',
            'pickup_date.after' => 'Pickup date must be after today',
            'pickup_time.required' => 'Pickup time is required',
            'contact_person.required' => 'Contact person is required',
            'contact_person.max' => 'Contact person name must not exceed 255 characters',
            'contact_phone.required' => 'Contact phone is required',
            'contact_phone.max' => 'Contact phone must not exceed 20 characters',
            'address.required' => 'Address is required',
            'address.max' => 'Address must not exceed 500 characters',
            'instructions.max' => 'Instructions must not exceed 1000 characters',
        ];
    }
}
