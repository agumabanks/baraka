<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Backend\Role;
use App\Models\User;

class AdminDashboardPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $needed = ['total_customers','booking_create'];

        // Ensure roles exist: hq_admin and branch_ops_manager
        $roles = [
            'hq_admin' => 'HQ Admin',
            'branch_ops_manager' => 'Branch Ops Manager',
        ];

        foreach ($roles as $slug => $name) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $name]);
            $perms = (array) ($role->permissions ?? []);
            $merged = array_values(array_unique(array_merge($perms, $needed)));
            $role->permissions = $merged;
            $role->save();
        }

        // Assign hq_admin to target user
        $email = env('ADMIN_EMAIL') ?: env('SEED_ADMIN_EMAIL');
        $user = $email ? User::where('email', $email)->first() : User::orderBy('id')->first();

        if ($user) {
            $hqAdmin = Role::where('slug', 'hq_admin')->first();
            if ($hqAdmin) {
                $user->role_id = $hqAdmin->id;
                // Mirror role permissions to user.permissions as middleware checks this field
                $userPerms = (array) ($user->permissions ?? []);
                $user->permissions = array_values(array_unique(array_merge($hqAdmin->permissions ?? [], $userPerms)));
                $user->save();
            }
        }
    }
}

