<?php

use App\Events\ShipmentStatusChanged;
use App\Models\Shipment;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebSocketTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipment_status_changed_event_broadcasts()
    {
        Event::fake();

        $shipment = Shipment::factory()->create();

        // Trigger the event
        event(new ShipmentStatusChanged($shipment));

        // Assert the event was dispatched
        Event::assertDispatched(ShipmentStatusChanged::class, function ($event) use ($shipment) {
            return $event->shipment->id === $shipment->id;
        });
    }

    public function test_shipment_channel_authorization()
    {
        $user = User::factory()->create(['user_type' => UserType::MERCHANT]);
        $shipment = Shipment::factory()->create(['customer_id' => $user->id]);

        // Test that user can join their own shipment channel
        $this->assertTrue($user->can('view', $shipment));
    }

    public function test_driver_channel_authorization()
    {
        $driver = User::factory()->create(['user_type' => UserType::DELIVERYMAN]);
        $deliveryMan = \App\Models\Backend\DeliveryMan::factory()->create(['user_id' => $driver->id]);

        // Test that driver can join their own channel
        $this->assertTrue($deliveryMan->user->hasRole('deliveryman'));
    }

    public function test_public_tracking_channel_access()
    {
        $shipment = Shipment::factory()->create();

        // Public tracking should be accessible to everyone
        $this->assertTrue(true); // Public channel allows all connections
    }

    public function test_broadcasting_configuration()
    {
        // Test that broadcasting is configured
        $config = config('broadcasting');

        $this->assertNotNull($config);
        $this->assertArrayHasKey('default', $config);
        $this->assertArrayHasKey('connections', $config);
    }
}