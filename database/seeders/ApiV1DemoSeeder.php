<?php

namespace Database\Seeders;

use App\Enums\ShipmentStatus;
use App\Enums\UserType;
use App\Models\Device;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApiV1DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo users
        $merchant = User::create([
            'name' => 'Demo Merchant',
            'email' => 'merchant@demo.com',
            'password' => Hash::make('password'),
            'user_type' => UserType::MERCHANT,
            'email_verified_at' => now(),
        ]);

        $driver = User::create([
            'name' => 'Demo Driver',
            'email' => 'driver@demo.com',
            'password' => Hash::make('password'),
            'user_type' => UserType::DELIVERYMAN,
            'email_verified_at' => now(),
        ]);

        $admin = User::create([
            'name' => 'Demo Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'user_type' => UserType::ADMIN,
            'email_verified_at' => now(),
        ]);

        // Create devices for users
        Device::create([
            'user_id' => $merchant->id,
            'device_uuid' => 'merchant-device-uuid-123',
            'platform' => 'ios',
            'push_token' => 'merchant-push-token',
            'last_seen_at' => now(),
        ]);

        Device::create([
            'user_id' => $driver->id,
            'device_uuid' => 'driver-device-uuid-456',
            'platform' => 'android',
            'push_token' => 'driver-push-token',
            'last_seen_at' => now(),
        ]);

        // Create shipments in various statuses
        $statuses = [
            ShipmentStatus::CREATED,
            ShipmentStatus::HANDED_OVER,
            ShipmentStatus::ARRIVE,
            ShipmentStatus::SORT,
            ShipmentStatus::LOAD,
            ShipmentStatus::DEPART,
            ShipmentStatus::IN_TRANSIT,
            ShipmentStatus::ARRIVE_DEST,
            ShipmentStatus::OUT_FOR_DELIVERY,
            ShipmentStatus::DELIVERED,
        ];

        foreach ($statuses as $status) {
            Shipment::create([
                'customer_id' => $merchant->id,
                'origin_branch_id' => 1, // Assuming hub exists
                'dest_branch_id' => 2, // Assuming hub exists
                'service_level' => 'standard',
                'incoterm' => 'DDP',
                'price_amount' => 50.00,
                'currency' => 'USD',
                'current_status' => $status,
                'created_by' => $admin->id,
                'metadata' => ['demo' => true],
            ]);
        }
    }
}
