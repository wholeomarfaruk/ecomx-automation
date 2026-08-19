<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Support\EcomxFashion\Catalog;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Instagram extends Component
{
    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.instagram');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.instagram', [
            'instagram' => Catalog::instagram(),
        ]);
    }
}
