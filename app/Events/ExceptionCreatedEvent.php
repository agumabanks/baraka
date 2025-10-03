<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExceptionCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Shipment $shipment;
    public array $exceptionData;

    /**
     * Create a new event instance.
     */
    public function __construct(Shipment $shipment, array $exceptionData)
    {
        $this->shipment = $shipment;
        $this->exceptionData = $exceptionData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('operations.exceptions'),
        ];

        // Add branch-specific channel
        if ($this->shipment->origin_branch_id) {
            $channels[] = new Channel("operations.exceptions.branch.{$this->shipment->origin_branch_id}");
        }

        if ($this->shipment->dest_branch_id && $this->shipment->dest_branch_id !== $this->shipment->origin_branch_id) {
            $channels[] = new Channel("operations.exceptions.branch.{$this->shipment->dest_branch_id}");
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'exception.created',
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'exception_data' => $this->exceptionData,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'exception.created';
    }
}