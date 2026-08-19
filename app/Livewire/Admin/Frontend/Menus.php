<?php

namespace App\Livewire\Admin\Frontend;

use Livewire\Component;

class Menus extends Component
{
    public function render()
    {
        return view('livewire.admin.frontend.coming-soon', [
            'title' => 'Menus',
            'description' => 'Header and footer navigation link management is planned but not built yet.',
        ])->layout('layouts.admin.admin');
    }
}
