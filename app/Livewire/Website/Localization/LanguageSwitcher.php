<?php

namespace App\Livewire\Website\Localization;

use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    /**
     * Runs entirely over Livewire's background AJAX request — no page navigation.
     * The browser reload is triggered client-side (see language-switcher.blade.php)
     * once this response comes back, so the new cookie is picked up on next paint.
     */
    public function switchTo(string $code): void
    {
        $language = active_languages()->firstWhere('code', $code);

        if (! $language) {
            return;
        }

        Cookie::queue('frontend_locale', $language->code, 60 * 24 * 365);

        $this->dispatch('language-switched', code: $language->code);
    }

    public function render(): mixed
    {
        $languages = active_languages();

        // Resolved independently of the SetFrontendLocale middleware's View::share,
        // since Livewire's background /livewire/update requests bypass that route
        // group entirely and would otherwise see activeLanguage as undefined.
        $activeLanguage = $languages->firstWhere('code', request()->cookie('frontend_locale'))
            ?? $languages->firstWhere('is_default', true)
            ?? $languages->first();

        return view('livewire.website.localization.language-switcher', [
            'languages'      => $languages,
            'activeLanguage' => $activeLanguage,
        ]);
    }
}
