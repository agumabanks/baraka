<?php

namespace App\Http\Requests\Merchant;

use App\Traits\ApiReturnFormatTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Request;

class SignUpRequest extends FormRequest
{
    use ApiReturnFormatTrait;

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
            'business_name' => ['required', 'string', 'unique:merchants'],
            'full_name' => ['required', 'string', 'max:191'],
            // 'first_name'        => ['required','string','max:191'],
            // 'last_name'         => ['nullable','string','max:191'],
            'address' => ['required', 'string', 'max:191'],
            'mobile' => ['required', 'numeric', 'digits_between:11,14', 'unique:users'],
            'password' => ['required', 'min:6'],
        ];
    }

    // public function failedValidation(Validator $validator)
    // {
    //     throw new HttpResponseException(response()->json([
    //         'success'   => false,
    //         'message'   => 'Validation errors',
    //         'data'      => $validator->errors()
    //     ], 422));
    // }
}
