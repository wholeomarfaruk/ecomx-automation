<?php

namespace App\Livewire\Admin\ThemeEngine;

use App\Support\EcomxFashion\PaletteRegistry;
use Livewire\Component;

class PaletteManager extends Component
{
    public function activate(string $palette): void
    {
        PaletteRegistry::setActive($palette);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => ucfirst($palette) . ' is now the active palette.',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.theme-engine.palette-manager', [
            'palettes' => config('ecomx-fashion.palettes', []),
            'swatches' => PaletteRegistry::swatches(),
            'active'   => PaletteRegistry::active(),
        ]);
    }
}
