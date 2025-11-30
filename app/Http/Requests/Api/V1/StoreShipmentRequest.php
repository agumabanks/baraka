<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required fields
            'origin_branch_id' => 'required|integer|exists:branches,id',
            'dest_branch_id' => 'required|integer|exists:branches,id|different:origin_branch_id',
            'customer_id' => 'required|integer|exists:users,id',
            'service_level' => 'required|string|in:standard,express,priority,economy',
            
            // Weight validation (critical fix)
            'weight' => 'required|numeric|min:0.01|max:10000',
            'weight_unit' => 'sometimes|string|in:kg,lb',
            
            // Dimensions validation
            'length' => 'nullable|numeric|min:0.1|max:500',
            'width' => 'nullable|numeric|min:0.1|max:500',
            'height' => 'nullable|numeric|min:0.1|max:500',
            'dimension_unit' => 'sometimes|string|in:cm,in',
            
            // Multi-parcel support
            'parcels' => 'sometimes|array|min:1|max:999',
            'parcels.*.weight_kg' => 'required_with:parcels|numeric|min:0.01|max:10000',
            'parcels.*.length_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.width_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.height_cm' => 'nullable|numeric|min:0.1|max:500',
            'parcels.*.description' => 'nullable|string|max:500',
            'parcels.*.declared_value' => 'nullable|numeric|min:0|max:9999999.99',
            
            // Financial fields
            'price_amount' => 'nullable|numeric|min:0|max:9999999.99',
            'currency' => 'required|string|size:3',
            'declared_value' => 'nullable|numeric|min:0|max:9999999.99',
            'insurance_amount' => 'nullable|numeric|min:0|max:9999999.99',
            'insurance_type' => 'nullable|string|in:none,basic,full,premium',
            'cod_amount' => 'nullable|numeric|min:0|max:9999999.99',
            
            // Trade terms
            'incoterm' => 'nullable|string|in:DDP,DAP,EXW,FCA,CPT,CIP,FOB,CFR,CIF',
            'payer_type' => 'nullable|string|in:sender,receiver,third_party',
            
            // SLA and priority
            'priority' => 'nullable|string|in:low,normal,high,urgent,critical',
            'expected_delivery_date' => 'nullable|date|after:today',
            'sla_days' => 'nullable|integer|min:1|max:365',
            
            // Additional info
            'special_instructions' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:100',
            'metadata' => 'sometimes|array',
            
            // Addresses
            'pickup_address' => 'nullable|string|max:500',
            'delivery_address' => 'nullable|string|max:500',
            'pickup_contact_name' => 'nullable|string|max:255',
            'pickup_contact_phone' => 'nullable|string|max:50',
            'delivery_contact_name' => 'nullable|string|max:255',
            'delivery_contact_phone' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            // Origin/Destination
            'origin_branch_id.required' => 'Origin branch is required',
            'origin_branch_id.exists' => 'Selected origin branch does not exist',
            'dest_branch_id.required' => 'Destination branch is required',
            'dest_branch_id.exists' => 'Selected destination branch does not exist',
            'dest_branch_id.different' => 'Destination must be different from origin',
            
            // Customer
            'customer_id.required' => 'Customer is required',
            'customer_id.exists' => 'Selected customer does not exist',
            
            // Service level
            'service_level.required' => 'Service level is required',
            'service_level.in' => 'Service level must be: standard, express, priority, or economy',
            
            // Weight validation messages (critical)
            'weight.required' => 'Shipment weight is required',
            'weight.numeric' => 'Weight must be a valid number',
            'weight.min' => 'Weight must be at least 0.01 kg',
            'weight.max' => 'Weight cannot exceed 10,000 kg',
            
            // Parcel weight validation
            'parcels.*.weight_kg.required_with' => 'Each parcel must have a weight',
            'parcels.*.weight_kg.numeric' => 'Parcel weight must be a valid number',
            'parcels.*.weight_kg.min' => 'Parcel weight must be at least 0.01 kg',
            'parcels.*.weight_kg.max' => 'Parcel weight cannot exceed 10,000 kg',
            
            // Dimensions
            'length.numeric' => 'Length must be a valid number',
            'length.min' => 'Length must be at least 0.1 cm',
            'length.max' => 'Length cannot exceed 500 cm',
            'width.numeric' => 'Width must be a valid number',
            'width.min' => 'Width must be at least 0.1 cm',
            'width.max' => 'Width cannot exceed 500 cm',
            'height.numeric' => 'Height must be a valid number',
            'height.min' => 'Height must be at least 0.1 cm',
            'height.max' => 'Height cannot exceed 500 cm',
            
            // Financial
            'price_amount.numeric' => 'Price amount must be a number',
            'price_amount.min' => 'Price amount cannot be negative',
            'currency.required' => 'Currency is required',
            'currency.size' => 'Currency must be a 3-letter code (e.g., USD, EUR)',
            'declared_value.numeric' => 'Declared value must be a number',
            'insurance_amount.numeric' => 'Insurance amount must be a number',
            'cod_amount.numeric' => 'COD amount must be a number',
            
            // Trade terms
            'incoterm.in' => 'Invalid Incoterm. Valid options: DDP, DAP, EXW, FCA, CPT, CIP, FOB, CFR, CIF',
            'payer_type.in' => 'Payer type must be: sender, receiver, or third_party',
            
            // SLA
            'priority.in' => 'Priority must be: low, normal, high, urgent, or critical',
            'expected_delivery_date.date' => 'Invalid delivery date format',
            'expected_delivery_date.after' => 'Expected delivery date must be in the future',
        ];
    }

    /**
     * Handle a failed validation attempt - return JSON response
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Prepare data for validation - normalize weight units
     */
    protected function prepareForValidation()
    {
        // Convert pounds to kg if needed
        if ($this->weight_unit === 'lb' && $this->weight) {
            $this->merge([
                'weight' => round($this->weight * 0.453592, 2),
            ]);
        }

        // Convert inches to cm if needed
        if ($this->dimension_unit === 'in') {
            $this->merge([
                'length' => $this->length ? round($this->length * 2.54, 1) : null,
                'width' => $this->width ? round($this->width * 2.54, 1) : null,
                'height' => $this->height ? round($this->height * 2.54, 1) : null,
            ]);
        }

        // Normalize service level to lowercase
        if ($this->service_level) {
            $this->merge([
                'service_level' => strtolower($this->service_level),
            ]);
        }
    }
}
