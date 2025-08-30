<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'name'                  => ['required','string','max:191'],
            'business_name'         => ['required','string','unique:merchants'],
            'mobile'                => ['required','numeric','digits_between:11,14','unique:users'],
            // 'email'                 => ['required','string','unique:users'],
            'hub'                   => ['required','numeric'],
            'status'                => ['required','numeric'],
            'password'              => ['required','min:6'],
            'address'               => ['required','string','max:191'],
            'payment_period'        => ['numeric']

        ];
    }
}
