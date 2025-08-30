<?php

namespace App\Http\Requests\DeliveryCharge;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

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
        if (Request::input('category') == 1) {
            return [
                'category'      => ['required'],
                'weight'        => ['required', 'numeric','unique:delivery_charges,weight'],
                'same_day'      => ['required','numeric',],
                'next_day'      => ['required','numeric',],
                'sub_city'      => ['required','numeric',],
                'outside_city'  => ['required','numeric',],
                'position'      => ['required','numeric',],
                'status'        => ['required','numeric',],
            ];
        }
        else {
            return [
                'category'      => ['required', 'numeric','unique:delivery_charges,category_id'],
                'same_day'      => ['required','numeric',],
                'next_day'      => ['required','numeric',],
                'sub_city'      => ['required','numeric',],
                'outside_city'  => ['required','numeric',],
                'position'      => ['required','numeric',],
                'status'        => ['required','numeric',],
            ];
        }

    }
}
