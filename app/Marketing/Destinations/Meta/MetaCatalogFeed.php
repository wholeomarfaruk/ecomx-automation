<?php

namespace App\Marketing\Destinations\Meta;

use App\Enums\Product\ProductType;
use App\Marketing\Catalog\CatalogItemId;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OfferService;
use App\Services\StockService;
use Generator;
use Illuminate\Support\Str;
use XMLWriter;

/**
 * Product catalog feed for Meta (RSS 2.0 with Google's "g:" namespace —
 * a format Meta's scheduled feeds accept, and Google Merchant Center too).
 * Field rules: https://developers.facebook.com/docs/marketing-api/catalog/reference/
 *
 * One item per thing a shopper can actually buy: every active variant of a
 * variable product (sharing the product's item_group_id, told apart by
 * color/size), or the product itself for simple/combo products. Ids come
 * from CatalogItemId — the same ids Conversions API and the dataLayer send.
 *
 * Deliberate choices:
 * - Out-of-stock items stay in the feed as "out of stock" rather than being
 *   dropped: a removed item loses its catalog audiences and turns in-flight
 *   events for it into unmatched ids.
 * - price/sale_price are what the product page shows (Product::unitPricing()
 *   — sale price plus per-unit offers), since Meta rejects items whose feed
 *   price doesn't match the landing page.
 * - Items with no image are skipped — Meta rejects them anyway.
 */
final class MetaCatalogFeed
{
    // Same currency the storefront's marketing events send.
    private const CURRENCY = 'BDT';

    private const MAX_ADDITIONAL_IMAGES = 10;

    /** @var array<int|string, ?string> file id → public URL, so a gallery shared by every variant is looked up once */
    private array $imageUrls = [];

    public function __construct(
        private readonly StockService $stock,
        private readonly OfferService $offers,
    ) {}

    public function render(): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

        $xml->startElement('channel');
        $xml->writeElement('title', (string) config('app.name'));
        $xml->writeElement('link', url('/'));
        $xml->writeElement('description', config('app.name').' product catalog');

