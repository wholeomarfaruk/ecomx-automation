<?php

namespace App\Exceptions\Inventory;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forProduct(string $productName, float $requested, float $available): self
    {
        return new self(
            "Not enough stock for \"{$productName}\": requested {$requested}, only {$available} available."
        );
    }
}
