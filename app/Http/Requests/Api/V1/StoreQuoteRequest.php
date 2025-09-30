<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_branch_id' => 'required|integer|exists:hubs,id',
            'destination_country' => 'required|string|max:2',
            'service_type' => 'required|string|in:standard,express,priority',
            'pieces' => 'required|integer|min:1',
            'weight_kg' => 'required|numeric|min:0.1',
            'volume_cm3' => 'sometimes|numeric|min:1',
            'dim_factor' => 'sometimes|integer|min:1',
            'currency' => 'required|string|size:3',
            'is_remote_area' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'origin_branch_id.required' => 'Origin branch is required',
            'origin_branch_id.exists' => 'Origin branch must exist',
            'destination_country.required' => 'Destination country is required',
            'destination_country.max' => 'Destination country must be 2 characters',
            'service_type.required' => 'Service type is required',
            'service_type.in' => 'Service type must be standard, express, or priority',
            'pieces.required' => 'Number of pieces is required',
            'pieces.min' => 'At least 1 piece is required',
            'weight_kg.required' => 'Weight is required',
            'weight_kg.min' => 'Weight must be at least 0.1 kg',
            'volume_cm3.numeric' => 'Volume must be a number',
            'volume_cm3.min' => 'Volume must be at least 1 cm³',
            'dim_factor.integer' => 'Dimensional factor must be an integer',
            'dim_factor.min' => 'Dimensional factor must be at least 1',
            'currency.required' => 'Currency is required',
            'currency.size' => 'Currency must be 3 characters',
        ];
    }
}
