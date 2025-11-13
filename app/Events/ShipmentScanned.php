<?php

namespace App\Events;

use App\Models\Shipment;
use App\Models\Backend\Branch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentScanned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Shipment $shipment;
    public Branch $branch;
    public string $action;
    public array $scanData;

    /**
     * Create a new event instance.
     */
    public function __construct(Shipment $shipment, Branch $branch, string $action, array $scanData = [])
    {
        $this->shipment = $shipment;
        $this->branch = $branch;
        $this->action = $action;
        $this->scanData = $scanData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('shipments'),
            new PrivateChannel('shipments.' . $this->shipment->id),
            new PrivateChannel('branch.' . $this->branch->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'shipment.scanned';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'shipment' => [
                'id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'current_status' => $this->shipment->current_status,
                'customer_name' => $this->shipment->customer->name ?? null,
                'origin_branch' => $this->shipment->originBranch->name ?? null,
                'destination_branch' => $this->shipment->destBranch->name ?? null,
            ],
            'scan_data' => [
                'action' => $this->action,
                'branch' => [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'code' => $this->branch->code,
                ],
                'timestamp' => now()->toISOString(),
                'device_id' => $this->scanData['device_id'] ?? null,
                'latitude' => $this->scanData['latitude'] ?? null,
                'longitude' => $this->scanData['longitude'] ?? null,
            ],
            'workflow' => [
                'next_expected_action' => $this->getNextExpectedAction(),
                'suggested_workflows' => $this->getSuggestedWorkflows(),
            ],
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return true; // Always broadcast scan events for real-time updates
    }

    /**
     * Get the next expected action for the shipment
     */
    private function getNextExpectedAction(): ?string
    {
        return match ($this->shipment->current_status) {
            'pending' => 'pickup',
            'picked_up' => 'inbound',
            'in_transit' => 'arrival',
            'arrived_at_hub' => 'outbound',
            'out_for_delivery' => 'delivery',
            default => null,
        };
    }

    /**
     * Get suggested workflows based on scan
     */
    private function getSuggestedWorkflows(): array
    {
        $suggestions = [];

        switch ($this->action) {
            case 'exception':
                $suggestions = [
                    'Create exception report',
                    'Notify customer service',
                    'Schedule follow-up',
                ];
                break;
            case 'delivery':
                $suggestions = [
                    'Send delivery confirmation',
                    'Update customer portal',
                    'Collect feedback',
                ];
                break;
            case 'inbound':
                $suggestions = [
                    'Update inventory',
                    'Sort packages',
                ];
                break;
            case 'outbound':
                $suggestions = [
                    'Prepare for dispatch',
                    'Update driver assignment',
                ];
                break;
        }

        return $suggestions;
    }
}