<?php

namespace App\Http\Requests\Fuel;

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
        return [
            'asset_id'        => ['required'],
            'fuel_type'       => ['required'],
            'invoice_of_fuel' => ['required'],
            'amount'          => ['required','numeric'],
        ];
    }

    public function attributes()
    {
        return [
            'asset_id' => 'asset'
        ];
    }
}
