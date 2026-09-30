<?php

namespace App\Marketing\Catalog;

use App\Enums\Product\ProductType;
use App\Models\Product;

/**
 * The one id scheme shared by the Meta catalog feed, Conversions API events
 * and the GTM dataLayer — an event's content_ids only match a catalog item
 * when both sides build the id the same way.
 *
 * Built from database ids, never product code / variant SKU: both are
 * editable in the admin (and variant SKUs are derived from the code), and a
 * changed id silently orphans the item's event history and catalog audiences.
 * Same approach as Shopify's Meta integration (variant id + product id).
 *
 *   simple / combo product 102  → item "102"
 *   variant 57 of product 102   → item "102_57", item group "102"
 */
final class CatalogItemId
{
    public static function item(int|string $productId, int|string|null $variantId = null): string
    {
        return $variantId ? "{$productId}_{$variantId}" : (string) $productId;
    }

    public static function group(int|string $productId): string
    {
        return (string) $productId;
    }

    /**
     * For a cart/order line: the variant only counts when the product is
     * variable, matching MetaCatalogFeed (which decides by product_type) —
     * a simple product left with stale variant rows is still one item.
     * With no product at hand (deleted), the line's variant is trusted.
     */
    public static function line(?Product $product, int|string $productId, int|string|null $variantId): string
    {
        $variantCounts = $product === null || $product->product_type === ProductType::VARIABLE;

        return self::item($productId, $variantCounts ? $variantId : null);
    }
}
