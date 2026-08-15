<?php

namespace App\Livewire\Admin\Localization;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class LanguageForm extends Component
{
    public int    $languageId  = 0;
    public string $code        = '';
    public string $name        = '';
    public string $native_name = '';
    public string $direction   = 'ltr';
    public bool   $is_active   = true;

    public function mount(int $id = 0): void
    {
        if ($id > 0) {
            $language = Language::findOrFail($id);

            $this->languageId  = $language->id;
            $this->code        = $language->code;
            $this->name        = $language->name;
            $this->native_name = $language->native_name;
            $this->direction   = $language->direction;
            $this->is_active   = $language->is_active;
        }
    }

    public function save(): void
    {
        $this->validate([
            'code'        => 'required|string|max:10|unique:languages,code,' . $this->languageId,
            'name'        => 'required|string|max:100',
            'native_name' => 'required|string|max:100',
            'direction'   => 'required|in:ltr,rtl',
        ]);

        if ($this->languageId > 0) {
            $language = Language::findOrFail($this->languageId);

            if ($language->is_default && $this->is_active === false) {
                $this->addError('is_active', 'The default language cannot be deactivated.');
                return;
            }

            $old = $language->only(['code', 'name', 'native_name', 'direction', 'is_active']);

            $language->update([
                'code'        => $this->code,
                'name'        => $this->name,
                'native_name' => $this->native_name,
                'direction'   => $this->direction,
                'is_active'   => $this->is_active,
            ]);

            Cache::forget('languages:active');

            activity('localization')
                ->causedBy(auth()->user())
                ->performedOn($language)
                ->withProperties([
                    'old'        => $old,
                    'attributes' => $language->only(['code', 'name', 'native_name', 'direction', 'is_active']),
                ])
                ->event('updated')
                ->log("Language \"{$language->name}\" was updated");

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Language updated successfully']);
        } else {
            $maxOrder = Language::max('sort_order') ?? 0;

            $language = Language::create([
                'code'        => $this->code,
                'name'        => $this->name,
                'native_name' => $this->native_name,
                'direction'   => $this->direction,
                'is_active'   => $this->is_active,
                'is_default'  => false,
                'sort_order'  => $maxOrder + 1,
            ]);

            Cache::forget('languages:active');

            activity('localization')
                ->causedBy(auth()->user())
                ->performedOn($language)
                ->withProperties(['code' => $language->code, 'name' => $language->name])
                ->event('created')
                ->log("Language \"{$language->name}\" was added");

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Language added successfully']);
        }

        $this->redirect(route('admin.settings.languages'), navigate: false);
    }

    public function render(): mixed
    {
        return view('livewire.admin.localization.language-form')->layout('layouts.admin.admin');
    }
}
