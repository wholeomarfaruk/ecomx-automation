<?php

namespace App\Http\Middleware;

use App\Models\Block;
use App\Services\BlockGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the full_site block scope on every web request — this is the
 * "user ke site show korbe na" requirement. Runs after DeviceTracker (needs
 * $device already resolved) and after the session starts (needs an
 * authenticated user, if any).
 *
 * DeviceTracker's own visit logging is untouched by this: it runs in
 * DeviceTracker::terminate(), after this middleware has already decided
 * whether to abort — so a blocked visitor's attempt still shows up in the
 * Visits tab, only the response they receive is a 404.
 *
 * Narrower scopes (orders, checkout, account_panel) are NOT enforced here —
 * see the `block.scope` alias below, applied only to the specific routes
 * that scope covers.
 */
class EnforceBlocks
{
    public function handle(Request $request, Closure $next): Response
    {
        // Storefront only — an IP/device/customer block must never lock
        // staff out of /admin, even if they share the blocked network or
        // happen to browse from the blocked device/browser profile.
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        if (app(BlockGuard::class)->isBlocked($request, Block::SCOPE_FULL_SITE)) {
            abort(404);
        }

        return $next($request);
    }
}
