<?php

namespace App\Support\EcomxFashion;

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
 * New field types can be added here + a matching form partial as sections
 * gain more configurable fields.
 */
class SectionSchema
{
    /** Field definitions for a section key, or [] if nothing is configurable yet. */
    public static function fieldsFor(string $section): array
    {
        return match ($section) {
            'hero' => [
                ['key' => 'bigSlides', 'label' => 'Big Slider', 'type' => 'media_list'],
                ['key' => 'sideSlides', 'label' => 'Side Slider', 'type' => 'media_list'],
            ],
            'marquee' => [
                ['key' => 'items', 'label' => 'Marquee Items', 'type' => 'text_list'],
            ],
            'categories' => [
                ['key' => 'categories', 'label' => 'Homepage Categories', 'type' => 'category_list'],
            ],
            'flash-sale' => [
                ['key' => 'badge', 'label' => 'Badge text', 'type' => 'text', 'placeholder' => '⚡ Flash Sale'],
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'Up to 40% off — ends in'],
                ['key' => 'buttonLabel', 'label' => 'Button label', 'type' => 'text', 'placeholder' => 'View all deals →'],
            ],
            'promo-strip' => [
                ['key' => 'banner', 'label' => 'Banner', 'type' => 'media_list'],
            ],
            'trending' => [
                ['key' => 'kicker', 'label' => 'Kicker text', 'type' => 'text', 'placeholder' => 'Trending now'],
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'The Trending Collection'],
                ['key' => 'categoryId', 'label' => 'Source category', 'type' => 'category_select'],
            ],
            'shop-by-style' => [
                ['key' => 'categoryIds', 'label' => 'Style Categories (max 6)', 'type' => 'category_multi_select', 'max' => 6],
            ],
            'reviews' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'Loved by our customers'],
                ['key' => 'linkLabel', 'label' => 'Link label', 'type' => 'text', 'placeholder' => 'Read all reviews →'],
            ],
            'why-faq' => [
                ['key' => 'kicker', 'label' => 'Kicker text', 'type' => 'text', 'placeholder' => 'Why Seldom Fashion'],
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'placeholder' => 'Made rarely. Made well. Made for Bangladesh.'],
                ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'placeholder' => "Since 2021 we've designed and finished every piece in our Dhaka atelier…"],
                ['key' => 'stats', 'label' => 'Trust Stats', 'type' => 'stat_list'],
                ['key' => 'faqs', 'label' => 'FAQs', 'type' => 'faq_list'],
            ],
            default => [],
        };
    }

    /** Default value per field key, used when a section has never been configured. */
    public static function defaultsFor(string $section): array
    {
        return array_map(
            fn (array $field) => in_array($field['type'], ['text', 'category_select'], true) ? '' : [],
            array_column(static::fieldsFor($section), null, 'key')
        );
    }
}
