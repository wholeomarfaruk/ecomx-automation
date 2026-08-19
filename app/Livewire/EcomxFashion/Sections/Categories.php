<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Models\Category;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Categories extends Component
{
    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.categories');
    }

    public function render()
    {
        $categories = Category::active()
            ->withCount('products')
            ->get()
            ->map(fn (Category $c) => [
                'name' => $c->name,
                'slug' => $c->slug,
                'count' => $c->products_count . ' ' . ($c->products_count === 1 ? 'piece' : 'pieces'),
                'image' => $c->featured_image_id ? file_path($c->featured_image_id) : null,
            ]);

        return view('ecomx-fashion.livewire.sections.categories', [
            'categories' => $categories,
        ]);
    }
}
