<?php

namespace App\Console\Commands;

use App\Enums\File\Type;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\File;
use App\Models\FileItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportSeldomFashionFeed extends Command
{
    protected $signature = 'import:seldom-fashion-feed {--limit=0 : Import only the first N products (0 = all)} {--category=Women : Category name to assign imported products to}';

    protected $description = 'Import products from seldomfashion.com/facebook-product-feed.xml into the catalog';

    private const FEED_URL = 'https://seldomfashion.com/facebook-product-feed.xml';

    private const COLOR_WORDS = [
        'Purple', 'Pink', 'Cream', 'Olive', 'Silver', 'Blush', 'Rose', 'Sky', 'Gold',
        'Green', 'Blue', 'Red', 'Black', 'White', 'Grey', 'Gray', 'Maroon', 'Navy',
        'Peach', 'Mint', 'Lavender', 'Lilac', 'Beige', 'Brown', 'Orange', 'Yellow',
        'Wine', 'Coral', 'Teal', 'Turquoise', 'Charcoal', 'Ash', 'Magenta',
    ];

    public function handle(): int
    {
        $this->info('Fetching feed: ' . self::FEED_URL);

        $xmlBody = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get(self::FEED_URL)->body();
        $xml = simplexml_load_string($xmlBody);

        if ($xml === false) {
            $this->error('Failed to parse feed XML');
            return self::FAILURE;
        }

        $namespaces = $xml->channel->item[0]->getNamespaces(true);
        $g = $namespaces['g'];

        $sizeAttribute = Attribute::firstOrCreate(
            ['slug' => 'size'],
            ['name' => 'Size', 'type' => 'select', 'status' => 'active']
        );
        $unstitchedValue = AttributeValue::firstOrCreate(
            ['attribute_id' => $sizeAttribute->id, 'slug' => 'unstitched'],
            ['value' => 'Unstitched', 'sort_order' => 1]
        );

        $colorAttribute = Attribute::firstOrCreate(
            ['slug' => 'color'],
            ['name' => 'Color', 'type' => 'color', 'status' => 'active']
        );

        $brand = Brand::firstOrCreate(
            ['slug' => Str::slug('Seldom Fashion')],
            ['name' => 'Seldom Fashion', 'status' => 'active']
        );

        $categoryName = (string) $this->option('category');
        $category = Category::where('name', $categoryName)->first();

        if (! $category) {
            $this->warn("Category '{$categoryName}' not found — imported products will not be assigned to any category.");
        }

        $maxSort = Product::max('sort_order') ?? 0;
        $imported = 0;
        $skipped = 0;

        $limit = (int) $this->option('limit');
        $items = $xml->channel->item;

        foreach ($items as $index => $item) {
            if ($limit > 0 && $imported >= $limit) {
                break;
            }

            $c = $item->children($g);
            $title = trim((string) $c->title);
            $link = trim((string) $c->link);
            $slug = Str::slug(basename(rtrim($link, '/')));

            if (Product::where('slug', $slug)->exists()) {
                $this->line("Skip (exists): {$title}");
                $skipped++;
                continue;
            }

            $price = $this->extractAmount((string) $c->price);
            $salePrice = $this->extractAmount((string) $c->sale_price);
            if ($salePrice !== null && $price !== null && $salePrice >= $price) {
                $salePrice = null;
            }

            $description = html_entity_decode((string) $c->description, ENT_QUOTES | ENT_HTML5);
            $imageUrl = trim((string) $c->image_link);
            $feedId = trim((string) $c->id);

            $code = 'SF-' . str_pad($feedId, 4, '0', STR_PAD_LEFT);
            if (Product::where('code', $code)->exists()) {
                $code = 'SF-' . str_pad($feedId, 4, '0', STR_PAD_LEFT) . '-' . Str::random(4);
            }

            $maxSort++;

            $product = Product::create([
                'code' => $code,
                'name' => $title,
                'slug' => $slug,
                'short_description' => Str::limit(strip_tags($description), 150),
                'description' => $description,
                'brand_id' => $brand->id,
                'status' => 'active',
                'stock_status' => 'in_stock',
                'product_type' => 'variable',
                'price' => $price,
                'sale_price' => $salePrice,
                'sort_order' => $maxSort,
            ]);

            if ($category) {
                $product->categories()->sync([$category->id]);
            }

            // Size attribute (always "Unstitched")
            $productSizeAttr = $product->productAttributes()->create(['attribute_id' => $sizeAttribute->id]);
            $product->attributeOrder()->create(['product_attribute_id' => $productSizeAttr->id, 'sort_order' => 1]);
            $productSizeAttrValue = $productSizeAttr->values()->create([
                'attribute_value_id' => $unstitchedValue->id,
                'sort_order' => 1,
            ]);

            // Color attribute, only if found in the title
            $colorName = $this->extractColor($title);
            $productColorAttrValue = null;
            if ($colorName !== null) {
                $colorValue = AttributeValue::firstOrCreate(
                    ['attribute_id' => $colorAttribute->id, 'slug' => Str::slug($colorName)],
                    ['value' => $colorName, 'sort_order' => 1]
                );

                $productColorAttr = $product->productAttributes()->create(['attribute_id' => $colorAttribute->id]);
                $product->attributeOrder()->create(['product_attribute_id' => $productColorAttr->id, 'sort_order' => 2]);
                $productColorAttrValue = $productColorAttr->values()->create([
                    'attribute_value_id' => $colorValue->id,
                    'sort_order' => 1,
                ]);
            }

            // Download the image and register it as a File
            $file = $this->downloadImage($imageUrl, $slug);
            if ($file) {
                $product->update([
                    'featured_image_id' => $file->id,
                    'image_ids' => [$file->id],
                ]);
            }

            // Single variant combining Size (+ Color if present)
            $valueIds = collect([$productSizeAttrValue->id, $productColorAttrValue?->id])->filter()->sort()->values();
            $combinationKey = $valueIds->implode('-');
            $labels = collect([$unstitchedValue->value, $colorName])->filter()->implode('-');
            $sku = Str::limit(strtoupper($code . '-' . Str::slug($labels, '-')), 190, '');

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'combination_key' => $combinationKey,
                'price' => $price,
                'sale_price' => $salePrice,
                'stock_quantity' => 10,
                'status' => 'active',
                'sort_order' => 1,
            ]);

            foreach ($valueIds as $productAttributeValueId) {
                ProductVariantValue::create([
                    'product_variant_id' => $variant->id,
                    'product_attribute_value_id' => $productAttributeValueId,
                ]);
            }

            if ($file) {
                $variant->media()->create([
                    'media_id' => $file->id,
                    'is_primary' => true,
                    'sort_order' => 1,
                ]);
            }

            $this->info("Imported: {$title}" . ($colorName ? " [{$colorName}]" : ''));
            $imported++;
        }

        $this->newLine();
        $this->info("Done. Imported: {$imported}, Skipped (already existed): {$skipped}");

        return self::SUCCESS;
    }

    private function extractAmount(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/[\d.]+/', $raw, $m)) {
            return (float) $m[0];
        }

        return null;
    }

    private function extractColor(string $title): ?string
    {
        foreach (self::COLOR_WORDS as $color) {
            if (preg_match('/\b' . preg_quote($color, '/') . '\b/i', $title)) {
                return $color;
            }
        }

        return null;
    }

    private function downloadImage(string $url, string $slug): ?File
    {
        if ($url === '') {
            return null;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(30)->get($url);
        } catch (\Throwable $e) {
            $this->warn("  Image download failed for {$slug}: {$e->getMessage()}");
            return null;
        }

        if (! $response->ok()) {
            $this->warn("  Image download failed for {$slug}: HTTP {$response->status()}");
            return null;
        }

        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        $extension = strtolower($extension);
        $fileName = $slug . '.' . $extension;
        $storedName = uniqid() . '-' . $fileName;
        $relativePath = 'uploads/' . $storedName;

        Storage::disk('public')->put($relativePath, $response->body());

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
