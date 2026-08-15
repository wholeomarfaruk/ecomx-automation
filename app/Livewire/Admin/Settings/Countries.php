<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Country;
use Livewire\Component;
use Livewire\WithPagination;

class Countries extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterAllowed = '';
    public string $filterActive  = '';

    public bool    $createModal     = false;
    public string  $newName         = '';
    public string  $newLocalName    = '';
    public string  $newCode         = '';
    public string  $newPhoneCode    = '';
    public string  $newCurrencyCode = '';
    public string  $newEmojiFlag    = '';
    public string  $newTimezone     = '';

    public bool    $editModal        = false;
    public ?int    $editingId        = null;
    public string  $editName         = '';
    public string  $editLocalName    = '';
    public string  $editCode         = '';
    public string  $editPhoneCode    = '';
    public string  $editCurrencyCode = '';
    public string  $editEmojiFlag    = '';
    public string  $editTimezone     = '';

    /** @var array<int, string> */
    public array $timezoneOptions = [];

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->timezoneOptions = timezone_identifiers_list();
    }

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterAllowed(): void { $this->resetPage(); }
    public function updatingFilterActive(): void  { $this->resetPage(); }

    public function toggleRegisterAllowed(int $id): void
    {
        $country   = Country::findOrFail($id);
        $newStatus = ! $country->is_register_allowed;
        $country->update(['is_register_allowed' => $newStatus]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($country)
            ->withProperties([
                'old'        => ['is_register_allowed' => ! $newStatus],
                'attributes' => ['is_register_allowed' => $newStatus],
            ])
            ->event('updated')
            ->log("Country \"{$country->name}\" registration " . ($newStatus ? 'enabled' : 'disabled'));

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $country->name . ' registration ' . ($newStatus ? 'enabled' : 'disabled'),
        ]);
    }

    public function toggleActive(int $id): void
    {
        $country   = Country::findOrFail($id);
        $newStatus = ! $country->is_active;
        $country->update(['is_active' => $newStatus]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($country)
            ->withProperties([
                'old'        => ['is_active' => ! $newStatus],
                'attributes' => ['is_active' => $newStatus],
            ])
            ->event('updated')
            ->log("Country \"{$country->name}\" was " . ($newStatus ? 'activated' : 'deactivated'));

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $country->name . ' ' . ($newStatus ? 'activated' : 'deactivated'),
        ]);
    }

    public function createCountry(): void
    {
        $this->validate([
            'newName'         => 'required|string|max:100',
            'newLocalName'    => 'nullable|string|max:100',
            'newCode'         => 'required|string|size:2|unique:countries,code',
            'newPhoneCode'    => 'required|string|max:10',
            'newCurrencyCode' => 'nullable|string|max:10',
            'newEmojiFlag'    => 'nullable|string|max:10',
            'newTimezone'     => 'nullable|string|max:255',
        ]);

        $country = Country::create([
            'name'                => $this->newName,
            'local_name'          => $this->newLocalName ?: null,
            'code'                => strtoupper($this->newCode),
            'phone_code'          => $this->newPhoneCode,
            'currency_code'       => $this->newCurrencyCode ?: null,
            'emoji_flag'          => $this->newEmojiFlag ?: null,
            'timezone'            => $this->newTimezone ?: null,
            'is_register_allowed' => false,
            'is_active'           => true,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($country)
            ->withProperties([
                'name'        => $country->name,
                'code'        => $country->code,
                'phone_code'  => $country->phone_code,
            ])
            ->event('created')
            ->log("Country \"{$country->name}\" ({$country->code}) was added");

        $this->reset(['newName', 'newLocalName', 'newCode', 'newPhoneCode', 'newCurrencyCode', 'newEmojiFlag', 'newTimezone', 'createModal']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Country added successfully']);
    }

    public function editCountry(int $id): void
    {
        $country = Country::findOrFail($id);

        $this->editingId        = $country->id;
        $this->editName         = $country->name;
        $this->editLocalName    = $country->local_name ?? '';
        $this->editCode         = $country->code;
        $this->editPhoneCode    = $country->phone_code ?? '';
        $this->editCurrencyCode = $country->currency_code ?? '';
        $this->editEmojiFlag    = $country->emoji_flag ?? '';
        $this->editTimezone     = $country->timezone ?? '';
        $this->editModal        = true;
    }

    public function updateCountry(): void
    {
        $country = Country::findOrFail($this->editingId);

        $this->validate([
            'editName'         => 'required|string|max:100',
            'editLocalName'    => 'nullable|string|max:100',
            'editCode'         => 'required|string|size:2|unique:countries,code,' . $country->id,
            'editPhoneCode'    => 'required|string|max:10',
            'editCurrencyCode' => 'nullable|string|max:10',
            'editEmojiFlag'    => 'nullable|string|max:10',
            'editTimezone'     => 'nullable|string|max:255',
        ]);

        $country->update([
            'name'          => $this->editName,
            'local_name'    => $this->editLocalName ?: null,
            'code'          => strtoupper($this->editCode),
            'phone_code'    => $this->editPhoneCode,
            'currency_code' => $this->editCurrencyCode ?: null,
            'emoji_flag'    => $this->editEmojiFlag ?: null,
            'timezone'      => $this->editTimezone ?: null,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($country)
            ->event('updated')
            ->log("Country \"{$country->name}\" ({$country->code}) was updated");

        $this->reset(['editingId', 'editName', 'editLocalName', 'editCode', 'editPhoneCode', 'editCurrencyCode', 'editEmojiFlag', 'editTimezone', 'editModal']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Country updated successfully']);
    }

    public function deleteCountry(int $id): void
    {
        $country = Country::findOrFail($id);
        $name    = $country->name;
        $code    = $country->code;

        $country->delete();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name, 'code' => $code])
            ->event('deleted')
            ->log("Country \"{$name}\" ({$code}) was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Country deleted']);
    }

    public function render(): mixed
    {
        $countries = Country::query()
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('phone_code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterAllowed !== '', fn($q) => $q->where('is_register_allowed', (bool) $this->filterAllowed))
            ->when($this->filterActive  !== '', fn($q) => $q->where('is_active',           (bool) $this->filterActive))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.settings.countries', compact('countries'))
            ->layout('layouts.admin.admin');
    }
}
