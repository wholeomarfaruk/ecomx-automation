<?php

namespace App\Livewire\Admin\Frontend;

use App\Livewire\Traits\WithMediaPicker;
use App\Support\EcomxAnyniche\ThemeRegistry;
use App\Support\IconLibrary;
use Livewire\Component;

/**
 * Admin editor for the active theme's storefront menus. Theme-agnostic:
 * resolves the active theme's own MenuRegistry class by name (same
 * ThemeRegistry::active()-driven pattern as
 * App\Livewire\Admin\ThemeEngine\SectionConfigPage), so this one component
 * serves both App\Support\EcomxAnyniche\MenuRegistry and
 * App\Support\EcomxFashion\MenuRegistry — each theme defines its own MENUS/
 * MENU_LABELS and, where relevant, its own quirks:
 *
 *  - Generic link-based menus (label/icon/image/url/new-tab) — every menu in
 *    every theme's registry so far — handled by loadItems()/persist() + the
 *    addItem/editItem/saveItem/removeItem/reorderItems/reorderChildren
 *    methods below.
 *  - One level of nested children is supported generically, but a theme can
 *    opt a specific menu out of it (e.g. ecomx-anyniche's 'header', since
 *    its top bar renders a flat strip only) via noChildrenMenus() —
 *    addItem()/saveItem() refuse a parentId for those, as defense in depth
 *    alongside the view hiding the "+ Sub" button.
 *  - 'search-categories' (ecomx-anyniche only) is a different kind of menu
 *    entirely — the header search bar's category-filter dropdown, so
 *    entries are Category references, not links. Handled by its own
 *    branch in loadItems() and its own
 *    addSearchCategory/removeSearchCategory/reorderSearchCategories methods
 *    (only reachable when the active theme's MENUS actually includes it —
 *    ecomx-fashion's registry doesn't declare this menu, so its tab/methods
 *    simply never apply there).
 * Reordering is client-driven (SortableJS, see the view) posting back full
 * id-order arrays via reorderItems()/reorderChildren()/reorderSearchCategories().
 */
class Menus extends Component
{
    use WithMediaPicker;

    public string $activeMenu = 'shop';

    /** @var array<int, array> Items for the currently active menu (link-shaped for shop/categories/header). */
    public array $items = [];

    /** @var array<int, array{id:string,category_id:int,name:string}> Saved entries — only populated when activeMenu is 'search-categories'. */
    public array $searchCategoryItems = [];

    /** @var array<int, array{id:int,name:string}> Every active Category, for the "search-categories" tab's add-picker. */
    public array $allCategories = [];

    /** Whether the create/edit modal is open. Distinct from editingId/editingParentId, both
     *  of which are legitimately null for "adding a new top-level item" too. */
    public bool $showForm = false;

    /** id of the item currently open in the edit form, or null when adding a new one. */
    public ?string $editingId = null;

    /** id of the parent whose child is being edited/added, or null for a top-level item. */
    public ?string $editingParentId = null;

    public string $formLabel = '';
    public string $formIcon = '';
    /** File id for the item's image, set via the media picker (WithMediaPicker::mediaSelected). Takes visual priority over formIcon in the offcanvas. */
    public ?int $formImageId = null;
    public string $formUrl = '';
    public bool $formNewTab = false;

    /**
     * Curated subset of App\Support\IconLibrary — the outline set only
     * (brand marks like "whatsapp"/"tiktok" don't make sense as a generic
     * nav-item icon), plus a leading '' for "no icon". Building this from
     * IconLibrary::ICONS rather than duplicating the list means it can
     * never drift out of sync with what <x-icon>/<x-anyniche::icon> can
     * actually render.
     */
    public static function icons(): array
    {
        return array_merge([''], array_keys(IconLibrary::ICONS));
    }

