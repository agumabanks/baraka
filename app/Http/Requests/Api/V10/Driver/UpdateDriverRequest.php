<?php

namespace App\Http\Requests\Api\V10\Driver;

use App\Enums\DriverStatus;
use App\Enums\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('driver')) ?? false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Driver $driver */
        $driver = $this->route('driver');
        $statusValues = array_map(fn (DriverStatus $status) => $status->value, DriverStatus::cases());
        $employmentValues = array_map(fn (EmploymentStatus $status) => $status->value, EmploymentStatus::cases());

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($driver?->user_id)],
            'phone' => ['sometimes', 'string', 'max:30'],
            'password' => ['sometimes', 'string', 'min:8'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'status' => ['sometimes', Rule::in($statusValues)],
            'employment_status' => ['sometimes', Rule::in($employmentValues)],
            'license_number' => ['nullable', 'string', 'max:120'],
            'license_expiry' => ['nullable', 'date'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'documents' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('drivers', 'code')->ignore($driver?->id)],
        ];
    }
}
