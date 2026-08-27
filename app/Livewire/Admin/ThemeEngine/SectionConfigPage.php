<?php

namespace App\Livewire\Admin\ThemeEngine;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Category;
use App\Support\EcomxFashion\PageRegistry;
use App\Support\EcomxFashion\PageSectionConfigRegistry;
use App\Support\EcomxFashion\SectionSchema;
use Livewire\Component;

/**
 * Full-page section config editor — route-mounted (/admin/frontend/{page}/{section}/edit)
 * rather than the event-driven modal this replaced (SectionConfigEditor).
 * Same field-manipulation methods/behaviour, just addressable by URL: no
 * more losing your place on refresh, and no more nested-modal ceiling for
 * fields that themselves need a picker or a bigger editing surface.
 */
class SectionConfigPage extends Component
{
    use WithMediaPicker;

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
        abort_unless(PageRegistry::exists($page), 404);
        abort_unless(in_array($section, PageRegistry::sectionKeysForPage($page), true), 404);

        $this->page = $page;
        $this->section = $section;

        $saved = PageSectionConfigRegistry::find($page, $section);
        $defaults = SectionSchema::defaultsFor($section);
        $this->values = array_merge($defaults, $saved ?? []);

        if ($this->needsCategories()) {
            $this->loadCategories();
        }
    }

    protected function needsCategories(): bool
    {
        return collect(SectionSchema::fieldsFor($this->section))
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
        return SectionSchema::fieldsFor($this->section);
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

    public function updateCategorySelect(string $fieldKey, string $categoryId): void
    {
        $this->values[$fieldKey] = $categoryId;
    }

    public function toggleCategoryMultiSelect(string $fieldKey, int $categoryId, bool $checked): void
    {
        $selected = $this->values[$fieldKey] ?? [];

        if ($checked) {
            $max = collect(SectionSchema::fieldsFor($this->section))
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

    public function save(): void
    {
        PageSectionConfigRegistry::save($this->page, $this->section, $this->values);

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
