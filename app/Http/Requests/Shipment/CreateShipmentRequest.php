<?php

namespace App\Http\Requests\Shipment;

use App\Enums\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared FormRequest for Shipment Creation
 * 
 * Used by both Admin and Branch modules to ensure consistent
 * validation rules for shipment creation across the system.
 * 
 * DHL-Grade compliance: Uses branches table (not hubs) for location references.
 */
class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }

        // Admin users can create shipments for any branch
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Branch users can create shipments for their branch
        if ($this->filled('origin_branch_id') && $user->current_branch_id) {
            return (int) $this->input('origin_branch_id') === $user->current_branch_id;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            // Customer info
            'customer_id' => 'nullable|integer|exists:users,id',
            'client_id' => 'nullable|integer|exists:customers,id',
            
            // Branch references - use branches table (not hubs)
            'origin_branch_id' => 'required|integer|exists:branches,id',
            'dest_branch_id' => 'required|integer|exists:branches,id|different:origin_branch_id',
            
            // Service details
            'service_level' => 'required|string|in:standard,express,priority,STANDARD,EXPRESS,PRIORITY,economy,ECONOMY',
            'incoterms' => 'nullable|string|in:DDP,DAP,EXW,FOB,CIF',
            'payer_type' => 'nullable|string|in:sender,receiver,third_party',
            
            // Pricing
            'price_amount' => 'nullable|numeric|min:0|max:999999.99',
            'declared_value' => 'nullable|numeric|min:0|max:999999.99',
            'insurance_amount' => 'nullable|numeric|min:0|max:999999.99',
            'customs_value' => 'nullable|numeric|min:0|max:999999.99',
            'currency' => 'required|string|size:3',
            
            // Dimensions
            'chargeable_weight_kg' => 'nullable|numeric|min:0|max:9999.99',
            'volume_cbm' => 'nullable|numeric|min:0|max:999.9999',
            
            // Instructions
            'special_instructions' => 'nullable|string|max:1000',
            'expected_delivery_date' => 'nullable|date|after:today',
            
            // Metadata
            'metadata' => 'nullable|array',
            
            // Parcels (for multi-parcel shipments)
            'parcels' => 'nullable|array',
            'parcels.*.description' => 'required_with:parcels|string|max:255',
            'parcels.*.weight_kg' => 'required_with:parcels|numeric|min:0.01|max:999.99',
            'parcels.*.length_cm' => 'nullable|numeric|min:0|max:999',
            'parcels.*.width_cm' => 'nullable|numeric|min:0|max:999',
            'parcels.*.height_cm' => 'nullable|numeric|min:0|max:999',
            'parcels.*.quantity' => 'nullable|integer|min:1|max:999',
        ];
    }

    public function messages(): array
    {
        return [
            'origin_branch_id.required' => 'Origin branch is required.',
            'origin_branch_id.exists' => 'Selected origin branch does not exist.',
            'dest_branch_id.required' => 'Destination branch is required.',
            'dest_branch_id.exists' => 'Selected destination branch does not exist.',
            'dest_branch_id.different' => 'Destination must be different from origin.',
            'service_level.required' => 'Service level is required.',
            'service_level.in' => 'Invalid service level. Must be standard, express, or priority.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be a 3-letter code (e.g., USD, UGX).',
            'expected_delivery_date.after' => 'Expected delivery date must be in the future.',
        ];
    }

    /**
     * Prepare data for shipment creation
     */
    public function prepareForShipment(): array
    {
        $data = $this->validated();
        
        // Normalize service level to uppercase
        if (isset($data['service_level'])) {
            $data['service_level'] = strtoupper($data['service_level']);
        }

        // Set default status
        $data['current_status'] = ShipmentStatus::BOOKED->value;
        $data['status'] = 'booked';
        $data['booked_at'] = now();
        
        // Set created_by if not set
        if (!isset($data['created_by']) && $this->user()) {
            $data['created_by'] = $this->user()->id;
        }

        return $data;
    }
}
