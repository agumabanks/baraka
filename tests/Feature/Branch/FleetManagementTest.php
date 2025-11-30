<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\Backend\Vehicle;
use App\Models\Driver;
use App\Models\User;
use App\Models\VehicleTrip;
use App\Models\VehicleMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->user = User::factory()->create([
            'primary_branch_id' => $this->branch->id,
        ]);
    }

    /** @test */
    public function it_can_create_vehicle_trip()
    {
        $vehicle = Vehicle::factory()->create(['branch_id' => $this->branch->id]);
        $driver = Driver::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->user);

        $response = $this->post(route('branch.fleet.trips.store'), [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'trip_type' => 'delivery',
            'planned_start_at' => now()->addHour(),
            'planned_end_at' => now()->addHours(5),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicle_trips', [
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    /** @test */
    public function it_can_start_trip()
    {
        $vehicle = Vehicle::factory()->create(['branch_id' => $this->branch->id]);
        $driver = Driver::factory()->create(['branch_id' => $this->branch->id]);
        
        $trip = VehicleTrip::factory()->create([
            'branch_id' => $this->branch->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'status' => 'planned',
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('branch.fleet.trips.start', $trip));

        $response->assertRedirect();
        $trip->refresh();
        $this->assertEquals('in_progress', $trip->status);
        $this->assertNotNull($trip->actual_start_at);
    }

    /** @test */
    public function it_can_schedule_maintenance()
    {
        $vehicle = Vehicle::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->user);

        $response = $this->post(route('branch.fleet.maintenance.store'), [
            'vehicle_id' => $vehicle->id,
            'maintenance_type' => 'routine',
            'description' => 'Oil change',
            'scheduled_at' => now()->addDays(3),
            'priority' => 'normal',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicle_maintenance', [
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->branch->id,
            'maintenance_type' => 'routine',
        ]);
    }

    /** @test */
    public function it_enforces_branch_isolation_on_trips()
    {
        $otherBranch = Branch::factory()->create();
        $vehicle = Vehicle::factory()->create(['branch_id' => $otherBranch->id]);
        $driver = Driver::factory()->create(['branch_id' => $otherBranch->id]);
        
        $trip = VehicleTrip::factory()->create([
            'branch_id' => $otherBranch->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('branch.fleet.trips.start', $trip));

        $response->assertStatus(403); // Forbidden
    }
}
