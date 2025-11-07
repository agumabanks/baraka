<?php

namespace App\Http\Requests\Api\V10\Driver;

use App\Enums\DriverStatus;
use App\Enums\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Driver::class) ?? false;
    }

    public function rules(): array
    {
        $statusValues = array_map(fn (DriverStatus $status) => $status->value, DriverStatus::cases());
        $employmentValues = array_map(fn (EmploymentStatus $status) => $status->value, EmploymentStatus::cases());

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required_without:user_id', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required_without:user_id', 'string', 'max:30'],
            'password' => ['required_without:user_id', 'string', 'min:8'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'status' => ['nullable', Rule::in($statusValues)],
            'employment_status' => ['nullable', Rule::in($employmentValues)],
            'license_number' => ['nullable', 'string', 'max:120'],
            'license_expiry' => ['nullable', 'date'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'documents' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'code' => ['nullable', 'string', 'max:50', 'unique:drivers,code'],
        ];
    }
}
