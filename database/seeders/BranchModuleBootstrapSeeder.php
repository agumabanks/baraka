<?php

namespace Database\Seeders;

use App\Enums\BranchStatus;
use App\Enums\BranchType;
use App\Enums\BranchWorkerRole;
use App\Enums\EmploymentStatus;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\BranchWorker;
use App\Models\Backend\Role;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BranchModuleBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base roles exist for FK integrity and permissions mapping.
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'permissions' => ['branch_manage', 'workforce_manage', 'branch_read', 'branch_analytics'],
                'status' => Status::ACTIVE,
            ]
        );

        $managerRole = Role::updateOrCreate(
            ['slug' => 'branch_manager'],
            [
                'name' => 'Branch Manager',
                'permissions' => ['branch_manage', 'branch_read'],
                'status' => Status::ACTIVE,
            ]
        );

        $workerRole = Role::updateOrCreate(
            ['slug' => 'operations_agent'],
            [
                'name' => 'Operations Agent',
                'permissions' => ['branch_read', 'workforce_manage'],
                'status' => Status::ACTIVE,
            ]
        );

        $clientRole = Role::updateOrCreate(
            ['slug' => 'client'],
            [
                'name' => 'Client',
                'permissions' => ['branch_read'],
                'status' => Status::ACTIVE,
            ]
        );

        $branch = Branch::firstOrCreate(
            ['code' => 'BRK-HUB'],
            [
                'name' => 'Baraka Central Hub',
                'type' => BranchType::HUB->value,
                'is_hub' => true,
                'address' => 'Plot 12 Jinja Rd, Kampala',
                'country' => 'Uganda',
                'city' => 'Kampala',
                'time_zone' => 'Africa/Kampala',
                'latitude' => 0.3136,
                'longitude' => 32.5811,
                'geo_lat' => 0.3136,
                'geo_lng' => 32.5811,
                'status' => BranchStatus::ACTIVE->toLegacy(),
                'capacity_parcels_per_day' => 5000,
            ]
        );

        // Ops admin with full access to branch + workforce + analytics.
        User::updateOrCreate(
            ['email' => 'ops.admin@example.com'],
            [
                'name' => 'Operations Admin',
                'password' => Hash::make('Password123!'),
                'status' => Status::ACTIVE,
                'role_id' => $adminRole->id,
                'user_type' => UserType::ADMIN,
                'permissions' => ['branch_manage', 'workforce_manage', 'branch_read', 'branch_analytics'],
                'primary_branch_id' => $branch->id,
                'mobile' => '+256700000001',
            ]
        );

        // Branch manager is also a system user with branch permissions.
        $managerUser = User::updateOrCreate(
            ['email' => 'branch.manager@example.com'],
            [
                'name' => 'Demo Branch Manager',
                'password' => Hash::make('Password123!'),
                'status' => Status::ACTIVE,
                'role_id' => $managerRole->id,
                'user_type' => UserType::INCHARGE,
                'permissions' => ['branch_manage', 'branch_read'],
                'primary_branch_id' => $branch->id,
                'mobile' => '+256700000002',
            ]
        );

        BranchManager::updateOrCreate(
            ['user_id' => $managerUser->id],
            [
                'branch_id' => $branch->id,
                'business_name' => 'Baraka Demo Manager',
                'current_balance' => 0,
                'status' => Status::ACTIVE,
            ]
        );

        // Branch worker / ops agent as system user.
        $workerUser = User::updateOrCreate(
            ['email' => 'branch.worker@example.com'],
            [
                'name' => 'Demo Ops Agent',
                'password' => Hash::make('Password123!'),
                'status' => Status::ACTIVE,
                'role_id' => $workerRole->id,
                'user_type' => UserType::DELIVERYMAN,
                'permissions' => ['branch_read', 'workforce_manage'],
                'primary_branch_id' => $branch->id,
                'mobile' => '+256700000003',
            ]
        );

        BranchWorker::updateOrCreate(
            ['user_id' => $workerUser->id],
            [
                'branch_id' => $branch->id,
                'role' => BranchWorkerRole::OPS_AGENT->value,
                'employment_status' => EmploymentStatus::ACTIVE->value,
                'contact_phone' => $workerUser->mobile ?? $workerUser->phone ?? '+256700000003',
                'status' => Status::ACTIVE,
                'assigned_at' => now(),
            ]
        );

        // Client seeded as a system user; kyc_data keeps a pointer to the user.
        $clientUser = User::updateOrCreate(
            ['email' => 'client.demo@example.com'],
            [
                'name' => 'Demo Logistics Client',
                'password' => Hash::make('Password123!'),
                'status' => Status::ACTIVE,
                'role_id' => $clientRole->id,
                'user_type' => UserType::MERCHANT,
                'permissions' => ['branch_read'],
                'primary_branch_id' => $branch->id,
                'mobile' => '+256700000004',
            ]
        );

        Client::updateOrCreate(
            ['primary_branch_id' => $branch->id, 'business_name' => 'Demo Logistics Client'],
            [
                'status' => Status::ACTIVE,
                'kyc_data' => ['account_user_id' => $clientUser->id],
            ]
        );
    }
}
