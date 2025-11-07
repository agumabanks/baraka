<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for contract management
 */
class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        
        $baseRules = [
            'customer_id' => 'required|integer|exists:customers,id',
            'name' => 'required|string|max:255',
            'contract_type' => 'required|string|in:standard,premium,enterprise,custom',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'volume_commitment' => 'sometimes|numeric|min:0',
            'discount_structure' => 'sometimes|array',
            'special_terms' => 'sometimes|string|max:2000',
            'auto_renewal' => 'sometimes|boolean',
            'notification_settings' => 'sometimes|array',
            'template_id' => 'sometimes|integer|exists:contract_templates,id',
            'billing_terms' => 'sometimes|array',
            'service_level_agreements' => 'sometimes|array',
            'penalty_clauses' => 'sometimes|array',
            'metadata' => 'sometimes|array'
        ];

        // For updates, make some fields optional
        if ($isUpdate) {
            $baseRules['name'] = 'sometimes|string|max:255';
            $baseRules['contract_type'] = 'sometimes|string|in:standard,premium,enterprise,custom';
            $baseRules['start_date'] = 'sometimes|date|after:today';
            $baseRules['end_date'] = 'sometimes|date|after:start_date';
        }

        return $baseRules;
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer ID is required',
            'customer_id.exists' => 'Selected customer does not exist',
            'name.required' => 'Contract name is required',
            'contract_type.required' => 'Contract type is required',
            'start_date.required' => 'Start date is required',
            'start_date.after' => 'Start date must be in the future',
            'end_date.required' => 'End date is required',
            'end_date.after' => 'End date must be after start date',
            'volume_commitment.min' => 'Volume commitment cannot be negative',
            'special_terms.max' => 'Special terms cannot exceed 2000 characters'
        ];
    }
}