<?php

namespace App\Livewire\Admin\ThemeEngine;

use App\Support\EcomxFashion\ThemeRegistry;
use Livewire\Component;

/**
 * Theme-agnostic: PageRegistry/PageSeoRegistry/PageSectionRegistry/
 * PageSettingsRegistry live one-per-theme under App\Support\{ThemeNamespace}\*,
 * so this resolves the active theme's classes by name (via
 * ThemeRegistry::active()) rather than hardcoding a single theme's import.
 */
class PageSectionManager extends Component
{
    public string $page = 'home';
    public string $activeTab = 'sections';

    public string $metaTitle = '';
    public string $metaDescription = '';
    public string $ogImage = '';

    /** Studly-cased theme namespace, e.g. 'ecomx-anyniche' -> 'EcomxAnyniche'. */
    protected static function themeNamespace(): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', ThemeRegistry::active())));
    }

    protected static function pageRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageRegistry';
    }

    protected static function pageSeoRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageSeoRegistry';
    }

    protected static function pageSectionRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageSectionRegistry';
    }

    protected static function pageSettingsRegistry(): string
    {
        return 'App\\Support\\' . static::themeNamespace() . '\\PageSettingsRegistry';
    }

    public function mount(string $page = 'home'): void
    {
        $this->page = $page;

        $seo = static::pageSeoRegistry()::forPage($this->page);
        $this->metaTitle = $seo['meta_title'];
        $this->metaDescription = $seo['meta_description'];
        $this->ogImage = $seo['og_image'];
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function saveSeo(): void
    {
        static::pageSeoRegistry()::save($this->page, [
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'og_image' => $this->ogImage,
        ]);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'SEO settings saved.',
        ]);
    }

    public function togglePublished(bool $value): void
    {
        static::pageSettingsRegistry()::setPublished($this->page, $value);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Page is now ' . ($value ? 'published' : 'draft') . '.',
        ]);
    }

    public function toggleActive(string $key, bool $value): void
    {
        static::pageSectionRegistry()::setActive($this->page, $key, $value);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $key . ' is now ' . ($value ? 'active' : 'inactive') . '.',
        ]);
    }

    /** @param string[] $orderedKeys */
    public function reorder(array $orderedKeys): void
    {
        static::pageSectionRegistry()::reorder($this->page, $orderedKeys);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Section order updated.',
        ]);
    }

    public function render()
    {
        $sections = static::pageSectionRegistry()::forPage($this->page);

        if (empty($sections)) {
            $configuredKeys = static::pageRegistry()::sectionKeysForPage($this->page);

            $sections = array_map(fn (int $order, string $key) => [
                'key' => $key,
                'active' => true,
                'order' => $order,
            ], array_keys($configuredKeys), $configuredKeys);
        }

        return view('livewire.admin.theme-engine.page-section-manager', [
            'sections' => $sections,
            'published' => static::pageSettingsRegistry()::isPublished($this->page),
        ]);
    }
}
