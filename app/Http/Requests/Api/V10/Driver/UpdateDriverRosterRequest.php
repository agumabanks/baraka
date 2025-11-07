<?php

namespace App\Http\Requests\Api\V10\Driver;

use App\Enums\RosterStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('roster')) ?? false;
    }

    public function rules(): array
    {
        $statusValues = array_map(fn (RosterStatus $status) => $status->value, RosterStatus::cases());

        return [
            'shift_type' => ['sometimes', 'string', 'max:40'],
            'start_time' => ['sometimes', 'date'],
            'end_time' => ['sometimes', 'date', 'after:start_time'],
            'status' => ['sometimes', Rule::in($statusValues)],
            'planned_hours' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
