<?php

namespace App\Livewire\Admin\Advance;

use App\Services\DataReset\DataResetService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

/**
 * Advance → Clean Data: superadmin-only wipe of business data (orders,
 * products, customers, accounting…) to start a fresh site. Requires the
 * typed phrase and the current password.
 */
class DataReset extends Component
{
    public array $selected = [];
    public ?array $counts = null;
    public string $confirmPhrase = '';
    public string $password = '';
    public ?array $result = null;

    protected function authorizeReset(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Unauthorized action.');
    }

    public function mount(): void
    {
        $this->authorizeReset();
    }

    /** Row counts load after first paint (wire:init). */
    public function loadCounts(DataResetService $service): void
    {
        $this->authorizeReset();
        $this->counts = $service->counts();
    }

    public function cleanData(DataResetService $service): void
    {
        $this->authorizeReset();

        $this->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'in:'.implode(',', array_keys(DataResetService::groups())),
            'confirmPhrase' => 'required|in:'.DataResetService::CONFIRM_PHRASE,
            'password' => 'required|string',
        ], [
            'selected.required' => 'Select at least one data group.',
            'confirmPhrase.in' => 'Type '.DataResetService::CONFIRM_PHRASE.' exactly to confirm.',
        ]);

        $user = auth()->user();
        if (! Hash::check($this->password, $user->password)) {
            $this->addError('password', 'Password is incorrect.');

            return;
        }

        try {
            $this->result = $service->run($this->selected, $user);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Reset failed: '.$e->getMessage()]);

            return;
        }

        activity('settings')
            ->causedBy($user)
            ->event('deleted')
            ->withProperties($this->result)
            ->log('Site data cleaned: '.implode(', ', $this->result['groups']));

        $this->reset('selected', 'confirmPhrase', 'password');
        $this->counts = $service->counts();
        $this->dispatch('close-data-reset-modal');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Data cleaned successfully']);
    }

    public function render()
    {
        return view('livewire.admin.advance.data-reset', [
            'groups' => DataResetService::groups(),
            'phrase' => DataResetService::CONFIRM_PHRASE,
        ])->layout('layouts.admin.admin');
    }
}
