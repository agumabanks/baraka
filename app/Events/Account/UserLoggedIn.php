<?php

namespace App\Events\Account;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public User $user;
    public array $metadata;

    public function __construct(User $user, array $metadata = [])
    {
        $this->user = $user;
        $this->metadata = $metadata;
    }
}
