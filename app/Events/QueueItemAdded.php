<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueItemAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $itemData;
    public $merchantId;

    /**
     * Create a new event instance.
     */
    public function __construct($itemData, $merchantId = null)
    {
        $this->itemData = $itemData;
        $this->merchantId = $merchantId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('dashboard-updates'),
        ];

        if ($this->merchantId) {
            $channels[] = new PrivateChannel('dashboard-updates.merchant.' . $this->merchantId);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'QueueItemAdded';
    }

    public function broadcastWith()
    {
        return [
            'itemData' => $this->itemData,
            'timestamp' => now()->toISOString()
        ];
    }
}