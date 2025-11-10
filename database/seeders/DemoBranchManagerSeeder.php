<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoBranchManagerSeeder extends Seeder
{
    /**
     * Seed a handful of branch manager accounts with known credentials.
     */
    public function run(): void
    {
        $branches = Branch::query()
            ->orderBy('id')
            ->take(3)
            ->get();

        if ($branches->isEmpty()) {
            $this->command?->warn('No branches available to seed demo managers.');
            return;
        }

        $role = Role::firstOrCreate(
            ['slug' => 'branch_ops_manager'],
            ['name' => 'Branch Ops Manager', 'permissions' => []]
        );

        $seeded = [];

        foreach ($branches as $branch) {
            $emailSlug = Str::slug($branch->code ?: $branch->name ?: 'branch-'.$branch->id);
            $email = "branch.ops+{$emailSlug}@baraka.sanaa.co";
            $passwordPlain = 'BranchOps#' . str_pad((string) $branch->id, 3, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "{$branch->name} Ops Lead",
                    'password' => Hash::make($passwordPlain),
                    'user_type' => UserType::INCHARGE,
                    'status' => 1,
                ]
            );

            $user->assignRole($role->slug);

            BranchManager::updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'user_id' => $user->id,
                    'business_name' => "{$branch->name} Operations",
                    'current_balance' => 0,
                    'status' => 1,
                    'metadata' => [
                        'seeded_demo' => true,
                        'seeded_at' => now()->toIso8601String(),
                    ],
                ]
            );

            $seeded[] = [
                'branch' => $branch->name,
                'email' => $email,
                'password' => $passwordPlain,
            ];
        }

        if ($this->command && $seeded) {
            $this->command->table(['Branch', 'Email', 'Password'], $seeded);
        }
    }
}
