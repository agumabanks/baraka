<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPrefsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'array'],
            'email.*' => ['boolean'],
            'sms' => ['nullable', 'array'],
            'sms.*' => ['boolean'],
            'quiet_hours' => ['nullable', 'array'],
            'quiet_hours.start' => ['nullable', 'date_format:H:i'],
            'quiet_hours.end' => ['nullable', 'date_format:H:i'],
            'frequency' => ['required', 'in:immediate,hourly,daily'],
        ];
    }
}
