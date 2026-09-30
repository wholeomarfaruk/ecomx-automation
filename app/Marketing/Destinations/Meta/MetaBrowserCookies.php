<?php

namespace App\Marketing\Destinations\Meta;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-party _fbp / _fbc cookies, set server-side because this storefront
 * runs no Meta Pixel to create them. Same format and 90-day lifetime the
 * Pixel (and Meta's own Parameter Builder library) use, so a Pixel added
 * later simply adopts them:
 * https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc
 *
 * Both are excluded from Laravel's cookie encryption (bootstrap/app.php) —
 * a Pixel-written value is plaintext and would fail to decrypt.
 */
final class MetaBrowserCookies
{
    public const FBP = '_fbp';

    public const FBC = '_fbc';

    private const LIFETIME_MINUTES = 60 * 24 * 90;

    /**
     * Creates whichever cookie is missing or stale and also writes it into
     * the request's own cookie bag, so events recorded later in this same
     * request (e.g. ViewContent from a Livewire mount on an ad landing
     * page) already carry it.
     *
     * @return array<string, string> cookies to send back via attach()
     */
    public function prepare(Request $request): array
    {
        if (! in_array('meta', config('marketing.destinations', []), true)) {
            return [];
        }

        $nowMs = (int) floor(microtime(true) * 1000);
        $cookies = [];

        // Subdomain index 1 is what Meta says to use for server-generated values.
        if (blank($request->cookie(self::FBP))) {
            $cookies[self::FBP] = "fb.1.{$nowMs}.".random_int(1000000000, 9999999999);
        }

        // A new ad click replaces the old click id; revisiting with the same
        // one keeps the original cookie (and its creation time). Meta: the
        // click id is case sensitive, so it's used exactly as received.
        $fbclid = $request->query('fbclid');

        if (is_string($fbclid) && $fbclid !== '' && $this->clickIdOf($request->cookie(self::FBC)) !== $fbclid) {
            $cookies[self::FBC] = "fb.1.{$nowMs}.{$fbclid}";
        }

        foreach ($cookies as $name => $value) {
            $request->cookies->set($name, $value);
        }

        return $cookies;
    }

    /** @param array<string, string> $cookies */
    public function attach(Response $response, array $cookies): void
    {
        foreach ($cookies as $name => $value) {
            $response->headers->setCookie(Cookie::make(
                $name,
                $value,
                self::LIFETIME_MINUTES,
                path: '/',
                httpOnly: false,
                sameSite: 'lax',
            ));
        }
    }

    /** fb.<index>.<time>.<fbclid>[.<appendix>] → <fbclid> */
    private function clickIdOf(?string $fbc): ?string
    {
        return $fbc ? (explode('.', $fbc)[3] ?? null) : null;
    }
}
