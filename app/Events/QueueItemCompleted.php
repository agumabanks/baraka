<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueItemCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $itemId;
    public $merchantId;

    /**
     * Create a new event instance.
     */
    public function __construct($itemId, $merchantId = null)
    {
        $this->itemId = $itemId;
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
        return 'QueueItemCompleted';
    }

    public function broadcastWith()
    {
        return [
            'itemId' => $this->itemId,
            'timestamp' => now()->toISOString()
        ];
    }
}