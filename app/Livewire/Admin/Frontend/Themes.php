<?php

namespace App\Livewire\Admin\Frontend;

use Livewire\Component;

class Themes extends Component
{
    public function render()
    {
        return view('livewire.admin.frontend.themes')->layout('layouts.admin.admin');
    }
}
