<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetFrontendLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $languages = active_languages();

        $language = $languages->firstWhere('code', $request->cookie('frontend_locale'))
            ?? $languages->firstWhere('is_default', true)
            ?? $languages->first();

        if ($language) {
            App::setLocale($language->code);
        }

        View::share('activeLanguage', $language);

        return $next($request);
    }
}
