<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Component;

class Currencies extends Component
{
    public bool   $createModal    = false;
    public string $newCode        = '';
    public string $newName        = '';
    public string $newSymbol      = '';
    public int    $newDecimalPlaces = 2;

    public function updatedNewCode(string $value): void
    {
        $this->newCode = Str::upper($value);
    }

    public function createCurrency(): void
    {
        $this->validate([
            'newCode'          => 'required|string|max:10|unique:currencies,code',
            'newName'          => 'required|string|max:100',
            'newSymbol'        => 'nullable|string|max:10',
            'newDecimalPlaces' => 'required|integer|min:0|max:6',
        ]);

        $currency = Currency::create([
            'code'           => $this->newCode,
            'name'           => $this->newName,
            'symbol'         => $this->newSymbol,
            'decimal_places' => $this->newDecimalPlaces,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($currency)
            ->withProperties(['code' => $currency->code, 'name' => $currency->name])
            ->event('created')
            ->log("Currency \"{$currency->code}\" was added");

        $this->reset(['newCode', 'newName', 'newSymbol', 'createModal']);
        $this->newDecimalPlaces = 2;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Currency added successfully']);
    }

    public function deleteCurrency(int $id): void
    {
        $currency = Currency::findOrFail($id);

        if (Setting::get('currency', null, 'localization') === $currency->code) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This is the site\'s active currency and cannot be deleted']);
            return;
        }

        $code = $currency->code;
        $currency->delete();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties(['code' => $code])
            ->event('deleted')
            ->log("Currency \"{$code}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Currency deleted']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.currencies', [
            'currencies'     => Currency::orderBy('code')->get(),
            'activeCurrency' => Setting::get('currency', null, 'localization'),
        ])->layout('layouts.admin.admin');
    }
}
