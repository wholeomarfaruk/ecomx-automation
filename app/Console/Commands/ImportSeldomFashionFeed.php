<?php

namespace App\Console\Commands;

use App\Enums\File\Type;
use App\Enums\Product\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\File;
use App\Models\FileItem;
use App\Models\Product;
use App\Services\Media\ThumbnailService;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportSeldomFashionFeed extends Command
{
    protected $signature = 'import:seldom-fashion-feed
        {--limit=0 : Import only the first N products from the list (0 = all)}
        {--offset=0 : Skip the first N products in the list — use to resume a run that stopped partway}
        {--category=Women : Category name to assign imported products to}
        {--stock=10 : Initial stock quantity for newly imported products}';

    protected $description = 'Import simple products from the seldomfashion.com JSON product API into the catalog';

    private const API_LIST_URL = 'https://seldomfashion.com/api/products';

    public function handle(StockService $stockService, ThumbnailService $thumbnailService): int
    {
        $this->info('Fetching feed: ' . self::API_LIST_URL);

        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(30)->retry(3, 2000)->get(self::API_LIST_URL);
        } catch (\Throwable $e) {
            $this->error('Failed to fetch product list: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $response->ok()) {
            $this->error("Failed to fetch product list — HTTP {$response->status()}");
            return self::FAILURE;
        }

        $products = $response->json();

        if (! is_array($products)) {
            $this->error('Failed to parse product list JSON');
            return self::FAILURE;
        }

        $offset = (int) $this->option('offset');
        if ($offset > 0) {
            $products = array_slice($products, $offset, null, true);
        }

        $brand = Brand::firstOrCreate(
            ['slug' => Str::slug('Seldom Fashion')],
            ['name' => 'Seldom Fashion', 'status' => 'active']
        );

        $categoryName = (string) $this->option('category');
        $category = Category::where('name', $categoryName)->first();

        if (! $category) {
            $this->warn("Category '{$categoryName}' not found — imported products will not be assigned to any category.");
        }

        $stockQuantity = (float) $this->option('stock');
        $maxSort = Product::max('sort_order') ?? 0;
        $imported = 0;
        $skipped = 0;

        $limit = (int) $this->option('limit');

        foreach ($products as $index => $item) {
            if ($limit > 0 && $imported >= $limit) {
                break;
            }

            $feedId = $item['id'] ?? null;
            $title = trim((string) ($item['name'] ?? ''));

            if ($feedId === null || $title === '') {
                $this->warn("Skip: malformed entry at index {$index}");
                continue;
            }

            $slug = Str::slug($title . '-' . $feedId);

            if (Product::where('slug', $slug)->exists()) {
                $this->line("Skip (exists): {$title}");
                $skipped++;
                continue;
            }

            $price = $this->extractAmount($item['price'] ?? null);
            $salePrice = $this->extractAmount($item['sale_price'] ?? null);
            $purchasePrice = $this->extractAmount($item['purchase_price'] ?? null);

            if ($salePrice !== null && $price !== null && $salePrice >= $price) {
                $salePrice = null;
            }

            $description = html_entity_decode((string) ($item['description_raw'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $shortDescription = $item['short_description'] ?? null;

            $code = 'SF-' . str_pad((string) $feedId, 4, '0', STR_PAD_LEFT);
            if (Product::where('code', $code)->exists()) {
                $code = 'SF-' . str_pad((string) $feedId, 4, '0', STR_PAD_LEFT) . '-' . Str::random(4);
            }

            $maxSort++;

            $product = Product::create([
                'code' => $code,
                'name' => $title,
                'slug' => $slug,
                'short_description' => $shortDescription ? Str::limit(strip_tags($shortDescription), 150) : Str::limit(strip_tags($description), 150),
                'description' => $description,
                'brand_id' => $brand->id,
                'status' => 'active',
                'stock_status' => 'in_stock',
                'product_type' => ProductType::SIMPLE->value,
                'price' => $price,
                'sale_price' => $salePrice,
                'purchase_price' => $purchasePrice,
                'sort_order' => $maxSort,
            ]);

            if ($category) {
                $product->categories()->sync([$category->id]);
            }

            // Download featured image + gallery images as Files, generating a
            // thumbnail for each via ThumbnailService (same pipeline as manual uploads).
            $imageUrls = collect([$item['featured_image'] ?? null])
                ->merge($item['gallery_images'] ?? [])
                ->filter()
                ->unique()
                ->values();

            $fileIds = [];
            foreach ($imageUrls as $position => $imageUrl) {
                $file = $this->downloadImage($imageUrl, $slug . '-' . $position);
                if ($file) {
                    $thumbnailService->generate($file);
                    $fileIds[] = $file->id;
                }
            }

            if (! empty($fileIds)) {
                $product->update([
                    'featured_image_id' => $fileIds[0],
                    'image_ids' => $fileIds,
                ]);
            }

            if ($stockQuantity > 0) {
                $stockService->setAbsolute($product, null, $stockQuantity, note: 'Initial stock from Seldom Fashion import');
            }

            $this->info("Imported: {$title}" . ($price === null ? ' [no price — set manually]' : ''));
            $imported++;
        }

        $this->newLine();
        $this->info("Done. Imported: {$imported}, Skipped (already existed): {$skipped}");

        return self::SUCCESS;
    }

    private function extractAmount(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/[\d.]+/', $raw, $m)) {
            return (float) $m[0];
        }

        return null;
    }

    private function downloadImage(string $url, string $slug): ?File
    {
        if ($url === '') {
            $this->warn("  Image download skipped for {$slug}: no image URL");
            return null;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(30)->retry(2, 1500)->get($url);
        } catch (\Throwable $e) {
            $this->warn("  Image download failed for {$slug}: {$url} — " . $e->getMessage());
            return null;
        }

        if (! $response->ok()) {
            $this->warn("  Image download failed for {$slug}: {$url} — HTTP {$response->status()} " . Str::limit($response->body(), 150));
            return null;
        }

        if ($response->body() === '') {
            $this->warn("  Image download failed for {$slug}: {$url} — empty response body");
            return null;
        }

        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        $extension = strtolower($extension);
        $fileName = $slug . '.' . $extension;
        $storedName = uniqid() . '-' . $fileName;
        $relativePath = 'uploads/' . $storedName;

        $stored = Storage::disk('public')->put($relativePath, $response->body());

        if (! $stored) {
            $this->warn("  Image download failed for {$slug}: {$url} — could not write to storage/app/public/{$relativePath} (check disk permissions/space)");
            return null;
        }

        $file = File::create([
            'name' => $fileName,
            'type' => Type::fromExtension($extension)?->value,
            'extension' => $extension,
        ]);

        FileItem::create([
            'file_id' => $file->id,
            'type' => 'original',
            'size' => strlen($response->body()),
            'path' => $relativePath,
        ]);

        return $file;
    }
}
