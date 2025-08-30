<?php

namespace App\Http\Requests\Merchant;

use App\Models\Backend\Merchant;
use Illuminate\Foundation\Http\FormRequest;

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
     * @return array
     */
    public function rules()
    {
        $user  = Merchant::findOrFail($this->id);
        $userID = $user->user_id;
        return [
            'name'                  => ['required','string','max:191'],
            'business_name'         => 'required|string|unique:merchants,business_name,'.$this->id,
            'mobile'                => 'required|numeric|digits_between:11,14|unique:users,mobile,'.$userID,
            'hub'                   => ['required','numeric'],
            'status'                => ['required','numeric'],
            'password'              => ['nullable','min:6'],
            'address'               => ['required','string','max:191'],
            'payment_period'        => ['numeric']
        ];
    }
}
