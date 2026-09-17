<?php

namespace App\Support\EcomxAnyniche;

/**
 * Declares editable field schemas per section key, consumed by the admin
 * SectionConfigPage (/admin/frontend/{page}/{section}/edit) to render a
 * dynamic form and by each section's frontend Livewire component to read
 * saved values with sane defaults.
 *
 * Field shape: ['key' => string, 'label' => string, 'type' => ..., ...].
 * Supported types:
 *   - 'text': single-line plain text (value stored as a bare string, not a list)
 *   - 'media_list': repeatable media-picker images (each item: id/url/thumbnail/link)
 *   - 'text_list': repeatable plain-text items (each item: text)
 *   - 'category_list': checklist of real App\Models\Category rows to show as
 *     homepage tiles (value stored as an ordered list of bare category ids
 *     via PageSectionConfigRegistry, like 'category_multi_select' — this
 *     project's Category model has no is_homepage_show/display_order columns
 *     of its own, so unlike the original design this never writes to the
 *     categories table).
 *   - 'category_select': single-category dropdown (value stored as a bare
 *     category id, or '' for none) — picks which category's products a
 *     section pulls from, distinct from 'category_list' which picks which
 *     categories themselves are shown as tiles.
 *   - 'category_multi_select': checklist of real App\Models\Category rows,
 *     capped at the field's 'max' (value stored as an ordered list of bare
 *     category ids via PageSectionConfigRegistry). Unlike 'category_list',
 *     this does not touch categories.is_homepage_show — it's a section-scoped
 *     selection, so two sections can each pick their own subset of categories
 *     without fighting over the same shared flag.
 *   - 'faq_list': repeatable {q, a} question/answer pairs, drag-reorderable.
 *   - 'stat_list': repeatable {val, label} stat-tile pairs, drag-reorderable.
 *   - 'icon_list': repeatable {icon, title, description} trust-badge-style
 *     items, drag-reorderable. 'icon' is a bare identifier (not markup) —
 *     the frontend component maps it to an inline SVG; see Trust::ICONS.
 *   - 'checkbox': single boolean toggle (value stored as a bare bool).
 * New field types can be added here + a matching form partial as sections
 * gain more configurable fields.
 */
class SectionSchema
{
    /** Field definitions for a section key, or [] if nothing is configurable yet. */
    public static function fieldsFor(string $section): array
    {
        return match (true) {
            $section === 'trust' => [
                ['key' => 'items', 'label' => 'Trust Badges', 'type' => 'icon_list'],
            ],
            $section === 'hero-slider' => [
                ['key' => 'slides', 'label' => 'Slider Images', 'type' => 'media_list'],
                ['key' => 'sideBanners', 'label' => 'Side Banners (max 2)', 'type' => 'media_list'],
            ],
            $section === 'category-carousel' => [
                ['key' => 'categoryIds', 'label' => 'Categories', 'type' => 'category_multi_select', 'max' => 20],
            ],
            str_starts_with($section, 'category-row-') => [
                ['key' => 'categoryId', 'label' => 'Category', 'type' => 'category_select'],
                ['key' => 'rail', 'label' => 'Horizontal rail (off = grid)', 'type' => 'checkbox'],
            ],
            $section === 'promo-strip-banner' => [
                ['key' => 'banner', 'label' => 'Banner (1 image)', 'type' => 'media_list'],
            ],
            $section === 'promos-grid' => [
                ['key' => 'promos', 'label' => 'Promo Banners', 'type' => 'media_list'],
            ],
            $section === 'discover-chips' => [
                ['key' => 'categoryIds', 'label' => 'Categories (max 18)', 'type' => 'category_multi_select', 'max' => 18],
            ],
            default => [],
        };
    }

    /** Default value per field key, used when a section has never been configured. */
    public static function defaultsFor(string $section): array
    {
        return array_map(
            fn (array $field) => match ($field['type']) {
                'text', 'category_select' => '',
                'checkbox' => true,
                default => [],
            },
            array_column(static::fieldsFor($section), null, 'key')
        );
    }
}
