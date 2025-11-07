<?php

namespace App\Http\Requests\Api\V10\Driver;

use App\Enums\DriverTimeLogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\DriverTimeLog::class) ?? false;
    }

    public function rules(): array
    {
        $types = array_map(fn (DriverTimeLogType $type) => $type->value, DriverTimeLogType::cases());

        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'roster_id' => ['nullable', 'integer', 'exists:driver_rosters,id'],
            'log_type' => ['required', Rule::in($types)],
            'logged_at' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'source' => ['nullable', 'string', 'max:30'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
