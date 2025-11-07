<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for instant quote generation
 */
class InstantQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow all requests for now, add auth logic as needed
    }

    public function rules(): array
    {
        return [
            // Basic shipment information
            'origin' => 'required|string|size:3|in:US,CA,UK,DE,FR,JP,AU',
            'destination' => 'required|string|size:3|in:US,CA,UK,DE,FR,JP,AU',
            'service_level' => 'required|string|in:express,priority,standard,economy',
            
            // Shipment data
            'shipment_data' => 'required|array',
            'shipment_data.weight_kg' => 'required|numeric|min:0.1|max:150',
            'shipment_data.pieces' => 'required|integer|min:1|max:1000',
            
            // Optional shipment details
            'shipment_data.dimensions' => 'sometimes|array',
            'shipment_data.dimensions.length_cm' => 'sometimes|numeric|min:1|max:200',
            'shipment_data.dimensions.width_cm' => 'sometimes|numeric|min:1|max:150',
            'shipment_data.dimensions.height_cm' => 'sometimes|numeric|min:1|max:150',
            'shipment_data.declared_value' => 'sometimes|numeric|min:1|max:100000',
            'shipment_data.contents' => 'sometimes|string|max:500',
            'shipment_data.hazardous' => 'sometimes|boolean',
            'shipment_data.refrigerated' => 'sometimes|boolean',
            'shipment_data.fragile' => 'sometimes|boolean',
            
            // Customer and pricing options
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'contract_id' => 'sometimes|integer|exists:contracts,id',
            'promo_code' => 'sometimes|string|min:3|max:50',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,CAD,JPY,AUD',
            
            // Response customization
            'include_competitor_data' => 'sometimes|boolean',
            'include_alternatives' => 'sometimes|boolean',
            'include_breakdown' => 'sometimes|boolean',
            'response_format' => 'sometimes|string|in:standard,detailed,summary',
            
            // Timing options
            'delivery_date' => 'sometimes|date|after:today',
            'urgent_processing' => 'sometimes|boolean',
            
            // Metadata
            'source' => 'sometimes|string|in:web,api,mobile,partner,admin',
            'reference_id' => 'sometimes|string|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'origin.required' => 'Origin country code is required',
            'origin.size' => 'Origin must be a 3-character country code',
            'destination.required' => 'Destination country code is required',
            'destination.size' => 'Destination must be a 3-character country code',
            'service_level.required' => 'Service level is required',
            'shipment_data.required' => 'Shipment data is required',
            'shipment_data.weight_kg.required' => 'Shipment weight is required',
            'shipment_data.weight_kg.min' => 'Minimum weight is 0.1 kg',
            'shipment_data.weight_kg.max' => 'Maximum weight is 150 kg',
            'shipment_data.pieces.required' => 'Number of pieces is required',
            'shipment_data.pieces.min' => 'Minimum 1 piece required',
            'shipment_data.pieces.max' => 'Maximum 1000 pieces allowed',
            'currency.size' => 'Currency must be a 3-character code',
            'promo_code.min' => 'Promo code must be at least 3 characters',
            'promo_code.max' => 'Promo code cannot exceed 50 characters'
        ];
    }

    public function validated(): array
    {
        $data = parent::validated();
        
        // Set defaults for optional fields
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['source'] = $data['source'] ?? 'api';
        $data['response_format'] = $data['response_format'] ?? 'standard';
        $data['include_breakdown'] = $data['include_breakdown'] ?? true;
        $data['urgent_processing'] = $data['urgent_processing'] ?? false;
        
        return $data;
    }
}