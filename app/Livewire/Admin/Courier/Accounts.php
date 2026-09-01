<?php

namespace App\Livewire\Admin\Courier;

use App\Courier\CourierManager;
use App\Models\Courier;
use App\Models\CourierAccount;
use Livewire\Attributes\Url;
use Livewire\Component;

class Accounts extends Component
{
    #[Url]
    public ?int $courier = null;

    public bool $showForm = false;
    public ?int $editingAccountId = null;

    public string $selectedCourierKey = '';
    public string $name = '';
    public array $credentials = [];
    public bool $is_default = false;
    public bool $is_active = true;

    public function mount(): void
    {
        if ($this->courier) {
            $this->selectedCourierKey = Courier::find($this->courier)?->driver_key ?? '';
        }
    }

    public function openCreate(?string $driverKey = null): void
    {
        $this->guardManage();

        $this->resetForm();
        $this->selectedCourierKey = $driverKey ?? $this->selectedCourierKey;
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $accountId): void
    {
        $this->guardManage();

        $account = CourierAccount::with('courier')->findOrFail($accountId);

        $this->editingAccountId = $account->id;
        $this->selectedCourierKey = $account->courier->driver_key;
        $this->name = $account->name;
        $this->credentials = $account->credentials ?? [];
        $this->is_default = $account->is_default;
        $this->is_active = $account->is_active;
        $this->showForm = true;
    }

    public function updatedSelectedCourierKey(): void
    {
        if (! $this->editingAccountId) {
            $this->credentials = [];
        }
    }

    public function save(): void
    {
        $this->guardManage();

        $this->validate([
            'selectedCourierKey' => 'required|string',
            'name' => 'required|string|max:150',
        ]);

        $courier = Courier::where('driver_key', $this->selectedCourierKey)->firstOrFail();

        if ($this->is_default) {
            CourierAccount::where('courier_id', $courier->id)->update(['is_default' => false]);
        }

        $account = CourierAccount::updateOrCreate(
            ['id' => $this->editingAccountId],
            [
                'courier_id' => $courier->id,
                'name' => $this->name,
                'credentials' => $this->credentials,
                'is_default' => $this->is_default,
                'is_active' => $this->is_active,
            ],
        );

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account saved.']);
        $this->showForm = false;
        $this->resetForm();
    }

    public function testAccount(int $accountId): void
    {
        $this->guardManage();

        $account = CourierAccount::with('courier')->findOrFail($accountId);

        $response = app(CourierManager::class)->test($account->courier->driver_key);

        $account->update(['last_tested_at' => now()]);

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Connection successful.' : ($response->errorMessage ?? 'Connection failed.'),
        ]);
    }

    public function checkBalance(int $accountId): void
    {
        $this->guardManage();

        $account = CourierAccount::with('courier')->findOrFail($accountId);

        $response = app(CourierManager::class)->balance($account->courier->driver_key);

        if ($response->success) {
            $account->update([
                'last_balance' => $response->data['balance'] ?? null,
                'last_balance_check_at' => now(),
            ]);
        }

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Balance updated.' : ($response->errorMessage ?? 'Balance check failed.'),
        ]);
    }

    public function toggleActive(int $accountId): void
    {
        $this->guardManage();

        $account = CourierAccount::findOrFail($accountId);
        $account->update(['is_active' => ! $account->is_active]);
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingAccountId', 'name', 'credentials', 'is_default']);
    }

    protected function guardManage(): void
    {
        if (! auth()->user()->can('courier_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (! auth()->user()->can('courier_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $couriers = Courier::orderBy('sort_order')->get();

        $accounts = CourierAccount::with('courier')
            ->when($this->courier, fn ($q) => $q->where('courier_id', $this->courier))
            ->orderBy('courier_id')
            ->orderByDesc('is_default')
            ->get();

        $selectedMeta = $this->selectedCourierKey
            ? collect(app(CourierManager::class)->installedCouriers())->firstWhere('key', $this->selectedCourierKey)
            : null;

        return view('livewire.admin.courier.accounts', [
            'couriers' => $couriers,
            'accounts' => $accounts,
            'selectedMeta' => $selectedMeta,
        ])->layout('layouts.admin.admin');
    }
}
