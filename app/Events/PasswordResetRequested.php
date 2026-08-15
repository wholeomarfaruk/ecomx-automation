<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class PasswordResetRequested
{
    use Dispatchable;

    public string $eventKey = 'password_reset';

    public function __construct(
        public User $user,
        public array $data = [],
    ) {
    }
}
