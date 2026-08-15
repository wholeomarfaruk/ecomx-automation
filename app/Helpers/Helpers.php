<?php
//Helpers/Helpers.php
use App\Models\File;
use App\Models\Language;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

if (!function_exists('file_path')) {
    function file_path($id, $type = 'original')
    {
        $file = File::with('items')->find($id);

        if (!$file) {
            return null;
        }

        $item = $file->items->firstWhere('type', $type);

        return $item ? asset('storage/' . $item->path) : null;
    }
}

if (!function_exists('site_timezone')) {
    /**
     * The site's configured display timezone (falls back to config('app.timezone'), i.e. UTC storage).
     */
    function site_timezone(): string
    {
        $timezone = Setting::get('timezone', config('app.timezone'), 'localization');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone');
    }
}

if (!function_exists('local_time')) {
    /**
     * Convert a stored (UTC) datetime to the site's configured display timezone.
     * Storage/casting stays UTC; only this converts for display.
     */
    function local_time(\DateTimeInterface|string|null $date): ?Carbon
    {
        if ($date === null) {
            return null;
        }

        return Carbon::parse($date)->setTimezone(site_timezone());
    }
}

if (!function_exists('active_languages')) {
    /**
     * All active languages, cached. Bust via Cache::forget('languages:active')
     * whenever a Language row's is_active/direction/is_default changes.
     *
     * @return \Illuminate\Support\Collection<int, Language>
     */
    function active_languages(): \Illuminate\Support\Collection
    {
        return Cache::rememberForever('languages:active', function () {
            return Language::active()->ordered()->get();
        });
    }
}

if (!function_exists('site_direction')) {
    /**
     * Layout direction ('ltr'|'rtl') for a given language code, or the default
     * language if no code is given. Used by the public frontend only — the
     * admin panel always renders ltr regardless of language data.
     */
    function site_direction(?string $code = null): string
    {
        $languages = active_languages();

        $language = $code
            ? $languages->firstWhere('code', $code)
            : $languages->firstWhere('is_default', true);

        return $language->direction ?? 'ltr';
    }
}
