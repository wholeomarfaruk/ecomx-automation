<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Settings extends Component
{
    public $user;
    public $name;
    public $email;
    public $phone;
    public $country_code;
    public $address;
    public $bio;

    public $current_password;
    public $password;
    public $password_confirmation;

    public function mount()
    {
        $this->user = Auth::user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->country_code = $this->user->country_code;
        $this->address = $this->user->address;
        $this->bio = $this->user->bio;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['name', 'email', 'phone', 'country_code', 'address', 'bio'])) {
            $this->validateOnly($propertyName, $this->rules());
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                \Illuminate\Validation\Rule::unique('users')->ignore($this->user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:5',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
        ];
    }

    public function updateAccount()
    {
        $validated = $this->validate();

        $this->user->update($validated);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Account settings updated successfully!'
        ]);
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($this->current_password, $this->user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $this->user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Password updated successfully!'
        ]);
    }

    public function render()
    {
        return view('livewire.admin.settings.settings')->layout('layouts.admin.admin');
    }
}