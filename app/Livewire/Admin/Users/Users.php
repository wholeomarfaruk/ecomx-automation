<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Country;
use App\Models\Gender;
use App\Models\Panel;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Password;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Users extends Component
{
    use WithMediaPicker;

    public $users;
    public $viewModal  = false;
    public $user;
    public $roles;
    public $role_name;
    public $UserModal  = false;
    public $newUserName, $newUserEmail, $newUserPassword;
    public string $newUserGender      = '';
    public string $newUserPhone       = '';
    public string $newUserCountryCode = '';
    public string $newUserRole        = '';
    public string $newUserAddress     = '';
    public string $newUserBio         = '';
    public array  $newUserPanels      = [];
    public string $search         = '';
    public string $filterRole     = '';
    public string $filterPanel    = '';
    public string $filterVerified = '';
    public $panels;
    public $countries;
    public $genders;
    public array  $panelIds    = [];
    public ?int   $avatar_id    = null;
    public string $editName     = '';
    public string $editEmail    = '';
    public string $editPassword = '';
    public string $editGender      = '';
    public string $editPhone       = '';
    public string $editCountryCode = '';
    public string $editAddress     = '';
    public string $editBio         = '';

    public function mount(UserService $userService): void
    {
        $this->users     = $userService->all();
        $this->roles     = Role::all();
        $this->panels    = Panel::all();
        $this->countries = Country::active()->orderBy('name')->get()->unique('phone_code')->values();
        $this->genders   = Gender::active()->get();
    }

    public function mediaSelected($field, $id): void
    {
        if (\is_array($id)) {
            $existing     = $this->$field ?? [];
            $this->$field = array_values(array_unique([...$existing, ...$id], SORT_REGULAR));
        } else {
            $this->$field = $id;
        }

        if ($field === 'avatar_id' && $this->user) {
            $this->user->update(['avatar_id' => $this->avatar_id]);

            activity('users')
                ->causedBy(auth()->user())
                ->performedOn($this->user)
                ->withProperties(['avatar_id' => $this->avatar_id])
                ->event('updated')
                ->log("Avatar updated for \"{$this->user->name}\"");

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Profile picture updated']);
        }
    }

    public function updatedRoleName(string $value, UserService $userService): void
    {
        if ($this->user?->hasRole('superadmin')) {
            $this->role_name = 'superadmin';
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Superadmin role cannot be changed']);
            return;
        }

        $oldRole = $this->user->roles->first()?->name;

        $userService->assignRole($this->user, $value);

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($this->user)
            ->withProperties([
                'old'        => ['role' => $oldRole],
                'attributes' => ['role' => $value],
            ])
            ->event('updated')
            ->log("Role changed to \"{$value}\" for \"{$this->user->name}\"");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Role updated']);
    }

    public function togglePanel(int $panelId): void
    {
        if (! $this->user) {
            return;
        }

        $panel = Panel::find($panelId);

        $this->user->panels()->toggle($panelId);
        $this->user->load('panels');
        $this->panelIds = $this->user->panels->pluck('id')->toArray();

        $isNowAttached = \in_array($panelId, $this->panelIds, true);

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($this->user)
            ->withProperties([
                'panel_id'   => $panelId,
                'panel_name' => $panel?->name,
                'action'     => $isNowAttached ? 'assigned' : 'removed',
            ])
            ->event('updated')
            ->log("Panel \"{$panel?->name}\" " . ($isNowAttached ? 'assigned to' : 'removed from') . " \"{$this->user->name}\"");

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $isNowAttached ? 'Panel assigned' : 'Panel removed',
        ]);
    }

    public function toggleNewUserPanel(int $panelId): void
    {
        if (in_array($panelId, $this->newUserPanels, true)) {
            $this->newUserPanels = array_values(array_diff($this->newUserPanels, [$panelId]));
        } else {
            $this->newUserPanels[] = $panelId;
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterRole', 'filterPanel', 'filterVerified']);
    }

    public function render(): mixed
    {
        $this->users = User::with('roles', 'panels', 'avatar')
            ->when($this->search, fn($q) => $q
                ->where(fn($s) => $s
                    ->where('name', 'LIKE', "%{$this->search}%")
                    ->orWhere('email', 'LIKE', "%{$this->search}%")
                )
            )
            ->when($this->filterRole, fn($q) => $q->whereHas('roles', fn($r) => $r->where('name', $this->filterRole)))
            ->when($this->filterPanel, fn($q) => $q->whereHas('panels', fn($p) => $p->where('panels.id', (int) $this->filterPanel)))
            ->when($this->filterVerified === 'verified', fn($q) => $q->whereNotNull('email_verified_at'))
            ->when($this->filterVerified === 'unverified', fn($q) => $q->whereNull('email_verified_at'))
            ->latest()
            ->get();

        return view('livewire.admin.users.users')->layout('layouts.admin.admin');
    }

    public function deleteUser(int $id, UserService $userService): void
    {
        $user = $userService->find($id);

        if (! $user) {
            abort(404);
        }

        $name  = $user->name;
        $email = $user->email;

        if (! $userService->delete($user)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Superadmin cannot be deleted']);
            return;
        }

        activity('users')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name, 'email' => $email])
            ->event('deleted')
            ->log("User \"{$name}\" ({$email}) was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'User deleted successfully']);
    }

    public function viewUser(int $id, UserService $userService): void
    {
        $user = User::with('panels', 'roles')->find($id);

        if (! $user) {
            abort(404);
        }

        $this->user         = $user;
        $this->role_name    = $user->roles->first()?->name;
        $this->avatar_id    = $user->avatar_id;
        $this->panelIds     = $user->panels->pluck('id')->toArray();
        $this->editName     = $user->name;
        $this->editEmail    = $user->email;
        $this->editPassword = '';
        $this->editGender      = $user->gender ?? '';
        $this->editPhone       = $user->phone ?? '';
        $this->editCountryCode = $user->country_code ?? '';
        $this->editAddress     = $user->address ?? '';
        $this->editBio         = $user->bio ?? '';
        $this->viewModal    = true;
    }

    public function removeAvatar(): void
    {
        if (! $this->user) {
            return;
        }

        $this->avatar_id = null;
        $this->user->update(['avatar_id' => null]);

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($this->user)
            ->event('updated')
            ->log("Avatar removed for \"{$this->user->name}\"");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Profile picture removed']);
    }

    public function updateUser(): void
    {
        if (! $this->user) {
            return;
        }

        $this->validate([
            'editName'        => 'required|min:2|max:255',
            'editEmail'       => 'required|email|unique:users,email,' . $this->user->id,
            'editPassword'    => 'nullable|min:8',
            'editGender'      => 'nullable|exists:genders,slug',
            'editPhone'       => 'nullable|string|max:20',
            'editCountryCode' => 'nullable|exists:countries,phone_code',
            'editAddress'     => 'nullable|max:500',
            'editBio'         => 'nullable|max:1000',
        ]);

        $old = [
            'name'         => $this->user->name,
            'email'        => $this->user->email,
            'gender'       => $this->user->gender,
            'phone'        => $this->user->phone,
            'country_code' => $this->user->country_code,
            'address'      => $this->user->address,
            'bio'          => $this->user->bio,
        ];

        $emailChanged = $this->editEmail !== $this->user->email;
        $phoneChanged = $this->editPhone !== ($this->user->phone ?? '');

        $data = [
            'name'         => $this->editName,
            'email'        => $this->editEmail,
            'gender'       => $this->editGender ?: null,
            'phone'        => $this->editPhone ?: null,
            'country_code' => $this->editCountryCode ?: null,
            'address'      => $this->editAddress ?: null,
            'bio'          => $this->editBio ?: null,
        ];

        if ($emailChanged) {
            $data['email_verified_at'] = null;
        }

        if ($phoneChanged) {
            $data['phone_verified_at'] = null;
        }

        if ($this->editPassword !== '') {
            $data['password'] = $this->editPassword;
        }

        $this->user->update($data);

        $new = [
            'name'         => $this->editName,
            'email'        => $this->editEmail,
            'gender'       => $this->editGender ?: null,
            'phone'        => $this->editPhone ?: null,
            'country_code' => $this->editCountryCode ?: null,
            'address'      => $this->editAddress ?: null,
            'bio'          => $this->editBio ?: null,
        ];

        $changed = array_filter(array_keys($new), fn ($k) => ($old[$k] ?? null) != ($new[$k] ?? null));

        if (! empty($changed) || $this->editPassword !== '') {
            $props = [
                'old'        => array_intersect_key($old, array_flip($changed)),
                'attributes' => array_intersect_key($new, array_flip($changed)),
            ];

            if ($this->editPassword !== '') {
                $props['attributes']['password'] = '(changed)';
            }

            activity('users')
                ->causedBy(auth()->user())
                ->performedOn($this->user)
                ->withProperties($props)
                ->event('updated')
                ->log("User \"{$this->user->name}\" was updated");
        }

        $this->user         = $this->user->fresh(['roles', 'panels']);
        $this->editPassword = '';

        $this->dispatch('toast', ['type' => 'success', 'message' => 'User updated successfully']);
        $this->dispatch('user-saved');
    }

    public function sendPasswordResetLink(int $id): void
    {
        $user = User::find($id);
        if (! $user) return;

        Password::sendResetLink(['email' => $user->email]);

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['email' => $user->email])
            ->event('updated')
            ->log("Password reset link sent to \"{$user->email}\"");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Password reset link sent to ' . $user->email]);
    }

    public function markEmailVerified(int $id): void
    {
        $user = User::find($id);
        if (! $user) return;

        $user->forceFill(['email_verified_at' => now()])->save();

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('updated')
            ->log("Email manually verified for \"{$user->name}\"");

        if ($this->user?->id === $id) {
            $this->user = $user->fresh(['roles', 'panels']);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Email marked as verified']);
    }

    public function revokeEmailVerification(int $id): void
    {
        $user = User::find($id);
        if (! $user) return;

        $user->forceFill(['email_verified_at' => null])->save();

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('updated')
            ->log("Email verification revoked for \"{$user->name}\"");

        if ($this->user?->id === $id) {
            $this->user = $user->fresh(['roles', 'panels']);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Email verification removed']);
    }

    public function markPhoneVerified(int $id): void
    {
        $user = User::find($id);
        if (! $user) return;

        $user->forceFill(['phone_verified_at' => now()])->save();

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('updated')
            ->log("Phone manually verified for \"{$user->name}\"");

        if ($this->user?->id === $id) {
            $this->user = $user->fresh(['roles', 'panels']);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Phone marked as verified']);
    }

    public function revokePhoneVerification(int $id): void
    {
        $user = User::find($id);
        if (! $user) return;

        $user->forceFill(['phone_verified_at' => null])->save();

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('updated')
            ->log("Phone verification revoked for \"{$user->name}\"");

        if ($this->user?->id === $id) {
            $this->user = $user->fresh(['roles', 'panels']);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Phone verification removed']);
    }

    public function registerUser(UserService $userService): void
    {
        $this->validate([
            'newUserName'        => 'required|min:3|max:255',
            'newUserEmail'       => 'required|email|unique:users,email',
            'newUserPassword'    => 'required|min:8',
            'newUserGender'      => 'nullable|exists:genders,slug',
            'newUserPhone'       => 'nullable|string|max:20',
            'newUserCountryCode' => 'nullable|exists:countries,phone_code',
            'newUserRole'        => 'nullable|exists:roles,name',
            'newUserAddress'     => 'nullable|max:500',
            'newUserBio'         => 'nullable|max:1000',
        ]);

        $user = $userService->create([
            'name'         => $this->newUserName,
            'email'        => $this->newUserEmail,
            'password'     => $this->newUserPassword,
            'gender'       => $this->newUserGender ?: null,
            'phone'        => $this->newUserPhone ?: null,
            'country_code' => $this->newUserCountryCode ?: null,
            'address'      => $this->newUserAddress ?: null,
            'bio'          => $this->newUserBio ?: null,
        ]);

        if ($this->newUserRole) {
            $userService->assignRole($user, $this->newUserRole);
        }

        if (! empty($this->newUserPanels)) {
            $user->panels()->sync($this->newUserPanels);
        }

        activity('users')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'name'   => $user->name,
                'email'  => $user->email,
                'role'   => $this->newUserRole ?: null,
                'panels' => $this->newUserPanels,
            ])
            ->event('created')
            ->log("User \"{$user->name}\" ({$user->email}) was created");

        $this->reset([
            'newUserName', 'newUserEmail', 'newUserPassword',
            'newUserGender', 'newUserPhone', 'newUserCountryCode',
            'newUserRole', 'newUserPanels', 'newUserAddress', 'newUserBio',
        ]);
        $this->UserModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'User created successfully']);
    }
}
