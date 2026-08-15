<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class OtpRequested
{
    use Dispatchable;

    public string $eventKey = 'otp';

    public function __construct(
        public User $user,
        public array $data = [],
    ) {
    }
}
