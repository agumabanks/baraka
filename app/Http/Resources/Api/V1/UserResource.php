<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'notification_prefs' => $this->notification_prefs,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
