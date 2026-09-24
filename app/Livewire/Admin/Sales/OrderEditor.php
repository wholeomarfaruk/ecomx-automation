<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Exceptions\Sales\CouponNotApplicableException;
use App\Livewire\Concerns\QuickAddsCustomer;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\CouponShippingService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Editing an existing order from its detail page (/admin/sales/orders/{id}):
 * items (add/remove, variant, quantity, unit price), charges & adjustments
 * (shipping, discount, tax, named extra charges), customer & addresses, and
 * notes — each in its own modal. Embedded in OrderDetail, which re-renders
 * on the 'order-updated' event this dispatches after every save.
 *
 * Rules:
 *  - Items, charges and customer can only change while the order is
 *    pending/confirmed/processing and no sale has been posted yet
 *    (isEditable()); after that, changes go through returns/refunds.
 *  - A line that's been packed, delivered or returned is locked (itemLocked()).
 *  - On a booked order (confirmed + book_on_order_confirm), editing a line's
 *    quantity/variant releases its old booking and books the new one;
 *    removing releases it; adding books it — all in one transaction, so an
 *    InsufficientStockException rolls the whole edit back.
 *  - Changing a line resets its line discount (e.g. a storefront offer
 *    discount), since that was calculated for the original line.
 *  - Source and notes are informational, so they can be edited at any status.
 */
class OrderEditor extends Component
{
    use QuickAddsCustomer;

    public int $orderId;

    // Items
    public bool $itemsModal = false;
    /** @var array<int, array{id: ?int, kind: string, product_id: string, variant_id: string, combo_id: string, is_gift: bool, label: string, quantity: string, unit_price: string, purchase_price: string, locked: bool}> */
    public array $editItems = [];
    /** Bound to the "add product" searchable-select; picking a product adds a line and resets. */
    public string $productPickerId = '';

    // Charges & adjustments
    public bool $chargesModal = false;
    public string $shippingAmount = '0';
    public string $discountType = 'fixed';
    public string $discountValue = '0';
    public string $taxAmount = '0';
    /** @var array<int, array{label: string, amount: string}> */
    public array $charges = [];
    /** Coupon (shipping-discount coupons — CouponShippingService), applied/removed immediately. */
    public string $couponCode = '';

    // Customer & addresses
    public bool $customerModal = false;
    public string $customerId = '';
    public string $billingAddressId = '';
    public string $shippingAddressId = '';

    // Source & notes (informational — editable at any status)
    public bool $notesModal = false;
    public string $source = '';
    public string $customerNote = '';
    public string $adminNote = '';

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    protected function order(): Order
    {
        return Order::with('items.batchAllocations', 'items.product', 'items.variant', 'charges', 'customer')->findOrFail($this->orderId);
    }

    /** Items/charges/customer are editable only before shipping and before any sale is posted. */
    public static function isEditable(Order $order): bool
    {
        return in_array($order->status, [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PROCESSING], true)
            && ! JournalEntry::query()
                ->where('source_type', Order::class)
                ->where('source_id', $order->id)
                ->where('purpose', 'like', 'sale%')
                ->exists();
    }

    /** A line that's been packed from a batch, delivered or returned can't change. */
    protected function itemLocked(OrderItem $item): bool
    {
        return $item->batchAllocations->isNotEmpty()
            || (float) $item->delivered_quantity > 0
            || (float) $item->returned_quantity > 0;
    }

    protected function ensureEditable(Order $order): bool
    {
        if (static::isEditable($order)) {
            return true;
        }

        $this->dispatch('toast', ['type' => 'error', 'message' => 'This order can no longer be edited — use returns/refunds instead.']);

        return false;
    }

