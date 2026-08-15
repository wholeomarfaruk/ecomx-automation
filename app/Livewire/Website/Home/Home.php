<?php

namespace App\Livewire\Website\Home;

use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        return view('livewire.website.home.home')->layout('layouts.website.website');
    }
}
