<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:created,handed_over,arrive,sort,load,depart,in_transit,arrive_dest,out_for_delivery,delivered,cancelled',
            'reason' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status value',
            'reason.max' => 'Reason must not exceed 500 characters',
        ];
    }
}
