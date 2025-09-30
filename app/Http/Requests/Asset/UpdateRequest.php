<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        if (Request::input('insurance_status') == 1) {
            $insurance_documents = ['nullable'];
            $insurance_expiry_date = ['required'];
            $insurance_registration = ['required'];
            $insurance_amount = ['required'];
        } else {
            $insurance_documents = ['nullable'];
            $insurance_expiry_date = ['nullable'];
            $insurance_registration = ['nullable'];
            $insurance_amount = ['nullable'];
        }

        return [
            'name' => ['required'],
            'assetcategory_id' => ['required'],
            // 'hub_id'                        => ['required'],
            'amount' => ['required'],
            'registration_documents' => ['nullable'],
            'purchase_date' => ['required'],
            // 'yearly_depreciation_value'     => ['required'],
            'registration_date' => ['required'],
            'registration_expiry_date' => ['required'],
            'insurance_status' => ['required'],
            'insurance_documents' => $insurance_documents,
            'insurance_expiry_date' => $insurance_expiry_date,
            'insurance_registration' => $insurance_registration,
            'insurance_amount' => $insurance_amount,
            // vehicle information
            'asset_type' => ['required'],
            'plate_no' => ['required'],
            'chasis_number' => ['required'],
            'model' => ['required'],
            'year' => ['required'],
            'brand' => ['required'],
            'color' => ['required'],
            // end vehicle information
        ];
    }

    public function attributes()
    {
        return [
            'amount' => 'cost',
            'vehicle_id' => 'vehicle',
        ];
    }
}
