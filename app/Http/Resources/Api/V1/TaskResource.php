<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'driver_id' => $this->driver_id,
            'type' => $this->type,
            'status' => $this->status,
            'priority' => $this->priority,
            'scheduled_at' => $this->scheduled_at,
            'completed_at' => $this->completed_at,
            'metadata' => $this->metadata,
            'shipment' => [
                'id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'current_status' => $this->shipment->current_status,
                'origin_branch' => $this->shipment->originBranch?->name,
                'dest_branch' => $this->shipment->destBranch?->name,
                'customer' => [
                    'id' => $this->shipment->customer->id,
                    'name' => $this->shipment->customer->name,
                    'phone' => $this->shipment->customer->mobile,
                ],
            ],
            'pod_proof' => $this->podProof ? [
                'id' => $this->podProof->id,
                'verified_at' => $this->podProof->verified_at,
                'signature_url' => $this->podProof->signature_url,
                'photo_url' => $this->podProof->photo_url,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}