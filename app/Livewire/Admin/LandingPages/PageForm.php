<?php

namespace App\Livewire\Admin\LandingPages;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\LandingPage;
use App\Models\LandingPageTemplate;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PageForm extends Component
{
    use WithMediaPicker;

    public ?int $editingId = null;

    public string $name = '';
    public string $title = '';
    public string $slug = '';
    public ?int $templateId = null;

    public string $seoMetaTitle = '';
    public string $seoMetaDescription = '';
    public string $seoMetaImage = '';

    public string $headerMode = 'none';
    public string $footerMode = 'none';

    /** @var array<string, mixed> dot-path key => value, matches schema.json field "key" values */
    public array $content = [];

    /** Which content dot-path a media picker selection should land in. */
    public ?string $pendingMediaField = null;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->loadPage($id);
            return;
        }

        $templates = LandingPageTemplate::active()->orderBy('name')->get();
        if ($templates->isNotEmpty()) {
            $this->selectTemplate($templates->first()->id);
        }
    }

    protected function loadPage(int $id): void
    {
        $page = LandingPage::findOrFail($id);

        $this->editingId = $page->id;
        $this->name = $page->name;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->templateId = $page->landing_page_template_id;

        $this->seoMetaTitle = $page->seo['meta_title'] ?? '';
        $this->seoMetaDescription = $page->seo['meta_description'] ?? '';
        $this->seoMetaImage = $page->seo['meta_image'] ?? '';

        $this->headerMode = $page->header_mode;
        $this->footerMode = $page->footer_mode;

        $this->content = $page->content ?? [];
    }

    public function updatedName(string $value): void
    {
        if ($this->editingId === null) {
            $this->slug = Str::slug($value);
        }
    }

    public function selectTemplate(int $templateId): void
    {
        $this->templateId = $templateId;

        if ($this->editingId === null) {
            $template = LandingPageTemplate::findOrFail($templateId);
            $this->content = $template->defaultContentData();
        }
    }

    public function template(): ?LandingPageTemplate
    {
        return $this->templateId ? LandingPageTemplate::find($this->templateId) : null;
    }

    /** @return array<string, array> schema.json sections, ordered */
    public function schemaSections(): array
    {
        $template = $this->template();
        if (! $template) {
            return [];
        }

        $sections = $template->schemaData();
        uasort($sections, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return $sections;
    }

    public function getFieldValue(string $key): mixed
    {
        return Arr::get($this->content, $key, '');
    }

    public function updateField(string $key, string $value): void
    {
        Arr::set($this->content, $key, $value);
    }

    public function addMediaSlot(string $fieldKey): void
    {
        $this->pendingMediaField = $fieldKey;
        $this->dispatch('openMediaPicker', target: 'pageFormMedia', multiple: false, type: 'image');
    }

    /** Overrides WithMediaPicker::mediaSelected — writes into the dot-path content array, not a flat property. */
    public function mediaSelected($field, $id): void
    {
        if ($field !== 'pageFormMedia' || $this->pendingMediaField === null) {
            return;
        }

        $fieldKey = $this->pendingMediaField;
        $this->pendingMediaField = null;

        Arr::set($this->content, $fieldKey, file_path($id));
    }

    /**
     * Repeater fields store their items at $key (an array of objects, each
     * shaped by the field's own "item_schema"). All four operations work
     * purely on array indexes under that dot-path — no separate storage,
     * so a repeater is just a normal part of $content like everything else.
     */
    public function addRepeaterItem(string $key, array $defaults = []): void
    {
        $items = Arr::get($this->content, $key, []);
        $items[] = $defaults;
        Arr::set($this->content, $key, array_values($items));
    }

    public function removeRepeaterItem(string $key, int $index): void
    {
        $items = Arr::get($this->content, $key, []);
        unset($items[$index]);
        Arr::set($this->content, $key, array_values($items));
    }

    public function moveRepeaterItem(string $key, int $index, int $direction): void
    {
        $items = Arr::get($this->content, $key, []);
        $target = $index + $direction;

        if (! isset($items[$index]) || ! isset($items[$target])) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
        Arr::set($this->content, $key, array_values($items));
    }

    public function updateRepeaterField(string $key, int $index, string $subKey, string $value): void
    {
        Arr::set($this->content, "{$key}.{$index}.{$subKey}", $value);
    }

    public function addRepeaterMediaSlot(string $key, int $index, string $subKey): void
    {
        $this->pendingMediaField = "{$key}.{$index}.{$subKey}";
        $this->dispatch('openMediaPicker', target: 'pageFormMedia', multiple: false, type: 'image');
    }

    /**
     * Backs the "product" schema field type's <x-searchable-select> — same
     * pattern as PurchaseOrderForm's variantOptions()/resolveVariantImages(),
     * so a schema.json field can say "type": "product" instead of forcing
     * admins to type a raw numeric product ID into a text box.
     */
    #[Computed]
    public function productOptions(): array
    {
        return Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->mapWithKeys(fn (Product $p) => [(string) $p->id => "{$p->name} [{$p->code}]"])
            ->all();
    }

    #[Computed]
    public function productImages(): array
    {
        return Product::query()
            ->active()
            ->whereNotNull('featured_image_id')
            ->get(['id', 'featured_image_id'])
            ->mapWithKeys(fn (Product $p) => [(string) $p->id => $p->featured_image])
            ->all();
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:190',
            'title' => 'required|string|max:190',
            'slug' => 'required|string|max:190|alpha_dash|unique:landing_pages,slug,' . ($this->editingId ?? 'NULL'),
            'templateId' => 'required|exists:landing_page_templates,id',
            'seoMetaTitle' => 'nullable|string|max:70',
            'seoMetaDescription' => 'nullable|string|max:160',
            'seoMetaImage' => 'nullable|string',
            'headerMode' => 'required|in:global,custom,none',
            'footerMode' => 'required|in:global,custom,none',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'title' => $this->title,
            'slug' => $this->slug,
            'landing_page_template_id' => $this->templateId,
            'content' => $this->content,
            'seo' => [
                'meta_title' => $this->seoMetaTitle,
                'meta_description' => $this->seoMetaDescription,
                'meta_image' => $this->seoMetaImage,
            ],
            'header_mode' => $this->headerMode,
            'footer_mode' => $this->footerMode,
        ];

        if ($this->editingId) {
            LandingPage::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Landing page updated']);
        } else {
            $data['status'] = 'draft';
            $data['created_by'] = auth()->id();
            $page = LandingPage::create($data);
            $this->editingId = $page->id;
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Landing page created']);
        }

        $this->redirect(route('admin.landingpages.pages.edit', $this->editingId), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.admin.landing-pages.page-form', [
            'templates' => LandingPageTemplate::active()->orderBy('name')->get(),
        ])->layout('layouts.admin.admin');
    }
}
