<?php

namespace App\Services\Media;

use App\Models\File;
use App\Models\FileItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Generates a single width-constrained, aspect-ratio-preserving thumbnail
 * variant for an image File — never crops, never distorts. Settings
 * (enabled/width/format/quality) come from the `settings` table's 'media'
 * group (see Setting::get()), falling back to config('media.thumbnail.*').
 * Format is currently always AVIF regardless of the configured value — see
 * settings()'s docblock — but the setting is stored now so a future format
 * (e.g. webp) can be added without another settings migration.
 */
class ThumbnailService
{
    public function settings(): array
    {
        return [
            'enabled' => (bool) Setting::get('thumbnail_enabled', config('media.thumbnail.enabled'), 'media'),
            'width' => (int) Setting::get('thumbnail_width', config('media.thumbnail.width'), 'media'),
            'format' => (string) Setting::get('thumbnail_format', config('media.thumbnail.format'), 'media'),
            'quality' => (int) Setting::get('thumbnail_quality', config('media.thumbnail.quality'), 'media'),
        ];
    }

    /**
     * Generates the thumbnail for $file's original FileItem, replacing any
     * existing thumbnail. No-ops (does not throw) when thumbnails are
     * disabled, the original is missing, or encoding fails — a missing
     * thumbnail is a valid state (see file_path()'s fallback to original).
     */
    public function generate(File $file): void
    {
        $settings = $this->settings();

        if (! $settings['enabled']) {
            return;
        }

        $original = $file->items()->where('type', 'original')->first();

        if (! $original || ! Storage::disk('public')->exists($original->path)) {
            return;
        }

        try {
            $manager = extension_loaded('imagick') ? ImageManager::imagick() : ImageManager::gd();
            $image = $manager->read(Storage::disk('public')->path($original->path));
            $image->scale(width: $settings['width']);

            $encoded = $image->encode(new AvifEncoder(quality: $settings['quality']));

            $thumbPath = 'uploads/thumbnails/' . uniqid() . '.avif';
            Storage::disk('public')->put($thumbPath, (string) $encoded);

            $file->items()->where('type', 'thumbnail')->get()->each(function (FileItem $old) {
                Storage::disk('public')->delete($old->path);
                $old->delete();
            });

            FileItem::create([
                'file_id' => $file->id,
                'type' => 'thumbnail',
                'size' => Storage::disk('public')->size($thumbPath),
                'path' => $thumbPath,
            ]);
        } catch (Throwable $e) {
            Log::warning("Thumbnail generation failed for file [{$file->id}]: {$e->getMessage()}");
        }
    }
}
