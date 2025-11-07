<?php

namespace Database\Seeders;

use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\BranchWorker;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoreErpSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'user_type' => 1,
            ]
        );

        $managerUser = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Branch Manager',
                'password' => Hash::make('password'),
                'user_type' => 1,
            ]
        );

        $workerUser = User::updateOrCreate(
            ['email' => 'worker@example.com'],
            [
                'name' => 'Branch Worker',
                'password' => Hash::make('password'),
                'user_type' => 3,
            ]
        );

        $clientUser = User::updateOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Client Contact',
                'password' => Hash::make('password'),
                'user_type' => 2,
            ]
        );

        $hub = Branch::updateOrCreate(
            ['code' => 'HUB-001'],
            [
                'name' => 'Central Hub',
                'type' => 'HUB',
                'is_hub' => true,
                'status' => 1,
                'metadata' => ['capacity' => '1000 parcels/day'],
            ]
        );

        $regional = Branch::updateOrCreate(
            ['code' => 'REG-001'],
            [
                'name' => 'Regional Branch',
                'type' => 'REGIONAL',
                'parent_branch_id' => $hub->id,
                'status' => 1,
                'metadata' => ['region' => 'North'],
            ]
        );

        $local = Branch::updateOrCreate(
            ['code' => 'LOC-001'],
            [
                'name' => 'Local Branch',
                'type' => 'LOCAL',
                'parent_branch_id' => $regional->id,
                'status' => 1,
                'metadata' => ['service_area' => 'Downtown'],
            ]
        );

        $manager = BranchManager::updateOrCreate(
            ['branch_id' => $regional->id],
            [
                'user_id' => $managerUser->id,
                'status' => 1,
            ]
        );

        $worker = BranchWorker::updateOrCreate(
            ['branch_id' => $local->id, 'user_id' => $workerUser->id],
            [
                'role' => 'courier',
                'assigned_at' => Carbon::now(),
                'status' => 1,
            ]
        );

        $client = Client::updateOrCreate(
            ['business_name' => 'Acme Logistics'],
            [
                'primary_branch_id' => $local->id,
                'status' => 'active',
                'kyc_data' => ['registration_number' => 'ACM123456'],
            ]
        );

        $shipment = Shipment::create([
            'client_id' => $client->id,
            'customer_id' => $clientUser->id,
            'origin_branch_id' => $local->id,
            'dest_branch_id' => $regional->id,
            'assigned_worker_id' => $worker->id,
            'tracking_number' => Str::upper(Str::random(12)),
            'status' => 'out_for_delivery',
            'created_by' => $admin->id,
            'service_level' => 'STANDARD',
            'incoterm' => 'DAP',
            'price_amount' => 49.99,
            'currency' => 'USD',
            'current_status' => 'OUT_FOR_DELIVERY',
            'assigned_at' => Carbon::now()->subDay(),
            'expected_delivery_date' => Carbon::now()->addDay(),
            'metadata' => ['package_count' => 3],
        ]);

        foreach ([
            ['status' => 'created', 'desc' => 'Shipment created at origin branch'],
            ['status' => 'ready_for_pickup', 'desc' => 'Courier assigned for pickup'],
            ['status' => 'in_transit', 'desc' => 'Arrived at regional sorting'],
            ['status' => 'out_for_delivery', 'desc' => 'Courier en route to recipient'],
        ] as $stage) {
            ShipmentLog::create([
                'shipment_id' => $shipment->id,
                'status' => $stage['status'],
                'description' => $stage['desc'],
                'location' => 'Riyadh',
                'created_by' => $managerUser->id,
                'logged_at' => Carbon::now(),
            ]);
        }

        $transactionReference = 'PAY-' . Str::upper(Str::random(8));

        Payment::create([
            'shipment_id' => $shipment->id,
            'client_id' => $client->id,
            'amount' => 49.99,
            'payment_method' => 'stripe',
            'status' => 'completed',
            'transaction_id' => $transactionReference,
            'transaction_reference' => $transactionReference,
            'paid_at' => Carbon::now(),
        ]);
    }
}
