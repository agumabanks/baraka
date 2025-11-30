<?php

namespace Database\Seeders;

use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\Backend\Vehicle;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\User;
use App\Models\VehicleTrip;
use App\Models\VehicleMaintenance;
use App\Models\CrmActivity;
use App\Models\CrmReminder;
use App\Models\ClientAddress;
use App\Models\MaintenanceWindow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class BranchModuleCompleteSeeder extends Seeder
{
    /**
     * Seed demo data for complete branch module testing
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Branch Module Demo Data...');

        // Get or create branches
        $branches = Branch::take(3)->get();
        if ($branches->count() < 3) {
            $this->command->warn('⚠️  Less than 3 branches found. Creating demo branches...');
            $this->createDemoBranches();
            $branches = Branch::take(3)->get();
        }

        foreach ($branches as $branch) {
            $this->command->info("📦 Seeding data for {$branch->name}...");

            // Create branch workers
            $workers = $this->seedWorkers($branch);
            $this->command->info("  ✅ Created {$workers->count()} workers");

            // Create customers
            $customers = $this->seedCustomers($branch);
            $this->command->info("  ✅ Created {$customers->count()} customers");

            // Create vehicles
            $vehicles = $this->seedVehicles($branch);
            $this->command->info("  ✅ Created {$vehicles->count()} vehicles");

            // Create drivers
            $drivers = $this->seedDrivers($branch);
            $this->command->info("  ✅ Created {$drivers->count()} drivers");

            // Create shipments
            $shipments = $this->seedShipments($branch, $customers);
            $this->command->info("  ✅ Created {$shipments->count()} shipments");

            // Create CRM data
            $this->seedCrmData($branch, $customers, $workers);
            $this->command->info("  ✅ Created CRM activities and reminders");

            // Create vehicle trips
            $trips = $this->seedVehicleTrips($branch, $vehicles, $drivers);
            $this->command->info("  ✅ Created {$trips->count()} vehicle trips");

            // Create maintenance records
            $maintenance = $this->seedMaintenance($branch, $vehicles);
            $this->command->info("  ✅ Created {$maintenance->count()} maintenance records");

            // Create maintenance windows
            $windows = $this->seedMaintenanceWindows($branch);
            $this->command->info("  ✅ Created {$windows->count()} maintenance windows");
        }

        $this->command->info('🎉 Branch Module Seeding Complete!');
    }

    protected function createDemoBranches(): void
    {
        $demoBranches = [
            ['name' => 'Kampala Central', 'code' => 'KLA', 'city' => 'Kampala'],
            ['name' => 'Entebbe Branch', 'code' => 'EBB', 'city' => 'Entebbe'],
            ['name' => 'Jinja Branch', 'code' => 'JIN', 'city' => 'Jinja'],
        ];

        foreach ($demoBranches as $data) {
            Branch::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'city' => $data['city'],
                    'status' => 1,
                ]
            );
        }
    }

    protected function seedWorkers(Branch $branch)
    {
        $roles = ['dispatcher', 'courier', 'warehouse_staff', 'customer_service'];
        $workers = collect();

        foreach ($roles as $index => $role) {
            $user = User::firstOrCreate(
                ['email' => "worker.{$role}.{$branch->code}@baraka.test"],
                [
                    'name' => ucfirst($role) . ' ' . $branch->name,
                    'password' => Hash::make('password'),
                    'mobile' => '+256700' . rand(100000, 999999),
                ]
            );

            $worker = BranchWorker::firstOrCreate(
                ['user_id' => $user->id, 'branch_id' => $branch->id],
                [
                    'role' => $role,
                    'status' => 1,
                ]
            );

            $workers->push($worker);
        }

        return $workers;
    }

    protected function seedCustomers(Branch $branch)
    {
        $customers = collect();
        $companies = ['ABC Ltd', 'XYZ Corp', 'Tech Solutions', 'Global Imports', 'Local Traders'];

        foreach ($companies as $company) {
            $customer = Customer::firstOrCreate(
                ['company_name' => $company . ' - ' . $branch->code],
                [
                    'primary_branch_id' => $branch->id,
                    'contact_person' => 'Manager ' . $company,
                    'email' => strtolower(str_replace(' ', '', $company)) . '.' . strtolower($branch->code) . '@example.com',
                    'phone' => '+256700' . rand(100000, 999999),
                    'status' => 'active',
                    'customer_type' => 'regular',
                    'credit_limit' => rand(500000, 5000000),
                ]
            );

            // Add addresses (skip if table doesn't exist)
            if (Schema::hasTable('client_addresses')) {
                ClientAddress::firstOrCreate(
                    ['customer_id' => $customer->id, 'type' => 'billing'],
                    [
                        'label' => 'Head Office',
                        'address_line_1' => rand(1, 999) . ' Main Street',
                        'city' => $branch->city ?? 'Kampala',
                        'country' => 'Uganda',
                        'is_default' => true,
                        'is_active' => true,
                    ]
                );
            }

            $customers->push($customer);
        }

        return $customers;
    }

    protected function seedVehicles(Branch $branch)
    {
        $vehicles = collect();
        $types = ['van', 'truck', 'motorcycle'];
        $plates = ['UAG', 'UBA', 'UAH'];

        for ($i = 1; $i <= 3; $i++) {
            $vehicle = Vehicle::firstOrCreate(
                ['plate_no' => $plates[$i - 1] . ' ' . rand(100, 999) . 'X'],
                [
                    'branch_id' => $branch->id,
                    'type' => $types[$i - 1],
                    'model' => ['Toyota Hiace', 'Isuzu FTR', 'Honda CB'][$i - 1],
                    'capacity_kg' => [1000, 5000, 150][$i - 1],
                    'status' => 'active',
                    'year' => rand(2018, 2023),
                ]
            );

            $vehicles->push($vehicle);
        }

        return $vehicles;
    }

    protected function seedDrivers(Branch $branch)
    {
        $drivers = collect();

        for ($i = 1; $i <= 3; $i++) {
            $user = User::firstOrCreate(
                ['email' => "driver{$i}.{$branch->code}@baraka.test"],
                [
                    'name' => "Driver {$i} {$branch->name}",
                    'password' => Hash::make('password'),
                    'mobile' => '+256700' . rand(100000, 999999),
                ]
            );

            $driver = Driver::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'branch_id' => $branch->id,
                    'license_number' => 'DL' . rand(100000, 999999),
                    'status' => 'ACTIVE',
                ]
            );

            $drivers->push($driver);
        }

        return $drivers;
    }

    protected function seedShipments(Branch $branch, $customers)
    {
        $shipments = collect();
        $statuses = ['PENDING', 'PICKED_UP', 'IN_TRANSIT', 'AT_ORIGIN_HUB', 'OUT_FOR_DELIVERY', 'DELIVERED'];

        // Get a user to use as customer (due to foreign key constraint)
        $user = User::first();
        if (!$user) {
            $this->command->warn("  ⚠️  No users found, skipping shipment creation");
            return $shipments;
        }

        foreach ($customers->take(2) as $customer) {
            for ($i = 0; $i < 3; $i++) {
                try {
                    $shipment = Shipment::create([
                        'tracking_number' => 'SHP-' . strtoupper(uniqid()),
                        'customer_id' => $user->id, // Using user ID due to FK constraint
                        'origin_branch_id' => $branch->id,
                        'dest_branch_id' => Branch::where('id', '!=', $branch->id)->inRandomOrder()->first()->id ?? $branch->id,
                        'current_status' => $statuses[array_rand($statuses)],
                        'expected_delivery_date' => now()->addDays(rand(1, 5)),
                    ]);
                    $shipment->created_at = now()->subDays(rand(0, 10));
                    $shipment->save();

                    $shipments->push($shipment);
                } catch (\Exception $e) {
                    // Skip if creation fails
                    continue;
                }
            }
        }

        return $shipments;
    }

    protected function seedCrmData(Branch $branch, $customers, $workers)
    {
        // Skip if CRM tables don't exist
        if (!Schema::hasTable('crm_activities')) {
            return;
        }

        foreach ($customers->take(3) as $customer) {
            // Create activities
            CrmActivity::firstOrCreate(
                ['customer_id' => $customer->id, 'activity_type' => 'call'],
                [
                    'user_id' => $workers->random()->user_id,
                    'subject' => 'Follow-up call',
                    'description' => 'Discussed shipping requirements',
                    'outcome' => 'positive',
                    'occurred_at' => now()->subDays(rand(1, 10)),
                ]
            );

            // Create reminders
            if (Schema::hasTable('crm_reminders')) {
                CrmReminder::firstOrCreate(
                    ['customer_id' => $customer->id, 'title' => 'Follow-up'],
                    [
                        'user_id' => $workers->random()->user_id,
                        'created_by' => $workers->random()->user_id,
                        'description' => 'Schedule next meeting',
                        'priority' => 'normal',
                        'status' => 'pending',
                        'reminder_at' => now()->addDays(rand(1, 7)),
                    ]
                );
            }
        }
    }

    protected function seedVehicleTrips(Branch $branch, $vehicles, $drivers)
    {
        $trips = collect();

        foreach ($vehicles->take(2) as $vehicle) {
            $trip = VehicleTrip::create([
                'branch_id' => $branch->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $drivers->random()->id,
                'origin_branch_id' => $branch->id,
                'trip_type' => 'delivery',
                'status' => ['planned', 'in_progress', 'completed'][rand(0, 2)],
                'planned_start_at' => now()->addHours(rand(1, 24)),
                'planned_end_at' => now()->addHours(rand(25, 48)),
                'total_stops' => rand(3, 10),
            ]);

            $trips->push($trip);
        }

        return $trips;
    }

    protected function seedMaintenance(Branch $branch, $vehicles)
    {
        $maintenance = collect();

        foreach ($vehicles as $vehicle) {
            $record = VehicleMaintenance::create([
                'vehicle_id' => $vehicle->id,
                'branch_id' => $branch->id,
                'reported_by_user_id' => User::first()->id,
                'maintenance_type' => ['routine', 'repair', 'inspection'][rand(0, 2)],
                'description' => 'Regular maintenance check',
                'status' => ['scheduled', 'completed'][rand(0, 1)],
                'scheduled_at' => now()->addDays(rand(1, 30)),
                'priority' => 'normal',
            ]);

            $maintenance->push($record);
        }

        return $maintenance;
    }

    protected function seedMaintenanceWindows(Branch $branch)
    {
        $windows = collect();

        // Skip if table doesn't exist
        if (!Schema::hasTable('maintenance_windows')) {
            return $windows;
        }

        for ($i = 1; $i <= 2; $i++) {
            try {
                $window = MaintenanceWindow::create([
                    'branch_id' => $branch->id,
                    'entity_type' => ['branch', 'vehicle'][rand(0, 1)],
                    'entity_id' => $branch->id,
                    'starts_at' => now()->addDays(rand(1, 7)),
                    'ends_at' => now()->addDays(rand(8, 14)),
                    'capacity_impact_percent' => rand(10, 50),
                    'status' => 'scheduled',
                    'description' => 'Scheduled maintenance window',
                ]);

                $windows->push($window);
            } catch (\Exception $e) {
                // Skip if creation fails
                continue;
            }
        }

        return $windows;
    }
}
