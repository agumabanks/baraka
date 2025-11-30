<?php

namespace App\Http\Requests\Shipment;

use App\Enums\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared FormRequest for Shipment Status Updates
 * 
 * Used by both Admin and Branch modules to ensure consistent
 * validation rules for shipment status transitions.
 */
class UpdateShipmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shipment = $this->route('shipment');
        
        // Admin users can update any shipment
        if ($this->user()->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Branch users can only update shipments in their branch
        if ($shipment && $this->user()->current_branch_id) {
            return $shipment->origin_branch_id === $this->user()->current_branch_id
                || $shipment->dest_branch_id === $this->user()->current_branch_id;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(array_map(fn($case) => $case->value, ShipmentStatus::cases())),
            ],
            'notes' => 'nullable|string|max:1000',
            'location_type' => 'nullable|string|in:branch,hub,warehouse,vehicle',
            'location_id' => 'nullable|integer|exists:branches,id',
            'performed_by' => 'nullable|integer|exists:users,id',
            'timestamp' => 'nullable|date',
            'force' => 'nullable|boolean',
            
            // Exception-specific fields
            'exception_type' => 'nullable|required_if:status,EXCEPTION|string|max:100',
            'exception_severity' => 'nullable|required_if:status,EXCEPTION|string|in:LOW,MEDIUM,HIGH,CRITICAL',
            'exception_notes' => 'nullable|string|max:1000',
            
            // Return-specific fields
            'return_reason' => 'nullable|required_if:status,RETURN_INITIATED|string|max:500',
            'return_notes' => 'nullable|string|max:1000',
            
            // Proof of delivery fields
            'pod_signature' => 'nullable|required_if:status,DELIVERED|string',
            'pod_photo' => 'nullable|file|image|max:5120',
            'recipient_name' => 'nullable|required_if:status,DELIVERED|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Shipment status is required.',
            'status.in' => 'Invalid shipment status value.',
            'exception_type.required_if' => 'Exception type is required when marking as exception.',
            'exception_severity.required_if' => 'Exception severity is required when marking as exception.',
            'return_reason.required_if' => 'Return reason is required when initiating a return.',
            'pod_signature.required_if' => 'Proof of delivery signature is required for delivered shipments.',
            'recipient_name.required_if' => 'Recipient name is required for delivered shipments.',
        ];
    }

    /**
     * Get the validated status as ShipmentStatus enum
     */
    public function getStatus(): ShipmentStatus
    {
        return ShipmentStatus::fromString($this->validated('status'));
    }

    /**
     * Get context data for lifecycle service
     */
    public function getLifecycleContext(): array
    {
        $context = [
            'notes' => $this->validated('notes'),
            'force' => $this->boolean('force', false),
            'timestamp' => $this->validated('timestamp') ?? now(),
        ];

        if ($this->filled('location_type')) {
            $context['location_type'] = $this->validated('location_type');
        }

        if ($this->filled('location_id')) {
            $context['location_id'] = $this->validated('location_id');
        }

        if ($this->filled('performed_by')) {
            $context['performed_by'] = $this->validated('performed_by');
        }

        // Exception data
        if ($this->getStatus() === ShipmentStatus::EXCEPTION) {
            $context['exception_type'] = $this->validated('exception_type');
            $context['exception_severity'] = $this->validated('exception_severity');
            $context['exception_notes'] = $this->validated('exception_notes');
        }

        // Return data
        if ($this->getStatus() === ShipmentStatus::RETURN_INITIATED) {
            $context['return_reason'] = $this->validated('return_reason');
            $context['return_notes'] = $this->validated('return_notes');
        }

        return $context;
    }
}
