<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'title', 'slug', 'landing_page_template_id', 'content',
        'status', 'seo', 'header_mode', 'footer_mode', 'published_at',
        'created_by',
    ];

    protected $casts = [
        'content' => 'array',
        'seo' => 'array',
        'published_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(LandingPageTemplate::class, 'landing_page_template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * View-data array (data/landingPage/selected_products/errors) for this
     * page's template.blade.php — content merged over the template's own
     * content.json defaults, so a page created before a template update
     * doesn't break on missing keys. Consumed by each template's root
     * Livewire component (e.g. BasicPromo::render()) to build its own view
     * — the shape template.blade.php files have always expected.
     */
    public function templateViewData(): array
    {
        $defaults = $this->template->defaultContentData();
        $content = $this->mergeContentOverDefaults($defaults, $this->content ?? []);

        return [
            'data' => json_decode(json_encode($content)),
            'landingPage' => $this,
            'errors' => new \Illuminate\Support\ViewErrorBag,
            // No ecommerce component integration yet (see
            // docs/landing-page-engine/04-phase-3-ecommerce-components.md)
            // — templates that reference $selected_products (e.g. for CAPI
            // view-item tracking) get an empty collection so they render
            // without crashing rather than silently faking product data.
            'selected_products' => new \Illuminate\Support\Collection,
        ];
    }

    /**
     * Shallow-safe recursive merge: page content overrides template
     * defaults key-by-key, without deep-diffing list/array shapes.
     */
    protected function mergeContentOverDefaults(array $defaults, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key]) && ! array_is_list($value)) {
                $defaults[$key] = $this->mergeContentOverDefaults($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }
}
