<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetMaintenanceAlertEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $maintenanceData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $maintenanceData)
    {
        $this->maintenanceData = $maintenanceData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('operations.alerts'),
        ];

        // Add branch-specific channel if branch_id is provided
        if (isset($this->maintenanceData['branch_id'])) {
            $channels[] = new Channel("operations.alerts.branch.{$this->maintenanceData['branch_id']}");
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $isOverdue = $this->maintenanceData['type'] === 'maintenance_overdue';

        return [
            'type' => 'alert.asset_maintenance',
            'title' => $isOverdue ? 'Overdue Asset Maintenance' : 'Upcoming Asset Maintenance',
            'message' => $isOverdue
                ? "Maintenance overdue for {$this->maintenanceData['asset_name']}"
                : "Maintenance due soon for {$this->maintenanceData['asset_name']}",
            'severity' => $isOverdue ? 'high' : 'medium',
            'data' => $this->maintenanceData,
            'action_required' => true,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'asset.maintenance.alert';
    }
}