<?php

namespace App\LandingPageEngine;

use App\Models\LandingPageTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TemplateDiscoveryService
{
    protected const KEY_PATTERN = '/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/';

    protected const REQUIRED_FILES = ['config.json', 'schema.json', 'content.json', 'template.blade.php'];

    /**
     * @return array{registered: array<int, string>, skipped: array<int, string>}
     */
    public function discover(): array
    {
        $registered = [];
        $skipped = [];

        foreach ($this->roots() as $source => $root) {
            if (! File::isDirectory($root)) {
                continue;
            }

            foreach (File::directories($root) as $folder) {
                $key = basename($folder);

                if (! preg_match(self::KEY_PATTERN, $key)) {
                    $skipped[] = "{$key} ({$source}): invalid key format";
                    Log::warning("[LandingPageEngine] Skipped template '{$key}': invalid key format.");
                    continue;
                }

                // config.json's own required-file presence is checked by
                // missingFiles() below; read it first (if present) so its
                // declared "root_component"/"template_blade" filenames (if
                // any) can be used for the rest of the required-file check
                // instead of assuming the {StudlyTemplateKey}.php/
                // template.blade.php conventions.
                $config = File::exists($folder . '/config.json') ? $this->readConfig($folder . '/config.json') : null;

                $missing = $this->missingFiles($folder, $key, $config ?? []);
                if ($missing !== []) {
                    $skipped[] = "{$key} ({$source}): missing " . implode(', ', $missing);
                    Log::warning("[LandingPageEngine] Skipped template '{$key}': missing " . implode(', ', $missing));
                    continue;
                }

                if ($config === null) {
                    $skipped[] = "{$key} ({$source}): invalid config.json";
                    Log::warning("[LandingPageEngine] Skipped template '{$key}': invalid config.json.");
                    continue;
                }

                LandingPageTemplate::updateOrCreate(
                    ['key' => $key],
                    [
                        'name' => $config['name'] ?? $key,
                        'category' => $config['category'] ?? null,
                        'description' => $config['description'] ?? null,
                        'preview_image' => $config['preview_image'] ?? null,
                        'version' => $config['version'] ?? null,
                        'status' => $config['status'] ?? 'active',
                        'source' => $source,
                        'capabilities' => $config['capabilities'] ?? null,
                    ]
                );

                $registered[] = "{$key} ({$source})";
            }
        }

        return ['registered' => $registered, 'skipped' => $skipped];
    }

    /**
     * @return array<string, string>
     */
    protected function roots(): array
    {
        return [
            'system' => resource_path('landingpage-templates'),
            // Publicly web-reachable (via the storage:link symlink at
            // public/storage) — a custom template's own assets
            // (preview_image, etc.) resolve to a real URL. See
            // LandingPageTemplate::basePath()/previewImageUrl().
            'custom' => storage_path('app/public/landingpage-templates'),
        ];
    }

    /**
     * @param  array  $config  Decoded config.json (empty array if the file
     *                         itself is missing/invalid — REQUIRED_FILES
     *                         below still catches that case directly).
     * @return array<int, string>
     */
    protected function missingFiles(string $folder, string $key, array $config): array
    {
        $missing = [];

        foreach (self::REQUIRED_FILES as $file) {
            if (! File::exists($folder . '/' . $file)) {
                $missing[] = $file;
            }
        }

        // Every template package must also ship a mandatory root Livewire
        // component whose render() exposes the template's blade view as
        // its own view. Filename is declared in config.json
        // ("root_component"), defaulting to the {StudlyTemplateKey}.php
        // convention when not declared — see
        // App\Models\LandingPageTemplate::rootComponentFilename(), which
        // this mirrors so discovery and the model/registrar never disagree
        // about the expected filename. Model isn't usable here yet (no DB
        // row exists to construct one from), so the same default logic is
        // duplicated inline.
        $rootComponentFile = $config['root_component'] ?? Str::studly($key) . '.php';
        if (! File::exists($folder . '/' . $rootComponentFile)) {
            $missing[] = $rootComponentFile . ' (mandatory root Livewire component)';
        }

        return $missing;
    }

    protected function readConfig(string $path): ?array
    {
        $decoded = json_decode(File::get($path), true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
    }
}
