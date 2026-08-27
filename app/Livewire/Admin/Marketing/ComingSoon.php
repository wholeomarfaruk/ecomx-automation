<?php

namespace App\Livewire\Admin\Marketing;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class ComingSoon extends Component
{
    public string $title;
    public string $description;
    public string $icon;

    public function mount(string $title, string $description, string $icon = 'chart'): void
    {
        $this->title = $title;
        $this->description = $description;
        $this->icon = $icon;
    }

    public function render()
    {
        return view('livewire.admin.marketing.coming-soon');
    }
}
