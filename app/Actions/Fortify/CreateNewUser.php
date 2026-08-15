<?php

namespace App\Actions\Fortify;

use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        $this->dispatchRegisteredEvent($user);

        return $user;
    }

    protected function dispatchRegisteredEvent(User $user): void
    {
        try {
            UserRegistered::dispatch($user, [
                'signup_date' => $user->created_at->format('M j, Y'),
                'login_url' => route('login'),
            ], 'signup_registered');
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch UserRegistered event for new user.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
