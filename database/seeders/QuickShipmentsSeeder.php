<?php

namespace Database\Seeders;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\Backend\Client;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuickShipmentsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating test shipments...');

        // Get existing data
        $branches = Branch::all();
        $workers = BranchWorker::all();
        $users = User::whereIn('user_type', [UserType::ADMIN, UserType::INCHARGE])->get();
        $clients = Client::all();

        if ($branches->isEmpty()) {
            $this->command->error('No branches found. Please create branches first.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->error('No users found. Please create users first.');
            return;
        }

        // Create a default client if none exist
        if ($clients->isEmpty()) {
            $adminUser = $users->where('user_type', UserType::ADMIN)->first();
            $client = Client::create([
                'name' => 'Test Merchant',
                'email' => 'merchant@test.com',
                'phone' => '+256700000000',
                'account_manager_id' => $adminUser->id,
                'status' => 'active',
                'api_key' => \Illuminate\Support\Str::random(32),
            ]);
            $clients = collect([$client]);
            $this->command->info('Created default test client');
        }

        $existingCount = Shipment::count();
        $this->command->info("Found {$existingCount} existing shipments.");

        // Status distribution for realistic data
        $statusDistribution = [
            'booked' => 8,
            'pickup_scheduled' => 6,
            'picked_up' => 10,
            'at_origin_hub' => 8,
            'bagged' => 5,
            'linehaul_departed' => 7,
            'at_destination_hub' => 8,
            'out_for_delivery' => 12,
            'delivered' => 25,
            'exception' => 3,
        ];

        $shipmentCount = 0;

        foreach ($statusDistribution as $statusValue => $count) {
            for ($i = 0; $i < $count; $i++) {
                $client = $clients->random();
                $originBranch = $branches->random();
                $destBranch = $branches->where('id', '!=', $originBranch->id)->first() ?? $branches->random();
                $worker = $workers->where('branch_id', $originBranch->id)->first() ?? $workers->random();
                $creator = $users->random();

                // Determine if this shipment has an exception
                $hasException = $statusValue === 'exception' || rand(0, 100) < 10;
                $exceptionTypes = ['delay', 'damage', 'missing_item', 'wrong_address', 'customer_unavailable'];
                $exceptionType = $hasException ? $exceptionTypes[array_rand($exceptionTypes)] : null;
                $exceptionSeverity = $hasException ? ['low', 'medium', 'high'][rand(0, 2)] : null;

                // Generate tracking number
                $trackingNumber = 'BRK' . date('Y') . str_pad($existingCount + $shipmentCount + 1, 8, '0', STR_PAD_LEFT);

                // Determine priority (10% express, 20% priority, 70% standard)
                $priorityRand = rand(1, 100);
                $priority = $priorityRand <= 10 ? 3 : ($priorityRand <= 30 ? 2 : 1);

                // Create timestamps based on status
                $createdAt = now()->subDays(rand(1, 30));
                $bookedAt = $createdAt;
                $assignedAt = $statusValue !== 'booked' ? $createdAt->copy()->addHours(rand(1, 4)) : null;
                $pickedUpAt = in_array($statusValue, ['picked_up', 'at_origin_hub', 'bagged', 'linehaul_departed', 'linehaul_arrived', 'at_destination_hub', 'out_for_delivery', 'delivered']) 
                    ? $assignedAt?->copy()->addHours(rand(1, 3)) : null;
                $hubProcessedAt = in_array($statusValue, ['bagged', 'linehaul_departed', 'linehaul_arrived', 'at_destination_hub', 'out_for_delivery', 'delivered']) 
                    ? $pickedUpAt?->copy()->addHours(rand(2, 6)) : null;
                $transferredAt = in_array($statusValue, ['linehaul_departed', 'linehaul_arrived', 'at_destination_hub', 'out_for_delivery', 'delivered']) 
                    ? $hubProcessedAt?->copy()->addHours(rand(1, 12)) : null;
                $deliveredAt = $statusValue === 'delivered' ? $transferredAt?->copy()->addHours(rand(1, 8)) : null;
                
                // Calculate expected delivery date
                $expectedDeliveryDate = $createdAt->copy()->addDays(rand(3, 7));

                $shipment = Shipment::create([
                    'tracking_number' => $trackingNumber,
                    'client_id' => $client->id,
                    'customer_id' => $creator->id,
                    'origin_branch_id' => $originBranch->id,
                    'dest_branch_id' => $destBranch->id,
                    'mode' => rand(0, 100) > 35
                        ? ShipmentMode::INDIVIDUAL->value
                        : ShipmentMode::GROUPAGE->value,
                    'assigned_worker_id' => $statusValue !== 'booked' && $worker ? $worker->id : null,
                    'service_level' => ['standard', 'express', 'priority'][rand(0, 2)],
                    'incoterm' => ['DAP', 'DDP'][rand(0, 1)],
                    'price_amount' => rand(50, 500),
                    'currency' => 'UGX',
                    'current_status' => $statusValue,
                    'status' => $statusValue,
                    'created_by' => $creator->id,
                    'assigned_at' => $assignedAt,
                    'picked_up_at' => $pickedUpAt,
                    'delivered_at' => $deliveredAt,
                    'expected_delivery_date' => $expectedDeliveryDate,
                    'has_exception' => $hasException,
                    'exception_type' => $exceptionType,
                    'exception_severity' => $exceptionSeverity,
                    'exception_notes' => $hasException ? "Exception: {$exceptionType} detected during transit" : null,
                    'exception_occurred_at' => $hasException ? $createdAt->copy()->addHours(rand(6, 48)) : null,
                    'priority' => $priority,
                    'metadata' => [
                        'weight' => rand(1, 50) / 10,
                        'dimensions' => [
                            'length' => rand(10, 100),
                            'width' => rand(10, 100),
                            'height' => rand(10, 100),
                        ],
                        'declared_value' => rand(100, 5000),
                        'insurance' => rand(0, 1) === 1,
                        'special_instructions' => rand(0, 1) === 1 ? 'Handle with care' : null,
                    ],
                    'created_at' => $createdAt,
                    'updated_at' => $deliveredAt ?? $assignedAt ?? $createdAt,
                ]);

                // Skip shipment log creation for now to avoid schema issues
                
                $shipmentCount++;
            }
        }

        $this->command->info("Successfully created {$shipmentCount} test shipments");
        $this->command->info("Total shipments in database: " . Shipment::count());
    }
}
