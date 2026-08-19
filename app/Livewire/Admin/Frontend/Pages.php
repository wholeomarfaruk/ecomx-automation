<?php

namespace App\Livewire\Admin\Frontend;

use App\Support\EcomxFashion\PageRegistry;
use Livewire\Component;

class Pages extends Component
{
    public function render()
    {
        return view('livewire.admin.frontend.pages', [
            'pages' => PageRegistry::all(),
        ])->layout('layouts.admin.admin');
    }
}
