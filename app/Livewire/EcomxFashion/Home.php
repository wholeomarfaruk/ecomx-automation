<?php

namespace App\Livewire\EcomxFashion;

use App\Livewire\EcomxFashion\Concerns\RendersPageSections;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Home extends Component
{
    use RendersPageSections;

    public function render()
    {
        return view('ecomx-fashion.livewire.home', [
            'sections' => $this->activeSections('home'),
        ]);
    }
}
