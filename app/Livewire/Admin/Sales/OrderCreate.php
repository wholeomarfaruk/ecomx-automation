<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Exceptions\Sales\CouponNotApplicableException;
use App\Models\Combo;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\CouponShippingService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OrderCreate extends Component
{
    public string $customerId  = '';
    public string $customerSearch = '';

    public string $billingAddressId  = '';
    public string $shippingAddressId = '';

    public string $source             = 'admin';
    public string $status             = 'pending';
    public string $paymentStatus      = 'pending';
    public string $fulfillmentStatus  = 'unfulfilled';

    public string $discountAmount = '0';
    public string $shippingAmount = '0';
    public string $taxAmount      = '0';

    public string $couponCode      = '';
    public float  $shippingDiscount = 0.0;
    public string $couponError     = '';

    public string $customerNote = '';
    public string $adminNote    = '';

    /** @var array<int, array{kind: string, product_id: string, variant_id: string, combo_id: string, is_gift: bool, label: string, quantity: string, unit_price: string, purchase_price: string}> */
    public array $items = [];

    public string $productSearch = '';
    public string $comboSearch   = '';

    public function updatedCustomerId(): void
    {
        $this->billingAddressId  = '';
        $this->shippingAddressId = '';
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId     = (string) $id;
        $this->customerSearch = '';
        $this->billingAddressId  = '';
        $this->shippingAddressId = '';
    }

    public function clearCustomer(): void
    {
        $this->customerId = '';
        $this->billingAddressId  = '';
        $this->shippingAddressId = '';
    }

    public function addProductItem(int $productId): void
    {
        $product = Product::active()->find($productId);

        if (! $product) {
            return;
        }

        $this->items[] = [
            'kind'           => 'product',
            'product_id'     => (string) $product->id,
            'variant_id'     => '',
            'combo_id'       => '',
            'is_gift'        => false,
            'label'          => $product->name,
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

        if (! $variantId) {
            $this->items[$index]['variant_id'] = '';
            return;
        }

        $variant = ProductVariant::active()->find($variantId);

        if (! $variant) {
            return;
        }

        $this->items[$index]['variant_id']     = (string) $variant->id;
        $this->items[$index]['unit_price']     = (string) ($variant->sale_price ?? $variant->price ?? $this->items[$index]['unit_price']);
        $this->items[$index]['purchase_price'] = (string) ($variant->purchase_price ?? $this->items[$index]['purchase_price']);
    }

    public function addComboItem(int $comboId): void
    {
        $combo = Combo::find($comboId);

        if (! $combo) {
            return;
        }

        $this->items[] = [
            'kind'           => 'combo',
            'product_id'     => '',
            'variant_id'     => '',
            'combo_id'       => (string) $combo->id,
            'is_gift'        => false,
            'label'          => $combo->name ?? "Custom Combo #{$combo->id}",
            'quantity'       => '1',
            'unit_price'     => (string) $combo->price,
            'purchase_price' => '',
        ];

        $this->comboSearch = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getItemsSubtotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            if ($item['is_gift']) {
                return 0;
            }

            return (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        });
    }

    public function getGrandTotalProperty(): float
    {
        $netShipping = max(0.0, (float) ($this->shippingAmount ?: 0) - $this->shippingDiscount);

        return $this->itemsSubtotal
            - (float) ($this->discountAmount ?: 0)
            + $netShipping
            + (float) ($this->taxAmount ?: 0);
    }

    public function updatedShippingAmount(): void
    {
        if ($this->couponCode !== '' && $this->shippingDiscount > 0) {
            $this->applyCoupon();
        }
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
            $coupon = app(CouponShippingService::class)->validate(
                $this->couponCode,
                $this->itemsSubtotal,
                $customer
            );

            $this->shippingDiscount = app(CouponShippingService::class)
                ->calculateShippingDiscount($coupon, (float) ($this->shippingAmount ?: 0));

            if ($this->shippingDiscount <= 0) {
                $this->couponError = 'This coupon does not offer a shipping discount.';
            }
        } catch (CouponNotApplicableException $e) {
            $this->shippingDiscount = 0.0;
            $this->couponError = $e->getMessage();
        }
    }

    public function removeCoupon(): void
    {
        $this->couponCode       = '';
        $this->shippingDiscount = 0.0;
        $this->couponError      = '';
    }

    protected function rules(): array
    {
        return [
            'customerId'          => 'nullable|integer|exists:customers,id',
            'billingAddressId'    => 'nullable|integer|exists:delivery_addresses,id',
            'shippingAddressId'   => 'nullable|integer|exists:delivery_addresses,id',
            'source'              => 'required|in:website,messenger,whatsapp,pos,admin,api',
            'status'              => 'required|in:pending,confirmed,processing,shipped,delivered,partially_delivered,cancelled,returned,partially_returned,refunded',
            'paymentStatus'       => 'required|in:pending,partial,paid,failed,refunded',
            'fulfillmentStatus'   => 'required|in:unfulfilled,partial,fulfilled',
            'discountAmount'      => 'nullable|numeric|min:0',
            'shippingAmount'      => 'nullable|numeric|min:0',
            'taxAmount'           => 'nullable|numeric|min:0',
            'items'               => 'required|array|min:1',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ];
    }

    public function save(): void
    {
        $this->validate();

        try {
            $order = DB::transaction(function () {
                $order = Order::create([
                    'customer_id'         => $this->customerId ?: null,
                    'source'              => $this->source,
                    'status'              => $this->status,
                    'payment_status'      => $this->paymentStatus,
                    'fulfillment_status'  => $this->fulfillmentStatus,
                    'discount_amount'     => $this->discountAmount ?: 0,
                    'shipping_amount'     => $this->shippingAmount ?: 0,
                    'tax_amount'          => $this->taxAmount ?: 0,
                    'customer_note'       => $this->customerNote ?: null,
                    'admin_note'          => $this->adminNote ?: null,
                    'billing_address_id'  => $this->billingAddressId ?: null,
                    'shipping_address_id' => $this->shippingAddressId ?: null,
                    'placed_at'           => now(),
                    'confirmed_at'        => $this->status === 'confirmed' ? now() : null,
                ]);

                foreach ($this->items as $item) {
                    $productName  = null;
                    $variantName  = null;
                    $sku          = null;

                    if ($item['kind'] === 'combo') {
                        $combo = Combo::find($item['combo_id']);
                        $productName = $combo?->name ?? "Custom Combo #{$item['combo_id']}";
                    } else {
                        $product = Product::find($item['product_id']);
                        $productName = $product?->name ?? 'Unknown product';
                        $sku = $product?->code;

                        if ($item['variant_id']) {
                            $variant = ProductVariant::find($item['variant_id']);
                            $variantName = $variant?->sku;
                            $sku = $variant?->sku ?? $sku;
                        }
                    }

                    $quantity  = (float) $item['quantity'];
                    $unitPrice = $item['is_gift'] ? 0 : (float) $item['unit_price'];

                    $order->items()->create([
                        'product_id'     => $item['product_id'] ?: null,
                        'variant_id'     => $item['variant_id'] ?: null,
                        'combo_id'       => $item['combo_id'] ?: null,
                        'is_gift'        => $item['is_gift'],
                        'product_name'   => $productName,
                        'variant_name'   => $variantName,
                        'sku'            => $sku,
                        'quantity'       => $quantity,
                        'unit_price'     => $unitPrice,
                        'purchase_price' => $item['purchase_price'] !== '' ? $item['purchase_price'] : null,
                        'total_amount'   => $unitPrice * $quantity,
                    ]);
                }

                if ($this->couponCode !== '' && $this->couponError === '') {
                    try {
                        app(CouponShippingService::class)->applyToOrder($order, $this->couponCode);
                    } catch (CouponNotApplicableException $e) {
                        // Coupon became invalid between preview and save (e.g. usage
                        // limit reached by a concurrent order) — proceed without it
                        // rather than losing the order the admin just built.
                        $order->recalculateTotals();
                    }
                } else {
                    $order->recalculateTotals();
                }

                $deductOnConfirm = (bool) Setting::get('deduct_on_order_confirm', true, 'inventory');

                if ($deductOnConfirm && $this->status === 'confirmed') {
                    $order->load('items');
                    app(StockService::class)->commitOrder($order);
                }

                return $order;
            });
        } catch (InsufficientStockException $e) {
            $this->addError('items', $e->getMessage());
            return;
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('created')
            ->log("Order #{$order->id} was created");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Order created successfully']);

        $this->redirect(route('admin.sales.orders.show', $order->id), navigate: true);
    }

    public function render(): mixed
    {
        $customerOptions = collect();
        if ($this->customerSearch !== '') {
            $customerOptions = Customer::where(fn ($q) => $q
                ->where('full_name', 'like', "%{$this->customerSearch}%")
                ->orWhere('phone', 'like', "%{$this->customerSearch}%"))
                ->limit(10)
                ->get();
        }

        $selectedCustomer = $this->customerId ? Customer::find($this->customerId) : null;

        $customerAddresses = $selectedCustomer
            ? DeliveryAddress::where('customer_id', $selectedCustomer->id)->get()
            : collect();

        $productOptions = collect();
        if ($this->productSearch !== '') {
            $productOptions = Product::active()
                ->where('product_type', '!=', 'combo')
                ->where(fn ($q) => $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('code', 'like', "%{$this->productSearch}%"))
                ->limit(10)
                ->get();
        }

        $comboOptions = collect();
        if ($this->comboSearch !== '') {
            $comboOptions = Combo::where('name', 'like', "%{$this->comboSearch}%")
                ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$this->comboSearch}%"))
                ->limit(10)
                ->get();
        }

        $productIds = collect($this->items)->where('kind', 'product')->pluck('product_id')->filter()->unique();
        $variantOptions = ProductVariant::active()
            ->whereIn('product_id', $productIds)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('product_id');

        return view('livewire.admin.sales.order-create', [
            'customerOptions'    => $customerOptions,
            'selectedCustomer'   => $selectedCustomer,
            'customerAddresses'  => $customerAddresses,
            'productOptions'     => $productOptions,
            'comboOptions'       => $comboOptions,
            'variantOptions'     => $variantOptions,
            'statuses'           => OrderStatus::cases(),
            'paymentStatuses'    => PaymentStatus::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
            'sources'            => OrderSource::cases(),
        ])->layout('layouts.admin.admin');
    }
}
