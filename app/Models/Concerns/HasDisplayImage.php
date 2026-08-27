<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Shared "which photo shows for this line item" rule for any model with a
 * `product` and `variant` relation (CartItem, WishlistItem, ...): the
 * variant's own colour-specific image when it has one, otherwise the
 * product's featured image. Used by the cart drawer, wishlist drawer, and
 * checkout summary so they all agree on which photo to show.
 */
trait HasDisplayImage
{
    protected function displayImage(): Attribute
    {
        return Attribute::get(fn () => $this->variant?->display_image ?? $this->product?->featured_image);
    }
}
