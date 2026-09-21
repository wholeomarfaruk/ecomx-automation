<?php

namespace App\Support\EcomxAnyniche;

/**
 * File-backed (no DB) registry of admin-editable navigation menus for the
 * offcanvas — see resources/views/ecomx-anyniche/partials/offcanvas.blade.php.
 * Storage: resources/{active theme}/config/menus.json. Mirrors
 * PageSectionRegistry/PageSettingsRegistry's read/write pattern (flock +
 * atomic rename) so concurrent admin saves can't corrupt the file.
 *
 * Six independent menus are tracked, keyed by slug:
 *  - 'shop'             — top nav links (defaults to a single "Shop all" link).
 *  - 'categories'       — defaults to the live category tree the first time
 *                         it's read, so a fresh install still shows real
 *                         categories before any admin ever visits the Menus
 *                         screen. Once saved, it's admin-owned state and the
 *                         DB is never consulted again for it.
 *  - 'header'           — top bar links (see partials/topbar.blade.php,
 *                         labeled "Top header" in the admin UI though the
 *                         storage slug stays 'header' for stability),
 *                         defaults to the two links it used to hardcode:
 *                         "Track order" and "About us".
 *  - 'search-categories' — the header search bar's "All categories" filter
 *                         dropdown (see Livewire\EcomxAnyniche\HeaderSearch
 *                         and its view). NOT a nav-link menu like the other
 *                         three: each entry must reference a real Category
 *                         (by id, with its name snapshotted for display and
 *                         for the actual search filter match — see
 *                         searchCategoryItems()), because selecting one
 *                         constrains a live product search rather than
 *                         linking anywhere. Uses a different, simpler item
 *                         shape — see searchCategoryItems()'s docblock.
 *  - 'footer-links'     — footer "Useful links" column (partials/footer.blade.php).
 *                         Defaults: All products, About us, Reviews, Track order.
 *  - 'footer-legal'     — footer "Legal" column. Defaults: Privacy policy
 *                         (real link) plus Delivery policy / Terms &
 *                         conditions / Refund & returns — the latter three
 *                         were hardcoded as dead '#' links before this, and
 *                         default to '#' here too (nothing to link to yet);
 *                         an admin can point them at real pages once they exist.
 *
 * Item shape (shop/categories/header): ['id' => string, 'label' => string,
 *   'icon' => ?string, 'image_id' => ?int, 'url' => string, 'new_tab' =>
 *   bool, 'children' => Item[]]. 'image_id' is a File id picked via the
 * admin media picker; when set it's shown in place of the icon in the
 * offcanvas. One level of children only — matches the offcanvas UI, which
 * only ever expands one level. ('header' additionally never has children —
 * the top bar renders a flat strip only.)
 *
 * No active-link highlighting — deliberately removed. Matching menu URLs
 * against the current request looked wrong in practice (e.g. every filtered
 * category listing highlighting unrelated items), so items render as plain
 * static links.
 */
class MenuRegistry
{
    public const MENUS = ['shop', 'categories', 'header', 'search-categories', 'footer-links', 'footer-legal'];

    /** Display labels for MENUS slugs — storage keys stay stable even when the admin-facing name changes. */
    public const MENU_LABELS = [
        'shop' => 'Shop',
        'categories' => 'Categories',
        'header' => 'Top header',
        'search-categories' => 'Search categories',
        'footer-links' => 'Footer — Useful links',
        'footer-legal' => 'Footer — Legal',
    ];

    /**
     * Per-request memoized file contents. The offcanvas partial calls
     * items('shop') and items('categories') on every storefront render —
     * without this, that's two separate file opens/locks/reads/decodes of
     * the same small file per request. Cleared by write() so a save() within
     * the same request (admin only) is never served stale from this cache.
     */
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
        if ($menu === 'shop') {
            return [
                static::newItem('Shop all', route('ecomx-anyniche.shop')),
            ];
        }

        if ($menu === 'header') {
            return [
                static::newItem('Track order', route('ecomx-anyniche.track')),
                static::newItem('About us', route('ecomx-anyniche.about')),
            ];
        }

