<?php

namespace App\Http\Requests\Invoice;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared FormRequest for Invoice Creation
 * 
 * Used by both Admin and Branch modules to ensure consistent
 * validation rules for invoice creation.
 */
class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin users can create invoices for any branch
        if ($this->user()->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Branch users can only create invoices for their branch
        if ($this->filled('branch_id') && $this->user()->current_branch_id) {
            return (int) $this->input('branch_id') === $this->user()->current_branch_id;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'shipment_id' => 'required|integer|exists:shipments,id',
            'customer_id' => 'required|integer|exists:users,id',
            'branch_id' => 'required|integer|exists:branches,id',
            'merchant_id' => 'nullable|integer|exists:customers,id',
            
            'subtotal' => 'required|numeric|min:0|max:999999.99',
            'tax_amount' => 'nullable|numeric|min:0|max:999999.99',
            'total_amount' => 'required|numeric|min:0|max:999999.99',
            'currency' => 'required|string|size:3',
            
            'status' => [
                'nullable',
                'string',
                Rule::in(array_map(fn($case) => $case->value, InvoiceStatus::cases())),
            ],
            
            'due_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'shipment_id.required' => 'Shipment ID is required.',
            'shipment_id.exists' => 'Selected shipment does not exist.',
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'branch_id.required' => 'Branch is required.',
            'branch_id.exists' => 'Selected branch does not exist.',
            'subtotal.required' => 'Subtotal is required.',
            'total_amount.required' => 'Total amount is required.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be a 3-letter code (e.g., USD, EUR).',
        ];
    }

    /**
     * Prepare data for invoice creation
     */
    public function prepareForInvoice(): array
    {
        $data = $this->validated();
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = InvoiceStatus::DRAFT;
        } elseif (is_string($data['status'])) {
            $data['status'] = InvoiceStatus::fromString($data['status']);
        }

        // Calculate tax if not provided
        if (!isset($data['tax_amount'])) {
            $data['tax_amount'] = $data['subtotal'] * 0.1; // 10% default tax
        }

        // Generate invoice number if needed (handled by model observer typically)
        return $data;
    }
}
