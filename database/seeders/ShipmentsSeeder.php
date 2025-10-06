<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShipmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use existing hubs table (legacy) since shipments FK references hubs, not unified_branches
        $hubs = \Illuminate\Support\Facades\DB::table('hubs')->get();
        $branches = \App\Models\UnifiedBranch::all();
        $customers = \App\Models\Customer::where('status', 'active')->get();
        $workers = \App\Models\BranchWorker::where('status', 1)->get();
        $users = \App\Models\User::whereIn('user_type', [
            \App\Enums\UserType::ADMIN,
            \App\Enums\UserType::INCHARGE
        ])->get();

        if ($hubs->isEmpty()) {
            $this->command->error('No hubs found in legacy hubs table.');
            return;
        }

        if ($branches->isEmpty()) {
            $this->command->error('No branches found. Run UnifiedBranchesSeeder first.');
            return;
        }

        if ($customers->isEmpty()) {
            $this->command->error('No customers found. Run CustomersSeeder first.');
            return;
        }

        // Start counter from existing shipments
        $existingCount = \App\Models\Shipment::count();
        $shipmentCount = $existingCount;
        $logCount = 0;

        $this->command->info("Found {$existingCount} existing shipments. Starting from #" . ($existingCount + 1));

        // Status distribution for realistic data
        $statusDistribution = [
            'CREATED' => 10,
            'HANDED_OVER' => 8,
            'ARRIVE' => 7,
            'SORT' => 5,
            'IN_TRANSIT' => 10,
            'ARRIVE_DEST' => 8,
            'OUT_FOR_DELIVERY' => 12,
            'DELIVERED' => 30,
            'RETURN_TO_SENDER' => 3,
            'DAMAGED' => 2,
        ];

        $totalShipments = 100;
        
        foreach ($statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $customer = $customers->random();
                // Use legacy hubs for origin/destination (shipments table FK points to hubs, not unified_branches)
                $originHub = $hubs->random();
                $destHub = $hubs->where('id', '!=', $originHub->id)->random();
                $originBranch = $branches->random(); // For logs/metadata
                $destBranch = $branches->where('id', '!=', $originBranch->id)->random();
                $worker = $workers->where('branch_id', $originBranch->id)->first() ?? $workers->random();
                $creator = $users->random();

                // Determine if this shipment has an exception
                $hasException = rand(0, 100) < 15; // 15% chance of exception
                $exceptionTypes = ['delay', 'damage', 'missing_item', 'wrong_address', 'customer_unavailable'];
                $exceptionType = $hasException ? $exceptionTypes[array_rand($exceptionTypes)] : null;
                $exceptionSeverity = $hasException ? ['low', 'medium', 'high'][rand(0, 2)] : null;

                // Generate tracking number
                $trackingNumber = 'BRK' . date('Y') . str_pad($shipmentCount + 1, 8, '0', STR_PAD_LEFT);

                // Determine priority (10% express, 20% priority, 70% standard)
                $priorityRand = rand(1, 100);
                $priority = $priorityRand <= 10 ? 3 : ($priorityRand <= 30 ? 2 : 1);

                // Create timestamps based on status
                $createdAt = now()->subDays(rand(1, 30));
                $assignedAt = $status !== 'CREATED' ? $createdAt->copy()->addHours(rand(1, 4)) : null;
                $pickedUpAt = in_array($status, ['HANDED_OVER', 'ARRIVE', 'SORT', 'IN_TRANSIT', 'ARRIVE_DEST', 'OUT_FOR_DELIVERY', 'DELIVERED']) 
                    ? $assignedAt?->copy()->addHours(rand(1, 3)) : null;
                $hubProcessedAt = in_array($status, ['SORT', 'IN_TRANSIT', 'ARRIVE_DEST', 'OUT_FOR_DELIVERY', 'DELIVERED']) 
                    ? $pickedUpAt?->copy()->addHours(rand(2, 6)) : null;
                $transferredAt = in_array($status, ['IN_TRANSIT', 'ARRIVE_DEST', 'OUT_FOR_DELIVERY', 'DELIVERED']) 
                    ? $hubProcessedAt?->copy()->addHours(rand(1, 12)) : null;
                $deliveredAt = $status === 'DELIVERED' ? $transferredAt?->copy()->addHours(rand(1, 8)) : null;
                $returnedAt = $status === 'RETURN_TO_SENDER' ? $createdAt->copy()->addDays(rand(3, 7)) : null;

                $shipment = \App\Models\Shipment::create([
                    'tracking_number' => $trackingNumber,
                    'customer_id' => $customer->account_manager_id ?? $users->random()->id,
                    'origin_branch_id' => $originHub->id, // Use hub ID (legacy)
                    'dest_branch_id' => $destHub->id, // Use hub ID (legacy)
                    'transfer_hub_id' => $branches->where('is_hub', true)->first()?->id,
                    'assigned_worker_id' => $status !== 'CREATED' ? $worker->user_id : null,
                    'service_level' => ['STANDARD', 'EXPRESS', 'PRIORITY'][rand(0, 2)],
                    'incoterm' => ['DAP', 'DDP'][rand(0, 1)],
                    'price_amount' => rand(50, 500),
                    'currency' => 'SAR',
                    'current_status' => $status,
                    'created_by' => $creator->id,
                    'assigned_at' => $assignedAt,
                    'picked_up_at' => $pickedUpAt,
                    'hub_processed_at' => $hubProcessedAt,
                    'transferred_at' => $transferredAt,
                    'delivered_at' => $deliveredAt,
                    'delivered_by' => $status === 'DELIVERED' ? $worker->user_id : null,
                    'returned_at' => $returnedAt,
                    'return_reason' => $status === 'RETURN_TO_SENDER' ? 'Customer refused delivery' : null,
                    'return_notes' => $status === 'RETURN_TO_SENDER' ? 'Package returned to sender after 3 delivery attempts' : null,
                    'has_exception' => $hasException,
                    'exception_type' => $exceptionType,
                    'exception_severity' => $exceptionSeverity,
                    'exception_notes' => $hasException ? "Exception: {$exceptionType} detected during transit" : null,
                    'exception_occurred_at' => $hasException ? $createdAt->copy()->addHours(rand(6, 48)) : null,
                    'priority' => $priority,
                    'metadata' => json_encode([
                        'weight' => rand(1, 50) / 10,
                        'dimensions' => [
                            'length' => rand(10, 100),
                            'width' => rand(10, 100),
                            'height' => rand(10, 100),
                        ],
                        'declared_value' => rand(100, 5000),
                        'insurance' => rand(0, 1) === 1,
                        'special_instructions' => rand(0, 1) === 1 ? 'Handle with care' : null,
                    ]),
                    'created_at' => $createdAt,
                    'updated_at' => $deliveredAt ?? $transferredAt ?? $hubProcessedAt ?? $pickedUpAt ?? $assignedAt ?? $createdAt,
                ]);

                // Create shipment logs for each status transition
                $this->createShipmentLogs($shipment, $status, $originBranch, $destBranch, $worker, $creator);
                $logCount += $this->getLogCountForStatus($status);

                $shipmentCount++;
            }
        }

        $this->command->info("Created {$shipmentCount} shipments with various statuses");
        $this->command->info("Created approximately {$logCount} shipment log entries");
    }

    private function createShipmentLogs($shipment, $status, $originBranch, $destBranch, $worker, $creator)
    {
        $logs = [];
        $currentTime = $shipment->created_at;

        // Always create CREATED log
        $logs[] = [
            'shipment_id' => $shipment->id,
            'branch_id' => $originBranch->id,
            'user_id' => $creator->id,
            'status' => 'CREATED',
            'description' => 'Shipment created and awaiting pickup',
            'location' => $originBranch->address,
            'latitude' => $originBranch->latitude,
            'longitude' => $originBranch->longitude,
            'metadata' => json_encode(['priority' => $shipment->priority]),
            'occurred_at' => $currentTime,
            'created_at' => $currentTime,
            'updated_at' => $currentTime,
        ];

        // Add subsequent logs based on status
        $statusFlow = [
            'HANDED_OVER' => ['description' => 'Package handed over by customer', 'branch' => $originBranch],
            'ARRIVE' => ['description' => 'Arrived at origin facility', 'branch' => $originBranch],
            'SORT' => ['description' => 'Sorted at facility', 'branch' => $originBranch],
            'IN_TRANSIT' => ['description' => 'In transit to destination', 'branch' => $originBranch],
            'ARRIVE_DEST' => ['description' => 'Arrived at destination facility', 'branch' => $destBranch],
            'OUT_FOR_DELIVERY' => ['description' => 'Out for delivery', 'branch' => $destBranch],
            'DELIVERED' => ['description' => 'Successfully delivered to customer', 'branch' => $destBranch],
            'RETURN_TO_SENDER' => ['description' => 'Returning to sender', 'branch' => $destBranch],
            'DAMAGED' => ['description' => 'Package damaged during transit', 'branch' => $originBranch],
        ];

        $statusOrder = array_keys($statusFlow);
        $currentStatusIndex = array_search($status, $statusOrder);

        if ($currentStatusIndex !== false) {
            for ($i = 1; $i <= $currentStatusIndex; $i++) {
                $logStatus = $statusOrder[$i];
                $logData = $statusFlow[$logStatus];
                $currentTime = $currentTime->copy()->addHours(rand(1, 6));

                $logs[] = [
                    'shipment_id' => $shipment->id,
                    'branch_id' => $logData['branch']->id,
                    'user_id' => $worker->user_id ?? $creator->id,
                    'status' => $logStatus,
                    'description' => $logData['description'],
                    'location' => $logData['branch']->address,
                    'latitude' => $logData['branch']->latitude,
                    'longitude' => $logData['branch']->longitude,
                    'metadata' => json_encode(['scan_type' => 'manual']),
                    'occurred_at' => $currentTime,
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime,
                ];
            }
        }

        // Insert all logs in bulk
        \App\Models\ShipmentLog::insert($logs);
    }

    private function getLogCountForStatus($status): int
    {
        $statusOrder = ['CREATED', 'HANDED_OVER', 'ARRIVE', 'SORT', 'IN_TRANSIT', 'ARRIVE_DEST', 'OUT_FOR_DELIVERY', 'DELIVERED'];
        $index = array_search($status, $statusOrder);
        return $index !== false ? $index + 1 : 1;
    }
}
