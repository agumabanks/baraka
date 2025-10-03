<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationalAlertEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $alertData;
    public array $recipients;

    /**
     * Create a new event instance.
     */
    public function __construct(array $alertData, array $recipients = [])
    {
        $this->alertData = $alertData;
        $this->recipients = $recipients;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('operations.alerts'),
        ];

        // Add user-specific channels for targeted alerts
        foreach ($this->recipients as $userId) {
            $channels[] = new PrivateChannel("operations.alerts.user.{$userId}");
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return array_merge($this->alertData, [
            'timestamp' => now()->toISOString(),
            'broadcast_id' => uniqid('alert_', true),
        ]);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'operational.alert';
    }
}