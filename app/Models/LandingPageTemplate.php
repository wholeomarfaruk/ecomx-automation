<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File as FileFacade;

class LandingPageTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'category', 'description', 'preview_image',
        'version', 'status', 'source', 'capabilities',
    ];

    protected $casts = [
        'capabilities' => 'array',
    ];

    public function landingPages()
    {
        return $this->hasMany(LandingPage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function basePath(): string
    {
        return $this->source === 'custom'
            ? storage_path("app/public/landingpage-templates/{$this->key}")
            : resource_path("landingpage-templates/{$this->key}");
    }

    /**
     * Public URL for the template's preview_image, when the template is
     * "custom" (its files live under storage/app/public, web-reachable via
     * the storage:link symlink at public/storage). System templates live
     * under resources/, which is never web-reachable — null there.
     */
    public function previewImageUrl(): ?string
    {
        if ($this->source !== 'custom' || ! $this->preview_image) {
            return null;
        }

        return asset("storage/landingpage-templates/{$this->key}/{$this->preview_image}");
    }

    public function configPath(): string
    {
        return $this->basePath() . '/config.json';
    }

    public function schemaPath(): string
    {
        return $this->basePath() . '/schema.json';
    }

    public function contentPath(): string
    {
        return $this->basePath() . '/content.json';
    }

    /**
     * Blade view filename — declared in config.json ("template_blade"),
     * defaulting to "template.blade.php" when not declared (every existing
     * template package). Explicit declaration lets a template author name
     * this file whatever they want without the engine having to guess.
     */
    public function bladePath(): string
    {
        $filename = $this->configData()['template_blade'] ?? 'template.blade.php';

        return $this->basePath() . '/' . $filename;
    }

    /**
     * Mandatory root Livewire component filename — declared in
     * config.json ("root_component"), defaulting to the
     * {StudlyTemplateKey}.php convention (e.g. BasicPromo.php for
     * basic-promo) when not declared. Read by
     * App\LandingPageEngine\TemplateDiscoveryService (required-file check)
     * and App\LandingPageEngine\TemplateComponentRegistrar (require_once +
     * Livewire::component() registration) — both must agree with this, so
     * this method is the single source of truth for the filename.
     */
    public function rootComponentFilename(): string
    {
        return $this->configData()['root_component'] ?? \Illuminate\Support\Str::studly($this->key) . '.php';
    }

    public function rootComponentPath(): string
    {
        return $this->basePath() . '/' . $this->rootComponentFilename();
    }

    public function configData(): array
    {
        return $this->readJson($this->configPath());
    }

    public function schemaData(): array
    {
        return $this->readJson($this->schemaPath());
    }

    public function defaultContentData(): array
    {
        return $this->readJson($this->contentPath());
    }

    /**
     * "fragment" (renders as content inside landingpage.layouts.landing_page,
     * layout owns <html>/<head>/<body>/title/meta/GTM) or "standalone"
     * (template.blade.php renders its own complete <html> document,
     * returned as a raw HTTP response — the original/legacy behavior).
     * Read live from config.json rather than stored as a DB column, same
     * reasoning as author/base_path: it's template-authoring metadata, not
     * something the admin UI needs to query/filter by.
     */
    public function renderMode(): string
    {
        return $this->configData()['render_mode'] ?? 'standalone';
    }

    protected function readJson(string $path): array
    {
        if (! FileFacade::exists($path)) {
            return [];
        }

        $decoded = json_decode(FileFacade::get($path), true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
}
