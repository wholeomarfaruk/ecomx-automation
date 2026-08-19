<?php

namespace App\Http\Responses;

use App\Models\Panel;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();

        // Find the first active panel the user belongs to, ordered by sort_order
        $panel = Panel::whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        if ($panel && $panel->route_name) {
            return redirect()->intended(route($panel->route_name));
        }

        // Fallback: superadmin/admin → admin panel, everyone else → public site
        // (there's no separate authenticated "app" area — only admin + website).
        if ($user->hasRole(['superadmin', 'admin'])) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(url('/'));
    }
}
