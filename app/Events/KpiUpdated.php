<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KpiUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $kpiId;
    public $newValue;
    public $trend;
    public $merchantId;

    /**
     * Create a new event instance.
     */
    public function __construct($kpiId, $newValue, $trend, $merchantId = null)
    {
        $this->kpiId = $kpiId;
        $this->newValue = $newValue;
        $this->trend = $trend;
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
        return 'KpiUpdated';
    }

    public function broadcastWith()
    {
        return [
            'kpiId' => $this->kpiId,
            'newValue' => $this->newValue,
            'trend' => $this->trend,
            'timestamp' => now()->toISOString()
        ];
    }
}