        if ($menu === 'footer-links') {
            return [
                static::newItem('All products', route('ecomx-anyniche.shop')),
                static::newItem('About us', route('ecomx-anyniche.about')),
                static::newItem('Reviews', route('ecomx-anyniche.reviews')),
                static::newItem('Track order', route('ecomx-anyniche.track')),
            ];
        }

        if ($menu === 'footer-legal') {
            return [
                static::newItem('Privacy policy', route('ecomx-anyniche.privacy-policy')),
                static::newItem('Delivery policy', '#'),
                static::newItem('Terms & conditions', '#'),
                static::newItem('Refund & returns', '#'),
            ];
        }

        if ($menu === 'categories') {
            return \App\Models\Category::active()
                ->whereNull('parent_id')
                ->with('children')
                ->get()
                ->map(fn ($cat) => [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'label' => $cat->name,
                    'icon' => null,
                    'image_id' => null,
                    'url' => route('ecomx-anyniche.category', $cat->slug),
                    'new_tab' => false,
                    'children' => $cat->children->map(fn ($child) => static::newItem(
                        $child->name,
                        route('ecomx-anyniche.category', $child->slug)
                    ))->values()->all(),
                ])
                ->values()
                ->all();
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

    /**
     * Ordered items for a link-based menu ('shop'/'categories'/'header'),
     * falling back to sensible defaults when never saved. Not for
     * 'search-categories' — see searchCategoryItems(), which has a
     * different item shape (no url/icon/children, just a Category
     * reference) since it drives a search filter, not navigation links.
     */
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

    /**
     * Ordered entries for the search bar's category filter dropdown.
     * Shape: ['id' => string, 'category_id' => int, 'name' => string] — 'name'
     * is a snapshot of the Category's name at save time (the actual value
     * HeaderSearch::selectCategory() filters products by), so a later
     * rename in Catalog > Categories doesn't retroactively change what a
     * saved dropdown entry matches until an admin re-saves this menu.
     * Defaults to every active top-level-or-not category, same "seed from
     * DB until first save" rule as 'categories' (see class docblock).
     */
    public static function searchCategoryItems(): array
    {
        $data = static::read();

        if (isset($data['search-categories'])) {
            return $data['search-categories'];
        }

        return \App\Models\Category::active()
            ->get(['id', 'name'])
            ->map(fn ($cat) => [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'category_id' => $cat->id,
                'name' => $cat->name,
            ])
            ->values()
            ->all();
    }

    /** @param array<int, array{category_id:int}> $items Order defines dropdown order; 'name' is re-snapshotted from the live Category here so a save always reflects its current name. */
    public static function saveSearchCategoryItems(array $items): void
    {
        $ids = array_column($items, 'category_id');
        $names = \App\Models\Category::whereIn('id', $ids)->pluck('name', 'id');

        $data = static::read();
        $data['search-categories'] = array_values(array_filter(array_map(function (array $item) use ($names) {
            $categoryId = (int) ($item['category_id'] ?? 0);

            if (! isset($names[$categoryId])) {
                return null; // category was deleted/deactivated since being added — drop it rather than save a dangling reference
            }

            return [
                'id' => $item['id'] ?? (string) \Illuminate\Support\Str::uuid(),
                'category_id' => $categoryId,
                'name' => $names[$categoryId],
            ];
        }, $items)));

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
                'children' => array_values(array_map(function (array $child) {
                    return [
                        'id' => $child['id'] ?? (string) \Illuminate\Support\Str::uuid(),
                        'label' => (string) ($child['label'] ?? ''),
                        'icon' => ($child['icon'] ?? '') !== '' ? $child['icon'] : null,
                        'image_id' => ! empty($child['image_id']) ? (int) $child['image_id'] : null,
                        'url' => (string) ($child['url'] ?? ''),
                        'new_tab' => (bool) ($child['new_tab'] ?? false),
                    ];
                }, $item['children'] ?? [])),
            ];
        }, $items));
    }
}
