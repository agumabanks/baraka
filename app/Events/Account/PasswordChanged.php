<?php

namespace App\Events\Account;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PasswordChanged
{
    use Dispatchable, SerializesModels;

    public User $user;
    public bool $forced;

    public function __construct(User $user, bool $forced = false)
    {
        $this->user = $user;
        $this->forced = $forced;
    }
}
