<?php

namespace App\Livewire\Admin\Profile;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\ActivityLog;
use App\Models\Gender;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Profile extends Component
{
    use WithMediaPicker;
    public $user;
    public $name;
    public $address;
    public $bio;
    public $gender;
    public $avatar_id;

    public function mount()
    {
        $this->user = Auth::user();
        $this->fillFromUser();
    }

    protected function fillFromUser(): void
    {
        $this->name = $this->user->name;
        $this->address = $this->user->address;
        $this->bio = $this->user->bio;
        $this->gender = $this->user->gender;
        $this->avatar_id = $this->user->avatar_id;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['name', 'address', 'bio', 'gender'])) {
            $this->validateOnly($propertyName, $this->rules());
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'gender' => 'nullable|exists:genders,slug',
            'avatar_id' => 'nullable|exists:files,id',
        ];
    }

    public function updateProfile()
    {
        $validated = $this->validate();
        $validated['gender'] = $validated['gender'] ?: null;
        $validated['avatar_id'] = $validated['avatar_id'] ?: null;

        $this->user->update($validated);
        $this->fillFromUser();

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Profile updated successfully!'
        ]);
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->fillFromUser();
    }

    public function render()
    {
        $this->user->loadMissing('roles', 'panels');

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
