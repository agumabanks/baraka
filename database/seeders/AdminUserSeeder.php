<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'info@baraka.co';

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            $user = new User();
            $user->name = 'baraka Administrator';
            $user->email = $email;
        }

        $user->password = Hash::make('admin');
        $user->user_type = \App\Enums\UserType::ADMIN;
        $user->phone_e164 = $user->phone_e164 ?: '0000000000';
        $user->save();

        // Assign Super Admin role if available (id=1 seeded by RoleSeeder)
        $user->role_id = 1; // Super Admin
        $user->save();
    }
}

