<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $invoice = '';
        if (! request()->id) {
            $invoice = ['required'];
        }

        return [
            'asset_id' => ['required'],
            'start_date' => ['required'],
            'end_date' => ['required'],
            'asset_id' => ['required'],
            'repair_details' => ['required'],
            'spare_parts_purchased_details' => ['required'],
            'invoice_of_the_purchases' => $invoice,
        ];
    }

    public function attributes()
    {
        return [
            'asset_id' => 'asset',
        ];
    }
}
