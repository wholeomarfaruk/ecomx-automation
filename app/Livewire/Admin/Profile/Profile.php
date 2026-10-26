<?php

namespace App\Livewire\Admin\Profile;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\ActivityLog;
use App\Models\Gender;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Profile extends Component
{
    use WithMediaPicker;
    public $user;
    public $name;
    public $email;
    public $phone;
    public $country_code;
    public $address;
    public $bio;
    public $gender;
    public $avatar_id;

    // Required only when the email is being changed.
    public $email_password;

    public function mount()
    {
        $this->user = Auth::user();
        $this->fillFromUser();
    }

    protected function fillFromUser(): void
    {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->country_code = $this->user->country_code;
        $this->address = $this->user->address;
        $this->bio = $this->user->bio;
        $this->gender = $this->user->gender;
        $this->avatar_id = $this->user->avatar_id;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['name', 'email', 'phone', 'country_code', 'address', 'bio', 'gender'])) {
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
                'max:255',
                Rule::unique('users')->ignore($this->user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{6,20}$/'],
            'country_code' => ['nullable', 'string', 'max:5', 'regex:/^\+?[0-9]{1,4}$/'],
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'gender' => 'nullable|exists:genders,slug',
            'avatar_id' => 'nullable|exists:files,id',
        ];
    }

    protected function messages()
    {
        return [
            'phone.regex' => 'Phone may only contain digits, spaces, dashes and a leading +.',
            'country_code.regex' => 'Use a format like +88.',
        ];
    }

    protected function emailChanged(): bool
    {
        return strcasecmp(trim((string) $this->email), (string) $this->user->email) !== 0;
    }

    public function updateProfile()
    {
        $validated = $this->validate();
        $validated['email'] = trim($validated['email']);
        $validated['gender'] = $validated['gender'] ?: null;
        $validated['avatar_id'] = $validated['avatar_id'] ?: null;

        if ($this->emailChanged()) {
            if (blank($this->email_password) || !Hash::check($this->email_password, $this->user->password)) {
                $this->addError('email_password', 'Enter your current password to change the email.');
                return;
            }
        }

        $this->user->update($validated);
        $this->reset('email_password');
        $this->fillFromUser();

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Profile updated successfully!'
        ]);
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset('email_password');
        $this->fillFromUser();
    }

    public function render()
    {
        $this->user->loadMissing('roles:id,name', 'panels:id,name');

        return view('livewire.admin.profile.profile', [
            'genders' => Gender::active()->orderBy('sort_order')->get(['name', 'slug']),
            'recentActivities' => ActivityLog::where('causer_type', User::class)
                ->where('causer_id', $this->user->id)
                ->latest('id')
                ->limit(10)
                ->get(['id', 'event', 'description', 'subject_type', 'subject_id', 'ip_address', 'user_agent', 'created_at']),
        ])->layout('layouts.admin.admin');
    }
}
