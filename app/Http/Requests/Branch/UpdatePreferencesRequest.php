<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'language' => ['required', 'in:en,fr,sw'],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['required', 'in:Y-m-d,m/d/Y,d/m/Y,d-M-Y'],
            'time_format' => ['required', 'in:24h,12h'],
            'currency_display' => ['required', 'in:symbol,code,name'],
            'number_format' => ['required', 'in:1,234.56,1.234,56,1 234.56'],
            'theme' => ['required', 'in:dark,light,auto'],
        ];
    }
}
