<?php

namespace App\Http\Middleware;

use App\Services\BlockGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces a narrow block scope (orders, checkout, account_panel) on the
 * specific routes that scope covers — unlike EnforceBlocks (full_site),
 * this doesn't hide the whole site, just refuses the one action, with a
 * message the visitor can actually see and act on.
 */
class BlockScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        if (app(BlockGuard::class)->isBlocked($request, $scope)) {
            abort(403, 'This action is not available for your account.');
        }

        return $next($request);
    }
}
