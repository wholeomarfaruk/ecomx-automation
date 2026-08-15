<?php

namespace App\Livewire\Admin\Permissions;

use App\Models\Panel;
use App\Models\User;
use Livewire\Component;

class PanelList extends Component
{
    public ?int $viewingPanelId = null;
    public string $userSearch = '';

    public function mount(): void
    {
        if (! auth()->user()->can('panel.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function toggleActive(int $id): void
    {
        if (! auth()->user()->can('panel.edit')) {
            abort(403);
        }

        $panel     = Panel::findOrFail($id);
        $newStatus = ! $panel->is_active;
        $panel->update(['is_active' => $newStatus]);

        activity('permissions')
            ->causedBy(auth()->user())
            ->performedOn($panel)
            ->withProperties([
                'old'        => ['is_active' => ! $newStatus],
                'attributes' => ['is_active' => $newStatus],
            ])
            ->event('updated')
            ->log("Panel \"{$panel->name}\" was " . ($newStatus ? 'activated' : 'deactivated'));

        $this->dispatch('toast', type: 'success', message: 'Panel status updated.');
    }

    public function removeUser(int $panelId, int $userId): void
    {
        if (! auth()->user()->can('panel.edit')) {
            abort(403);
        }

        $panel = Panel::findOrFail($panelId);
        $user  = User::findOrFail($userId);

        $panel->users()->detach($userId);

        activity('permissions')
            ->causedBy(auth()->user())
            ->performedOn($panel)
            ->withProperties([
                'user_id'    => $user->id,
                'user_name'  => $user->name,
                'user_email' => $user->email,
            ])
            ->event('updated')
            ->log("User \"{$user->name}\" was removed from \"{$panel->name}\" panel");

        $this->dispatch('toast', type: 'success', message: 'User removed from panel.');
    }

    public function updatedViewingPanelId(): void
    {
        $this->userSearch = '';
    }

    public function render()
    {
        $panels = Panel::withCount('users')
            ->with('users:id,name,email')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($panel) {
                $panel->resolved_url = $panel->route_name && \Route::has($panel->route_name)
                    ? route($panel->route_name)
                    : null;
                return $panel;
            });

        $viewingPanel = null;
        if ($this->viewingPanelId) {
            $viewingPanel = Panel::withCount('users')
                ->with(['users' => function ($q) {
                    $q->select('users.id', 'users.name', 'users.email')
                      ->when($this->userSearch, fn ($q) => $q
                          ->where('users.name', 'like', "%{$this->userSearch}%")
                          ->orWhere('users.email', 'like', "%{$this->userSearch}%")
                      );
                }])
                ->find($this->viewingPanelId);
        }

        return view('livewire.admin.permissions.panel-list', compact('panels', 'viewingPanel'))
            ->layout('layouts.admin.admin');
    }
}
