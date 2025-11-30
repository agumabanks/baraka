<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:technical,account,shipment,feature,other'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'message' => ['required', 'string', 'min:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Please provide a subject for your ticket.',
            'category.required' => 'Please select a category.',
            'priority.required' => 'Please select a priority level.',
            'message.required' => 'Please describe your issue.',
            'message.min' => 'Please provide more details (at least 10 characters).',
        ];
    }
}
