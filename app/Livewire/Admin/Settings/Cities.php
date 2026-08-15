<?php

namespace App\Livewire\Admin\Settings;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Livewire\Component;
use Livewire\WithPagination;

class Cities extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterCountry = '';
    public string $filterState   = '';
    public string $filterActive  = '';

    public bool   $createModal   = false;
    public string $newCountryId  = '';
    public string $newStateId    = '';
    public string $newName       = '';
    public string $newLocalName  = '';
    public string $newCode       = '';

    public bool   $editModal        = false;
    public ?int   $editingId        = null;
    public string $editCountryId    = '';
    public string $editStateId      = '';
    public string $editName         = '';
    public string $editLocalName    = '';
    public string $editCode         = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterCountry(): void
    {
        $this->filterState = '';
        $this->resetPage();
    }
    public function updatingFilterState(): void  { $this->resetPage(); }
    public function updatingFilterActive(): void { $this->resetPage(); }

    public function updatedNewCountryId(): void { $this->newStateId = ''; }
    public function updatedEditCountryId(): void { $this->editStateId = ''; }

    public function toggleActive(int $id): void
    {
        $city      = City::findOrFail($id);
        $newStatus = ! $city->is_active;
        $city->update(['is_active' => $newStatus]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($city)
            ->withProperties([
                'old'        => ['is_active' => ! $newStatus],
                'attributes' => ['is_active' => $newStatus],
            ])
            ->event('updated')
            ->log("City \"{$city->name}\" was " . ($newStatus ? 'activated' : 'deactivated'));

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $city->name . ' ' . ($newStatus ? 'activated' : 'deactivated'),
        ]);
    }

    public function createCity(): void
    {
        $this->validate([
            'newCountryId' => 'required|exists:countries,id',
            'newStateId'   => 'required|exists:states,id',
            'newName'      => 'required|string|max:100',
            'newLocalName' => 'nullable|string|max:100',
            'newCode'      => 'nullable|string|max:50',
        ]);

        $city = City::create([
            'country_id' => $this->newCountryId,
            'state_id'   => $this->newStateId,
            'name'       => $this->newName,
            'local_name' => $this->newLocalName ?: null,
            'code'       => $this->newCode ?: null,
            'is_active'  => true,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($city)
            ->event('created')
            ->log("City \"{$city->name}\" was added");

        $this->reset(['newCountryId', 'newStateId', 'newName', 'newLocalName', 'newCode', 'createModal']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'City added successfully']);
    }

    public function editCity(int $id): void
    {
        $city = City::findOrFail($id);

        $this->editingId     = $city->id;
        $this->editCountryId = (string) $city->country_id;
        $this->editStateId   = (string) $city->state_id;
        $this->editName      = $city->name;
        $this->editLocalName = $city->local_name ?? '';
        $this->editCode      = $city->code ?? '';
        $this->editModal     = true;
    }

    public function updateCity(): void
    {
        $city = City::findOrFail($this->editingId);

        $this->validate([
            'editCountryId' => 'required|exists:countries,id',
            'editStateId'   => 'required|exists:states,id',
            'editName'      => 'required|string|max:100',
            'editLocalName' => 'nullable|string|max:100',
            'editCode'      => 'nullable|string|max:50',
        ]);

        $city->update([
            'country_id' => $this->editCountryId,
            'state_id'   => $this->editStateId,
            'name'       => $this->editName,
            'local_name' => $this->editLocalName ?: null,
            'code'       => $this->editCode ?: null,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($city)
            ->event('updated')
            ->log("City \"{$city->name}\" was updated");

        $this->reset(['editingId', 'editCountryId', 'editStateId', 'editName', 'editLocalName', 'editCode', 'editModal']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'City updated successfully']);
    }

    public function deleteCity(int $id): void
    {
        $city = City::findOrFail($id);
        $name = $city->name;

        $city->delete();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("City \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'City deleted']);
    }

    public function render(): mixed
    {
        $cities = City::query()
            ->with(['country', 'state'])
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('local_name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterCountry !== '', fn($q) => $q->where('country_id', $this->filterCountry))
            ->when($this->filterState   !== '', fn($q) => $q->where('state_id', $this->filterState))
            ->when($this->filterActive  !== '', fn($q) => $q->where('is_active', (bool) $this->filterActive))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $countries = Country::orderBy('name')->get(['id', 'name', 'emoji_flag']);

        $filterStates = $this->filterCountry !== ''
            ? State::where('country_id', $this->filterCountry)->orderBy('name')->get(['id', 'name'])
            : collect();

        $newStates = $this->newCountryId !== ''
            ? State::where('country_id', $this->newCountryId)->orderBy('name')->get(['id', 'name'])
            : collect();

        $editStates = $this->editCountryId !== ''
            ? State::where('country_id', $this->editCountryId)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.admin.settings.cities', compact('cities', 'countries', 'filterStates', 'newStates', 'editStates'))
            ->layout('layouts.admin.admin');
    }
}
