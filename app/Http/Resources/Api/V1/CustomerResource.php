<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'user_type' => $this->user_type,
            'user_type_label' => $this->user_type_label,
            'is_client' => $this->is_client,
            'status' => $this->status,
            'notification_prefs' => $this->notification_prefs,
            'shipments_count' => $this->shipments->count(),
            'merchant' => $this->merchant?->only(['id', 'name', 'company_name']),
            'devices' => $this->devices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'platform' => $device->platform,
                    'device_uuid' => $device->device_uuid,
                    'last_seen_at' => $device->last_seen_at,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
