<?php

namespace App\Livewire\Admin\Localization;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LanguageList extends Component
{
    public function toggleActive(int $id): void
    {
        $language = Language::findOrFail($id);

        if ($language->is_default && $language->is_active) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'The default language cannot be deactivated']);
            return;
        }

        $newStatus = ! $language->is_active;
        $language->update(['is_active' => $newStatus]);

        Cache::forget('languages:active');

        activity('localization')
            ->causedBy(auth()->user())
            ->performedOn($language)
            ->withProperties([
                'old'        => ['is_active' => ! $newStatus],
                'attributes' => ['is_active' => $newStatus],
            ])
            ->event('updated')
            ->log("Language \"{$language->name}\" was " . ($newStatus ? 'enabled' : 'disabled'));

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $language->name . ' ' . ($newStatus ? 'enabled' : 'disabled'),
        ]);
    }

    public function setDefault(int $id): void
    {
        $language = Language::findOrFail($id);

        if ($language->is_default) {
            return;
        }

        if (! $language->is_active) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only an active language can be set as default']);
            return;
        }

        DB::transaction(function () use ($language) {
            Language::where('is_default', true)->update(['is_default' => false]);
            $language->update(['is_default' => true]);
        });

        Cache::forget('languages:active');

        activity('localization')
            ->causedBy(auth()->user())
            ->performedOn($language)
            ->event('updated')
            ->log("Language \"{$language->name}\" was set as default");

        $this->dispatch('toast', ['type' => 'success', 'message' => $language->name . ' is now the default language']);
    }

    public function deleteLanguage(int $id): void
    {
        $language = Language::findOrFail($id);

        if ($language->is_default) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'The default language cannot be deleted']);
            return;
        }

        $name = $language->name;
        $language->delete();

        Cache::forget('languages:active');

        activity('localization')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Language \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Language deleted']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.localization.language-list', [
            'languages' => Language::orderBy('sort_order')->orderBy('name')->get(),
        ])->layout('layouts.admin.admin');
    }
}
