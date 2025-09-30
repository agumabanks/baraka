<?php

namespace App\Http\Requests\DeliveryCharge;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        if (Request::input('category') == 1) {
            return [
                'category' => ['required'],
                'weight' => ['required', 'numeric', Rule::unique('delivery_charges', 'weight')->ignore($this->id)],
                'same_day' => ['required', 'numeric'],
                'next_day' => ['required', 'numeric'],
                'sub_city' => ['required', 'numeric'],
                'outside_city' => ['required', 'numeric'],
                'position' => ['required', 'numeric'],
                'status' => ['required', 'numeric'],
            ];
        } else {
            return [
                'category' => ['required', 'numeric', Rule::unique('delivery_charges', 'category_id')->ignore($this->id)],
                'same_day' => ['required', 'numeric'],
                'next_day' => ['required', 'numeric'],
                'sub_city' => ['required', 'numeric'],
                'outside_city' => ['required', 'numeric'],
                'position' => ['required', 'numeric'],
                'status' => ['required', 'numeric'],
            ];
        }
    }
}
