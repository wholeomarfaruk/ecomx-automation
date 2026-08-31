<?php

namespace App\Livewire\EcomxFashion;

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Header's account icon/dropdown — split out of the (plain Blade, non-reactive)
 * header partial so it can re-render the moment AuthModal logs someone in.
 * Without this, @guest/@auth in a static partial stays frozen at whatever
 * the page loaded with, even after AuthModal's own DOM updates and its
 * dispatch('authenticated') fire — a Livewire component update never
 * touches markup owned by a different, non-Livewire part of the page.
 * (Logout goes through a plain form POST/full page reload, not a Livewire
 * action, so it needs no listener here — the reload already picks up @guest.)
 */
class AccountMenu extends Component
{
    #[On('authenticated')]
    public function refresh(): void
    {
        // Listener re-renders by simply existing — auth()->user() is read fresh in the view.
    }

    public function render()
    {
        return view('livewire.ecomx-fashion.account-menu');
    }
}
