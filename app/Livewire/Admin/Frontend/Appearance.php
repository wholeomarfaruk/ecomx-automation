<?php

namespace App\Livewire\Admin\Frontend;

use Livewire\Component;

class Appearance extends Component
{
    public function render()
    {
        return view('livewire.admin.frontend.appearance')->layout('layouts.admin.admin');
    }
}
