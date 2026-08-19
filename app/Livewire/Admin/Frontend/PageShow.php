<?php

namespace App\Livewire\Admin\Frontend;

use App\Support\EcomxFashion\PageRegistry;
use Livewire\Component;

class PageShow extends Component
{
    public string $page;

    public function mount(string $page): void
    {
        abort_unless(PageRegistry::exists($page), 404);

        $this->page = $page;
    }

    public function render()
    {
        return view('livewire.admin.frontend.page-show', [
            'meta' => PageRegistry::find($this->page),
        ])->layout('layouts.admin.admin');
    }
}
