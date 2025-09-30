<?php

namespace App\Http\Requests\Accident;

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
        $document = '';
        if (request()->multi_documents) {
            $document = ['required'];
        }

        return [
            'asset_id' => ['required'],
            'date_of_accident' => ['required'],
            'driver_responsible' => ['required'],
            'cost_of_repair' => ['required', 'numeric'],
            'spare_parts' => ['required'],
            'multi_documents' => $document,
        ];
    }

    public function attributes()
    {
        return [
            'asset_id' => 'asset',
            'multi_documents' => 'upload documents',
        ];
    }
}
