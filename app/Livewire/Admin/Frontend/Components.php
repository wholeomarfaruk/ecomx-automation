<?php

namespace App\Livewire\Admin\Frontend;

use Livewire\Component;

class Components extends Component
{
    public function render()
    {
        return view('livewire.admin.frontend.coming-soon', [
            'title' => 'Components',
            'description' => 'Header, footer, and other reusable component settings are planned but not built yet.',
        ])->layout('layouts.admin.admin');
    }
}
