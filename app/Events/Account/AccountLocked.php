<?php

namespace App\Events\Account;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountLocked
{
    use Dispatchable, SerializesModels;

    public User $user;
    public string $reason;
    public int $attempts;

    public function __construct(User $user, string $reason, int $attempts)
    {
        $this->user = $user;
        $this->reason = $reason;
        $this->attempts = $attempts;
    }
}
