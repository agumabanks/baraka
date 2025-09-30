<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_branch_id' => 'required|integer|exists:hubs,id',
            'dest_branch_id' => 'required|integer|exists:hubs,id',
            'service_level' => 'required|string|in:standard,express,priority',
            'incoterm' => 'required|string|in:DDP,DAP,EXW',
            'price_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'metadata' => 'sometimes|array',
        ];
    }

    public function messages(): array
    {
        return [
            'origin_branch_id.required' => 'Origin branch is required',
            'dest_branch_id.required' => 'Destination branch is required',
            'service_level.required' => 'Service level is required',
            'service_level.in' => 'Service level must be standard, express, or priority',
            'incoterm.required' => 'Incoterm is required',
            'incoterm.in' => 'Incoterm must be DDP, DAP, or EXW',
            'price_amount.required' => 'Price amount is required',
            'price_amount.numeric' => 'Price amount must be a number',
            'price_amount.min' => 'Price amount must be greater than 0',
            'currency.required' => 'Currency is required',
            'currency.size' => 'Currency must be 3 characters',
        ];
    }
}
