<?php

namespace App\Livewire\Admin\Frontend;

use App\Support\IconLibrary;
use Livewire\Component;

/**
 * View-only gallery of every icon in App\Support\IconLibrary — the shared
 * source behind <x-icon>/<x-anyniche::icon> (both themes) and the Menus
 * icon picker (see Menus::icons()). No config here: this page exists purely
 * as a reference so an admin/developer can see what's available and copy a
 * name, not to add/remove icons (that's still a code change to IconLibrary).
 */
class Icons extends Component
{
    public string $search = '';

    public function getOutlineIconsProperty(): array
    {
        return $this->filter(array_keys(IconLibrary::ICONS));
    }

    public function getBrandIconsProperty(): array
    {
        return $this->filter(array_keys(IconLibrary::BRAND));
    }

    protected function filter(array $names): array
    {
        $needle = trim(mb_strtolower($this->search));

        if ($needle === '') {
            return $names;
        }

        return array_values(array_filter($names, fn (string $name) => str_contains($name, $needle)));
    }

    public function render()
    {
        return view('livewire.admin.frontend.icons')
            ->layout('layouts.admin.admin');
    }
}
