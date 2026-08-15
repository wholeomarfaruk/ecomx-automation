<?php

namespace App\Livewire\Admin\Auth;

use Livewire\Component;

class Login extends Component
{
    public function render()
    {
        // No sidebar/topbar — the visitor isn't logged in yet. layouts.admin.guest
        // is the minimal branded shell shared by admin-only auth pages.
        return view('livewire.admin.auth.login')->layout('layouts.admin.guest');
    }
}
