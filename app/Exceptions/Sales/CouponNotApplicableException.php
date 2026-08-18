<?php

namespace App\Exceptions\Sales;

use RuntimeException;

class CouponNotApplicableException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('This coupon code does not exist.');
    }

    public static function notActive(): self
    {
        return new self('This coupon is not currently active.');
    }

    public static function outOfSchedule(): self
    {
        return new self('This coupon is not valid at this time.');
    }

    public static function usageLimitReached(): self
    {
        return new self('This coupon has reached its usage limit.');
    }

    public static function customerUsageLimitReached(): self
    {
        return new self('You have already used this coupon the maximum number of times.');
    }

    public static function notAssignedToCustomer(): self
    {
        return new self('This coupon is not available for this customer.');
    }

    public static function minOrderAmountNotMet(float $minOrderAmount): self
    {
        return new self("This coupon requires a minimum order amount of {$minOrderAmount}.");
    }

    public static function conditionsNotMet(): self
    {
        return new self('This order does not meet the conditions for this coupon.');
    }
}
