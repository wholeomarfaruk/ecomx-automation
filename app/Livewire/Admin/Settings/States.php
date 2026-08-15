<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Country;
use App\Models\State;
use Livewire\Component;
use Livewire\WithPagination;

class States extends Component
{
    use WithPagination;

    public string $search         = '';
    public string $filterCountry  = '';
    public string $filterActive   = '';

    public bool   $createModal      = false;
    public string $newCountryId     = '';
    public string $newName          = '';
    public string $newLocalName     = '';
    public string $newCode          = '';

    public bool   $editModal        = false;
    public ?int   $editingId        = null;
    public string $editCountryId    = '';
    public string $editName         = '';
    public string $editLocalName    = '';
    public string $editCode         = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterCountry(): void { $this->resetPage(); }
    public function updatingFilterActive(): void  { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $state     = State::findOrFail($id);
        $newStatus = ! $state->is_active;
        $state->update(['is_active' => $newStatus]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($state)
            ->withProperties([
                'old'        => ['is_active' => ! $newStatus],
                'attributes' => ['is_active' => $newStatus],
            ])
            ->event('updated')
            ->log("State \"{$state->name}\" was " . ($newStatus ? 'activated' : 'deactivated'));

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $state->name . ' ' . ($newStatus ? 'activated' : 'deactivated'),
        ]);
    }

    public function createState(): void
    {
        $this->validate([
            'newCountryId' => 'required|exists:countries,id',
            'newName'      => 'required|string|max:100',
            'newLocalName' => 'nullable|string|max:100',
            'newCode'      => 'nullable|string|max:50',
        ]);

        $state = State::create([
            'country_id' => $this->newCountryId,
            'name'       => $this->newName,
            'local_name' => $this->newLocalName ?: null,
            'code'       => $this->newCode ?: null,
            'is_active'  => true,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($state)
            ->event('created')
            ->log("State \"{$state->name}\" was added");

        $this->reset(['newCountryId', 'newName', 'newLocalName', 'newCode', 'createModal']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'State added successfully']);
    }

    public function editState(int $id): void
    {
        $state = State::findOrFail($id);

        $this->editingId     = $state->id;
        $this->editCountryId = (string) $state->country_id;
        $this->editName      = $state->name;
        $this->editLocalName = $state->local_name ?? '';
        $this->editCode      = $state->code ?? '';
        $this->editModal     = true;
    }

    public function updateState(): void
    {
        $state = State::findOrFail($this->editingId);

        $this->validate([
            'editCountryId' => 'required|exists:countries,id',
            'editName'      => 'required|string|max:100',
            'editLocalName' => 'nullable|string|max:100',
            'editCode'      => 'nullable|string|max:50',
        ]);

        $state->update([
            'country_id' => $this->editCountryId,
            'name'       => $this->editName,
            'local_name' => $this->editLocalName ?: null,
            'code'       => $this->editCode ?: null,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($state)
            ->event('updated')
            ->log("State \"{$state->name}\" was updated");

        $this->reset(['editingId', 'editCountryId', 'editName', 'editLocalName', 'editCode', 'editModal']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'State updated successfully']);
    }

    public function deleteState(int $id): void
    {
        $state = State::findOrFail($id);
        $name  = $state->name;

        $state->delete();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("State \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'State deleted']);
    }

    public function render(): mixed
    {
        $states = State::query()
            ->with('country')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('local_name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterCountry !== '', fn($q) => $q->where('country_id', $this->filterCountry))
            ->when($this->filterActive  !== '', fn($q) => $q->where('is_active', (bool) $this->filterActive))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $countries = Country::orderBy('name')->get(['id', 'name', 'emoji_flag']);

        return view('livewire.admin.settings.states', compact('states', 'countries'))
            ->layout('layouts.admin.admin');
    }
}
