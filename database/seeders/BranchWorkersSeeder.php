<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchWorkersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = \App\Models\UnifiedBranch::all();

        if ($branches->isEmpty()) {
            $this->command->error('No branches found. Please run UnifiedBranchesSeeder first.');
            return;
        }

        // Worker names pool
        $workerNames = [
            'Omar Al-Harbi', 'Layla Al-Dosari', 'Yousef Al-Shammari', 'Nora Al-Ghamdi',
            'Abdullah Al-Mutairi', 'Hala Al-Subaie', 'Saad Al-Enezi', 'Rania Al-Asmari',
            'Fahad Al-Shahrani', 'Maha Al-Balawi', 'Sultan Al-Dawsari', 'Lina Al-Qadir',
            'Faisal Al-Zahrani', 'Aisha Al-Mazroa', 'Bandar Al-Harbi', 'Nouf Al-Otaibi',
            'Turki Al-Rashid', 'Joud Al-Ghamdi', 'Nasser Al-Qahtani', 'Salma Al-Malki',
            'Waleed Al-Shamrani', 'Hind Al-Zahrani', 'Majed Al-Anazi', 'Reem Al-Saud',
            'Talal Al-Ghamdi', 'Lama Al-Harbi', 'Saud Al-Dosari', 'Jana Al-Mutairi',
            'Mishal Al-Shammari', 'Dina Al-Enezi', 'Rayan Al-Subaie', 'Nada Al-Qahtani',
        ];

        $workerCount = 0;
        $userCount = 0;

        foreach ($branches as $branch) {
            // LOCAL branches: 5-8 workers, REGIONAL: 10-12, HUB: 15-20
            $workersNeeded = $branch->type === 'HUB' ? rand(15, 20) : 
                           ($branch->type === 'REGIONAL' ? rand(10, 12) : rand(5, 8));

            for ($i = 0; $i < $workersNeeded; $i++) {
                // Create a delivery worker user
                $nameIndex = ($userCount + $i) % count($workerNames);
                $name = $workerNames[$nameIndex];
                $email = strtolower(str_replace([' ', '-'], ['', ''], $name)) . ($userCount + $i) . '@baraka.sanaa.co';
                $mobile = '+9665' . str_pad((10000000 + $userCount + $i), 8, '0', STR_PAD_LEFT);

                // Check if user already exists
                $user = \App\Models\User::where('email', $email)->first();
                
                if (!$user) {
                    $user = \App\Models\User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => bcrypt('password123'),
                        'mobile' => $mobile,
                        'user_type' => \App\Enums\UserType::DELIVERYMAN,
                        'status' => 1,
                        'verification_status' => 1,
                    ]);
                }

                // Determine role: 1 supervisor per 10 workers
                $role = ($i === 0 && $workersNeeded > 5) ? 'supervisor' : 
                       (($i === 1 && $workersNeeded > 10) ? 'dispatcher' : 'worker');

                // Check if assignment already exists
                $exists = \App\Models\BranchWorker::where('branch_id', $branch->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$exists) {
                    \App\Models\BranchWorker::create([
                        'branch_id' => $branch->id,
                        'user_id' => $user->id,
                        'role' => $role,
                        'permissions' => json_encode([
                            'can_pickup' => true,
                            'can_deliver' => true,
                            'can_scan' => true,
                            'can_update_status' => $role !== 'worker',
                            'can_handle_cod' => true,
                            'can_manage_team' => $role === 'supervisor',
                        ]),
                        'work_schedule' => json_encode([
                            'monday' => ['start' => '08:00', 'end' => '17:00'],
                            'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                            'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                            'thursday' => ['start' => '08:00', 'end' => '17:00'],
                            'friday' => ['start' => 'off', 'end' => 'off'],
                            'saturday' => ['start' => '08:00', 'end' => '17:00'],
                            'sunday' => ['start' => '08:00', 'end' => '17:00'],
                        ]),
                        'hourly_rate' => $role === 'supervisor' ? 75 : ($role === 'dispatcher' ? 60 : 45),
                        'assigned_at' => now()->subDays(rand(1, 180))->toDateString(),
                        'notes' => $role === 'supervisor' ? 'Team supervisor' : null,
                        'metadata' => json_encode([
                            'vehicle_assigned' => $role !== 'dispatcher',
                            'zone' => $branch->code,
                            'experience_years' => rand(1, 10),
                        ]),
                        'status' => 1,
                    ]);

                    $workerCount++;
                }
            }

            $userCount += $workersNeeded;
        }

        $this->command->info("Created {$workerCount} branch worker assignments across {$branches->count()} branches");
        $this->command->info("Total worker users created: {$userCount}");
    }
}
