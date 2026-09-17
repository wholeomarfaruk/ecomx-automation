<?php

namespace App\Livewire\Admin\Frontend;

use App\Support\EcomxFashion\ThemeRegistry;
use Livewire\Component;

class PageShow extends Component
{
    public string $page;

    /** Studly-cased theme namespace, e.g. 'ecomx-anyniche' -> 'EcomxAnyniche'. Matches PageSectionManager's resolution. */
    protected static function themeNamespace(): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', ThemeRegistry::active())));
    }

    protected static function pageRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageRegistry';
    }

    public function mount(string $page): void
    {
        abort_unless(static::pageRegistry()::exists($page), 404);

        $this->page = $page;
    }

    public function render()
    {
        return view('livewire.admin.frontend.page-show', [
            'meta' => static::pageRegistry()::find($this->page),
        ])->layout('layouts.admin.admin');
    }
}
