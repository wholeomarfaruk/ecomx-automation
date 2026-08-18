<?php

namespace App\Enums\Sales;

enum ConditionType: string
{
    case CART_AMOUNT      = 'cart_amount';
    case QUANTITY         = 'quantity';
    case PRODUCT          = 'product';
    case VARIANT          = 'variant';
    case CATEGORY         = 'category';
    case BRAND            = 'brand';
    case CUSTOMER         = 'customer';
    case CUSTOMER_GROUP   = 'customer_group';
    case PAYMENT_METHOD   = 'payment_method';
    case SHIPPING_METHOD  = 'shipping_method';

    public function label(): string
    {
        return match ($this) {
            self::CART_AMOUNT     => 'Cart Amount',
            self::QUANTITY        => 'Quantity',
            self::PRODUCT         => 'Product',
            self::VARIANT         => 'Variant',
            self::CATEGORY        => 'Category',
            self::BRAND           => 'Brand',
            self::CUSTOMER        => 'Customer',
            self::CUSTOMER_GROUP  => 'Customer Group',
            self::PAYMENT_METHOD  => 'Payment Method',
            self::SHIPPING_METHOD => 'Shipping Method',
        };
    }
}