    /** Studly-cased active-theme namespace, e.g. 'ecomx-anyniche' -> 'EcomxAnyniche'. */
    protected static function themeNamespace(): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', ThemeRegistry::active())));
    }

    /** Fully-qualified class name of the active theme's own MenuRegistry. */
    public static function menuRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\MenuRegistry';
    }

    /**
     * Menu slugs (within the active theme's MENUS) that never accept a
     * submenu — currently just ecomx-anyniche's 'header' (its top bar
     * renders a flat strip only). A theme not listed here simply has no
     * such restriction; ecomx-fashion's five menus don't render children at
     * all today, so this is moot for it either way (see its MenuRegistry's
     * docblock), but the check stays generic rather than hardcoding a
     * single theme's slug directly in the guards below.
     */
    public static function noChildrenMenus(): array
    {
        return ['header'];
    }

    public function mount(): void
    {
        $registry = static::menuRegistry();
        $this->activeMenu = $registry::MENUS[0] ?? $this->activeMenu;

        $this->loadItems();
    }

    protected function loadItems(): void
    {
        $registry = static::menuRegistry();

        if ($this->activeMenu === 'search-categories' && in_array('search-categories', $registry::MENUS, true)) {
            $this->searchCategoryItems = $registry::searchCategoryItems();
            $this->allCategories = \App\Models\Category::active()->get(['id', 'name'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all();
            $this->items = [];
            return;
        }

        $this->items = $registry::items($this->activeMenu);
    }

    public function setActiveMenu(string $menu): void
    {
        $registry = static::menuRegistry();

        if (! in_array($menu, $registry::MENUS, true)) {
            return;
        }

        $this->activeMenu = $menu;
        $this->closeForm();
        $this->loadItems();
    }

    protected function persist(): void
    {
        $registry = static::menuRegistry();
        $registry::save($this->activeMenu, $this->items);
    }

    /** Adds a Category to the search-filter dropdown (no-op if it's already there). */
    public function addSearchCategory(int $categoryId): void
    {
        if (collect($this->searchCategoryItems)->contains('category_id', $categoryId)) {
            return;
        }

        $this->searchCategoryItems[] = [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'category_id' => $categoryId,
            'name' => collect($this->allCategories)->firstWhere('id', $categoryId)['name'] ?? '',
        ];

        $registry = static::menuRegistry();
        $registry::saveSearchCategoryItems($this->searchCategoryItems);
        $this->searchCategoryItems = $registry::searchCategoryItems();
    }

    public function removeSearchCategory(string $id): void
    {
        $this->searchCategoryItems = array_values(array_filter($this->searchCategoryItems, fn ($item) => $item['id'] !== $id));
        $registry = static::menuRegistry();
        $registry::saveSearchCategoryItems($this->searchCategoryItems);
    }

    /** @param string[] $orderedIds searchCategoryItems ids in their new display order. */
    public function reorderSearchCategories(array $orderedIds): void
    {
        $byId = collect($this->searchCategoryItems)->keyBy('id');

        $this->searchCategoryItems = collect($orderedIds)
            ->map(fn (string $id) => $byId->get($id))
            ->filter()
            ->values()
            ->all();

        $registry = static::menuRegistry();
        $registry::saveSearchCategoryItems($this->searchCategoryItems);
    }

    public function addItem(?string $parentId = null): void
    {
        if ($this->activeMenu === 'search-categories') {
            return;
        }

        if ($parentId !== null && in_array($this->activeMenu, static::noChildrenMenus(), true)) {
            return;
        }

        $this->showForm = true;
        $this->editingId = null;
        $this->editingParentId = $parentId;
        $this->formLabel = '';
        $this->formIcon = '';
        $this->formImageId = null;
        $this->formUrl = '';
        $this->formNewTab = false;
    }

    public function editItem(string $id, ?string $parentId = null): void
    {
        if ($this->activeMenu === 'search-categories') {
            return;
        }

        if ($parentId !== null && in_array($this->activeMenu, static::noChildrenMenus(), true)) {
            return;
        }

        $item = $parentId === null
            ? $this->findTop($id)
            : $this->findChild($parentId, $id);

        if ($item === null) {
            return;
        }

        $this->showForm = true;
        $this->editingId = $id;
        $this->editingParentId = $parentId;
        $this->formLabel = $item['label'];
        $this->formIcon = $item['icon'] ?? '';
        $this->formImageId = $item['image_id'] ?? null;
        $this->formUrl = $item['url'];
        $this->formNewTab = $item['new_tab'];
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->editingParentId = null;
        $this->formLabel = '';
        $this->formIcon = '';
        $this->formImageId = null;
        $this->formUrl = '';
        $this->formNewTab = false;
    }

    public function saveItem(): void
    {
        if ($this->activeMenu === 'search-categories') {
            $this->closeForm();
            return;
        }

        if ($this->editingParentId !== null && in_array($this->activeMenu, static::noChildrenMenus(), true)) {
            $this->closeForm();
            return;
        }

        $this->validate([
            'formLabel' => ['required', 'string', 'max:60'],
            'formUrl' => ['required', 'string', 'max:2048'],
        ]);

        $payload = [
            'label' => $this->formLabel,
            'icon' => $this->formIcon !== '' ? $this->formIcon : null,
            'image_id' => $this->formImageId,
            'url' => $this->formUrl,
            'new_tab' => $this->formNewTab,
        ];

        $registry = static::menuRegistry();

        if ($this->editingParentId === null) {
            // Top-level item (new or edit).
            if ($this->editingId === null) {
                $this->items[] = array_merge(
                    $registry::newItem($payload['label'], $payload['url'], $payload['icon'], $payload['new_tab']),
                    ['image_id' => $payload['image_id']]
                );
            } else {
                foreach ($this->items as &$item) {
                    if ($item['id'] === $this->editingId) {
                        $item = array_merge($item, $payload);
                        break;
                    }
                }
                unset($item);
            }
        } else {
            // Child item (new or edit), under editingParentId.
            foreach ($this->items as &$parent) {
                if ($parent['id'] !== $this->editingParentId) {
                    continue;
                }

                $parent['children'] = $parent['children'] ?? [];

                if ($this->editingId === null) {
                    $child = $registry::newItem($payload['label'], $payload['url'], $payload['icon'], $payload['new_tab']);
                    unset($child['children']);
                    $child['image_id'] = $payload['image_id'];
                    $parent['children'][] = $child;
                } else {
                    foreach ($parent['children'] as &$child) {
                        if ($child['id'] === $this->editingId) {
                            $child = array_merge($child, $payload);
                            break;
                        }
                    }
                    unset($child);
                }
                break;
            }
            unset($parent);
        }

        $this->persist();
        $this->closeForm();

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Menu item saved.',
        ]);
    }

    public function removeItem(string $id, ?string $parentId = null): void
    {
        // Guard against clobbering search-categories via the wrong code
        // path: persist() below calls the active registry's save() (link-item
        // shape/normalize()), not saveSearchCategoryItems() — see
        // removeSearchCategory() for the real deletion path on that tab.
        if ($this->activeMenu === 'search-categories') {
            return;
        }

        if ($parentId === null) {
            $this->items = array_values(array_filter($this->items, fn ($item) => $item['id'] !== $id));
        } else {
            foreach ($this->items as &$parent) {
                if ($parent['id'] === $parentId) {
                    $parent['children'] = array_values(array_filter($parent['children'] ?? [], fn ($child) => $child['id'] !== $id));
                    break;
                }
            }
            unset($parent);
        }

        $this->persist();

        if ($this->editingId === $id) {
            $this->closeForm();
        }

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Menu item removed.',
        ]);
    }

    /** @param string[] $orderedIds Top-level item ids in their new display order. */
    public function reorderItems(array $orderedIds): void
    {
        // See removeItem()'s guard comment — reorderSearchCategories() is the
        // real reorder path for that tab.
        if ($this->activeMenu === 'search-categories') {
            return;
        }

        $byId = collect($this->items)->keyBy('id');

        $this->items = collect($orderedIds)
            ->map(fn (string $id) => $byId->get($id))
            ->filter()
            ->values()
            ->all();

        $this->persist();
    }

    /** @param string[] $orderedIds Child item ids (within one parent) in their new display order. */
    public function reorderChildren(string $parentId, array $orderedIds): void
    {
        foreach ($this->items as &$parent) {
            if ($parent['id'] !== $parentId) {
                continue;
            }

            $byId = collect($parent['children'] ?? [])->keyBy('id');

            $parent['children'] = collect($orderedIds)
                ->map(fn (string $id) => $byId->get($id))
                ->filter()
                ->values()
                ->all();
            break;
        }
        unset($parent);

        $this->persist();
    }

    protected function findTop(string $id): ?array
    {
        foreach ($this->items as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    protected function findChild(string $parentId, string $id): ?array
    {
        foreach ($this->items as $parent) {
            if ($parent['id'] !== $parentId) {
                continue;
            }

            foreach ($parent['children'] ?? [] as $child) {
                if ($child['id'] === $id) {
                    return $child;
                }
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.admin.frontend.menus')
            ->layout('layouts.admin.admin');
    }
}
