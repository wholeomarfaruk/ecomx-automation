<?php

namespace App\Services;

use App\Enums\Sales\ConditionOperator;
use App\Enums\Sales\ConditionType;
use App\Enums\Sales\DiscountRuleType;
use App\Enums\Sales\PromotionStatus;
use App\Exceptions\Sales\CouponNotApplicableException;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PromotionCondition;

/**
 * Evaluates a coupon's free_shipping discount rule against an order and
 * persists the result. Scoped to shipping discounts only — percentage/fixed
 * discounts on the item subtotal are out of scope for this service.
 */
class CouponShippingService
{
    /**
     * Find the coupon and validate it's usable for this order/customer, but
     * do not persist anything.
     *
     * @throws CouponNotApplicableException
     */
    public function validate(string $code, float $cartAmount, ?Customer $customer = null): Coupon
    {
        $coupon = Coupon::with('promotion.conditions', 'promotion.discountRules')
            ->whereRaw('UPPER(code) = ?', [strtoupper($code)])
            ->first();

        if (! $coupon) {
            throw CouponNotApplicableException::notFound();
        }

        $promotion = $coupon->promotion;

        if ($promotion->status !== PromotionStatus::ACTIVE) {
            throw CouponNotApplicableException::notActive();
        }

        $now = now();
        if (($promotion->starts_at && $now->lt($promotion->starts_at))
            || ($promotion->ends_at && $now->gt($promotion->ends_at))) {
            throw CouponNotApplicableException::outOfSchedule();
        }

        if ($coupon->usage_limit !== null && $coupon->usages()->count() >= $coupon->usage_limit) {
            throw CouponNotApplicableException::usageLimitReached();
        }

        if ($customer && $coupon->usage_limit_per_customer !== null) {
            $customerUsages = $coupon->usages()->where('customer_id', $customer->id)->count();
            if ($customerUsages >= $coupon->usage_limit_per_customer) {
                throw CouponNotApplicableException::customerUsageLimitReached();
            }
        }

        if ($customer && $coupon->customers()->exists() && ! $coupon->customers()->where('customer_id', $customer->id)->exists()) {
            throw CouponNotApplicableException::notAssignedToCustomer();
        }

        if ($coupon->min_order_amount !== null && $cartAmount < (float) $coupon->min_order_amount) {
            throw CouponNotApplicableException::minOrderAmountNotMet((float) $coupon->min_order_amount);
        }

        if (! $this->conditionsMet($promotion->conditions, $cartAmount)) {
            throw CouponNotApplicableException::conditionsNotMet();
        }

        return $coupon;
    }

    /**
     * Compute the shipping discount a coupon grants, given the order's
     * current shipping amount. Returns 0 if the coupon has no free_shipping
     * rule. The result is always clamped to [0, shippingAmount].
     */
    public function calculateShippingDiscount(Coupon $coupon, float $shippingAmount): float
    {
        $rule = $coupon->promotion->discountRules
            ->firstWhere('type', DiscountRuleType::FREE_SHIPPING);

        if (! $rule) {
            return 0.0;
        }

        $cap = $rule->max_discount_amount !== null ? (float) $rule->max_discount_amount : $shippingAmount;

        return max(0.0, min($shippingAmount, $cap));
    }

    /**
     * Validate, apply the coupon's shipping discount to the order, and
     * record a coupon_usages row. Persists changes and calls
     * Order::recalculateTotals() so total_amount/due_amount reflect it.
     *
     * @throws CouponNotApplicableException
     */
    public function applyToOrder(Order $order, string $code): Coupon
    {
        $coupon = $this->validate($code, (float) $order->subtotal, $order->customer);

        $shippingDiscount = $this->calculateShippingDiscount($coupon, (float) $order->shipping_amount);

        $order->coupon_id = $coupon->id;
        $order->coupon_code = $coupon->code;
        $order->shipping_discount = $shippingDiscount;
        $order->save();

        $coupon->usages()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id'     => $order->customer_id,
                'discount_amount' => $shippingDiscount,
                'used_at'         => now(),
            ]
        );

        $order->recalculateTotals();

        return $coupon;
    }

    protected function conditionsMet(iterable $conditions, float $cartAmount): bool
    {
        foreach ($conditions as $condition) {
            /** @var PromotionCondition $condition */
            if ($condition->type !== ConditionType::CART_AMOUNT) {
                // Only cart_amount conditions are evaluated by this service;
                // any other condition type on the coupon is treated as
                // already satisfied to avoid falsely rejecting coupons that
                // combine shipping rules with conditions this service
                // doesn't yet understand (e.g. category/brand targeting).
                continue;
            }

            if (! $this->compare($cartAmount, $condition->operator, (float) $condition->value_decoded)) {
                return false;
            }
        }

        return true;
    }

    protected function compare(float $actual, ConditionOperator $operator, float $expected): bool
    {
        return match ($operator) {
            ConditionOperator::EQUALS => $actual === $expected,
            ConditionOperator::NOT_EQUALS => $actual !== $expected,
            ConditionOperator::GREATER_THAN => $actual > $expected,
            ConditionOperator::GREATER_THAN_OR_EQUAL => $actual >= $expected,
            ConditionOperator::LESS_THAN => $actual < $expected,
            ConditionOperator::LESS_THAN_OR_EQUAL => $actual <= $expected,
            default => true,
        };
    }
}