    protected function saved(Order $order, string $message, array $changes): void
    {
        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->withProperties(['changes' => $changes])
            ->log("Order #{$order->id}: {$message}");

        $this->dispatch('order-updated');
        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    // ── Items ────────────────────────────────────────────────────────────

    public function openItemsModal(): void
    {
        $order = $this->order();

        if (! $this->ensureEditable($order)) {
            return;
        }

        $this->editItems = $order->items->map(fn (OrderItem $item) => [
            'id'             => $item->id,
            'kind'           => $item->combo_id ? 'combo' : 'product',
            'product_id'     => (string) ($item->product_id ?? ''),
            'variant_id'     => (string) ($item->variant_id ?? ''),
            'combo_id'       => (string) ($item->combo_id ?? ''),
            'is_gift'        => (bool) $item->is_gift,
            'label'          => $item->product_name,
            'quantity'       => rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.'),
            'unit_price'     => (string) (float) $item->unit_price,
            'purchase_price' => $item->purchase_price !== null ? (string) (float) $item->purchase_price : '',
            'locked'         => $this->itemLocked($item),
        ])->values()->all();

        $this->resetValidation();
        $this->itemsModal = true;
    }

    public function updatedProductPickerId(string $value): void
    {
        if ($value !== '') {
            $product = Product::active()->find((int) $value);

            if ($product) {
                $this->editItems[] = [
                    'id'             => null,
                    'kind'           => 'product',
                    'product_id'     => (string) $product->id,
                    'variant_id'     => '',
                    'combo_id'       => '',
                    'is_gift'        => false,
                    'label'          => $product->name,
                    'quantity'       => '1',
                    'unit_price'     => (string) $product->sellingPrice(),
                    'purchase_price' => $product->purchase_price !== null ? (string) (float) $product->purchase_price : '',
                    'locked'         => false,
                ];
            }
        }

        $this->productPickerId = '';
    }

    public function selectVariantForLine(int $index, ?string $variantId): void
    {
        $line = $this->editItems[$index] ?? null;

        if (! $line || $line['locked'] || $line['kind'] !== 'product') {
            return;
        }

        $this->editItems[$index]['variant_id'] = (string) ($variantId ?? '');

        $variant = $variantId ? ProductVariant::active()->where('product_id', $line['product_id'])->find((int) $variantId) : null;
        $product = Product::find($line['product_id']);

        if ($product && ! $line['is_gift']) {
            $this->editItems[$index]['unit_price'] = (string) $product->sellingPrice($variant);
        }

        if ($variant?->purchase_price !== null) {
            $this->editItems[$index]['purchase_price'] = (string) (float) $variant->purchase_price;
        }
    }

    public function removeLine(int $index): void
    {
        if (! isset($this->editItems[$index]) || $this->editItems[$index]['locked']) {
            return;
        }

        unset($this->editItems[$index]);
        $this->editItems = array_values($this->editItems);
    }

    public function saveItems(): void
    {
        $this->validate([
            'editItems'                => 'required|array|min:1',
            'editItems.*.quantity'     => 'required|numeric|min:0.001',
            'editItems.*.unit_price'   => 'required|numeric|min:0',
            'editItems.*.purchase_price' => 'nullable|numeric|min:0',
        ], [
            'editItems.required' => 'An order needs at least one item.',
            'editItems.min'      => 'An order needs at least one item.',
        ], [
            'editItems.*.quantity'   => 'quantity',
            'editItems.*.unit_price' => 'unit price',
        ]);

        $order = $this->order();

        if (! $this->ensureEditable($order)) {
            return;
        }

        $stock = app(StockService::class);
        $booked = $order->status->isBookable()
            && (bool) Setting::get('book_on_order_confirm', true, 'inventory')
            && $stock->isOrderBooked($order);

        $existing = $order->items->keyBy('id');
        $changes = [];

        try {
            DB::transaction(function () use ($order, $stock, $booked, $existing, &$changes) {
                $keptIds = [];
                $offerDiscountReset = false;

                foreach ($this->editItems as $line) {
                    $quantity = (float) $line['quantity'];
                    $unitPrice = $line['is_gift'] ? 0.0 : (float) $line['unit_price'];
                    $variantId = $line['variant_id'] !== '' ? (int) $line['variant_id'] : null;
                    $purchasePrice = $line['purchase_price'] !== '' ? (float) $line['purchase_price'] : null;

                    if ($line['id']) {
                        /** @var OrderItem|null $item */
                        $item = $existing->get($line['id']);

                        if (! $item) {
                            continue;
                        }

                        $keptIds[] = $item->id;

                        if ($this->itemLocked($item)) {
                            continue;
                        }

                        $stockChanged = abs((float) $item->quantity - $quantity) > 0.0001 || (int) $item->variant_id !== (int) $variantId;
                        $priceChanged = abs((float) $item->unit_price - $unitPrice) > 0.0001
                            || (float) ($item->purchase_price ?? -1) !== (float) ($purchasePrice ?? -1);

                        if (! $stockChanged && ! $priceChanged) {
                            continue;
                        }

                        $before = ['qty' => (float) $item->quantity, 'price' => (float) $item->unit_price, 'variant' => $item->variant?->sku];

                        if ($booked && $stockChanged) {
                            $stock->releaseItemBooking($item, "Order #{$order->id} item edited");
                        }

                        $offerDiscountReset = $offerDiscountReset || (float) $item->discount_amount > 0;

                        $item->update($this->lineAttributes($item->product_id, $variantId, $item->combo_id, $quantity, $unitPrice, $purchasePrice, (bool) $item->is_gift, $item->product_name));

                        if ($booked && $stockChanged) {
                            $stock->bookItem($item->fresh(['product', 'variant']), "Order #{$order->id} item edited");
                        }

                        $changes[] = ['item' => $item->product_name, 'before' => $before, 'after' => ['qty' => $quantity, 'price' => $unitPrice, 'variant' => $item->fresh('variant')->variant?->sku]];

                        continue;
                    }

                    $product = Product::find($line['product_id']);

                    if (! $product) {
                        continue;
                    }

                    $item = $order->items()->create(
                        ['product_id' => $product->id]
                        + $this->lineAttributes($product->id, $variantId, null, $quantity, $unitPrice, $purchasePrice, false, $product->name)
                    );

                    if ($booked) {
                        $stock->bookItem($item->fresh(['product', 'variant']), "Order #{$order->id} item added");
                    }

                    $changes[] = ['item' => $product->name, 'added' => ['qty' => $quantity, 'price' => $unitPrice]];
                }

                foreach ($existing as $id => $item) {
                    if (in_array($id, $keptIds, true) || $this->itemLocked($item)) {
                        continue;
                    }

                    if ($booked) {
                        $stock->releaseItemBooking($item, "Order #{$order->id} item removed");
                    }

                    $offerDiscountReset = $offerDiscountReset || (float) $item->discount_amount > 0;
                    $changes[] = ['item' => $item->product_name, 'removed' => ['qty' => (float) $item->quantity, 'price' => (float) $item->unit_price]];
                    $item->delete();
                }

                // Storefront offer line discounts were dropped with the lines
                // they belonged to — keep only the free-delivery part of the
                // recorded offers, so "Offer applied" rows don't show savings
                // the order no longer has.
                if ($offerDiscountReset) {
                    $order->offers()->where('shipping_discount', '<=', 0)->delete();
                    $order->offers()->update(['discount_amount' => 0]);
                }

                $order->recalculateTotals();

                // A discount larger than the (now smaller) items subtotal would
                // make the total — and the sale posted at completion — negative.
                if ((float) $order->discount_amount > (float) $order->subtotal) {
                    throw new \DomainException('The order discount (' . number_format((float) $order->discount_amount, 2) . ') is more than the new items subtotal (' . number_format((float) $order->subtotal, 2) . ') — lower it under Charges & Discount first.');
                }
            });
        } catch (InsufficientStockException|\DomainException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->itemsModal = false;
        $this->saved($order, 'items updated', $changes);
    }

    /** Snapshot columns for an order line, same shape OrderCreate::save() writes. Line discount resets on any change. */
    protected function lineAttributes(?int $productId, ?int $variantId, ?int $comboId, float $quantity, float $unitPrice, ?float $purchasePrice, bool $isGift, string $fallbackName): array
    {
        $product = $productId ? Product::find($productId) : null;
        // Only a variant of this line's own product.
        $variant = $variantId && $productId ? ProductVariant::where('product_id', $productId)->find($variantId) : null;

        return [
            'variant_id'      => $variant?->id,
            'product_name'    => $comboId ? $fallbackName : ($product?->name ?? $fallbackName),
            'variant_name'    => $variant?->sku,
            'sku'             => $variant?->sku ?? $product?->code,
            'quantity'        => $quantity,
            'unit_price'      => $unitPrice,
            'purchase_price'  => $purchasePrice,
            'discount_amount' => 0,
            'total_amount'    => $isGift ? 0 : round($unitPrice * $quantity, 2),
        ];
    }

    // ── Charges & adjustments ────────────────────────────────────────────

    public function openChargesModal(): void
    {
        $order = $this->order();

        if (! $this->ensureEditable($order)) {
            return;
        }

        $this->shippingAmount = (string) (float) $order->shipping_amount;
        $this->discountType = 'fixed';
        $this->discountValue = (string) (float) $order->discount_amount;
        $this->taxAmount = (string) (float) $order->tax_amount;
        $this->charges = $order->charges->map(fn ($c) => ['label' => $c->label, 'amount' => (string) (float) $c->amount])->values()->all();
        $this->couponCode = $order->coupon_code ?? '';

        $this->resetValidation();
        $this->chargesModal = true;
    }

    public function addChargeRow(): void
    {
        $this->charges[] = ['label' => '', 'amount' => ''];
    }

    public function removeChargeRow(int $index): void
    {
        unset($this->charges[$index]);
        $this->charges = array_values($this->charges);
    }

    public function saveCharges(): void
    {
        $this->validate([
            'shippingAmount'     => 'required|numeric|min:0',
            'discountType'       => 'required|in:fixed,percent',
            'discountValue'      => 'required|numeric|min:0' . ($this->discountType === 'percent' ? '|max:100' : ''),
            'taxAmount'          => 'required|numeric|min:0',
            'charges'            => 'array',
            'charges.*.label'    => 'required|string|max:100',
            'charges.*.amount'   => 'required|numeric|min:0.01',
        ], [], [
            'charges.*.label'  => 'charge name',
            'charges.*.amount' => 'charge amount',
        ]);

        $order = $this->order();

        if (! $this->ensureEditable($order)) {
            return;
        }

        $itemsSubtotal = (float) $order->items->sum(fn (OrderItem $item) => $item->is_gift ? 0 : (float) $item->total_amount);

        $discount = $this->discountType === 'percent'
            ? round($itemsSubtotal * (float) $this->discountValue / 100, 2)
            : round((float) $this->discountValue, 2);

        if ($discount > $itemsSubtotal) {
            $this->addError('discountValue', 'Discount can\'t be more than the items subtotal (' . number_format($itemsSubtotal, 2) . ').');

            return;
        }

        $before = [
            'shipping' => (float) $order->shipping_amount,
            'discount' => (float) $order->discount_amount,
            'tax'      => (float) $order->tax_amount,
            'charges'  => $order->charges->map(fn ($c) => [$c->label => (float) $c->amount])->all(),
        ];

        DB::transaction(function () use ($order, $discount) {
            $order->update([
                'shipping_amount' => (float) $this->shippingAmount,
                'discount_amount' => $discount,
                'tax_amount'      => (float) $this->taxAmount,
            ]);

            $order->charges()->delete();

            foreach ($this->charges as $charge) {
                $order->charges()->create([
                    'label'  => trim($charge['label']),
                    'amount' => round((float) $charge['amount'], 2),
                ]);
            }

            $order->recalculateTotals();
        });

        // Shipping may have changed — re-size (or drop) an applied coupon.
        // (One toast at a time, so a dropped coupon goes in the saved message.)
        $message = 'charges updated';

        if ($order->coupon_code) {
            $warning = $this->syncCoupon($order->fresh(), $order->coupon_code);

            if ($warning !== null) {
                $message .= " — coupon removed: {$warning}";
            }
        }

        $this->chargesModal = false;
        $this->saved($order, $message, [
            'before' => $before,
            'after'  => [
                'shipping' => (float) $this->shippingAmount,
                'discount' => $discount,
                'tax'      => (float) $this->taxAmount,
                'charges'  => collect($this->charges)->map(fn ($c) => [trim($c['label']) => (float) $c['amount']])->all(),
            ],
        ]);
    }

    public function applyCoupon(): void
    {
        $this->validate(['couponCode' => 'required|string|max:100'], [], ['couponCode' => 'coupon code']);

        $order = $this->order();

        if (! $this->ensureEditable($order)) {
            return;
        }

        $error = $this->syncCoupon($order, trim($this->couponCode));

        if ($error !== null) {
            $this->addError('couponCode', $error);

            return;
        }

        $order->refresh();
        $this->couponCode = $order->coupon_code ?? '';
        $this->saved($order, "coupon {$order->coupon_code} applied", ['coupon' => $order->coupon_code, 'shipping_discount' => (float) $order->shipping_discount]);
    }

    public function removeCoupon(): void
    {
        $order = $this->order();

        if (! $this->ensureEditable($order) || ! $order->coupon_code) {
            return;
        }

        $code = $order->coupon_code;
        $this->clearCoupon($order);
        $this->couponCode = '';

        $this->saved($order, "coupon {$code} removed", ['coupon' => $code]);
    }

    /**
     * Applies $code to the order's (saved) shipping charge, on top of any
     * free-delivery offer discount it already has, and records its usage.
     * Returns an error message instead (and removes the coupon) when it
     * doesn't apply.
     */
    protected function syncCoupon(Order $order, string $code): ?string
    {
        $service = app(CouponShippingService::class);

        try {
            $coupon = $service->validate($code, (float) $order->subtotal, $order->customer);
        } catch (CouponNotApplicableException $e) {
            $this->clearCoupon($order);

            return $e->getMessage();
        }

        $offersShipping = (float) $order->offers()->sum('shipping_discount');
        $couponDiscount = $service->calculateShippingDiscount($coupon, max(0.0, (float) $order->shipping_amount - $offersShipping));

        if ($couponDiscount <= 0) {
            $this->clearCoupon($order);

            return "This coupon gives no discount on this order's shipping charge.";
        }

        DB::transaction(function () use ($order, $coupon, $offersShipping, $couponDiscount) {
            CouponUsage::where('order_id', $order->id)->where('coupon_id', '!=', $coupon->id)->delete();

            $order->update([
                'coupon_id'         => $coupon->id,
                'coupon_code'       => $coupon->code,
                'shipping_discount' => $offersShipping + $couponDiscount,
            ]);

            $coupon->usages()->updateOrCreate(
                ['order_id' => $order->id],
                ['customer_id' => $order->customer_id, 'discount_amount' => $couponDiscount, 'used_at' => now()]
            );

            $order->recalculateTotals();
        });

        return null;
    }

    /** Drops the coupon and its usage; keeps any free-delivery offer discount. */
    protected function clearCoupon(Order $order): void
    {
        DB::transaction(function () use ($order) {
            CouponUsage::where('order_id', $order->id)->delete();

            $order->update([
                'coupon_id'         => null,
                'coupon_code'       => null,
                'shipping_discount' => (float) $order->offers()->sum('shipping_discount'),
            ]);

            $order->recalculateTotals();
        });
    }

    // ── Customer & addresses ─────────────────────────────────────────────

    public function openCustomerModal(): void
    {
        $order = $this->order();

        if (! $this->ensureEditable($order)) {
            return;
        }

        $this->customerId = (string) ($order->customer_id ?? '');
        $this->billingAddressId = (string) ($order->billing_address_id ?? '');
        $this->shippingAddressId = (string) ($order->shipping_address_id ?? '');

        $this->resetValidation();
        $this->customerModal = true;
    }

    public function updatedCustomerId(): void
    {
        // Default to the new customer's default addresses (if any).
        $addresses = $this->customerId ? DeliveryAddress::where('customer_id', $this->customerId)->get() : collect();

        $this->billingAddressId = (string) ($addresses->firstWhere('is_default_billing', true)?->id ?? $addresses->first()?->id ?? '');
        $this->shippingAddressId = (string) ($addresses->firstWhere('is_default_shipping', true)?->id ?? $addresses->first()?->id ?? '');
    }

    /** QuickAddsCustomer hook: select the new customer and their address. */
    protected function customerQuickAdded(Customer $customer, DeliveryAddress $address): void
    {
        $this->customerId = (string) $customer->id;
        $this->billingAddressId = (string) $address->id;
        $this->shippingAddressId = (string) $address->id;
    }

    public function saveCustomer(): void
    {
        $this->validate([
            'customerId'        => 'nullable|integer|exists:customers,id',
            'billingAddressId'  => 'nullable|integer|exists:delivery_addresses,id',
            'shippingAddressId' => 'nullable|integer|exists:delivery_addresses,id',
        ]);

        $order = $this->order();

        if (! $this->ensureEditable($order)) {
            return;
        }

        // Addresses must belong to the chosen customer.
        foreach (['billingAddressId', 'shippingAddressId'] as $field) {
            if ($this->{$field} !== '' && ! DeliveryAddress::whereKey($this->{$field})->where('customer_id', $this->customerId ?: 0)->exists()) {
                $this->addError($field, 'Pick an address belonging to this customer.');

                return;
            }
        }

        // A payment already recorded against this order was posted as an
        // advance for the current customer — moving it to someone else
        // would leave that advance on the wrong customer's account.
        if ((int) $order->customer_id !== (int) $this->customerId && (float) $order->paid_amount > 0) {
            $this->addError('customerId', 'This order already has a payment recorded for its customer — it can\'t be moved to another customer.');

            return;
        }

        $before = [
            'customer' => $order->customer?->full_name,
            'billing_address_id' => $order->billing_address_id,
            'shipping_address_id' => $order->shipping_address_id,
        ];

        $order->update([
            'customer_id'         => $this->customerId ?: null,
            'billing_address_id'  => $this->billingAddressId ?: null,
            'shipping_address_id' => $this->shippingAddressId ?: null,
        ]);

        $this->customerModal = false;
        $this->saved($order, 'customer & address updated', [
            'before' => $before,
            'after'  => [
                'customer' => $order->fresh('customer')->customer?->full_name,
                'billing_address_id' => $order->billing_address_id,
                'shipping_address_id' => $order->shipping_address_id,
            ],
        ]);
    }

    // ── Notes ────────────────────────────────────────────────────────────

    public function openNotesModal(): void
    {
        $order = Order::findOrFail($this->orderId);

        $this->source = $order->source->value;
        $this->customerNote = $order->customer_note ?? '';
        $this->adminNote = $order->admin_note ?? '';

        $this->resetValidation();
        $this->notesModal = true;
    }

    public function saveNotes(): void
    {
        $this->validate([
            'source'       => ['required', \Illuminate\Validation\Rule::enum(OrderSource::class)],
            'customerNote' => 'nullable|string|max:5000',
            'adminNote'    => 'nullable|string|max:5000',
        ]);

        $order = Order::findOrFail($this->orderId);
        $before = ['source' => $order->source->value, 'customer_note' => $order->customer_note, 'admin_note' => $order->admin_note];

        $order->update([
            'source'        => $this->source,
            'customer_note' => trim($this->customerNote) ?: null,
            'admin_note'    => trim($this->adminNote) ?: null,
        ]);

        $this->notesModal = false;
        $this->saved($order, 'source & notes updated', ['before' => $before, 'after' => ['source' => $order->source->value, 'customer_note' => $order->customer_note, 'admin_note' => $order->admin_note]]);
    }

    public function render(): mixed
    {
        $order = $this->order();
        $editable = static::isEditable($order);

        $productOptions = collect();
        $productImages = collect();
        $variantOptions = collect();

        if ($this->itemsModal) {
            $products = Product::active()
                ->where('product_type', '!=', 'combo')
                ->with('featuredImage.items')
                ->get(['id', 'name', 'code', 'featured_image_id']);

            $productOptions = $products->mapWithKeys(fn ($p) => [$p->id => $p->code ? "{$p->name} ({$p->code})" : $p->name]);
            $productImages = $products->mapWithKeys(function ($p) {
                // getRelation(): $p->featuredImage resolves to the getFeaturedImageAttribute() URL string, not the relation.
                $items = $p->getRelation('featuredImage')?->items;
                $item = $items?->firstWhere('type', 'thumbnail') ?? $items?->firstWhere('type', 'original');

                return [$p->id => $item ? asset('storage/' . $item->path) : null];
            });

            $productIds = collect($this->editItems)->where('kind', 'product')->pluck('product_id')->filter()->unique();
            $variantOptions = ProductVariant::active()->whereIn('product_id', $productIds)->orderBy('sort_order')->get()->groupBy('product_id');
        }

        $customerOptions = $this->customerModal
            ? Customer::orderBy('full_name')->get(['id', 'full_name', 'phone'])
                ->mapWithKeys(fn ($c) => [$c->id => $c->phone ? "{$c->full_name} — {$c->phone}" : $c->full_name])
            : collect();

        $customerAddresses = $this->customerModal && $this->customerId
            ? DeliveryAddress::where('customer_id', $this->customerId)->get()
            : collect();

        $editItemsTotal = collect($this->editItems)->sum(fn ($l) => $l['is_gift'] ? 0 : (float) $l['quantity'] * (float) $l['unit_price']);

        return view('livewire.admin.sales.order-editor', [
            'order'             => $order,
            'editable'          => $editable,
            'productOptions'    => $productOptions,
            'productImages'     => $productImages,
            'variantOptions'    => $variantOptions,
            'customerOptions'   => $customerOptions,
            'customerAddresses' => $customerAddresses,
            'editItemsTotal'    => $editItemsTotal,
            'sources'           => OrderSource::cases(),
        ]);
    }
}
