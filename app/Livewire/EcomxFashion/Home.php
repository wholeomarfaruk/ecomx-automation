<?php

namespace App\Livewire\EcomxFashion;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Home extends Component
{
    public function render()
    {
        return view('ecomx-fashion.livewire.home');
    }
}