        foreach ($this->items() as $item) {
            $xml->startElement('item');

            foreach ($item as $field => $value) {
                // Arrays are repeated fields (additional_image_link).
                foreach ((array) $value as $one) {
                    $xml->writeElement('g:'.$field, $one);
                }
            }

            $xml->endElement();
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /** @return Generator<int, array<string, string|string[]>> */
    public function items(): Generator
    {
        $products = Product::query()
            ->where('status', 'active')
            ->with([
                'brand:id,name',
                'categories:id,name',
                'variants' => fn ($query) => $query
                    ->where('status', 'active')
                    ->with(['values.productAttributeValue.attributeValue.attribute', 'media']),
            ]);

        foreach ($products->lazyById(100) as $product) {
            yield from $this->itemsFor($product);
        }
    }

    /** @return Generator<int, array<string, string|string[]>> */
    private function itemsFor(Product $product): Generator
    {
        if ($product->product_type === ProductType::VARIABLE) {
            // A variable product with no active variants has nothing to buy.
            foreach ($product->variants as $variant) {
                $item = $this->buildItem($product, $variant);

                if ($item) {
                    yield $item;
                }
            }

            return;
        }

        $item = $this->buildItem($product, null);

        if ($item) {
            yield $item;
        }
    }

    /** @return array<string, string|string[]>|null */
    private function buildItem(Product $product, ?ProductVariant $variant): ?array
    {
        $pricing = $product->unitPricing($variant);

        if ($pricing['regular'] <= 0) {
            return null;
        }

        [$image, $additionalImages] = $this->images($product, $variant);

        // No image or no product page (empty slug) — Meta rejects the item anyway.
        if (! $image || ! $product->url) {
            return null;
        }

        $options = $variant ? $variant->options_map : [];
        $onSale = $pricing['discounted'] < $pricing['regular'];

        $item = [
            'id' => CatalogItemId::item($product->id, $variant?->id),
            'item_group_id' => $variant ? CatalogItemId::group($product->id) : null,
            'title' => Str::limit($this->clean($product->name), 150, ''),
            'description' => $this->description($product),
            'link' => $product->url,
            'image_link' => $image,
            'additional_image_link' => $additionalImages,
            'brand' => $this->clean($product->brand?->name) ?: (string) config('app.name'),
            'condition' => 'new',
            'availability' => $this->inStock($product, $variant) ? 'in stock' : 'out of stock',
            'price' => $this->money($pricing['regular']),
            'sale_price' => $onSale ? $this->money($pricing['discounted']) : null,
            'sale_price_effective_date' => $onSale ? $this->saleWindow($product, $variant, $pricing) : null,
            'product_type' => $this->clean($product->categories->first()?->name) ?: null,
            // Same attribute names the storefront buy box keys its pickers on.
            'color' => $options['Color'] ?? null,
            'size' => $options['Size'] ?? null,
            'additional_variant_attribute' => $this->otherOptions($options),
        ];

        return array_filter($item, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * Mirrors what the storefront lets through to a completed order:
     * CartManager rejects a product marked out_of_stock outright, a variant
     * needs its own stock, and a simple product needs stock per StockService
     * (which checkout's stock commit enforces).
     */
    private function inStock(Product $product, ?ProductVariant $variant): bool
    {
        if ($product->stock_status === 'out_of_stock') {
            return false;
        }

        return match (true) {
            $variant !== null => (float) $variant->stock_quantity > 0,
            $product->product_type === ProductType::COMBO => ($product->comboAvailableQuantity() ?? 0) > 0,
            default => $this->stock->available($product, null) > 0,
        };
    }

    /**
     * The variant's own photo leads when it has one (e.g. the Red colour's
     * image), then the product's gallery.
     *
     * @return array{0: ?string, 1: string[]}
     */
    private function images(Product $product, ?ProductVariant $variant): array
    {
        $fileIds = array_merge(
            $variant ? $variant->media->sortByDesc('is_primary')->pluck('media_id')->all() : [],
            array_filter([$product->featured_image_id]),
            (array) ($product->image_ids ?? []),
        );

        $urls = collect($fileIds)
            ->filter()
            ->unique()
            ->map(fn ($id) => $this->imageUrl($id))
            ->filter()
            ->unique()
            ->values();

        return [$urls->first(), $urls->slice(1, self::MAX_ADDITIONAL_IMAGES)->values()->all()];
    }

    private function imageUrl(int|string $fileId): ?string
    {
        if (! array_key_exists($fileId, $this->imageUrls)) {
            $this->imageUrls[$fileId] = file_path($fileId);
        }

        return $this->imageUrls[$fileId];
    }

    /**
     * Meta's sale_price_effective_date ("start/end", ISO 8601), so the
     * catalog reverts to the regular price by itself when a time-limited
     * offer ends instead of showing a stale sale until the next fetch.
     *
     * Only when the whole discount comes from dated offers: if the product's
     * own sale price is part of it, the price after the offer ends is that
     * sale price — which Meta can't express — so no window is sent and the
     * next fetch corrects it.
     *
     * @param array{regular: float, selling: float, discounted: float} $pricing
     */
    private function saleWindow(Product $product, ?ProductVariant $variant, array $pricing): ?string
    {
        if ($pricing['selling'] < $pricing['regular']) {
            return null;
        }

        $endsAt = $this->offers->unitPriceEndsAt($product, $pricing['selling'], $variant?->id);

        return $endsAt ? now()->format('Y-m-d\TH:iP').'/'.$endsAt->format('Y-m-d\TH:iP') : null;
    }

    private function description(Product $product): string
    {
        $text = $this->clean(html_entity_decode(
            strip_tags((string) ($product->description ?: $product->short_description)),
            ENT_QUOTES | ENT_HTML5,
        ));

        return Str::limit($text ?: $this->clean($product->name), 5000, '');
    }

    /** Meta's format for variant attributes beyond color/size: "Label:Value,Label:Value". */
    private function otherOptions(array $options): ?string
    {
        $pairs = collect($options)
            ->except(['Color', 'Size'])
            ->map(fn ($value, $label) => "{$label}:{$value}");

        return $pairs->isEmpty() ? null : $pairs->implode(',');
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '').' '.self::CURRENCY;
    }

    /**
     * Replaces invalid UTF-8 (e.g. Latin-1 bytes from an import) instead of
     * letting the /u regexes below fail and blank the field, drops control
     * characters XML can't carry, and collapses whitespace.
     */
    private function clean(?string $value): string
    {
        $value = mb_scrub((string) $value, 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }
}
