<?php

namespace App\Events;

use App\Models\DeliveryMan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driver;

    public $locations;

    /**
     * Create a new event instance.
     */
    public function __construct(DeliveryMan $driver, array $locations)
    {
        $this->driver = $driver;
        $this->locations = $locations;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('driver.'.$this->driver->user_id),
        ];
    }

    public function broadcastAs()
    {
        return 'driver.location.updated';
    }
}
