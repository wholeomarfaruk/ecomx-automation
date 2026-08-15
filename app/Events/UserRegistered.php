<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserRegistered
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public array $data = [],
        public string $eventKey = 'user_registered',
    ) {
    }
}
