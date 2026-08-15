<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PanelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $panel): Response
    {

        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->hasPanel($panel)) {
            abort(403, "Access Denied: You do not have {$panel} panel access to this panel.");
        }

        return $next($request);
    }
}
