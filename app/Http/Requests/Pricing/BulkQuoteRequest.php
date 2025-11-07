<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for bulk quote generation
 */
class BulkQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_requests' => 'required|array|min:1|max:100',
            'shipment_requests.*.origin' => 'required|string|size:3|in:US,CA,UK,DE,FR,JP,AU',
            'shipment_requests.*.destination' => 'required|string|size:3|in:US,CA,UK,DE,FR,JP,AU',
            'shipment_requests.*.service_level' => 'required|string|in:express,priority,standard,economy',
            'shipment_requests.*.shipment_data' => 'required|array',
            'shipment_requests.*.shipment_data.weight_kg' => 'required|numeric|min:0.1|max:150',
            'shipment_requests.*.shipment_data.pieces' => 'required|integer|min:1|max:1000',
            'shipment_requests.*.shipment_data.dimensions' => 'sometimes|array',
            'shipment_requests.*.shipment_data.declared_value' => 'sometimes|numeric|min:1|max:100000',
            'shipment_requests.*.customer_reference' => 'sometimes|string|max:100',
            
            // Global options
            'customer_id' => 'sometimes|integer|exists:customers,id',
            'contract_id' => 'sometimes|integer|exists:contracts,id',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,CAD,JPY,AUD',
            'include_analytics' => 'sometimes|boolean',
            'optimization_mode' => 'sometimes|string|in:cost,time,reliability,balanced',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'callback_url' => 'sometimes|url',
            'batch_name' => 'sometimes|string|max:255',
            'notify_on_completion' => 'sometimes|boolean',
            'include_service_alternatives' => 'sometimes|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'shipment_requests.required' => 'Shipment requests are required',
            'shipment_requests.min' => 'At least 1 shipment request required',
            'shipment_requests.max' => 'Maximum 100 shipment requests allowed per batch',
            'shipment_requests.*.origin.required' => 'Origin is required for each shipment',
            'shipment_requests.*.destination.required' => 'Destination is required for each shipment',
            'shipment_requests.*.service_level.required' => 'Service level is required for each shipment',
            'shipment_requests.*.shipment_data.required' => 'Shipment data is required for each shipment',
            'shipment_requests.*.shipment_data.weight_kg.required' => 'Weight is required for each shipment',
            'callback_url.url' => 'Callback URL must be a valid URL',
            'priority.in' => 'Priority must be one of: low, normal, high, urgent',
            'optimization_mode.in' => 'Optimization mode must be one of: cost, time, reliability, balanced'
        ];
    }
}