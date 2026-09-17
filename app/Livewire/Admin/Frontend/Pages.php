<?php

namespace App\Livewire\Admin\Frontend;

use App\Support\EcomxFashion\ThemeRegistry;
use Livewire\Component;

class Pages extends Component
{
    /** Studly-cased theme namespace, e.g. 'ecomx-anyniche' -> 'EcomxAnyniche'. Matches PageSectionManager's resolution. */
    protected static function themeNamespace(): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', ThemeRegistry::active())));
    }

    protected static function pageRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageRegistry';
    }

    public function render()
    {
        return view('livewire.admin.frontend.pages', [
            'pages' => static::pageRegistry()::all(),
        ])->layout('layouts.admin.admin');
    }
}
