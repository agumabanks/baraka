<?php

namespace Tests\Feature\Api\V10;

use App\Enums\DriverStatus;
use App\Enums\DriverTimeLogType;
use App\Enums\EmploymentStatus;
use App\Enums\RosterStatus;
use App\Enums\UserType;
use App\Models\Backend\Branch;
use App\Models\Driver;
use App\Models\DriverRoster;
use App\Models\DriverTimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => database_path('testing.sqlite')]);
    }

    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
        ]);

        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_can_create_driver_profile(): void
    {
        $this->actingAsAdmin();
        $branch = Branch::factory()->create();

        $payload = [
            'name' => 'Karim Hussain',
            'phone' => '+966500000001',
            'password' => 'secret123',
            'branch_id' => $branch->id,
            'employment_status' => EmploymentStatus::ACTIVE->value,
        ];

        $response = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/drivers', $payload);

        $response->assertCreated();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('drivers', [
            'name' => 'Karim Hussain',
            'branch_id' => $branch->id,
            'status' => DriverStatus::ACTIVE->value,
        ]);
    }

    public function test_admin_can_manage_driver_rosters_and_time_logs(): void
    {
        $this->actingAsAdmin();
        $branch = Branch::factory()->create();
        $driver = Driver::create([
            'user_id' => User::factory()->create(['user_type' => UserType::DELIVERYMAN])->id,
            'branch_id' => $branch->id,
            'name' => 'Test Driver',
            'phone' => '+966500000002',
            'status' => DriverStatus::ACTIVE->value,
            'employment_status' => EmploymentStatus::ACTIVE->value,
            'code' => 'DRV-TEST-001',
        ]);

        $start = Carbon::now()->startOfDay()->addHours(8);
        $end = $start->copy()->addHours(9);

        $createResponse = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/driver-rosters', [
                'driver_id' => $driver->id,
                'branch_id' => $branch->id,
                'start_time' => $start->toIso8601String(),
                'end_time' => $end->toIso8601String(),
                'status' => RosterStatus::SCHEDULED->value,
            ]);

        $createResponse->assertCreated();
        $rosterId = $createResponse->json('data.id');

        $this->assertDatabaseHas('driver_rosters', [
            'id' => $rosterId,
            'driver_id' => $driver->id,
        ]);

        $overlapResponse = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/driver-rosters', [
                'driver_id' => $driver->id,
                'branch_id' => $branch->id,
                'start_time' => $start->copy()->addHour()->toIso8601String(),
                'end_time' => $end->copy()->addHour()->toIso8601String(),
            ]);

        $overlapResponse->assertStatus(422);

        $logResponse = $this->withHeader('apiKey', config('rxcourier.api_key'))
            ->postJson('/api/v10/driver-time-logs', [
                'driver_id' => $driver->id,
                'roster_id' => $rosterId,
                'log_type' => DriverTimeLogType::CHECK_IN->value,
                'logged_at' => $start->copy()->addMinutes(15)->toIso8601String(),
            ]);

        $logResponse->assertCreated();
        $this->assertDatabaseHas('driver_time_logs', [
            'driver_id' => $driver->id,
            'roster_id' => $rosterId,
            'log_type' => DriverTimeLogType::CHECK_IN->value,
        ]);
    }
}
