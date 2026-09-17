<?php

namespace App\Livewire\Admin\ThemeEngine;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Category;
use App\Support\EcomxFashion\ThemeRegistry;
use Livewire\Component;

/**
 * Full-page section config editor — route-mounted (/admin/frontend/{page}/{section}/edit)
 * rather than the event-driven modal this replaced (SectionConfigEditor).
 * Same field-manipulation methods/behaviour, just addressable by URL: no
 * more losing your place on refresh, and no more nested-modal ceiling for
 * fields that themselves need a picker or a bigger editing surface.
 *
 * Theme-agnostic: PageRegistry/PageSectionConfigRegistry/SectionSchema live
 * one-per-theme under App\Support\{ThemeNamespace}\*, so this resolves the
 * active theme's classes by name (via ThemeRegistry::active()) rather than
 * hardcoding a single theme's import — the same theme.json-driven "which
 * theme is active" state that ActiveTheme::slug() reads elsewhere.
 */
class SectionConfigPage extends Component
{
    use WithMediaPicker;

    /** Studly-cased theme namespace, e.g. 'ecomx-anyniche' -> 'EcomxAnyniche'. */
    protected static function themeNamespace(): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', ThemeRegistry::active())));
    }

    protected static function pageRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageRegistry';
    }

    protected static function pageSectionConfigRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageSectionConfigRegistry';
    }

    protected static function sectionSchema(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\SectionSchema';
    }

    public string $page = '';
    public string $section = '';

    /** @var array<string, mixed> field key => value (shape depends on field type) */
    public array $values = [];

    /** @var array<int, array{id:int,name:string}> Loaded for category_list/category_select/category_multi_select fields only. */
    public array $categories = [];

    /**
     * Which field a media picker selection should land in — set right
     * before dispatching openMediaPicker. Must be public: addMediaSlot()
     * and mediaSelected() are separate Livewire round-trips (the picker
     * modal dispatches its own request), and a protected/private property
     * is not part of Livewire's public-property hydration, so it would
     * silently reset to null before mediaSelected() ever sees it.
     */
    public ?string $pendingMediaField = null;

    public function mount(string $page, string $section): void
    {
        $pageRegistry = static::pageRegistry();

        abort_unless($pageRegistry::exists($page), 404);
        abort_unless(in_array($section, $pageRegistry::sectionKeysForPage($page), true), 404);

        $this->page = $page;
        $this->section = $section;

        $saved = static::pageSectionConfigRegistry()::find($page, $section);
        $defaults = static::sectionSchema()::defaultsFor($section);
        $this->values = array_merge($defaults, $saved ?? []);

        if ($this->needsCategories()) {
            $this->loadCategories();
        }
    }

    protected function needsCategories(): bool
    {
        return collect(static::sectionSchema()::fieldsFor($this->section))
            ->contains(fn (array $field) => in_array($field['type'], ['category_list', 'category_select', 'category_multi_select'], true));
    }

    protected function loadCategories(): void
    {
        $this->categories = Category::active()
            ->get(['id', 'name'])
            ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }

    public function fields(): array
    {
        return static::sectionSchema()::fieldsFor($this->section);
    }

    public function addMediaSlot(string $fieldKey): void
    {
        $this->pendingMediaField = $fieldKey;
        $this->dispatch('openMediaPicker', target: 'sectionConfigMedia', multiple: false, type: 'image');
    }

    /** Overrides WithMediaPicker::mediaSelected — media_list fields append {id,url,link}, not a bare id. */
    public function mediaSelected($field, $id): void
    {
        if ($field !== 'sectionConfigMedia' || $this->pendingMediaField === null) {
            return;
        }

        $fieldKey = $this->pendingMediaField;
        $this->pendingMediaField = null;

        $this->values[$fieldKey][] = [
            'id' => $id,
            'url' => file_path($id),
            'link' => '',
        ];
    }

    public function removeMediaItem(string $fieldKey, int $index): void
    {
        unset($this->values[$fieldKey][$index]);
        $this->values[$fieldKey] = array_values($this->values[$fieldKey]);
    }

    public function updateMediaLink(string $fieldKey, int $index, string $link): void
    {
        $this->values[$fieldKey][$index]['link'] = $link;
    }

    public function updateText(string $fieldKey, string $value): void
    {
        $this->values[$fieldKey] = $value;
    }

    public function updateCheckbox(string $fieldKey, bool $value): void
    {
        $this->values[$fieldKey] = $value;
    }

    public function updateCategorySelect(string $fieldKey, string $categoryId): void
    {
        $this->values[$fieldKey] = $categoryId;
    }

    public function toggleCategoryMultiSelect(string $fieldKey, int $categoryId, bool $checked): void
    {
        $selected = $this->values[$fieldKey] ?? [];

        if ($checked) {
            $max = collect(static::sectionSchema()::fieldsFor($this->section))
                ->firstWhere('key', $fieldKey)['max'] ?? null;

            if ($max !== null && count($selected) >= $max) {
                return;
            }

            if (! in_array($categoryId, $selected, true)) {
                $selected[] = $categoryId;
            }
        } else {
            $selected = array_values(array_diff($selected, [$categoryId]));
        }

        $this->values[$fieldKey] = $selected;
    }

    public function addTextItem(string $fieldKey): void
    {
        $this->values[$fieldKey][] = ['text' => ''];
    }

    public function updateTextItem(string $fieldKey, int $index, string $text): void
    {
        $this->values[$fieldKey][$index]['text'] = $text;
    }

    public function removeTextItem(string $fieldKey, int $index): void
    {
        unset($this->values[$fieldKey][$index]);
        $this->values[$fieldKey] = array_values($this->values[$fieldKey]);
    }

    /** @param int[] $orderedIndexes Current indexes of $values[$fieldKey], in their new order. */
    public function reorderTextItems(string $fieldKey, array $orderedIndexes): void
    {
        $this->values[$fieldKey] = array_values(array_map(
            fn (int $i) => $this->values[$fieldKey][$i],
            $orderedIndexes
        ));
    }

    public function addFaqItem(string $fieldKey): void
    {
        $this->values[$fieldKey][] = ['q' => '', 'a' => ''];
    }

    public function updateFaqItem(string $fieldKey, int $index, string $part, string $value): void
    {
        $this->values[$fieldKey][$index][$part] = $value;
    }

    public function removeFaqItem(string $fieldKey, int $index): void
    {
        unset($this->values[$fieldKey][$index]);
        $this->values[$fieldKey] = array_values($this->values[$fieldKey]);
    }

    /** @param int[] $orderedIndexes Current indexes of $values[$fieldKey], in their new order. */
    public function reorderFaqItems(string $fieldKey, array $orderedIndexes): void
    {
        $this->values[$fieldKey] = array_values(array_map(
            fn (int $i) => $this->values[$fieldKey][$i],
            $orderedIndexes
        ));
    }

    public function addStatItem(string $fieldKey): void
    {
        $this->values[$fieldKey][] = ['val' => '', 'label' => ''];
    }

    public function updateStatItem(string $fieldKey, int $index, string $part, string $value): void
    {
        $this->values[$fieldKey][$index][$part] = $value;
    }

    public function removeStatItem(string $fieldKey, int $index): void
    {
        unset($this->values[$fieldKey][$index]);
        $this->values[$fieldKey] = array_values($this->values[$fieldKey]);
    }

    /** @param int[] $orderedIndexes Current indexes of $values[$fieldKey], in their new order. */
    public function reorderStatItems(string $fieldKey, array $orderedIndexes): void
    {
        $this->values[$fieldKey] = array_values(array_map(
            fn (int $i) => $this->values[$fieldKey][$i],
            $orderedIndexes
        ));
    }

    public function addIconItem(string $fieldKey): void
    {
        $trustClass = 'App\\Livewire\\' . static::themeNamespace() . '\\Sections\\Trust';
        $defaultIcon = class_exists($trustClass) ? ($trustClass::ICONS[0] ?? '') : '';

        $this->values[$fieldKey][] = ['icon' => $defaultIcon, 'title' => '', 'description' => ''];
    }

    public function updateIconItem(string $fieldKey, int $index, string $part, string $value): void
    {
        $this->values[$fieldKey][$index][$part] = $value;
    }

    public function removeIconItem(string $fieldKey, int $index): void
    {
        unset($this->values[$fieldKey][$index]);
        $this->values[$fieldKey] = array_values($this->values[$fieldKey]);
    }

    /** @param int[] $orderedIndexes Current indexes of $values[$fieldKey], in their new order. */
    public function reorderIconItems(string $fieldKey, array $orderedIndexes): void
    {
        $this->values[$fieldKey] = array_values(array_map(
            fn (int $i) => $this->values[$fieldKey][$i],
            $orderedIndexes
        ));
    }

    public function save(): void
    {
        static::pageSectionConfigRegistry()::save($this->page, $this->section, $this->values);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Section configuration saved.',
        ]);
    }

    public function saveAndExit(): void
    {
        $this->save();

        $this->redirect(route('admin.frontend.menu.show', $this->page), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.theme-engine.section-config-page')
            ->layout('layouts.admin.admin');
    }
}
