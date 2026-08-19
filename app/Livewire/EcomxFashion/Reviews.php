<?php

namespace App\Livewire\EcomxFashion;

use App\Support\EcomxFashion\Catalog;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Reviews extends Component
{
    public int $star = 0;
    public string $filter = 'All';
    public string $sort = 'Most helpful';

    public array $dist = [[5,1856],[4,312],[3,98],[2,32],[1,16]];

    public function setStar(int $s): void { $this->star = $this->star === $s ? 0 : $s; }

    public function getItemsProperty(): array
    {
        $items = collect(Catalog::reviews())
            ->filter(fn ($r) => ($this->star === 0 || $r['rating'] === $this->star)
                && ($this->filter === 'All'
                    || ($this->filter === 'With photos' && !$r['video'])
                    || ($this->filter === 'With video' && $r['video'])
                    || ($this->filter === 'Verified' && $r['verified'])));

        $items = match ($this->sort) {
            'Newest'        => $items->sortByDesc('id'),
            'Highest rated' => $items->sortByDesc('rating'),
            'Lowest rated'  => $items->sortBy('rating'),
            default         => $items->sortByDesc('helpful'),
        };

        return $items->values()->all();
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.reviews', ['items' => $this->items]);
    }
}
