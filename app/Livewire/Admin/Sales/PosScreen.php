<?php

namespace App\Livewire\Admin\Sales;

use App\Exceptions\Inventory\InsufficientStockException;
use App\Exceptions\Sales\CouponNotApplicableException;
use App\Models\Combo;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\CouponShippingService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PosScreen extends Component
{
    public ?PosSession $session = null;

    public string $productSearch = '';

    /** @var array<int, array{product_id: string, variant_id: string, label: string, image_url: string|null, quantity: string, unit_price: string, purchase_price: string}> */
    public array $items = [];

    public string $customerId = '';
    public string $couponCode = '';

    public float  $shippingDiscount = 0.0;
    public string $couponError      = '';

    public string $discountAmount = '';

    public string $paymentMethod  = 'cash';
    public string $amountTendered = '';

    // quick customer registration
    public bool   $newCustomerModal    = false;
    public string $newCustomerName     = '';
    public string $newCustomerPhone    = '';
    public string $newCustomerEmail    = '';

    public function mount(): void
    {
        $this->session = PosSession::where('user_id', auth()->id())->open()->latest('id')->first();
    }

    public function addProductItem(int $productId): void
    {
        $product = Product::active()->find($productId);

        if (! $product) {
            return;
        }

        $this->items[] = [
            'product_id'     => (string) $product->id,
            'variant_id'     => '',
            'label'          => $product->name,
            'image_url'      => $product->featured_image_id ? file_path($product->featured_image_id) : null,
            'quantity'       => '1',
            'unit_price'     => (string) ($product->sale_price ?? $product->price ?? 0),
            'purchase_price' => (string) ($product->purchase_price ?? ''),
        ];

        $this->productSearch = '';
    }

    public function selectVariantForItem(int $index, ?int $variantId): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $product = Product::find($this->items[$index]['product_id']);

        if (! $variantId) {
            $this->items[$index]['variant_id'] = '';
            $this->items[$index]['image_url']  = $product?->featured_image_id ? file_path($product->featured_image_id) : null;
            return;
        }

        $variant = ProductVariant::active()->with(['media' => fn ($q) => $q->where('is_primary', true)])->find($variantId);

        if (! $variant) {
            return;
        }

        $variantImage = $variant->media->first();

        $this->items[$index]['variant_id']     = (string) $variant->id;
        $this->items[$index]['unit_price']     = (string) ($variant->sale_price ?? $variant->price ?? $this->items[$index]['unit_price']);
        $this->items[$index]['purchase_price'] = (string) ($variant->purchase_price ?? $this->items[$index]['purchase_price']);
        $this->items[$index]['image_url']      = $variantImage
            ? file_path($variantImage->media_id)
            : ($product?->featured_image_id ? file_path($product->featured_image_id) : null);
    }

    public function incrementQuantity(int $index): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = (string) ((float) $this->items[$index]['quantity'] + 1);
        }
    }

    public function decrementQuantity(int $index): void
    {
        if (isset($this->items[$index])) {
            $qty = (float) $this->items[$index]['quantity'] - 1;
            $this->items[$index]['quantity'] = (string) max(1, $qty);
        }
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Computes the minimum allowed selling price for a purchase price, based
     * on the site-wide minimum margin percentage (Settings > Pricing).
     * Returns null when there's no purchase price to protect against.
     */
    public function minAllowedPrice(float $purchasePrice): ?float
    {
        if ($purchasePrice <= 0) {
            return null;
        }

        $minMarginPercent = (float) Setting::get('min_margin_percent', '15', 'pricing');

        return round($purchasePrice * (1 + $minMarginPercent / 100), 2);
    }

    /**
     * Hard-enforces the minimum margin over purchase price whenever a
     * cashier edits the unit price directly: prices below the minimum are
     * snapped back up to the minimum rather than accepted, and the cashier
     * is told why. The field cannot go below purchase price + margin%.
     */
    public function updatedItems($value, $key): void
    {
        [$index, $field] = explode('.', $key) + [null, null];

        if ($field !== 'unit_price' || $index === null) {
            return;
        }

        $item = $this->items[$index];
        $purchasePrice = (float) ($item['purchase_price'] ?? 0);
        $unitPrice     = (float) ($item['unit_price'] ?? 0);

        $minAllowedPrice = $this->minAllowedPrice($purchasePrice);

        if ($minAllowedPrice !== null && $unitPrice < $minAllowedPrice) {
            $this->items[$index]['unit_price'] = (string) $minAllowedPrice;

            $this->dispatch('toast', [
                'type'    => 'warning',
                'message' => sprintf(
                    'Price cannot go below the minimum margin — set to %.2f (purchase %.2f + %.0f%%).',
                    $minAllowedPrice,
                    $purchasePrice,
                    (float) Setting::get('min_margin_percent', '15', 'pricing')
                ),
            ]);
        }
    }

    public function getItemsSubtotalProperty(): float
    {
        return collect($this->items)->sum(fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0));
    }

    public function getDiscountAmountClampedProperty(): float
    {
        return max(0.0, min((float) ($this->discountAmount ?: 0), $this->itemsSubtotal));
    }

    public function updatedDiscountAmount(): void
    {
        $this->validateOnly('discountAmount', [
            'discountAmount' => ['nullable', 'numeric', 'min:0', 'max:' . $this->itemsSubtotal],
        ], [
            'discountAmount.max' => 'Discount cannot exceed the subtotal (' . number_format($this->itemsSubtotal, 2) . ').',
        ]);
    }

    public function getGrandTotalProperty(): float
    {
        return max(0.0, $this->itemsSubtotal - $this->discountAmountClamped - $this->shippingDiscount);
    }

    public function getChangeDueProperty(): float
    {
        return max(0.0, (float) ($this->amountTendered ?: 0) - $this->grandTotal);
    }

    public function updatedCustomerId(): void
    {
        if ($this->couponCode !== '') {
            $this->applyCoupon();
        }
    }

    public function openNewCustomerModal(): void
    {
        $this->reset(['newCustomerName', 'newCustomerPhone', 'newCustomerEmail']);
        $this->resetValidation();
        $this->newCustomerModal = true;
    }

    public function createCustomer(): void
    {
        $this->validate([
            'newCustomerName'  => 'required|string|max:150',
            'newCustomerPhone' => 'required|string|max:20',
            'newCustomerEmail' => 'nullable|email|max:150',
        ]);

        $code = 'CUS-' . str_pad((string) (Customer::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);

        [$firstName, $lastName] = array_pad(explode(' ', trim($this->newCustomerName), 2), 2, null);

        $customer = Customer::create([
            'customer_code' => $code,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'full_name'     => $this->newCustomerName,
            'phone'         => $this->newCustomerPhone,
            'email'         => $this->newCustomerEmail ?: null,
            'status'        => 'active',
        ]);

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($customer)
            ->event('created')
            ->log("Customer \"{$customer->full_name}\" was added");

        $this->customerId       = (string) $customer->id;
        $this->newCustomerModal = false;

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer registered and selected']);
    }

    public function applyCoupon(): void
    {
        $this->couponError = '';

        if ($this->couponCode === '') {
            $this->shippingDiscount = 0.0;
            return;
        }

        $customer = $this->customerId ? Customer::find($this->customerId) : null;

        try {
            $coupon = app(CouponShippingService::class)->validate($this->couponCode, $this->itemsSubtotal, $customer);

            $this->shippingDiscount = app(CouponShippingService::class)->calculateShippingDiscount($coupon, $this->itemsSubtotal);

            if ($this->shippingDiscount <= 0) {
                $this->couponError = 'This coupon does not offer a discount for this sale.';
            }
        } catch (CouponNotApplicableException $e) {
            $this->shippingDiscount = 0.0;
            $this->couponError = $e->getMessage();
        }
    }

    public function updatedCouponCode(): void
    {
        $this->applyCoupon();
    }

    public function removeCoupon(): void
    {
        $this->couponCode       = '';
        $this->shippingDiscount = 0.0;
        $this->couponError      = '';
    }

    public function completeSale(): void
    {
        if (! $this->session) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'No open session. Please open a session first.']);
            return;
        }

        $this->validate([
            'items'              => 'required|array|min:1',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discountAmount'     => ['nullable', 'numeric', 'min:0', 'max:' . $this->itemsSubtotal],
            'paymentMethod'      => 'required|string|max:50',
            'amountTendered'     => 'required|numeric|min:0',
        ], [
            'discountAmount.max' => 'Discount cannot exceed the subtotal (' . number_format($this->itemsSubtotal, 2) . ').',
        ]);

        foreach ($this->items as $index => $item) {
            $minAllowedPrice = $this->minAllowedPrice((float) ($item['purchase_price'] ?? 0));

            if ($minAllowedPrice !== null && (float) $item['unit_price'] < $minAllowedPrice) {
                $this->addError(
                    "items.{$index}.unit_price",
                    sprintf('Price for "%s" is below the minimum margin (min %.2f).', $item['label'], $minAllowedPrice)
                );
                return;
            }
        }

        if ((float) $this->amountTendered < $this->grandTotal) {
            $this->addError('amountTendered', 'Amount tendered is less than the total due.');
            return;
        }

        $warehouse = $this->session->register?->branch_id
            ? Warehouse::where('branch_id', $this->session->register->branch_id)->first()
            : null;
        $warehouse ??= Warehouse::default();

        $stockService = app(StockService::class);

        // Pre-flight every line before creating anything, so a failed sale
        // doesn't leave a half-built order behind.
        foreach ($this->items as $index => $item) {
            if (! $item['product_id']) {
                continue; // combo line — stock is derived from its components, not tracked directly
            }

            $product = Product::find($item['product_id']);
            $variant = $item['variant_id'] ? ProductVariant::find($item['variant_id']) : null;

            if (! $product) {
                continue;
            }

            $available = $stockService->available($product, $variant, $warehouse);

            if ($available < (float) $item['quantity']) {
                $this->addError(
                    "items.{$index}.quantity",
                    sprintf('Only %s in stock for "%s".', $available, $item['label'])
                );
                return;
            }
        }

        try {
            $order = DB::transaction(function () use ($warehouse, $stockService) {
                $order = Order::create([
                    'customer_id'     => $this->customerId ?: null,
                    'source'          => 'pos',
                    'status'          => 'confirmed',
                    'payment_status'  => 'paid',
                    'fulfillment_status' => 'fulfilled',
                    'discount_amount' => $this->discountAmountClamped,
                    'placed_at'       => now(),
                    'confirmed_at'    => now(),
                ]);

                foreach ($this->items as $item) {
                    $product = Product::find($item['product_id']);
                    $variant = $item['variant_id'] ? ProductVariant::find($item['variant_id']) : null;

                    $quantity  = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];

                    $orderItem = $order->items()->create([
                        'product_id'     => $item['product_id'],
                        'variant_id'     => $item['variant_id'] ?: null,
                        'product_name'   => $product?->name ?? 'Unknown product',
                        'variant_name'   => $variant?->sku,
                        'sku'            => $variant?->sku ?? $product?->code,
                        'quantity'       => $quantity,
                        'unit_price'     => $unitPrice,
                        'purchase_price' => $item['purchase_price'] !== '' ? $item['purchase_price'] : null,
                        'total_amount'   => $unitPrice * $quantity,
                    ]);

                    if ($product) {
                        $stockService->decrease(
                            $product, $variant, $quantity, 'sale',
                            warehouse: $warehouse, reference: $orderItem,
                            note: "POS sale — Order #{$order->id}",
                        );
                    }
                }

                if ($this->couponCode !== '' && $this->couponError === '') {
                    try {
                        app(CouponShippingService::class)->applyToOrder($order, $this->couponCode);
                    } catch (CouponNotApplicableException $e) {
                        $order->recalculateTotals();
                    }
                } else {
                    $order->recalculateTotals();
                }

                $order->payments()->create([
                    'payment_method' => $this->paymentMethod,
                    'amount'         => $order->total_amount,
                    'status'         => 'paid',
                    'paid_at'        => now(),
                ]);
                $order->recalculateTotals();

                PosSale::create([
                    'session_id'  => $this->session->id,
                    'order_id'    => $order->id,
                    'customer_id' => $this->customerId ?: null,
                    'created_by'  => auth()->id(),
                ]);

                if ($this->paymentMethod === 'cash') {
                    $this->session->cashMovements()->create([
                        'type'       => 'cash_in',
                        'amount'     => $order->total_amount,
                        'reason'     => "POS sale — Order #{$order->id}",
                        'created_by' => auth()->id(),
                    ]);
                }

                return $order;
            });
        } catch (InsufficientStockException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('created')
            ->log("POS sale — Order #{$order->id} completed on session #{$this->session->id}");

        $this->dispatch('toast', ['type' => 'success', 'message' => "Sale completed — Order #{$order->id}"]);

        $this->reset(['items', 'customerId', 'couponCode', 'shippingDiscount', 'couponError', 'discountAmount', 'amountTendered', 'productSearch']);
        $this->paymentMethod = 'cash';
    }

    public function render(): mixed
    {
        $productOptions = collect();
        if ($this->productSearch !== '') {
            $productOptions = Product::active()
                ->where('product_type', '!=', 'combo')
                ->where(fn ($q) => $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('code', 'like', "%{$this->productSearch}%"))
                ->limit(20)
                ->get();
        } else {
            $productOptions = Product::active()->where('product_type', '!=', 'combo')->orderByDesc('id')->limit(20)->get();
        }

        $customers = Customer::orderBy('full_name')->get(['id', 'full_name', 'phone']);
        $customerOptions = $customers->mapWithKeys(fn ($c) => [$c->id => $c->full_name . ($c->phone ? " ({$c->phone})" : '')]);

        $coupons = Coupon::whereHas('promotion', fn ($q) => $q->where('status', 'active'))->orderBy('code')->get(['id', 'code']);
        $couponOptions = $coupons->mapWithKeys(fn ($c) => [$c->code => $c->code]);

        $selectedCustomer = $this->customerId ? Customer::find($this->customerId) : null;

        $productIds = collect($this->items)->pluck('product_id')->filter()->unique();
        $variantOptions = ProductVariant::active()
            ->whereIn('product_id', $productIds)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('product_id');

        return view('livewire.admin.sales.pos-screen', [
            'productOptions'   => $productOptions,
            'customerOptions'  => $customerOptions,
            'couponOptions'    => $couponOptions,
            'selectedCustomer' => $selectedCustomer,
            'variantOptions'   => $variantOptions,
        ])->layout('layouts.admin.admin');
    }
}
