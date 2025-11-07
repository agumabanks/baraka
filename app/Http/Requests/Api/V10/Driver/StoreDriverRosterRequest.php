<?php

namespace App\Http\Requests\Api\V10\Driver;

use App\Enums\RosterStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\DriverRoster::class) ?? false;
    }

    public function rules(): array
    {
        $statusValues = array_map(fn (RosterStatus $status) => $status->value, RosterStatus::cases());

        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'shift_type' => ['nullable', 'string', 'max:40'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'status' => ['nullable', Rule::in($statusValues)],
            'planned_hours' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
