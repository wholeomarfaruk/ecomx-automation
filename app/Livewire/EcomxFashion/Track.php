<?php

namespace App\Livewire\EcomxFashion;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Track extends Component
{
    public string $orderId = '';
    public string $phone = '';
    public bool $tracked = false;

    public array $steps = [
        ['label'=>'Order placed','sub'=>'Confirmed','done'=>true],
        ['label'=>'Processing','sub'=>'In our atelier','done'=>true],
        ['label'=>'Shipped','sub'=>'With courier','done'=>true],
        ['label'=>'Out for delivery','sub'=>'Today','done'=>false],
        ['label'=>'Delivered','sub'=>'Awaiting','done'=>false],
    ];

    public function track(): void
    {
        $this->tracked = true;
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.track');
    }
}
