<?php

namespace App\Http\Requests\Api\V10;

use App\Enums\ScanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordScanEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_map(fn (ScanType $type) => $type->value, ScanType::cases()))],
            'occurred_at' => ['nullable', 'date'],
            'sscc' => ['nullable', 'string', 'max:40'],
            'shipment_id' => ['nullable', 'integer', 'exists:shipments,id'],
            'tracking_number' => ['nullable', 'string', 'max:40'],
            'bag_id' => ['nullable', 'integer', 'exists:bags,id'],
            'bag_code' => ['nullable', 'string', 'max:40'],
            'route_id' => ['nullable', 'integer', 'exists:routes,id'],
            'stop_id' => ['nullable', 'integer', 'exists:stops,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'leg_id' => ['nullable', 'integer', 'exists:transport_legs,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'location_type' => ['nullable', 'string', 'max:40'],
            'location_id' => ['nullable', 'integer'],
            'status_after' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:2000'],
            'geojson' => ['nullable', 'array'],
            'payload' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
