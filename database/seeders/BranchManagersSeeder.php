<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchManagersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, ensure we have users to assign as managers
        // Check if admin/manager users exist
        $adminUsers = \App\Models\User::where('user_type', \App\Enums\UserType::ADMIN)
            ->orWhere('user_type', \App\Enums\UserType::INCHARGE)
            ->get();

        if ($adminUsers->isEmpty()) {
            // Create some manager users
            $managerUsers = [];
            
            $managerUsers[] = \App\Models\User::create([
                'name' => 'Ahmed Al-Rashid',
                'email' => 'ahmed.rashid@baraka.sanaa.co',
                'password' => bcrypt('password123'),
                'mobile' => '+966501234567',
                'user_type' => \App\Enums\UserType::ADMIN,
                'status' => 1,
                'verification_status' => 1,
            ]);

            $managerUsers[] = \App\Models\User::create([
                'name' => 'Fatima Al-Zahrani',
                'email' => 'fatima.zahrani@baraka.sanaa.co',
                'password' => bcrypt('password123'),
                'mobile' => '+966502345678',
                'user_type' => \App\Enums\UserType::INCHARGE,
                'status' => 1,
                'verification_status' => 1,
            ]);

            $managerUsers[] = \App\Models\User::create([
                'name' => 'Mohammed Al-Qahtani',
                'email' => 'mohammed.qahtani@baraka.sanaa.co',
                'password' => bcrypt('password123'),
                'mobile' => '+966503456789',
                'user_type' => \App\Enums\UserType::INCHARGE,
                'status' => 1,
                'verification_status' => 1,
            ]);

            $managerUsers[] = \App\Models\User::create([
                'name' => 'Sara Al-Malki',
                'email' => 'sara.malki@baraka.sanaa.co',
                'password' => bcrypt('password123'),
                'mobile' => '+966504567890',
                'user_type' => \App\Enums\UserType::INCHARGE,
                'status' => 1,
                'verification_status' => 1,
            ]);

            $managerUsers[] = \App\Models\User::create([
                'name' => 'Khalid Al-Otaibi',
                'email' => 'khalid.otaibi@baraka.sanaa.co',
                'password' => bcrypt('password123'),
                'mobile' => '+966505678901',
                'user_type' => \App\Enums\UserType::INCHARGE,
                'status' => 1,
                'verification_status' => 1,
            ]);
        } else {
            $managerUsers = $adminUsers->take(5)->all();
        }

        // Get all branches
        $branches = \App\Models\UnifiedBranch::all();

        if ($branches->isEmpty()) {
            $this->command->error('No branches found. Please run UnifiedBranchesSeeder first.');
            return;
        }

        $managerCount = 0;

        // Assign managers to each branch
        foreach ($branches as $index => $branch) {
            // HUBs get 2 managers, REGIONAL get 2, LOCAL get 1
            $managersNeeded = $branch->type === 'HUB' ? 2 : ($branch->type === 'REGIONAL' ? 2 : 1);

            for ($i = 0; $i < $managersNeeded; $i++) {
                // Cycle through available managers
                $userIndex = ($index * 2 + $i) % count($managerUsers);
                $user = $managerUsers[$userIndex];

                // Check if this assignment already exists
                $exists = \App\Models\BranchManager::where('branch_id', $branch->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$exists) {
                    \App\Models\BranchManager::create([
                        'branch_id' => $branch->id,
                        'user_id' => $user->id,
                        'business_name' => $branch->name,
                        'current_balance' => 0,
                        'cod_charges' => json_encode([
                            'percentage' => 2.5,
                            'minimum' => 10,
                            'maximum' => 500,
                        ]),
                        'payment_info' => json_encode([
                            'bank' => 'Saudi National Bank',
                            'account_number' => 'SA' . str_pad($branch->id, 20, '0', STR_PAD_LEFT),
                            'iban' => 'SA80' . str_pad($branch->id, 18, '0', STR_PAD_LEFT),
                        ]),
                        'settlement_config' => json_encode([
                            'frequency' => 'weekly',
                            'day' => 'Sunday',
                            'auto_settle' => true,
                        ]),
                        'metadata' => json_encode([
                            'assigned_date' => now()->toDateString(),
                            'performance_target' => 95,
                        ]),
                        'status' => 1,
                    ]);

                    $managerCount++;
                }
            }
        }

        $this->command->info("Created {$managerCount} branch manager assignments across {$branches->count()} branches");
        $this->command->info("Manager users created/used: " . count($managerUsers));
    }
}
