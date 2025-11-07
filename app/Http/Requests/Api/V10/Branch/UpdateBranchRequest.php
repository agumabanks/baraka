<?php

namespace App\Http\Requests\Api\V10\Branch;

use App\Enums\BranchType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('branch')) ?? false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Backend\Branch $branch */
        $branch = $this->route('branch');
        $branchTypes = array_map(fn (BranchType $type) => $type->value, BranchType::cases());

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branch?->id)],
            'type' => ['sometimes', Rule::in($branchTypes)],
            'parent_branch_id' => ['nullable', 'integer', 'exists:branches,id', Rule::notIn([$branch?->id])],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'time_zone' => ['nullable', 'string', 'max:64'],
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
