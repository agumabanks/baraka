<?php

namespace App\Http\Requests\Api\V10\Branch;

use App\Enums\BranchType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Backend\Branch::class) ?? false;
    }

    public function rules(): array
    {
        $branchTypes = array_map(fn (BranchType $type) => $type->value, BranchType::cases());

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:branches,code'],
            'type' => ['required', Rule::in($branchTypes)],
            'parent_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'time_zone' => ['required', 'string', 'max:64'],
            'capacity_parcels_per_day' => ['nullable', 'integer', 'min:0'],
            'geo_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'operating_hours' => ['nullable', 'array'],
            'operating_hours.*.start' => ['nullable', 'string', 'max:5'],
            'operating_hours.*.end' => ['nullable', 'string', 'max:5'],
            'capabilities' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:ACTIVE,INACTIVE,MAINTENANCE,SUSPENDED'],
        ];
    }
}
