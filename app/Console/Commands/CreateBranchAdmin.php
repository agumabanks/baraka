<?php

namespace App\Console\Commands;

use App\Models\Backend\Branch;
use App\Models\Backend\BranchManager;
use App\Models\Backend\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateBranchAdmin extends Command
{
    protected $signature = 'branch:make-admin {branch_id} {email} {--name=} {--password=}';

    protected $description = 'Create a branch admin user bound to a branch with branch_manage permissions';

    public function handle(): int
    {
        $branch = Branch::find($this->argument('branch_id'));
        if (! $branch) {
            $this->error('Branch not found');
            return 1;
        }

        $email = $this->argument('email');
        $name = $this->option('name') ?? 'Branch Admin '.$branch->code;
        $password = $this->option('password') ?? Str::random(12);

        $role = Role::firstOrCreate(
            ['slug' => 'branch_manager'],
            ['name' => 'Branch Manager', 'permissions' => ['branch_manage', 'branch_read']]
        );

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'primary_branch_id' => $branch->id,
                'permissions' => ['branch_manage', 'branch_read'],
                'user_type' => 'branch_manager',
            ]
        );

        $user->role_id = $role->id;
        $user->primary_branch_id = $branch->id;
        $user->save();

        BranchManager::firstOrCreate(
            ['branch_id' => $branch->id, 'user_id' => $user->id],
            ['status' => 1]
        );

        $this->info("Branch admin created: {$user->email}");
        $this->info("Password: {$password}");
        $this->info("Branch: {$branch->code}");

        return 0;
    }
}
