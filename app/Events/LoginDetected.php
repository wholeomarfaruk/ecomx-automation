<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class LoginDetected
{
    use Dispatchable;

    public string $eventKey = 'login';

    public function __construct(
        public User $user,
        public array $data = [],
    ) {
    }
}
