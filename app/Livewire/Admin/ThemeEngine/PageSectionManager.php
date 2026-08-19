<?php

namespace App\Livewire\Admin\ThemeEngine;

use App\Support\EcomxFashion\PageRegistry;
use App\Support\EcomxFashion\PageSeoRegistry;
use App\Support\EcomxFashion\PageSectionRegistry;
use App\Support\EcomxFashion\PageSettingsRegistry;
use Livewire\Component;

class PageSectionManager extends Component
{
    public string $page = 'home';
    public string $activeTab = 'sections';

    public string $metaTitle = '';
    public string $metaDescription = '';
    public string $ogImage = '';

    public function mount(string $page = 'home'): void
    {
        $this->page = $page;

        $seo = PageSeoRegistry::forPage($this->page);
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
        PageSeoRegistry::save($this->page, [
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
        PageSettingsRegistry::setPublished($this->page, $value);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Page is now ' . ($value ? 'published' : 'draft') . '.',
        ]);
    }

    public function toggleActive(string $key, bool $value): void
    {
        PageSectionRegistry::setActive($this->page, $key, $value);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $key . ' is now ' . ($value ? 'active' : 'inactive') . '.',
        ]);
    }

    /** @param string[] $orderedKeys */
    public function reorder(array $orderedKeys): void
    {
        PageSectionRegistry::reorder($this->page, $orderedKeys);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Section order updated.',
        ]);
    }

    public function render()
    {
        $sections = PageSectionRegistry::forPage($this->page);

        if (empty($sections)) {
            $configuredKeys = PageRegistry::sectionKeysForPage($this->page);

            $sections = array_map(fn (int $order, string $key) => [
                'key' => $key,
                'active' => true,
                'order' => $order,
            ], array_keys($configuredKeys), $configuredKeys);
        }

        return view('livewire.admin.theme-engine.page-section-manager', [
            'sections' => $sections,
            'published' => PageSettingsRegistry::isPublished($this->page),
        ]);
    }
}
