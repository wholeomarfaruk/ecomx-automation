<?php

namespace App\Support\EcomxFashion;

/**
 * File-backed (no DB) registry of admin-editable navigation menus for this
 * theme. Storage: resources/{active theme}/config/menus.json. Mirrors
 * PageSectionRegistry's read/write pattern (flock + atomic rename) so
 * concurrent admin saves can't corrupt the file.
 *
 * Mirrors App\Support\EcomxAnyniche\MenuRegistry's structure (per-theme
 * duplication is this codebase's existing convention — see ActiveTheme,
 * PageSectionRegistry etc. in this same namespace), adapted to this theme's
 * actual layout: partials/header.blade.php (topbar links + main nav +
 * mobile drawer) and partials/footer.blade.php (three link columns), all
 * previously hardcoded PHP arrays.
 *
 * Five independent menus are tracked, keyed by slug:
 *  - 'topbar'      — the thin promo-bar strip above the header. Defaults to
 *                    what was hardcoded: Track Order / About Us / Contact.
 *  - 'main-nav'    — the header's primary nav row, also reused verbatim by
 *                    the mobile drawer (previously duplicated by hand in
 *                    both places — see header.blade.php's single $nav loop
 *                    now driving both). Defaults to: New In / Women / Men /
 *                    Accessories / Flash Sale.
 *  - 'footer-shop'   — footer "Shop" column. Defaults: New In / Women / Men / Accessories.
 *  - 'footer-about'  — footer "About" column. Defaults: Our Story / Ateliers / Sustainability / Reviews.
 *  - 'footer-help'   — footer "Help" column. Defaults: Track Order / Size Guide / Care Guide / Contact.
 *
 * NOT covered (deliberately, per scope decision): the mega-menu's per-column
 * sub-links (mega-menu.blade.php) are placeholder content — every link
 * points at the same route regardless of label, so there's nothing
 * functional to make admin-editable yet — and the bottom tab bar
 * (bottom-nav.blade.php) is mostly action buttons (search/cart/account),
 * not a navigation link list.
 *
 * Item shape: ['id' => string, 'label' => string, 'icon' => ?string,
 *   'image_id' => ?int, 'url' => string, 'new_tab' => bool, 'children' => Item[]].
 * 'children' is always empty here — none of this theme's five menus render
 * a submenu — but the shape stays consistent with EcomxAnyniche's registry
 * for the same reasons (future-proofing, shared mental model), even though
 * nothing here currently reads it.
 */
class MenuRegistry
{
    public const MENUS = ['topbar', 'main-nav', 'footer-shop', 'footer-about', 'footer-help'];

    public const MENU_LABELS = [
        'topbar' => 'Top bar',
        'main-nav' => 'Main nav',
        'footer-shop' => 'Footer — Shop',
        'footer-about' => 'Footer — About',
        'footer-help' => 'Footer — Help',
    ];

    /** Per-request memoized file contents — see EcomxAnyniche\MenuRegistry for why. */
    protected static ?array $cache = null;

    protected static function path(): string
    {
        return ActiveTheme::resourcePath('config/menus.json');
    }

    protected static function read(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $path = static::path();

        if (! file_exists($path)) {
            return static::$cache = [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        try {
            flock($handle, LOCK_SH);
            $contents = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $data = json_decode($contents, true);

        return static::$cache = (is_array($data) ? $data : []);
    }

    protected static function write(array $data): void
    {
        $path = static::path();
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmp = $path . '.' . uniqid('', true) . '.tmp';
        file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        rename($tmp, $path);
        static::$cache = null;
    }

    protected static function defaultItems(string $menu): array
    {
        if ($menu === 'topbar') {
            return [
                static::newItem('Track Order', route('ecomx-fashion.track')),
                static::newItem('About Us', route('ecomx-fashion.home')),
                static::newItem('Contact', 'tel:' . config('ecomx-fashion.phone')),
            ];
        }

        if ($menu === 'main-nav') {
            return [
                static::newItem('New In', route('ecomx-fashion.shop')),
                static::newItem('Women', route('ecomx-fashion.shop', ['cat' => ['women']])),
                static::newItem('Men', route('ecomx-fashion.shop', ['cat' => ['men']])),
                static::newItem('Accessories', route('ecomx-fashion.shop')),
                static::newItem('Flash Sale', route('ecomx-fashion.shop', ['offer' => ['flash_sale']])),
            ];
        }

        if ($menu === 'footer-shop') {
            return [
                static::newItem('New In', route('ecomx-fashion.shop')),
                static::newItem('Women', route('ecomx-fashion.category')),
                static::newItem('Men', route('ecomx-fashion.category')),
                static::newItem('Accessories', route('ecomx-fashion.shop')),
            ];
        }

        if ($menu === 'footer-about') {
            return [
                static::newItem('Our Story', route('ecomx-fashion.home')),
                static::newItem('Ateliers', route('ecomx-fashion.home')),
                static::newItem('Sustainability', route('ecomx-fashion.home')),
                static::newItem('Reviews', route('ecomx-fashion.reviews')),
            ];
        }

        if ($menu === 'footer-help') {
            return [
                static::newItem('Track Order', route('ecomx-fashion.track')),
                static::newItem('Size Guide', route('ecomx-fashion.product')),
                static::newItem('Care Guide', route('ecomx-fashion.home')),
                static::newItem('Contact', route('ecomx-fashion.home')),
            ];
        }

        return [];
    }

    public static function newItem(string $label, string $url = '', ?string $icon = null, bool $newTab = false, ?int $imageId = null): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'label' => $label,
            'icon' => $icon,
            'image_id' => $imageId,
            'url' => $url,
            'new_tab' => $newTab,
            'children' => [],
        ];
    }

    /** Ordered items for a menu, falling back to sensible defaults when never saved. */
    public static function items(string $menu): array
    {
        $data = static::read();

        return $data[$menu] ?? static::defaultItems($menu);
    }

    public static function save(string $menu, array $items): void
    {
        $data = static::read();
        $data[$menu] = static::normalize($items);

        static::write($data);
    }

    /** Strips out anything but the known keys, re-keys ids for any new items missing one. */
    protected static function normalize(array $items): array
    {
        return array_values(array_map(function (array $item) {
            return [
                'id' => $item['id'] ?? (string) \Illuminate\Support\Str::uuid(),
                'label' => (string) ($item['label'] ?? ''),
                'icon' => ($item['icon'] ?? '') !== '' ? $item['icon'] : null,
                'image_id' => ! empty($item['image_id']) ? (int) $item['image_id'] : null,
                'url' => (string) ($item['url'] ?? ''),
                'new_tab' => (bool) ($item['new_tab'] ?? false),
                'children' => [], // this theme's menus never render children — see class docblock
            ];
        }, $items));
    }
}
