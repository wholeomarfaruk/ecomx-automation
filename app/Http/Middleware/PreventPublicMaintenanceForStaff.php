<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Lets any logged-in admin-panel user (superadmin, admin, staff — same access
 * check as PanelMiddleware) browse the public site normally while maintenance
 * mode is on, so they can preview it before bringing it back online. Anonymous
 * visitors still see the standard maintenance response.
 *
 * This does NOT extend Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance
 * because that class's except() list is shared static state — routes excluded
 * there (e.g. '/', so the global maintenance check can't 503 it before session/
 * auth is even available) would otherwise also be treated as excluded here,
 * defeating the whole point of this middleware. This enforces independently.
 */
class PreventPublicMaintenanceForStaff
{
    public function handle(Request $request, Closure $next)
    {
        if (! app()->isDownForMaintenance()) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->hasPanel('admin')) {
            return $next($request);
        }

        throw new HttpException(503, 'Service Unavailable');
    }
}
