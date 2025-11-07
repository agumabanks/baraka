<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\CustomerMilestone;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerMilestoneAchieved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Customer $customer,
        public CustomerMilestone $milestone
    ) {}

    public function broadcastOn()
    {
        return new PrivateChannel('customer-milestones');
    }
}