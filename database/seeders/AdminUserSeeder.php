<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'email' => 'info@baraka.co',
                'name' => 'baraka Administrator',
                'password' => 'admin',
            ],
            [
                'email' => 'info@sanaa.co',
                'name' => 'sanaa Administrator',
                'password' => '@sanaa.co1234',
            ],
        ];

        $superAdminRoleId = null;
        if (Schema::hasTable('roles')) {
            $superAdminRoleId = DB::table('roles')->where('id', 1)->value('id');
        }

        foreach ($admins as $admin) {
            $user = User::query()->firstOrNew(['email' => $admin['email']]);

            if (! $user->exists || ! $user->name) {
                $user->name = $admin['name'];
            }

            $user->email = $admin['email'];
            $user->password = Hash::make($admin['password']);
            $user->user_type = UserType::ADMIN;

            if (Schema::hasColumn('users', 'phone_e164') && ! $user->phone_e164) {
                $user->phone_e164 = '0000000000';
            }

            $user->save();

            // Assign Super Admin role if available (id=1 seeded by RoleSeeder)
            if ($superAdminRoleId && $user->role_id !== $superAdminRoleId) {
                $user->role_id = $superAdminRoleId; // Super Admin
                $user->save();
            }
        }
    }
}
